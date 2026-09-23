<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    public const PREFIXES = [
        'invoice' => 'INV',
        'quote' => 'QT',
        'receipt' => 'RC',
    ];

    protected $fillable = [
        'type',
        'number',
        'client_name',
        'issue_date',
        'discount',
        'total',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(DocumentItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'invoice' => 'Invoice',
            'quote' => 'Quotation',
            'receipt' => 'Receipt',
            default => ucfirst($this->type),
        };
    }

    public function getTypeTitleAttribute(): string
    {
        return strtoupper($this->type_label);
    }

    public function getSubtotalAttribute(): float
    {
        return (float) $this->items->sum(fn ($line) => (float) $line->line_total);
    }

    public static function nextNumber(string $type): string
    {
        $counter = DocumentCounter::where('type', $type)->lockForUpdate()->first();

        if (! $counter) {
            $counter = DocumentCounter::create(['type' => $type, 'sequence' => 0]);
        }

        $counter->increment('sequence');
        $counter->refresh();

        return sprintf('%s-%06d', self::PREFIXES[$type] ?? strtoupper(substr($type, 0, 2)), $counter->sequence);
    }
}
