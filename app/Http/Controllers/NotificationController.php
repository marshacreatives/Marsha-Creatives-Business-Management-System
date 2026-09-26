<?php

namespace App\Http\Controllers;

use App\Notifications\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * The bell dropdown's poll. Returns the newest notifications for the
     * signed in user plus the unread count used for the badge.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $limit = min(max((int) $request->integer('limit', 15), 1), 50);

        $notifications = $user->notifications()
            ->where('type', AppNotification::class)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($notification) => $this->present($notification));

        return response()->json([
            'unread_count' => $user->unreadNotifications()
                ->where('type', AppNotification::class)
                ->count(),
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mark a single notification as read, scoped to its owner so one user can
     * never touch another's notification by guessing an id.
     */
    public function read(Request $request, string $id): JsonResponse
    {
        $notification = $this->findOwned($request, $id);

        $notification->markAsRead();

        return response()->json(['ok' => true]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()
            ->where('type', AppNotification::class)
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $notification = $this->findOwned($request, $id);

        $notification->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * The full notifications page behind the dropdown's "View all" link.
     */
    public function page(Request $request): View
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->where('type', AppNotification::class)
            ->latest()
            ->paginate(30);

        $categories = collect(config('notifications.categories'))
            ->map(fn (array $config, string $key) => [
                'key' => $key,
                'label' => $config['label'],
                'color' => $config['color'],
            ])
            ->values();

        return view('notifications.index', compact('notifications', 'categories'));
    }

    /**
     * Look up one of the signed in user's own notifications.
     *
     * Answers with JSON rather than letting findOrFail raise, because every
     * caller of this action is the bell's JavaScript, which would otherwise
     * try to parse an HTML error page.
     */
    private function findOwned(Request $request, string $id): DatabaseNotification
    {
        $notification = $request->user()->notifications()
            ->where('type', AppNotification::class)
            ->find($id);

        if ($notification === null) {
            abort(404, 'Notification not found.');
        }

        return $notification;
    }

    /**
     * Shape a stored notification for the dropdown and the full page.
     */
    private function present(DatabaseNotification $notification): array
    {
        $data = $notification->data;
        $category = $data['category'] ?? 'job';

        return [
            'id' => $notification->id,
            'title' => $data['title'] ?? '',
            'body' => $data['body'] ?? '',
            'url' => $data['url'] ?? null,
            'category' => $category,
            'color' => config("notifications.categories.{$category}.color", 'blue'),
            'created_at' => $notification->created_at?->toIso8601String(),
            'read_at' => $notification->read_at?->toIso8601String(),
        ];
    }
}
