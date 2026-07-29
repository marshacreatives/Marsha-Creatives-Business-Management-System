<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyBalance extends Model
{
    protected $table = 'company_balances';

    protected $fillable = [
        'balance',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
        ];
    }

    public static function getBalance(): float
    {
        $record = static::firstOrCreate([], ['balance' => 0]);
        return (float) $record->balance;
    }

    public static function updateBalance(float $amount): void
    {
        $record = static::firstOrCreate([], ['balance' => 0]);
        $record->update(['balance' => $amount]);
    }

    public static function adjustBalance(float $adjustment): float
    {
        $record = static::firstOrCreate([], ['balance' => 0]);
        $newBalance = (float) $record->balance + $adjustment;
        $record->update(['balance' => $newBalance]);
        return $newBalance;
    }
}
