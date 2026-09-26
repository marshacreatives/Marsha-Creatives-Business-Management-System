<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;
use Throwable;

class GenerateVapidKeys extends Command
{
    protected $signature = 'push:vapid
                            {--show : Display the currently configured keys instead of generating new ones}';

    protected $description = 'Generate the VAPID key pair used to sign browser push notifications';

    public function handle(): int
    {
        if ($this->option('show')) {
            $this->showCurrent();

            return self::SUCCESS;
        }

        $keys = $this->generate();

        if ($keys === null) {
            return self::FAILURE;
        }

        $this->writeEnv('VAPID_PUBLIC_KEY', $keys['publicKey']);
        $this->writeEnv('VAPID_PRIVATE_KEY', $keys['privateKey']);

        $this->info('VAPID keys generated.');
        $this->line('  Public key:  '.$keys['publicKey']);
        $this->newLine();
        $this->warn('The private key has been written to .env. Never commit it or share it.');

        if ($this->laravel->environment('local')) {
            $this->newLine();
            $this->comment('Also add the same three lines to .env.example without real values.');
        }

        return self::SUCCESS;
    }

    /**
     * @return array{publicKey: string, privateKey: string}|null
     */
    protected function generate(): ?array
    {
        try {
            $keys = VAPID::createVapidKeys();
        } catch (Throwable $e) {
            $this->reportEnvironmentProblem($e);

            return null;
        }

        // Never persist a pair we cannot actually sign with. A silently broken
        // key is far worse than a loud failure here.
        try {
            VAPID::getVapidHeaders(
                'https://updates.push.services.mozilla.com/wpush/v2/verify',
                'mailto:admin@example.com',
                $keys['publicKey'],
                $keys['privateKey'],
            );
        } catch (Throwable $e) {
            $this->error('Generated keys failed validation: '.$e->getMessage());

            return null;
        }

        return $keys;
    }

    /**
     * Overridable so the command can be exercised in tests without touching
     * the real .env.
     */
    protected function envPath(): string
    {
        return base_path('.env');
    }

    private function reportEnvironmentProblem(Throwable $e): void
    {
        $this->error('Could not generate VAPID keys: '.$e->getMessage());
        $this->newLine();
        $this->line('This PHP build cannot create EC keys through the OpenSSL extension.');
        $this->newLine();
        $this->comment('Run this command somewhere else, for example over SSH on the server,');
        $this->comment('then copy the two values into .env manually:');
        $this->newLine();
        $this->line('  VAPID_PUBLIC_KEY=<public key>');
        $this->line('  VAPID_PRIVATE_KEY=<private key>');
        $this->line('  VAPID_SUBJECT=mailto:you@yourdomain.com');
    }

    private function showCurrent(): void
    {
        $public = config('services.vapid.public_key');
        $private = config('services.vapid.private_key');
        $subject = config('services.vapid.subject');

        $this->line('Public key:  '.($public ?: '(not set)'));
        $this->line('Subject:     '.($subject ?: '(not set)'));
        $this->line('Private key: '.$private ? substr($private, 0, 12).'... (set)' : '(not set)');

        if (! $public || ! $private) {
            $this->newLine();
            $this->warn('Push is not configured. Run "php artisan push:vapid" to generate a pair.');
        }
    }

    /**
     * Set a key in .env, guaranteeing exactly one entry. A naive replace would
     * duplicate the line if the key already appeared twice, and appending would
     * leave an earlier value silently winning.
     */
    private function writeEnv(string $key, string $value): void
    {
        $path = $this->envPath();

        if (! file_exists($path)) {
            $this->error('.env not found at '.$path);

            return;
        }

        $lines = preg_split('/\r\n|\r|\n/', (string) file_get_contents($path));
        $kept = array_values(array_filter(
            $lines,
            fn (string $line) => ! preg_match('/^\s*'.preg_quote($key, '/').'\s*=/', $line),
        ));

        $kept[] = $key.'='.$value;

        file_put_contents($path, implode(PHP_EOL, $kept).PHP_EOL);
    }
}
