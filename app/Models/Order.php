<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'number', 'user_id', 'guest_email', 'guest_phone',
        'status', 'payment_status', 'fulfilment_status',
        'currency', 'subtotal', 'discount_total', 'shipping_total',
        'tax_total', 'rounding', 'grand_total',
        'paid_amount', 'refunded_amount',
        'coupon_id', 'coupon_code', 'coupon_discount',
        'shipping_method', 'courier', 'tracking_number', 'tracking_url',
        'payment_method', 'payment_provider', 'payment_provider_ref',
        'customer_notes', 'internal_notes',
        'locale', 'ip_address', 'user_agent', 'order_channel',
        'confirmed_at', 'packed_at', 'shipped_at', 'delivered_at',
        'cancelled_at', 'returned_at', 'tracking_token',
    ];

    protected function casts(): array
    {
        return [
            'subtotal'           => 'decimal:2',
            'discount_total'     => 'decimal:2',
            'shipping_total'     => 'decimal:2',
            'tax_total'          => 'decimal:2',
            'rounding'           => 'decimal:2',
            'grand_total'        => 'decimal:2',
            'paid_amount'        => 'decimal:2',
            'refunded_amount'    => 'decimal:2',
            'coupon_discount'    => 'decimal:2',
            'status'             => OrderStatus::class,
            'payment_status'     => PaymentStatus::class,
            'confirmed_at'        => 'datetime',
            'packed_at'           => 'datetime',
            'shipped_at'          => 'datetime',
            'delivered_at'        => 'datetime',
            'cancelled_at'        => 'datetime',
            'returned_at'         => 'datetime',
        ];
    }

    // --------------------------------------------------------------------------
    // Relationships
    // --------------------------------------------------------------------------
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(OrderAddress::class);
    }

    public function shippingAddress()
    {
        return $this->addresses()->where('type', 'shipping')->first();
    }

    public function billingAddress()
    {
        return $this->addresses()->where('type', 'billing')->first();
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function exchangeRequests(): HasMany
    {
        return $this->hasMany(ExchangeRequest::class);
    }

    public function inventoryMovements(): MorphMany
    {
        return $this->morphMany(InventoryMovement::class, 'reference');
    }

    public function stockReservations(): MorphMany
    {
        return $this->morphMany(StockReservation::class, 'reservable');
    }

    // --------------------------------------------------------------------------
    // Scopes
    // --------------------------------------------------------------------------
    public function scopeStatus(Builder $q, OrderStatus $status)
    {
        return $q->where('status', $status);
    }

    public function scopePaymentStatus(Builder $q, PaymentStatus $status)
    {
        return $q->where('payment_status', $status);
    }

    public function scopeSearch(Builder $q, ?string $term)
    {
        if (! $term) {
            return $q;
        }
        return $q->where(function ($inner) use ($term) {
            $inner->where('number', 'like', "%{$term}%")
                ->orWhere('guest_email', 'like', "%{$term}%")
                ->orWhere('guest_phone', 'like', "%{$term}%")
                ->orWhere('tracking_number', 'like', "%{$term}%");
        });
    }

    public function scopeInPeriod(Builder $q, $from, $to)
    {
        return $q->whereBetween('created_at', [$from, $to]);
    }

    public function scopeTodays($q)
    {
        return $q->whereDate('created_at', now());
    }

    // --------------------------------------------------------------------------
    // State Machine
    // --------------------------------------------------------------------------
    public function transitionTo(OrderStatus $next, ?User $actor = null, ?string $note = null): bool
    {
        if (! $this->status->canTransitionTo($next)) {
            return false;
        }

        $previous = $this->status;
        $this->status = $next;

        $timestamps = [
            OrderStatus::Confirmed->value => 'confirmed_at',
            OrderStatus::Packed->value    => 'packed_at',
            OrderStatus::Shipped->value    => 'shipped_at',
            OrderStatus::Delivered->value  => 'delivered_at',
            OrderStatus::Cancelled->value  => 'cancelled_at',
            OrderStatus::Returned->value  => 'returned_at',
        ];
        if ($column = $timestamps[$next->value] ?? null) {
            $this->{$column} = now();
        }

        $saved = $this->save();

        OrderStatusHistory::create([
            'order_id'           => $this->id,
            'previous_status'    => $previous->value,
            'new_status'         => $next->value,
            'note'               => $note,
            'customer_notified'  => false,
            'actor_id'           => $actor?->id,
        ]);

        return $saved;
    }

    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::Paid;
    }

    public function isCancellable(): bool
    {
        return $this->status->isCancellable();
    }

    public function canBePaid(): bool
    {
        return in_array($this->payment_status, [
            PaymentStatus::Unpaid,
            PaymentStatus::Pending,
            PaymentStatus::Failed,
        ], true);
    }

    public function outstandingAmount(): float
    {
        return max(0, (float) $this->grand_total - (float) $this->paid_amount);
    }

    public function trackUrl(): ?string
    {
        if ($this->tracking_url) {
            return $this->tracking_url;
        }
        if ($this->tracking_number && $this->courier) {
            return match (strtolower($this->courier)) {
                'tcs'      => "https://www.tcsexpress.com/Tracking?tc={$this->tracking_number}",
                'leopards' => "https://www.leopards.com/track?={$this->tracking_number}",
                'dhl'      => "https://www.dhl.com/pk-en/home/tracking?tracking-id={$this->tracking_number}",
                default    => null,
            };
        }
        return null;
    }

    public function generateTrackingToken(): string
    {
        $this->tracking_token = hash_hmac('sha256', $this->uuid, config('app.key'));
        $this->save();
        return $this->tracking_token;
    }

    public static function nextOrderNumber(): string
    {
        $year = now()->format('y');
        $prefix = "HS-{$year}-";
        $count = self::where('number', 'like', $prefix.'%')->count();
        $next = str_pad((string) ($count + 1), 6, '0', STR_PAD_LEFT);
        return $prefix.$next;
    }
}
