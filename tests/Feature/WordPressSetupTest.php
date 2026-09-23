<?php

namespace Tests\Feature;

use App\Jobs\RunWordPressSetup;
use App\Models\User;
use App\Models\WordPressSite;
use App\Services\WordPress\WordPressClient;
use App\Services\WordPress\WordPressSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WordPressSetupTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        foreach (glob(base_path('plugins').DIRECTORY_SEPARATOR.'*.zip') ?: [] as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    public function test_normalize_url_accepts_common_wordpress_links(): void
    {
        $this->assertSame('https://example.com', WordPressClient::normalizeUrl('https://example.com/wp-login.php'));
        $this->assertSame('https://example.com', WordPressClient::normalizeUrl('https://example.com/wp-admin'));
        $this->assertSame('https://example.com', WordPressClient::normalizeUrl('https://example.com/wp-admin/'));
        $this->assertSame('https://example.com', WordPressClient::normalizeUrl('example.com'));
        $this->assertSame('http://example.com', WordPressClient::normalizeUrl('http://example.com'));
        $this->assertSame('https://sub.example.io/blog', WordPressClient::normalizeUrl('https://sub.example.io/blog/wp-admin/'));
    }

    public function test_admin_can_view_setup_index(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.wordpress.index'));

        $response->assertOk();
        $response->assertSee('Quick WordPress Setup');
    }

    public function test_employee_cannot_access_setup(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($employee)->get(route('admin.wordpress.index'))->assertForbidden();
    }

    public function test_store_queues_setup_job(): void
    {
        Queue::fake();

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.wordpress.store'), [
            'site_url' => 'https://client-site.com/wp-login.php',
            'username' => 'admin',
            'password' => 's3cr3t',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('wordpress_sites', [
            'site_url' => 'https://client-site.com',
            'status' => 'pending',
            'created_by' => $admin->id,
        ]);

        Queue::assertPushed(RunWordPressSetup::class, function (RunWordPressSetup $job) {
            return $job->username === 'admin'
                && $job->encryptedPassword !== 's3cr3t'
                && $job->site->is($this->lastWordPressSite());
        });
    }

    public function test_store_rejects_invalid_url(): void
    {
        Queue::fake();

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->from(route('admin.wordpress.index'))->post(route('admin.wordpress.store'), [
            'site_url' => 'ht!tp://broken',
            'username' => 'admin',
            'password' => 'secret',
        ]);

        $response->assertRedirect(route('admin.wordpress.index'));
        $response->assertSessionHasErrors('site_url');
        $this->assertDatabaseCount('wordpress_sites', 0);
    }

    public function test_service_runs_full_sop_and_activates_theme_and_plugin(): void
    {
        $state = ['themes' => [], 'plugins' => [], 'loginAttempts' => 0];

        Http::fake(function (Request $request) use (&$state) {
            $url = $request->url();

            if (str_contains($url, 'wp-login.php')) {
                if ($request->method() === 'POST') {
                    $state['loginAttempts']++;

                    if ($request['log'] === 'admin' && $request['pwd'] === 'secret') {
                        return Http::response('', 302, ['Set-Cookie' => 'wordpress_logged_in_test=abc']);
                    }

                    return Http::response($this->loginFormHtml('Wrong password.'), 200);
                }

                return Http::response($this->loginFormHtml(), 200);
            }

            if (str_contains($url, 'theme-install.php')) {
                return Http::response($this->themeInstallPageHtml('hello-elementor'), 200);
            }

            if (str_contains($url, 'plugin-install.php')) {
                if (str_contains($url, 'tab=upload')) {
                    return Http::response($this->pluginUploadPageHtml(), 200);
                }

                if (preg_match('/\bs=([^&\s]+)/', $url, $sm)) {
                    return Http::response($this->pluginSearchPageHtml(urldecode($sm[1])), 200);
                }

                return Http::response($this->pluginSearchPageHtml('google-site-kit'), 200);
            }

            if (str_contains($url, 'update.php')) {
                if (($request['action'] ?? '') === 'install-theme') {
                    $state['themes'][$request['theme']] = false;

                    return Http::response('ok', 200);
                }

                if (($request['action'] ?? '') === 'install-plugin') {
                    $state['plugins'][$request['plugin']] = false;

                    return Http::response('ok', 200);
                }

                if (str_contains($url, 'action=upload-plugin')) {
                    $state['plugins']['my-custom-plugin'] = false;

                    return Http::response('ok', 200);
                }

                return Http::response('ok', 200);
            }

            if (str_contains($url, 'themes.php')) {
                if (str_contains($url, 'action=activate')) {
                    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
                    $state['themes'][$query['stylesheet'] ?? ''] = true;

                    return Http::response('ok', 200);
                }

                return Http::response($this->themesPageHtml($state['themes']), 200);
            }

            if (str_contains($url, 'plugins.php')) {
                if (str_contains($url, 'action=activate')) {
                    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
                    $slug = explode('/', rawurldecode($query['plugin'] ?? ''))[0];
                    $state['plugins'][$slug] = true;

                    return Http::response('ok', 200);
                }

                return Http::response($this->pluginsPageHtml($state['plugins']), 200);
            }

            return Http::response('page not found', 404);
        });

        $site = WordPressSite::create([
            'site_url' => 'https://client-site.com',
            'status' => 'pending',
            'steps_log' => [],
            'created_by' => User::factory()->create(['role' => 'admin'])->id,
        ]);

        $service = app(WordPressSetupService::class, ['site' => $site]);
        $result = $service->setup('admin', 'secret');

        $result->refresh();

        $this->assertSame('completed', $result->status, 'Setup failed: '.($result->error_message ?? 'no error message'));
        $this->assertSame('hello-elementor', $result->active_theme);
        $this->assertContains('google-site-kit', $result->installed_plugins ?? []);
        $this->assertContains('wps-hide-login', $result->installed_plugins ?? []);

        $steps = collect($result->steps_log);
        $this->assertSame('completed', $steps->firstWhere('step', 'login')['status']);
        $this->assertSame('completed', $steps->firstWhere('step', 'install_theme')['status']);
        $this->assertSame('completed', $steps->firstWhere('step', 'install_plugin')['status']);
        $this->assertSame('completed', $steps->firstWhere('step', 'install_wps_hide_login')['status']);
        $this->assertTrue($state['themes']['hello-elementor']);
        $this->assertTrue($state['plugins']['google-site-kit']);
        $this->assertArrayHasKey('wps-hide-login', $state['plugins']);
        $this->assertFalse($state['plugins']['wps-hide-login']);
    }

    public function test_service_installs_plugins_from_folder(): void
    {
        $state = ['themes' => [], 'plugins' => [], 'loginAttempts' => 0];

        $zipPath = base_path('plugins').DIRECTORY_SEPARATOR.'my-custom-plugin.zip';
        file_put_contents($zipPath, 'not-a-real-zip');

        Http::fake(function (Request $request) use (&$state) {
            $url = $request->url();

            if (str_contains($url, 'wp-login.php')) {
                if ($request->method() === 'POST') {
                    return Http::response('', 302, ['Set-Cookie' => 'wordpress_logged_in_test=abc']);
                }

                return Http::response($this->loginFormHtml(), 200);
            }

            if (str_contains($url, 'theme-install.php')) {
                return Http::response($this->themeInstallPageHtml('hello-elementor'), 200);
            }

            if (str_contains($url, 'plugin-install.php')) {
                if (str_contains($url, 'tab=upload')) {
                    return Http::response($this->pluginUploadPageHtml(), 200);
                }

                if (preg_match('/\bs=([^&\s]+)/', $url, $sm)) {
                    return Http::response($this->pluginSearchPageHtml(urldecode($sm[1])), 200);
                }

                return Http::response($this->pluginSearchPageHtml('google-site-kit'), 200);
            }

            if (str_contains($url, 'update.php')) {
                if (($request['action'] ?? '') === 'install-theme') {
                    $state['themes'][$request['theme']] = false;

                    return Http::response('ok', 200);
                }

                if (($request['action'] ?? '') === 'install-plugin') {
                    $state['plugins'][$request['plugin']] = false;

                    return Http::response('ok', 200);
                }

                if (str_contains($url, 'action=upload-plugin')) {
                    $state['plugins']['my-custom-plugin'] = false;

                    return Http::response('ok', 200);
                }

                return Http::response('ok', 200);
            }

            if (str_contains($url, 'themes.php')) {
                if (str_contains($url, 'action=activate')) {
                    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
                    $state['themes'][$query['stylesheet'] ?? ''] = true;

                    return Http::response('ok', 200);
                }

                return Http::response($this->themesPageHtml($state['themes']), 200);
            }

            if (str_contains($url, 'plugins.php')) {
                if (str_contains($url, 'action=activate')) {
                    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
                    $slug = explode('/', rawurldecode($query['plugin'] ?? ''))[0];
                    $state['plugins'][$slug] = true;

                    return Http::response('ok', 200);
                }

                return Http::response($this->pluginsPageHtml($state['plugins']), 200);
            }

            return Http::response('page not found', 404);
        });

        $site = WordPressSite::create([
            'site_url' => 'https://client-site.com',
            'status' => 'pending',
            'steps_log' => [],
            'created_by' => User::factory()->create(['role' => 'admin'])->id,
        ]);

        $result = app(WordPressSetupService::class, ['site' => $site])->setup('admin', 'secret');
        $result->refresh();

        $this->assertSame('completed', $result->status);
        $this->assertContains('my-custom-plugin', $result->installed_plugins ?? []);
        $this->assertTrue($state['plugins']['my-custom-plugin']);
        $this->assertSame(
            'completed',
            collect($result->steps_log)->firstWhere('step', 'plugin:my-custom-plugin.zip')['status']
        );
    }

    public function test_service_marks_failed_on_bad_login(): void
    {
        Http::fake([
            '*/wp-login.php' => function (Request $request) {
                if ($request->method() === 'POST') {
                    return Http::response($this->loginFormHtml('The password you entered is incorrect.'), 200);
                }

                return Http::response($this->loginFormHtml(), 200);
            },
        ]);

        $site = WordPressSite::create([
            'site_url' => 'https://client-site.com',
            'status' => 'pending',
            'steps_log' => [],
            'created_by' => User::factory()->create(['role' => 'admin'])->id,
        ]);

        $result = app(WordPressSetupService::class, ['site' => $site])->setup('admin', 'wrong-password');
        $result->refresh();

        $this->assertSame('failed', $result->status);
        $this->assertStringContainsString('The password you entered is incorrect', $result->error_message);
        $this->assertSame('failed', collect($result->steps_log)->firstWhere('step', 'login')['status']);
    }

    // ------------------------------------------------------------------
    // HTML fixtures for the fake WordPress instance
    // ------------------------------------------------------------------

    private function lastWordPressSite(): WordPressSite
    {
        return WordPressSite::latest('id')->firstOrFail();
    }

    private function loginFormHtml(string $error = ''): string
    {
        $errorBox = $error ? '<div id="login_error"><p>'.$error.'</p></div>' : '';

        return '<html><body>'.$errorBox.
            '<form id="loginform" action="wp-login.php" method="post">'.
            '<input type="hidden" name="redirect_to" value="https://client-site.com/wp-admin/">'.
            '<input type="hidden" name="testcookie" value="1">'.
            '<input class="input" type="text" name="log">'.
            '<input class="input" type="password" name="pwd">'.
            '<input type="checkbox" name="rememberme">'.
            '<input type="submit" value="Log In">'.
            '</form></body></html>';
    }

    private function themesPageHtml(array $themes): string
    {
        $html = '<div class="themes">';

        foreach ($themes as $slug => $active) {
            $html .= '<div class="theme'.($active ? ' active' : '').'" data-slug="'.$slug.'">';
            $html .= '<div class="theme-name">'.$slug.'</div>';

            if (! $active) {
                $html .= '<a href="themes.php?action=activate&stylesheet='.$slug.'&_wpnonce=aaa111">Activate</a>';
            }

            $html .= '</div>';
        }

        return $html.'</div>';
    }

    private function themeInstallPageHtml(string $slug): string
    {
        return '<html><body><div class="theme-info">'.
            '<a href="update.php?action=install-theme&theme='.$slug.'&_wpnonce=bbb222">Install Now</a>'.
            '</div></body></html>';
    }

    private function pluginsPageHtml(array $plugins): string
    {
        $html = '<table class="widefat plugins"><tbody>';

        foreach ($plugins as $slug => $active) {
            $file = $slug.'/'.$slug.'.php';
            $html .= '<tr id="'.$slug.'" data-plugin="'.$file.'" data-slug="'.$slug.'" class="'.($active ? 'active' : '').'">';
            $html .= '<td class="plugin-title">'.$slug.'</td><td class="plugin-action-links">';

            if (! $active) {
                $html .= '<a href="plugins.php?action=activate&plugin='.rawurlencode($file).'&plugin_status=all&paged=1&s=&_wpnonce=ccc333">Activate</a>';
            } else {
                $html .= '<a href="plugins.php?action=deactivate&plugin='.rawurlencode($file).'&plugin_status=all&paged=1&s=&_wpnonce=ccc333">Deactivate</a>';
            }

            $html .= '</td></tr>';
        }

        return $html.'</tbody></table>';
    }

    private function pluginSearchPageHtml(string $slug): string
    {
        return '<html><body><div class="plugin-card">'.
            '<a href="update.php?action=install-plugin&plugin='.$slug.'&_wpnonce=ddd444">Install Now</a>'.
            '</div></body></html>';
    }

    private function pluginUploadPageHtml(): string
    {
        return '<html><body>'.
            '<form name="uploadplugin" id="uploadplugin" method="post" action="update.php?action=upload-plugin" enctype="multipart/form-data">'.
            '<input type="hidden" name="_wpnonce" value="eee555">'.
            '<input type="file" name="pluginzip">'.
            '</form></body></html>';
    }
}
