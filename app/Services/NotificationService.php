<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Single entry point for raising in-app notifications.
 *
 * Wrapping every send in a try/catch is deliberate. A notification is a
 * courtesy to the user, never a precondition for the action that triggered
 * it, so nothing here is ever allowed to propagate.
 */
class NotificationService
{
    /**
     * Notify every admin.
     *
     * When the actor is themselves an admin they are skipped, so an admin is
     * not told about their own actions.
     */
    public static function notifyAdmins(
        string $title,
        string $body,
        ?string $url = null,
        string $category = 'job',
    ): void {
        $actorId = auth()->id();

        $admins = User::where('role', 'admin')
            ->when($actorId, fn ($query) => $query->where('id', '!=', $actorId))
            ->get();

        if ($admins->isEmpty()) {
            return;
        }

        $notification = new AppNotification($title, $body, $category, $url);

        foreach ($admins as $admin) {
            static::send($admin, $notification);
        }
    }

    /**
     * Notify one specific user, unless they are the one who acted.
     */
    public static function notifyUser(
        User $user,
        string $title,
        string $body,
        ?string $url = null,
        string $category = 'job',
    ): void {
        if (auth()->check() && auth()->id() === $user->id) {
            return;
        }

        static::send($user, new AppNotification($title, $body, $category, $url));
    }

    private static function send(User $user, AppNotification $notification): void
    {
        try {
            $user->notify($notification);
        } catch (Throwable $e) {
            Log::error('Notification could not be delivered.', [
                'user_id' => $user->id,
                'title' => $notification->title,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
