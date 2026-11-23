<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sale extends Model
{
    protected $fillable = [
        'receipt_number',
        'user_id',
        'subtotal',
        'vat',
        'total',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'vat' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Shape a sale for receipts and recent-sale tables.
     *
     * @return array<string, mixed>
     */
    public function toReceiptData(): array
    {
        $this->loadMissing(['items', 'payment', 'user:id,name']);

        return [
            'id' => $this->id,
            'receipt' => $this->receipt_number,
            'date' => $this->created_at->toIso8601String(),
            'cashier' => $this->user?->name ?? 'Unknown',
            'subtotal' => (float) $this->subtotal,
            'vat' => (float) $this->vat,
            'total' => (float) $this->total,
            'status' => $this->status,
            'payment' => [
                'method' => $this->payment?->method ?? '—',
                'amount' => (float) ($this->payment?->amount ?? $this->total),
                'change' => (float) ($this->payment?->change ?? 0),
            ],
            'items' => $this->items->map(fn (SaleItem $item) => [
                'id' => $item->id,
                'name' => $item->product_name,
                'sku' => $item->sku,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'line_total' => (float) $item->line_total,
            ])->values(),
            'business' => Setting::business(),
        ];
    }
}
