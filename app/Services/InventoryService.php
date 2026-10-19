<?php

namespace App\Services;

use App\Models\InventoryAlert;
use App\Models\InventoryMovement;
use App\Models\StockReservation;
use App\Models\User;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class InventoryService
{
    /**
     * Atomically reserve stock for a cart/order. Uses row-level locking
     * to prevent overselling under concurrent checkout.
     */
    public function reserve(ProductVariant $variant, int $quantity, Model $reservable,
        ?string $reason = null): StockReservation
    {
        if ($quantity <= 0) {
            throw new RuntimeException('Reservation quantity must be positive.');
        }

        return DB::transaction(function () use ($variant, $quantity, $reservable, $reason) {
            // Lock the variant row for the duration of the transaction.
            $locked = DB::table('product_variants')
                ->where('id', $variant->id)
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                throw new RuntimeException('Variant not found.');
            }

            $available = $locked->stock_quantity - $locked->reserved_stock - $locked->safety_stock;

            if ($available < $quantity && ! $locked->allow_backorder) {
                throw new RuntimeException(
                    "Insufficient stock for SKU {$variant->sku}. Available: {$available}."
                );
            }

            DB::table('product_variants')
                ->where('id', $variant->id)
                ->increment('reserved_stock', $quantity);

            $reservation = StockReservation::create([
                'product_variant_id' => $variant->id,
                'quantity'           => $quantity,
                'reservable_type'    => $reservable::class,
                'reservable_id'      => $reservable->getKey(),
                'expires_at'         => now()->addMinutes(config('storefront.inventory.reserve_minutes', 30)),
                'reason'             => $reason,
            ]);

            $movement = InventoryMovement::create([
                'product_variant_id'  => $variant->id,
                'product_id'          => $variant->product_id,
                'type'                => 'reservation',
                'direction'          => 'none',
                'quantity_change'     => 0,
                'previous_stock'      => $locked->stock_quantity,
                'new_stock'           => $locked->stock_quantity,
                'reserved_change'     => $quantity,
                'previous_reserved'   => $locked->reserved_stock,
                'new_reserved'        => $locked->reserved_stock + $quantity,
                'reason'              => $reason ?? 'Reservation for '.$reservable::class,
                'reference_type'      => $reservable::class,
                'reference_id'        => $reservable->getKey(),
                'actor_id'            => auth()->id(),
            ]);

            return $reservation;
        });
    }

    /**
     * Release a previously held reservation. Idempotent — safe to call
     * multiple times (only decrements reserved_stock once).
     */
    public function release(StockReservation $reservation): bool
    {
        return DB::transaction(function () use ($reservation) {
            if ($reservation->released_at) {
                return true; // already released
            }

            $locked = DB::table('product_variants')
                ->where('id', $reservation->product_variant_id)
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                Log::warning('StockReservation: variant missing during release', [
                    'reservation_id' => $reservation->id,
                    'variant_id' => $reservation->product_variant_id,
                ]);
                $reservation->update(['released_at' => now()]);
                return true;
            }

            $newReserved = max(0, $locked->reserved_stock - $reservation->quantity);

            DB::table('product_variants')
                ->where('id', $reservation->product_variant_id)
                ->update(['reserved_stock' => $newReserved]);

            $reservation->update(['released_at' => now()]);

            InventoryMovement::create([
                'product_variant_id' => $reservation->product_variant_id,
                'type'               => 'release',
                'direction'          => 'none',
                'quantity_change'    => 0,
                'previous_stock'     => $locked->stock_quantity,
                'new_stock'          => $locked->stock_quantity,
                'reserved_change'    => -$reservation->quantity,
                'previous_reserved'  => $locked->reserved_stock,
                'new_reserved'       => $newReserved,
                'reason'             => 'Reservation expired/released',
                'reference_type'     => $reservation::class,
                'reference_id'       => $reservation->id,
                'actor_id'           => auth()->id(),
            ]);

            return true;
        });
    }

    /**
     * Convert a reservation into a sale — actually decrements stock.
     * Safe to call multiple times (idempotent via released_at check).
     */
    public function consume(StockReservation $reservation, Model $order): bool
    {
        return DB::transaction(function () use ($reservation, $order) {
            if ($reservation->released_at) {
                return true; // already consumed
            }

            $locked = DB::table('product_variants')
                ->where('id', $reservation->product_variant_id)
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                return false;
            }

            $newStock = max(0, $locked->stock_quantity - $reservation->quantity);
            $newReserved = max(0, $locked->reserved_stock - $reservation->quantity);

            DB::table('product_variants')
                ->where('id', $reservation->product_variant_id)
                ->update([
                    'stock_quantity'  => $newStock,
                    'reserved_stock'  => $newReserved,
                ]);

            $reservation->update(['released_at' => now()]);

            InventoryMovement::create([
                'product_variant_id' => $reservation->product_variant_id,
                'product_id'         => $locked->product_id,
                'type'               => 'sale_out',
                'direction'          => 'out',
                'quantity_change'    => -$reservation->quantity,
                'previous_stock'     => $locked->stock_quantity,
                'new_stock'          => $newStock,
                'reserved_change'    => -$reservation->quantity,
                'previous_reserved'  => $locked->reserved_stock,
                'new_reserved'       => $newReserved,
                'reason'             => "Order {$order->number} confirmed",
                'reference_type'     => $order::class,
                'reference_id'       => $order->id,
                'actor_id'           => auth()->id(),
            ]);

            $this->refreshAlerts(ProductVariant::find($reservation->product_variant_id));
            return true;
        });
    }

    /**
     * Manual adjustment by staff. All fields mandatory for audit trail.
     */
    public function adjust(ProductVariant $variant, int $newStock, string $reason,
        ?User $actor = null, ?string $notes = null): InventoryMovement
    {
        if (! $reason) {
            throw new RuntimeException('Reason is required for manual stock adjustments.');
        }

        return DB::transaction(function () use ($variant, $newStock, $reason, $actor, $notes) {
            $locked = DB::table('product_variants')
                ->where('id', $variant->id)
                ->lockForUpdate()
                ->first();

            $previous = $locked->stock_quantity;
            $change = $newStock - $previous;

            DB::table('product_variants')
                ->where('id', $variant->id)
                ->update(['stock_quantity' => $newStock]);

            $movement = InventoryMovement::create([
                'product_variant_id' => $variant->id,
                'product_id'         => $variant->product_id,
                'type'               => 'adjustment',
                'direction'          => $change > 0 ? 'in' : ($change < 0 ? 'out' : 'none'),
                'quantity_change'    => $change,
                'previous_stock'     => $previous,
                'new_stock'          => $newStock,
                'reserved_change'    => 0,
                'previous_reserved'  => $locked->reserved_stock,
                'new_reserved'       => $locked->reserved_stock,
                'reason'             => $reason,
                'notes'              => $notes,
                'actor_id'           => $actor?->id ?? auth()->id(),
            ]);

            $this->refreshAlerts($variant->refresh());
            return $movement;
        });
    }

    /**
     * Restock after return / exchange / damage.
     */
    public function restock(ProductVariant $variant, int $quantity, string $reason,
        ?User $actor = null, ?Model $reference = null): InventoryMovement
    {
        if ($quantity <= 0) {
            throw new RuntimeException('Restock quantity must be positive.');
        }

        return DB::transaction(function () use ($variant, $quantity, $reason, $actor, $reference) {
            $locked = DB::table('product_variants')
                ->where('id', $variant->id)
                ->lockForUpdate()
                ->first();

            $newStock = $locked->stock_quantity + $quantity;

            DB::table('product_variants')
                ->where('id', $variant->id)
                ->update(['stock_quantity' => $newStock]);

            $movement = InventoryMovement::create([
                'product_variant_id' => $variant->id,
                'product_id'         => $variant->product_id,
                'type'               => 'return_in',
                'direction'          => 'in',
                'quantity_change'    => $quantity,
                'previous_stock'     => $locked->stock_quantity,
                'new_stock'          => $newStock,
                'reserved_change'    => 0,
                'previous_reserved'  => $locked->reserved_stock,
                'new_reserved'       => $locked->reserved_stock,
                'reason'             => $reason,
                'reference_type'     => $reference?->getMorphClass(),
                'reference_id'       => $reference?->getKey(),
                'actor_id'           => $actor?->id ?? auth()->id(),
            ]);

            $this->refreshAlerts($variant->refresh());
            return $movement;
        });
    }

    /**
     * Recompute low/out-of-stock alerts for a variant.
     */
    public function refreshAlerts(ProductVariant $variant): void
    {
        InventoryAlert::where('product_variant_id', $variant->id)
            ->where('is_resolved', false)
            ->update(['is_resolved' => true, 'resolved_at' => now()]);

        $available = $variant->availableStock();
        $threshold = $variant->low_stock_threshold
            ?? config('storefront.inventory.low_stock_threshold', 5);

        if ($available === 0) {
            InventoryAlert::create([
                'product_variant_id' => $variant->id,
                'type'               => 'out_of_stock',
                'threshold'          => 0,
                'current_stock'      => $available,
            ]);
        } elseif ($available <= $threshold) {
            InventoryAlert::create([
                'product_variant_id' => $variant->id,
                'type'               => 'low_stock',
                'threshold'          => $threshold,
                'current_stock'      => $available,
            ]);
        }
    }

    /**
     * Release expired reservations. Scheduled command calls this every minute.
     */
    public function releaseExpired(): int
    {
        $count = 0;
        StockReservation::expired()->with('productVariant')->chunk(200, function ($reservations) use (&$count) {
            foreach ($reservations as $reservation) {
                try {
                    $this->release($reservation);
                    $count++;
                } catch (\Throwable $e) {
                    Log::error('Failed to release reservation', [
                        'reservation_id' => $reservation->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });
        return $count;
    }
}
