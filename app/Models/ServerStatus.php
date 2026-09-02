<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServerStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_name',
        'cpu_usage',
        'ram_usage',
        'ram_total_gb',
        'ram_used_gb',
        'disk_usage',
        'disk_total_gb',
        'disk_used_gb',
        'load_average_1',
        'load_average_5',
        'load_average_15',
        'active_connections',
        'total_processes',
        'uptime_seconds',
        'services_status',
        'checked_at',
    ];

    protected $casts = [
        'services_status' => 'array',
        'checked_at' => 'datetime',
    ];

    public function scopeLatest($query)
    {
        return $query->orderBy('checked_at', 'desc');
    }

    public function scopeForServer($query, string $serverName)
    {
        return $query->where('server_name', $serverName);
    }

    public function getUptimeFormattedAttribute(): string
    {
        $days = floor($this->uptime_seconds / 86400);
        $hours = floor(($this->uptime_seconds % 86400) / 3600);
        $minutes = floor(($this->uptime_seconds % 3600) / 60);

        if ($days > 0) {
            return "{$days}d {$hours}h {$minutes}m";
        }
        return "{$hours}h {$minutes}m";
    }

    public function getCpuStatusAttribute(): string
    {
        return match(true) {
            $this->cpu_usage >= 90 => 'critical',
            $this->cpu_usage >= 70 => 'warning',
            default => 'healthy',
        };
    }

    public function getRamStatusAttribute(): string
    {
        return match(true) {
            $this->ram_usage >= 90 => 'critical',
            $this->ram_usage >= 75 => 'warning',
            default => 'healthy',
        };
    }

    public function getDiskStatusAttribute(): string
    {
        return match(true) {
            $this->disk_usage >= 95 => 'critical',
            $this->disk_usage >= 85 => 'warning',
            default => 'healthy',
        };
    }
}
