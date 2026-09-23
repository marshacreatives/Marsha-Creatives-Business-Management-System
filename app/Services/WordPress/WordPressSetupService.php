<?php

namespace App\Services\WordPress;

use App\Models\WordPressSite;
use Illuminate\Support\Collection;

class WordPressSetupService
{
    public function __construct(
        protected WordPressSite $site,
    ) {}

    /**
     * Run the standard website setup SOP:
     * 1. Log in to wp-admin.
     * 2. Install + activate the Hello Elementor theme.
     * 3. Install + activate the Google Site Kit plugin.
     * 4. Install the WPS Hide Login plugin (left inactive so the login
     *    URL is unchanged and future setup runs can still authenticate).
     * 5. Upload + activate every plugin zip inside the plugins/ folder.
     *
     * Progress is written to the site record's steps_log as it goes.
     * The record is left in the completed or failed state.
     */
    public function setup(string $username, string $password): WordPressSite
    {
        $this->site->forceFill([
            'status' => 'in_progress',
            'error_message' => null,
            'steps_log' => [],
        ])->save();

        $client = new WordPressClient($this->site->site_url);

        try {
            $this->site->appendStep('login', 'in_progress', 'Logging in to wp-admin');
            $client->login($username, $password);
            $this->site->markStep('login', 'completed', 'Authenticated with WordPress');

            $this->site->appendStep('install_theme', 'in_progress', 'Installing Hello Elementor');
            $client->installTheme('hello-elementor');
            $this->site->markStep(
                'install_theme',
                'completed',
                $client->themeActive('hello-elementor') ? 'Installed & activated' : 'Installed'
            );
            $this->site->forceFill(['active_theme' => 'hello-elementor'])->save();

            $this->site->appendStep('install_plugin', 'in_progress', 'Installing Google Site Kit');
            $client->installPlugin('google-site-kit');
            $this->site->markStep(
                'install_plugin',
                'completed',
                $client->pluginActive($client->pluginFile('google-site-kit') ?: 'google-site-kit') ? 'Installed & activated' : 'Installed'
            );

            $this->site->appendStep('install_wps_hide_login', 'in_progress', 'Installing WPS Hide Login');
            $client->installPlugin('wps-hide-login', false);
            $this->site->markStep('install_wps_hide_login', 'completed', 'Installed (not activated)');

            $this->installFolderPlugins($client);

            $this->site->forceFill([
                'status' => 'completed',
                'installed_plugins' => $client->listInstalledPlugins(),
            ])->save();
        } catch (WordPressException $e) {
            $this->fail($client, $e);
        } catch (\Throwable $e) {
            $this->fail($client, new WordPressException('Unexpected error: '.$e->getMessage(), 0, $e));
        }

        return $this->site;
    }

    protected function installFolderPlugins(WordPressClient $client): void
    {
        $zips = $this->pluginZips();

        if ($zips->isEmpty()) {
            $this->site->appendStep('extra_plugins', 'completed', 'No plugin zips in the plugins/ folder');

            return;
        }

        $this->site->appendStep('extra_plugins', 'in_progress', $zips->count().' plugin zip(s) in the plugins/ folder');

        foreach ($zips as $zip) {
            $label = basename($zip);
            $this->site->appendStep('plugin:'.$label, 'in_progress', 'Uploading '.$label);

            $slug = $client->uploadPlugin($zip);
            $this->site->markStep('plugin:'.$label, 'completed', 'Installed & activated ('.$slug.')');
        }

        $this->site->markStep('extra_plugins', 'completed', 'All folder plugins processed');
    }

    /**
     * Plugin zip files placed in the project's plugins/ folder.
     */
    protected function pluginZips(): Collection
    {
        $files = glob(base_path('plugins').DIRECTORY_SEPARATOR.'*.zip');

        return collect($files ?: [])->sort()->values();
    }

    protected function fail(WordPressClient $client, WordPressException $e): void
    {
        $log = collect($this->site->steps_log ?? [])
            ->map(function (array $entry) {
                if (($entry['status'] ?? null) === 'in_progress') {
                    $entry['status'] = 'failed';
                }

                return $entry;
            })
            ->values()
            ->all();

        $this->site->forceFill([
            'status' => 'failed',
            'steps_log' => $log,
            'error_message' => $e->getMessage().(count($client->debug()) ? ' ('.implode('; ', $client->debug()).')' : ''),
        ])->save();
    }
}
