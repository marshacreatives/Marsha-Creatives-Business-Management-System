<?php

namespace App\Notifications\Channels;

use App\Models\PushSubscription;
use GuzzleHttp\Client;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\MessageSentReport;
use Minishlink\WebPush\WebPush;
use Throwable;

/**
 * Delivers a notification to every browser that subscribed for it.
 *
 * This sends synchronously rather than through the queue because the app runs
 * on shared hosting where no worker process can be kept alive. Every failure
 * mode is caught and logged, because a push service being slow or unreachable
 * must never stop a user from saving a job.
 */
class WebPushChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! config('notifications.push.enabled') || ! $this->isConfigured()) {
            return;
        }

        if (! method_exists($notifiable, 'pushSubscriptions')) {
            return;
        }

        $limit = (int) config('notifications.push.per_user_limit', 5);

        // Ordered by id rather than created_at because the timestamp column has
        // no sub-second precision, so same-second rows would order arbitrarily.
        $subscriptions = $notifiable->pushSubscriptions()
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        if (! method_exists($notification, 'toWebPush')) {
            return;
        }

        try {
            $payload = $notification->toWebPush($notifiable);
        } catch (Throwable $e) {
            Log::warning('Web push payload could not be built.', ['error' => $e->getMessage()]);

            return;
        }

        $this->deliver($subscriptions, $payload);
    }

    /**
     * @param  Collection<int, PushSubscription>  $subscriptions
     */
    private function deliver(Collection $subscriptions, string $payload): void
    {
        try {
            $webPush = $this->makeWebPush();
        } catch (Throwable $e) {
            Log::warning('Web Push client could not be created.', ['error' => $e->getMessage()]);

            return;
        }

        /** @var array<int, MessageSentReport> $reports */
        $reports = [];

        foreach ($subscriptions as $subscription) {
            try {
                $reports[$subscription->id] = $webPush->sendOneNotification(
                    $subscription->toWebPushSubscription(),
                    $payload,
                );
            } catch (Throwable $e) {
                // One unreachable push service must not abandon the others.
                Log::warning('Web push request failed for a subscription.', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->pruneDeadSubscriptions($reports);
    }

    /**
     * Drop subscriptions the push service reports as gone, and log the rest.
     * Endpoints expire when a user clears site data or removes the PWA, so
     * without this the table grows and every send gets slower.
     *
     * @param  array<int, MessageSentReport>  $reports
     */
    protected function pruneDeadSubscriptions(array $reports): void
    {
        foreach ($reports as $subscriptionId => $report) {
            if ($report->isSubscriptionExpired()) {
                PushSubscription::whereKey($subscriptionId)->delete();

                continue;
            }

            if (! $report->isSuccess()) {
                Log::info('Web push was not accepted by the push service.', [
                    'subscription_id' => $subscriptionId,
                    'reason' => $report->getReason(),
                ]);
            }
        }
    }

    protected function makeWebPush(): WebPush
    {
        $timeout = (int) config('notifications.push.timeout', 5);

        // Guzzle ships as a direct dependency of laravel/framework, so it is
        // always present. Handing it to WebPush is what lets us cap how long a
        // push request may hold the user's request open.
        $client = new Client([
            'timeout' => $timeout,
            'connect_timeout' => $timeout,
        ]);

        return new WebPush([
            'VAPID' => [
                'subject' => config('services.vapid.subject'),
                'publicKey' => config('services.vapid.public_key'),
                'privateKey' => config('services.vapid.private_key'),
            ],
        ], [
            'TTL' => 86400,
        ], $client);
    }

    private function isConfigured(): bool
    {
        return (bool) config('services.vapid.public_key')
            && (bool) config('services.vapid.private_key');
    }
}
