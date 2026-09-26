<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * A single notification type for every in-app event.
 *
 * The event specific details live in the payload, which keeps the number of
 * classes manageable and means new event types never require a migration.
 */
class AppNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly string $category = 'job',
        public readonly ?string $url = null,
    ) {}

    /**
     * Push is delivered inline rather than through the queue because shared
     * hosting cannot keep a worker process alive. See config/notifications.php.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', Channels\WebPushChannel::class];
    }

    /**
     * The payload persisted to the notifications table and returned to the
     * bell dropdown by the polling endpoint.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'category' => $this->category,
            'url' => $this->url,
        ];
    }

    /**
     * The JSON payload handed to the service worker. Kept small on purpose:
     * push services reject messages beyond roughly 4KB.
     */
    public function toWebPush(object $notifiable): string
    {
        return json_encode([
            'title' => $this->title,
            'body' => $this->body,
            'category' => $this->category,
            'url' => $this->url,
        ], JSON_THROW_ON_ERROR);
    }
}
