<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'user_id', 'label', 'first_name', 'last_name',
        'phone_country', 'phone',
        'address_line_1', 'address_line_2', 'landmark',
        'city', 'province', 'country', 'postal_code',
        'is_default_shipping', 'is_default_billing',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_default_shipping' => 'boolean',
            'is_default_billing'  => 'boolean',
            'metadata'            => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function fullPhone(): string
    {
        return $this->phone_country.' '.$this->phone;
    }
}
