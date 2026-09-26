<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Browser Push Delivery
    |--------------------------------------------------------------------------
    |
    | This application is hosted on shared hosting, where no long-running
    | "queue:work" process can be kept alive. Push messages are therefore sent
    | synchronously, inside the request that triggered them. The timeout below
    | caps how long that request can be held open, and delivery failures are
    | logged rather than surfaced so the originating action always succeeds.
    |
    */

    'push' => [

        /*
        | Seconds to wait on a push service before giving up. Keep this low;
        | a user creating a job should not stare at a spinner.
        */
        'timeout' => (int) env('PUSH_TIMEOUT', 5),

        /*
        | How many device subscriptions to push to per recipient. One user may
        | have a phone, a laptop and a desktop registered at once.
        */
        'per_user_limit' => (int) env('PUSH_PER_USER_LIMIT', 5),

        /*
        | Disable push delivery entirely without removing any code. The
        | in-app notification bell keeps working.
        */
        'enabled' => env('PUSH_ENABLED', true),

    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Retention
    |--------------------------------------------------------------------------
    |
    | Notifications older than this many days are pruned by the
    | "notifications:prune" command, which is safe to run from cron.
    |
    */

    'prune_after_days' => (int) env('NOTIFICATIONS_PRUNE_AFTER_DAYS', 60),

    /*
    |--------------------------------------------------------------------------
    | Notification Categories
    |--------------------------------------------------------------------------
    |
    | Each key is rendered with its own accent colour in the bell dropdown and
    | is used to group the full notifications page.
    |
    */

    'categories' => [
        'job' => ['label' => 'Jobs', 'color' => 'blue'],
        'fund' => ['label' => 'Fund Requests', 'color' => 'amber'],
        'document' => ['label' => 'Documents', 'color' => 'emerald'],
    ],

];
