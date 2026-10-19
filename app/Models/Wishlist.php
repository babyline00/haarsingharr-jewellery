<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wishlist extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = ['user_id', 'session_id', 'name'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function addProduct(Product $product, ?int $variantId = null): WishlistItem
    {
        return WishlistItem::firstOrCreate([
            'wishlist_id'         => $this->id,
            'product_id'           => $product->id,
        ], [
            'product_variant_id'  => $variantId,
        ]);
    }
}
