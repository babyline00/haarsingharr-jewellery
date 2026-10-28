<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExchangeRequest extends Model
{
    use HasFactory, HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'number', 'order_id', 'order_item_id',
        'type', 'status', 'reason',
        'customer_notes', 'admin_notes',
        'requested_variant_id', 'quantity',
        'refund_amount', 'refund_id',
        'received_at', 'restocked_at', 'completed_at', 'return_label_url',
    ];

    protected function casts(): array
    {
        return [
            'quantity'        => 'integer',
            'refund_amount'   => 'decimal:2',
            'received_at'     => 'datetime',
            'restocked_at'    => 'datetime',
            'completed_at'    => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function requestedVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'requested_variant_id');
    }

    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class);
    }

    public static function nextNumber(): string
    {
        $prefix = 'EX-'.now()->format('y').'-';
        $count = self::where('number', 'like', $prefix.'%')->count();
        return $prefix.str_pad((string) ($count + 1), 6, '0', STR_PAD_LEFT);
    }
}
