<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    /**
     * The browser needs the public half of the VAPID pair before it can
     * subscribe. The private key is never exposed.
     */
    public function key(): JsonResponse
    {
        return response()->json([
            'public_key' => config('services.vapid.public_key'),
            'enabled' => (bool) config('notifications.push.enabled')
                && (bool) config('services.vapid.public_key')
                && (bool) config('services.vapid.private_key'),
        ]);
    }

    /**
     * Store a subscription from navigator.serviceWorker.ready.pushManager.
     *
     * The endpoint is globally unique because it identifies one browser
     * profile: re-subscribing after a key rotation replaces the existing row
     * and re-points it at whoever is now signed in.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => 'required|string|max:500',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        $user = $request->user();

        $subscription = PushSubscription::updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'user_id' => $user->id,
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'content_encoding' => 'aes128gcm',
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ],
        );

        return response()->json(['ok' => true, 'id' => $subscription->id]);
    }

    /**
     * Stop pushing to this browser, used when the user opts out.
     */
    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => 'required|string|max:500',
        ]);

        PushSubscription::where('endpoint', $data['endpoint'])
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['ok' => true]);
    }
}
