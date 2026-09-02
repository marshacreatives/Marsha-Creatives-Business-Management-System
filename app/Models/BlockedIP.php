<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockedIP extends Model
{
    use HasFactory;

    protected $table = 'blocked_ips';

    protected $fillable = [
        'ip',
        'reason',
        'blocked_by',
        'is_permanent',
        'blocked_at',
        'unblocked_at',
        'is_active',
    ];

    protected $casts = [
        'is_permanent' => 'boolean',
        'is_active' => 'boolean',
        'blocked_at' => 'datetime',
        'unblocked_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePermanent($query)
    {
        return $query->where('is_permanent', true);
    }

    public function block(string $reason, string $blockedBy = 'agent', bool $permanent = false): void
    {
        $this->update([
            'reason' => $reason,
            'blocked_by' => $blockedBy,
            'is_permanent' => $permanent,
            'blocked_at' => now(),
            'unblocked_at' => null,
            'is_active' => true,
        ]);
    }

    public function unblock(): void
    {
        $this->update([
            'is_active' => false,
            'unblocked_at' => now(),
        ]);
    }
}
