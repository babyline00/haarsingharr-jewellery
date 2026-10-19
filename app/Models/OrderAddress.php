<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderAddress extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'order_id', 'type', 'first_name', 'last_name',
        'phone_country', 'phone',
        'address_line_1', 'address_line_2', 'landmark',
        'city', 'province', 'country', 'postal_code',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function fullAddress(): string
    {
        return collect([
            $this->address_line_1,
            $this->address_line_2,
            $this->landmark,
            $this->city,
            $this->province,
            $this->postal_code,
            $this->country,
        ])->filter()->implode(', ');
    }
}
