<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShippingZone extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'name', 'code', 'countries', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'countries' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class)->orderBy('sort_order')->orderBy('id');
    }

    public function activeRates(): HasMany
    {
        return $this->rates()->where('is_active', true);
    }

    public function countryList(): string
    {
        return collect($this->countries ?? [])->filter()->implode(', ') ?: 'All countries';
    }
}
