<?php

namespace App\Models;

use App\Traits\GeneratesSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreLocation extends Model
{
    use HasFactory, GeneratesSlug;

    protected $fillable = [
        'name', 'slug', 'address', 'phone', 'email', 'whatsapp',
        'map_link', 'latitude', 'longitude', 'hours',
        'is_main', 'status', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'latitude'   => 'decimal:7',
            'longitude'  => 'decimal:7',
            'is_main'    => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // --------------------------------------------------------------------------
    // Scopes
    // --------------------------------------------------------------------------
    public function scopeActive(Builder $q)
    {
        return $q->where('status', 'active');
    }

    public function scopeMain(Builder $q)
    {
        return $q->where('is_main', true);
    }
}
