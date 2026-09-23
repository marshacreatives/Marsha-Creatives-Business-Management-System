<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentCounter extends Model
{
    protected $fillable = [
        'type',
        'sequence',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
        ];
    }
}
