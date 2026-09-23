<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WordPressSite extends Model
{
    protected $table = 'wordpress_sites';

    protected $fillable = [
        'site_url',
        'status',
        'current_step',
        'steps_log',
        'installed_plugins',
        'active_theme',
        'error_message',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'steps_log' => 'array',
            'installed_plugins' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Queued',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'failed' => 'Failed',
            default => ucfirst($this->status),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'bg-gray-100 text-gray-800',
            'in_progress' => 'bg-blue-100 text-blue-800',
            'completed' => 'bg-green-100 text-green-800',
            'failed' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function appendStep(string $step, string $status = 'pending', ?string $detail = null): void
    {
        $log = $this->steps_log ?? [];

        // Keep a single entry per step name so repeated installs update in place.
        $log = collect($log)->filter(fn ($entry) => ($entry['step'] ?? null) !== $step)->values()->all();

        $log[] = [
            'step' => $step,
            'status' => $status,
            'detail' => $detail,
            'at' => now()->toIso8601String(),
        ];

        $this->forceFill([
            'steps_log' => $log,
            'current_step' => $step,
        ])->save();
    }

    public function markStep(string $step, string $status, ?string $detail = null): void
    {
        $log = collect($this->steps_log ?? [])->map(function ($entry) use ($step, $status, $detail) {
            if (($entry['step'] ?? null) !== $step) {
                return $entry;
            }

            return [
                'step' => $step,
                'status' => $status,
                'detail' => $detail,
                'at' => now()->toIso8601String(),
            ];
        })->values()->all();

        $this->forceFill(['steps_log' => $log])->save();
    }
}
