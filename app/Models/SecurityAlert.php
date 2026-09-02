<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SecurityAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'severity',
        'source_ip',
        'source_port',
        'destination_port',
        'description',
        'raw_log',
        'action_taken',
        'is_resolved',
        'occurred_at',
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'occurred_at' => 'datetime',
    ];

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeHigh($query)
    {
        return $query->where('severity', 'high');
    }

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('occurred_at', '>=', now()->subHours($hours));
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function getSeverityColorAttribute(): string
    {
        return match($this->severity) {
            'critical' => '#dc3545',
            'high' => '#fd7e14',
            'medium' => '#ffc107',
            'low' => '#17a2b8',
            'info' => '#6c757d',
            default => '#6c757d',
        };
    }

    public function getSeverityBadgeAttribute(): string
    {
        return match($this->severity) {
            'critical' => 'bg-red-500',
            'high' => 'bg-orange-500',
            'medium' => 'bg-yellow-500',
            'low' => 'bg-cyan-500',
            'info' => 'bg-gray-500',
            default => 'bg-gray-500',
        };
    }
}
