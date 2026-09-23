<?php

namespace App\Services\WordPress;

use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WordPressException extends RuntimeException {}

class WordPressClient
{
    protected string $siteUrl;

    protected CookieJar $cookies;

    protected bool $loggedIn = false;

    protected array $debug = [];

    protected int $timeout = 90;

    public function __construct(string $siteUrl)
    {
        $this->siteUrl = static::normalizeUrl($siteUrl);
        $this->cookies = new CookieJar;
    }

    public function siteUrl(): string
    {
        return $this->siteUrl;
    }

    public function debug(): array
    {
        return $this->debug;
    }

    public function isLoggedIn(): bool
    {
        return $this->loggedIn;
    }

    /**
     * Accept a login link, wp-admin path, or bare site URL and return a
     * normalized base URL such as https://example.com (no trailing slash).
     */
    public static function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }

        $parts = parse_url($url);

        if (! isset($parts['host']) || $parts === false) {
            throw new WordPressException("'{$url}' is not a valid WordPress URL.");
        }

        $host = $parts['host'];

        if (! preg_match('/^[a-z0-9._-]+$/i', $host)) {
            throw new WordPressException("'{$url}' does not look like a valid website address.");
        }

        $scheme = strtolower($parts['scheme']);
        $authority = $host.(isset($parts['port']) ? ':'.$parts['port'] : '');
        $path = '/'.trim($parts['path'] ?? '', '/');

        if (preg_match('#/wp-(login|admin)\.php$#i', $path)) {
            $path = preg_replace('#/wp-(login|admin)\.php$#i', '', $path);
        } elseif (preg_match('#/wp-admin/?$#i', $path)) {
            $path = preg_replace('#/wp-admin/?$#i', '', $path);
        }

        return $scheme.'://'.$authority.rtrim($path, '/');
    }

    /**
     * Log in to wp-login.php and store the session cookies.
     */
    public function login(string $username, string $password): bool
    {
        $page = $this->http()->get($this->loginUrl());

        $this->trackRequest('GET /wp-login.php', $page);

        if (! $this->checkStatus($page, 'GET /wp-login.php')) {
            throw new WordPressException('Could not reach the WordPress login page (HTTP '.$page->status().').');
        }

        $fields = $this->extractFormFields($page->body(), 'loginform');
        $fields['log'] = $username;
        $fields['pwd'] = $password;
        $fields['wp-submit'] = 'Log In';
        $fields['testcookie'] = '1';

        $response = $this->http()->asForm()->post($this->loginUrl(), $fields);

        $this->trackRequest('POST /wp-login.php', $response);

        if (! $this->checkStatus($response, 'POST /wp-login.php')) {
            throw new WordPressException('WordPress returned HTTP '.$response->status().' while logging in.');
        }

        // A successful login issues a wordpress_logged_in_<hash> cookie.
        $this->loggedIn = collect($this->cookies->toArray())
            ->contains(fn ($cookie) => str_starts_with($cookie['Name'], 'wordpress_logged_in_'));

        if (! $this->loggedIn) {
            $setCookies = implode(';', $response->headers('Set-Cookie'));
            $this->loggedIn = str_contains($setCookies, 'wordpress_logged_in_');
        }

        if (! $this->loggedIn) {
            $message = $this->loginErrorMessage($response->body());
            throw new WordPressException($message ?: 'Login failed: username or password was rejected.');
        }

        return true;
    }

    /**
     * Install (and optionally activate) a WordPress.org theme by slug.
     */
    public function installTheme(string $slug, bool $activate = true): void
    {
        if ($this->themeInstalled($slug)) {
            if ($activate && ! $this->themeActive($slug)) {
                $this->activateTheme($slug);
            }

            return;
        }

        $html = $this->html('/wp-admin/theme-install.php?tab=theme-information&theme='.rawurlencode($slug));
        $nonce = $this->nonceFrom(
            $html,
            '/action=install-theme&theme=([^&"]+)&_wpnonce=([a-f0-9]+)/i',
            fn (array $m) => $m[1] === $slug
        );

        if (! $nonce) {
            throw new WordPressException("Could not find the install link for theme '{$slug}'. It may not be available on wordpress.org.");
        }

        $this->assertAdminPage($html, 'Theme install page');

        $response = $this->http()->asForm()->post('/wp-admin/update.php', [
            'action' => 'install-theme',
            'theme' => $slug,
            '_wpnonce' => $nonce,
        ]);

        $this->trackRequest('POST /wp-admin/update.php?action=install-theme', $response);
        $this->checkStatus($response, 'Installing theme '.$slug);
        $this->assertAdminPage($response->body(), 'Installing theme '.$slug);

        if (! $this->themeInstalled($slug)) {
            throw new WordPressException("The theme '{$slug}' could not be installed.");
        }

        if ($activate) {
            $this->activateTheme($slug);
        }
    }

    public function activateTheme(string $slug): void
    {
        if ($this->themeActive($slug)) {
            return;
        }

        $html = $this->html('/wp-admin/themes.php');
        $block = $this->themeCard($html, $slug);

        if ($block === null) {
            throw new WordPressException("The theme '{$slug}' is not installed.");
        }

        $nonce = $this->nonceFrom(
            $block,
            '/action=activate&stylesheet=([^&"]+)&[^"]*?_wpnonce=([a-f0-9]+)/i',
            fn (array $m) => $m[1] === $slug
        );

        if (! $nonce) {
            throw new WordPressException("The theme '{$slug}' has no activate link on the themes screen.");
        }

        $response = $this->http()->get('/wp-admin/themes.php?action=activate&stylesheet='
            .rawurlencode($slug).'&_wpnonce='.$nonce);

        $this->trackRequest('GET /wp-admin/themes.php?action=activate', $response);
        $this->checkStatus($response, 'Activating theme '.$slug);
        $this->assertAdminPage($response->body(), 'Activating theme '.$slug);

        if (! $this->themeActive($slug)) {
            throw new WordPressException("The theme '{$slug}' could not be activated.");
        }
    }

    /**
     * Install (and optionally activate) a WordPress.org plugin by slug.
     */
    public function installPlugin(string $slug, bool $activate = true): void
    {
        if ($this->pluginInstalled($slug)) {
            if ($activate) {
                $this->activatePlugin($slug);
            }

            return;
        }

        $html = $this->html('/wp-admin/plugin-install.php?tab=search&type=term&s='.rawurlencode($slug));
        $nonce = $this->nonceFrom(
            $html,
            '/action=install-plugin&plugin=([^&"]+)&_wpnonce=([a-f0-9]+)/i',
            fn (array $m) => $m[1] === $slug
        );

        if (! $nonce) {
            throw new WordPressException("Could not find the install link for plugin '{$slug}' on wordpress.org.");
        }

        $this->assertAdminPage($html, 'Plugin search page');

        $response = $this->http()->asForm()->post('/wp-admin/update.php', [
            'action' => 'install-plugin',
            'plugin' => $slug,
            '_wpnonce' => $nonce,
        ]);

        $this->trackRequest('POST /wp-admin/update.php?action=install-plugin', $response);
        $this->checkStatus($response, 'Installing plugin '.$slug);
        $this->assertAdminPage($response->body(), 'Installing plugin '.$slug);

        if (! $this->pluginInstalled($slug)) {
            throw new WordPressException("The plugin '{$slug}' could not be installed.");
        }

        if ($activate) {
            $this->activatePlugin($slug);
        }
    }

    public function activatePlugin(string $slug): void
    {
        $file = $this->pluginFile($slug);

        if ($file === null) {
            throw new WordPressException("The plugin '{$slug}' is not installed.");
        }

        if ($this->pluginActive($file)) {
            return;
        }

        $html = $this->html('/wp-admin/plugins.php');
        $row = $this->pluginRow($html, $slug);

        $nonce = $this->nonceFrom(
            $row,
            '/action=activate&plugin=([^&"]+)&[^"]*?_wpnonce=([a-f0-9]+)/i',
            function (array $m) use ($file) {
                return rawurldecode($m[1]) === $file;
            }
        );

        if (! $nonce) {
            throw new WordPressException("The plugin '{$slug}' has no activate link on the plugins screen.");
        }

        $response = $this->http()->get('/wp-admin/plugins.php?action=activate&plugin='
            .rawurlencode($file).'&_wpnonce='.$nonce);

        $this->trackRequest('GET /wp-admin/plugins.php?action=activate', $response);
        $this->checkStatus($response, 'Activating plugin '.$slug);
        $this->assertAdminPage($response->body(), 'Activating plugin '.$slug);

        if (! $this->pluginActive($file)) {
            throw new WordPressException("The plugin '{$slug}' could not be activated.");
        }
    }

    /**
     * Upload a plugin zip through the Plugin Install screen.
     */
    public function uploadPlugin(string $zipPath, bool $activate = true): string
    {
        if (! is_file($zipPath)) {
            throw new WordPressException("Plugin zip not found: {$zipPath}");
        }

        $html = $this->html('/wp-admin/plugin-install.php?tab=upload');
        $nonce = $this->hiddenValue($html, '_wpnonce');

        if (! $nonce) {
            throw new WordPressException('Could not find the plugin upload form.');
        }

        $this->assertAdminPage($html, 'Plugin upload page');

        $slug = $this->guessPluginSlug($zipPath);

        $response = $this->http()
            ->attach('pluginzip', file_get_contents($zipPath), basename($zipPath))
            ->post('/wp-admin/update.php?action=upload-plugin', ['_wpnonce' => $nonce]);

        $this->trackRequest('POST /wp-admin/update.php?action=upload-plugin', $response);
        $this->checkStatus($response, 'Uploading plugin '.basename($zipPath));
        $this->assertAdminPage($response->body(), 'Uploading plugin '.basename($zipPath));

        if (! $this->pluginInstalled($slug)) {
            throw new WordPressException("The uploaded plugin '".basename($zipPath)."' did not appear in the plugins list.");
        }

        if ($activate) {
            $this->activatePlugin($slug);
        }

        return $slug;
    }

    // ------------------------------------------------------------------
    // State checks
    // ------------------------------------------------------------------

    public function themeInstalled(string $slug): bool
    {
        return $this->themeCard($this->html('/wp-admin/themes.php'), $slug) !== null;
    }

    public function themeActive(string $slug): bool
    {
        $html = $this->html('/wp-admin/themes.php');

        $block = $this->themeCard($html, $slug);

        if ($block === null) {
            return false;
        }

        // An activate link for this slug means it is not the active theme.
        $hasActivateLink = $this->nonceFrom(
            $block,
            '/action=activate&stylesheet=([^&"]+)&[^"]*?_wpnonce=([a-f0-9]+)/i',
            fn (array $m) => $m[1] === $slug
        ) !== null;

        if ($hasActivateLink) {
            return false;
        }

        // Otherwise rely on the "active" class in the card's opening tag.
        if (preg_match('/<div[^>]*data-slug="'.preg_quote($slug, '/').'"[^>]*>/i', $html, $tag)
            && preg_match('/class="([^"]*)"/i', $tag[0], $class)) {
            return in_array('active', preg_split('/\s+/', trim($class[1])), true);
        }

        return true;
    }

    public function pluginInstalled(string $slug): bool
    {
        return $this->pluginFile($slug) !== null;
    }

    public function pluginActive(string $file): bool
    {
        $html = $this->html('/wp-admin/plugins.php');

        if (! preg_match('/<tr[^>]*data-plugin="'.preg_quote($file, '/').'"[^>]*>/i', $html, $tag)) {
            return false;
        }

        if (! preg_match('/class="([^"]*)"/i', $tag[0], $class)) {
            return false;
        }

        return in_array('active', preg_split('/\s+/', trim($class[1])), true);
    }

    public function listInstalledPlugins(): array
    {
        $html = $this->html('/wp-admin/plugins.php');
        $plugins = [];

        if (preg_match_all('/data-slug="([^"]+)"/', $html, $matches)) {
            foreach ($matches[1] as $slug) {
                if (! in_array($slug, $plugins, true)) {
                    $plugins[] = $slug;
                }
            }
        }

        return $plugins;
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    protected function http(): PendingRequest
    {
        return Http::withOptions([
            'cookies' => $this->cookies,
            'connect_timeout' => 15,
            'timeout' => $this->timeout,
            'verify' => true,
            'http_errors' => false,
            'allow_redirects' => ['max' => 10],
        ])->accept('text/html');
    }

    protected function loginUrl(): string
    {
        return $this->siteUrl.'/wp-login.php';
    }

    protected function get(string $path): Response
    {
        return $this->http()->get($this->siteUrl.$path);
    }

    protected function html(string $path): string
    {
        $response = $this->get($path);
        $this->trackRequest('GET '.$path, $response);
        $this->checkStatus($response, $path);
        $this->assertAdminPage($response->body(), $path);

        return str_replace(['&#038;', '&amp;'], '&', $response->body());
    }

    protected function checkStatus(Response $response, string $context): bool
    {
        if ($response->status() >= 400 && $response->status() !== 404) {
            throw new WordPressException("{$context} responded with HTTP ".$response->status().'.');
        }

        return true;
    }

    protected function assertAdminPage(string $html, string $context): void
    {
        $markers = [
            'The link you followed has expired',
            'Sorry, you are not allowed to access this page.',
            'You do not have sufficient permissions to access this page.',
        ];

        foreach ($markers as $marker) {
            if (str_contains($html, $marker)) {
                throw new WordPressException("{$context}: {$marker}");
            }
        }
    }

    protected function trackRequest(string $label, Response $response): void
    {
        $this->debug[] = sprintf('%s => %s', $label, $response->status());
    }

    /**
     * Parse hidden/text inputs out of a <form> (matched by id/name) into a key => value map.
     */
    protected function extractFormFields(string $html, string $formMarker = ''): array
    {
        $subject = $html;

        if ($formMarker !== '') {
            $pattern = '/<form[^>]*(?:id|name)="'.preg_quote($formMarker, '/').'"[^>]*>(.*?)<\/form>/is';

            if (preg_match($pattern, $html, $match)) {
                $subject = $match[1];
            }
        }

        $fields = [];

        if (preg_match_all('/<input[^>]*>/i', $subject, $inputs)) {
            foreach ($inputs[0] as $input) {
                if (! preg_match('/name="([^"]+)"/i', $input, $name)) {
                    continue;
                }

                $type = 'text';
                if (preg_match('/type="([^"]+)"/i', $input, $typeMatch)) {
                    $type = strtolower($typeMatch[1]);
                }

                if (in_array($type, ['submit', 'button', 'checkbox', 'hidden'], true)) {
                    continue;
                }

                $value = '';
                if (preg_match('/value="([^"]*)"/i', $input, $valueMatch)) {
                    $value = $valueMatch[1];
                }

                $fields[$name[1]] = $value;
            }
        }

        return $fields;
    }

    protected function loginErrorMessage(string $html): string
    {
        if (preg_match('/id="login_error"[^>]*>(.*?)<\/div>/is', $html, $match)) {
            return trim(strip_tags($match[1]));
        }

        return '';
    }

    protected function nonceFrom(string $html, string $regex, callable $filter): ?string
    {
        if (preg_match_all($regex, $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                if ($filter($match)) {
                    return $match[2];
                }
            }
        }

        return null;
    }

    protected function hiddenValue(string $html, string $name): ?string
    {
        $quoted = preg_quote($name, '/');

        if (preg_match('/name="'.$quoted.'"[^>]*?value="([^"]*)"/i', $html, $match)) {
            return $match[1];
        }

        if (preg_match('/value="([^"]*)"[^>]*?name="'.$quoted.'"/i', $html, $match)) {
            return $match[1];
        }

        return null;
    }

    /**
     * Isolate the <div class="theme"> card for a given slug.
     */
    protected function themeCard(string $html, string $slug): ?string
    {
        $cards = preg_split('/<div class="theme(?=["\s])/i', $html);

        foreach (array_slice($cards, 1) as $block) {
            if (preg_match('/data-slug="'.preg_quote($slug, '/').'"/', $block)) {
                return $block;
            }
        }

        return null;
    }

    /**
     * Isolate a single plugin <tr> row body for a slug (contents between its
     * opening and closing tags, which holds the action links).
     */
    protected function pluginRow(string $html, string $slug): string
    {
        if (preg_match('/<tr[^>]*data-slug="'.preg_quote($slug, '/').'"[^>]*>(.*?)<\/tr>/is', $html, $match)) {
            return $match[1];
        }

        return '';
    }

    /**
     * Primary plugin file (basename) for a slug, e.g. google-site-kit/google-site-kit.php.
     */
    public function pluginFile(string $slug): ?string
    {
        $html = $this->html('/wp-admin/plugins.php');

        preg_match_all('/<tr[^>]*data-plugin="([^"]+)"[^>]*data-slug="([^"]+)"/i', $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            if ($match[2] === $slug) {
                return $match[1];
            }
        }

        preg_match_all('/<tr[^>]*data-slug="([^"]+)"[^>]*data-plugin="([^"]+)"/i', $html, $reversed, PREG_SET_ORDER);

        foreach ($reversed as $match) {
            if ($match[1] === $slug) {
                return $match[2];
            }
        }

        return null;
    }

    /**
     * Best-effort slug from a plugin zip filename: my-plugin.zip => my-plugin.
     */
    protected function guessPluginSlug(string $zipPath): string
    {
        $name = strtolower(pathinfo($zipPath, PATHINFO_FILENAME));

        return preg_replace('/[^a-z0-9-_]+/', '-', $name);
    }
}
