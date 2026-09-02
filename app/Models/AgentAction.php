<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_name',
        'action',
        'resource',
        'severity',
        'status',
        'description',
        'details',
        'performed_at',
    ];

    protected $casts = [
        'performed_at' => 'datetime',
    ];

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('performed_at', '>=', now()->subHours($hours));
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
