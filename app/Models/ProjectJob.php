<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectJob extends Model
{
    protected $table = 'project_jobs';

    protected $fillable = [
        'project_name',
        'expense',
        'cost',
        'status',
        'assigned_to',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'expense' => 'decimal:2',
            'cost' => 'decimal:2',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getProfitAttribute(): float
    {
        return (float) $this->cost - (float) $this->expense;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => $this->status,
        };
    }
}
