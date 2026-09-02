<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'check_type',
        'status',
        'alerts_found',
        'ips_blocked',
        'details',
        'execution_time_ms',
        'started_at',
    ];

    protected $casts = [
        'alerts_found' => 'integer',
        'ips_blocked' => 'integer',
        'execution_time_ms' => 'float',
        'started_at' => 'datetime',
    ];

    public function scopeByCheckType($query, string $type)
    {
        return $query->where('check_type', $type);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('started_at', '>=', now()->subHours($hours));
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }
}
