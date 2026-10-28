<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    use HasFactory, HasUuid, LogsActivity;

    protected $fillable = [
        'order_id', 'courier', 'method', 'tracking_number', 'tracking_url',
        'status', 'shipping_cost', 'weight_grams',
        'shipped_at', 'delivered_at', 'estimated_delivery_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'shipping_cost'   => 'decimal:2',
            'weight_grams'    => 'decimal:2',
            'shipped_at'      => 'datetime',
            'delivered_at'    => 'datetime',
            'estimated_delivery_at' => 'datetime',
        ];
    }

    public function statusLabel(): string
    {
        return ucwords(str_replace('_', ' ', $this->status));
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
