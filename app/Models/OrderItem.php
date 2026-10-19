<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'order_id', 'product_id', 'product_variant_id',
        'product_name', 'product_slug', 'sku', 'variant_title',
        'variant_attributes', 'product_image',
        'quantity', 'unit_price', 'sale_price', 'unit_cost',
        'line_total', 'tax_amount', 'discount_amount',
        'refund_amount', 'refunded_quantity', 'fulfilment_status',
    ];

    protected function casts(): array
    {
        return [
            'quantity'              => 'integer',
            'unit_price'            => 'decimal:2',
            'sale_price'            => 'decimal:2',
            'unit_cost'             => 'decimal:2',
            'line_total'            => 'decimal:2',
            'tax_amount'            => 'decimal:2',
            'discount_amount'       => 'decimal:2',
            'refund_amount'         => 'decimal:2',
            'refunded_quantity'     => 'integer',
            'variant_attributes'   => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
