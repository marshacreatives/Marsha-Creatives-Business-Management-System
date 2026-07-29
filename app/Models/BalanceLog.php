<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BalanceLog extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'previous_balance',
        'new_balance',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'previous_balance' => 'decimal:2',
            'new_balance' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
