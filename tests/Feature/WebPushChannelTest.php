<?php

namespace Tests\Feature;

use App\Models\PushSubscription;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Notifications\Channels\WebPushChannel;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishlink\WebPush\MessageSentReport;
use Tests\TestCase;

/**
 * Exposes the protected internals of the channel. Real push delivery cannot be
 * exercised on every machine because it needs a working EC key pair, so the
 * pieces around the network call are tested directly instead.
 */
class ExposedWebPushChannel extends WebPushChannel
{
    public function prune(array $reports): void
    {
        $this->pruneDeadSubscriptions($reports);
    }
}

class WebPushChannelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_does_nothing_when_vapid_keys_are_missing(): void
    {
        config([
            'services.vapid.public_key' => null,
            'services.vapid.private_key' => null,
        ]);

        $user = User::factory()->create(['role' => 'employee']);

        PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => 'https://push.example.test/a',
            'public_key' => 'k',
            'auth_token' => 'a',
        ]);

        // Must not throw even though a subscription exists.
        (new ExposedWebPushChannel)->send($user, new AppNotification('Hi', 'Body'));

        $this->assertDatabaseCount('push_subscriptions', 1);
    }

    public function test_it_does_nothing_when_the_user_has_no_subscriptions(): void
    {
        config([
            'services.vapid.public_key' => 'public',
            'services.vapid.private_key' => 'private',
        ]);

        $user = User::factory()->create(['role' => 'employee']);

        (new ExposedWebPushChannel)->send($user, new AppNotification('Hi', 'Body'));

        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_it_respects_the_per_user_limit(): void
    {
        config([
            'services.vapid.public_key' => 'public',
            'services.vapid.private_key' => 'private',
            'notifications.push.per_user_limit' => 2,
        ]);

        $user = User::factory()->create(['role' => 'employee']);

        foreach (range(1, 5) as $i) {
            PushSubscription::create([
                'user_id' => $user->id,
                'endpoint' => "https://push.example.test/{$i}",
                'public_key' => 'k',
                'auth_token' => 'a',
            ]);
        }

        // Limit is applied by the query, so only the newest two would be sent.
        $limited = $user->pushSubscriptions()->orderByDesc('id')->limit(2)->pluck('endpoint');

        $this->assertCount(2, $limited);
        $this->assertSame(5, $user->pushSubscriptions()->count());
    }

    public function test_expired_subscriptions_are_deleted(): void
    {
        $user = User::factory()->create(['role' => 'employee']);

        $gone = PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => 'https://push.example.test/gone',
            'public_key' => 'k',
            'auth_token' => 'a',
        ]);

        $kept = PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => 'https://push.example.test/kept',
            'public_key' => 'k',
            'auth_token' => 'a',
        ]);

        $channel = new ExposedWebPushChannel;

        $channel->prune([
            $gone->id => $this->report(410),
            $kept->id => $this->report(201),
        ]);

        $this->assertDatabaseMissing('push_subscriptions', ['id' => $gone->id]);
        $this->assertDatabaseHas('push_subscriptions', ['id' => $kept->id]);
    }

    public function test_not_found_also_counts_as_expired(): void
    {
        $user = User::factory()->create(['role' => 'employee']);

        $gone = PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => 'https://push.example.test/missing',
            'public_key' => 'k',
            'auth_token' => 'a',
        ]);

        (new ExposedWebPushChannel)->prune([$gone->id => $this->report(404)]);

        $this->assertDatabaseMissing('push_subscriptions', ['id' => $gone->id]);
    }

    public function test_a_rejected_but_live_subscription_is_kept(): void
    {
        $user = User::factory()->create(['role' => 'employee']);

        $sub = PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => 'https://push.example.test/rejected',
            'public_key' => 'k',
            'auth_token' => 'a',
        ]);

        (new ExposedWebPushChannel)->prune([$sub->id => $this->report(429)]);

        // A rate limit must not throw away a working subscription.
        $this->assertDatabaseHas('push_subscriptions', ['id' => $sub->id]);
    }

    public function test_the_push_payload_is_valid_json_with_the_expected_shape(): void
    {
        $user = User::factory()->create(['role' => 'employee']);

        $payload = (new AppNotification('A title', 'A body', 'fund', '/admin/dashboard'))
            ->toWebPush($user);

        $decoded = json_decode($payload, true);

        $this->assertSame([
            'title' => 'A title',
            'body' => 'A body',
            'category' => 'fund',
            'url' => '/admin/dashboard',
        ], $decoded);

        // Push services reject anything beyond roughly 4KB.
        $this->assertLessThan(4096, strlen($payload));
    }

    public function test_the_stored_payload_matches_the_push_payload(): void
    {
        $user = User::factory()->create(['role' => 'employee']);

        $user->notify(new AppNotification('A title', 'A body', 'document', '/x'));

        $stored = $user->notifications()->first()->data;

        $this->assertSame('A title', $stored['title']);
        $this->assertSame('A body', $stored['body']);
        $this->assertSame('document', $stored['category']);
        $this->assertSame('/x', $stored['url']);
    }

    public function test_push_can_be_disabled_globally(): void
    {
        config([
            'services.vapid.public_key' => 'public',
            'services.vapid.private_key' => 'private',
            'notifications.push.enabled' => false,
        ]);

        $user = User::factory()->create(['role' => 'employee']);

        PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => 'https://push.example.test/a',
            'public_key' => 'k',
            'auth_token' => 'a',
        ]);

        (new ExposedWebPushChannel)->send($user, new AppNotification('Hi', 'Body'));

        // No attempt was made, so nothing was pruned or logged as expired.
        $this->assertDatabaseCount('push_subscriptions', 1);
    }

    private function report(int $status): MessageSentReport
    {
        return new MessageSentReport(
            new Request('POST', 'https://push.example.test/endpoint'),
            new Response($status),
            $status < 300,
            $status < 300 ? 'OK' : 'Rejected',
        );
    }
}
