<?php

namespace Tests\Feature;

use App\Console\Commands\GenerateVapidKeys;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * A stand-in for the real command that skips actual key generation, because
 * this PHP build cannot create EC keys through OpenSSL.
 */
class FakeVapidCommand extends GenerateVapidKeys
{
    public static string $envPath = '';

    protected function generate(): ?array
    {
        return ['publicKey' => 'mock-public-key', 'privateKey' => 'mock-private-key'];
    }

    protected function envPath(): string
    {
        return static::$envPath;
    }
}

class VapidCommandTest extends TestCase
{
    use RefreshDatabase;

    protected string $envPath = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->envPath = base_path('.env.testing-vapid');
        FakeVapidCommand::$envPath = $this->envPath;

        $this->app->bind(GenerateVapidKeys::class, fn () => new FakeVapidCommand);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->envPath.'*') ?: [] as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    public function test_push_vapid_writes_keys_to_the_env_file(): void
    {
        file_put_contents($this->envPath, "APP_NAME=Test\nVAPID_PUBLIC_KEY=old-value\n");

        $exit = Artisan::call('push:vapid');
        $output = Artisan::output();

        $this->assertSame(0, $exit, $output);
        $this->assertStringContainsString('VAPID keys generated', $output);

        $contents = file_get_contents($this->envPath);

        $this->assertStringContainsString('VAPID_PUBLIC_KEY=mock-public-key', $contents);
        $this->assertStringContainsString('VAPID_PRIVATE_KEY=mock-private-key', $contents);
        // The previous value must be replaced, not left to win by ordering.
        $this->assertStringNotContainsString('VAPID_PUBLIC_KEY=old-value', $contents);
        // Unrelated keys must survive.
        $this->assertStringContainsString('APP_NAME=Test', $contents);
    }

    public function test_push_vapid_replaces_rather_than_appends_duplicates(): void
    {
        file_put_contents($this->envPath, "VAPID_PUBLIC_KEY=first\nVAPID_PUBLIC_KEY=second\n");

        $exit = Artisan::call('push:vapid');

        $this->assertSame(0, $exit, Artisan::output());

        $contents = file_get_contents($this->envPath);

        $this->assertSame(1, substr_count($contents, 'VAPID_PUBLIC_KEY='));
        $this->assertStringNotContainsString('VAPID_PUBLIC_KEY=first', $contents);
        $this->assertStringNotContainsString('VAPID_PUBLIC_KEY=second', $contents);
    }

    public function test_push_vapid_appends_when_the_keys_are_absent(): void
    {
        file_put_contents($this->envPath, "APP_NAME=Test\n");

        $exit = Artisan::call('push:vapid');

        $this->assertSame(0, $exit, Artisan::output());

        $contents = file_get_contents($this->envPath);

        $this->assertSame(1, substr_count($contents, 'VAPID_PUBLIC_KEY='));
        $this->assertSame(1, substr_count($contents, 'VAPID_PRIVATE_KEY='));
    }

    public function test_prune_removes_only_old_read_notifications(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $admin->notify(new AppNotification('Old read', 'Body'));
        $admin->notifications()->first()->forceFill([
            'read_at' => now()->subDays(120),
            'created_at' => now()->subDays(120),
        ])->save();

        $admin->notify(new AppNotification('Old unread', 'Body'));
        $admin->notifications()->skip(1)->first()->forceFill([
            'created_at' => now()->subDays(120),
        ])->save();

        $admin->notify(new AppNotification('Recent', 'Body'));

        $this->assertSame(3, $admin->notifications()->count());

        $exit = Artisan::call('notifications:prune', ['--days' => 60]);

        $this->assertSame(0, $exit, Artisan::output());
        // The old read one goes; the old unread and the recent one stay.
        $this->assertSame(2, $admin->notifications()->count());
        $this->assertDatabaseMissing('notifications', [
            'id' => $admin->notifications()->where('data->title', 'Old read')->value('id'),
        ]);
    }
}
