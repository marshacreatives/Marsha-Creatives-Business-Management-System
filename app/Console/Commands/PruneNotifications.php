<?php

namespace App\Console\Commands;

use App\Notifications\AppNotification;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;

class PruneNotifications extends Command
{
    protected $signature = 'notifications:prune {--days= : Override the configured retention window}';

    protected $description = 'Delete read notifications older than the retention window';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('notifications.prune_after_days', 60));
        $cutoff = now()->subDays($days);

        // Read notifications are safe to drop. Unread ones are kept so a user
        // never silently loses something they had not seen yet.
        $deleted = DatabaseNotification::query()
            ->where('type', AppNotification::class)
            ->whereNotNull('read_at')
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Deleted {$deleted} read notification(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
