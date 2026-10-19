<?php

namespace App\Console\Commands;

use App\Models\StockReservation;
use App\Services\InventoryService;
use Illuminate\Console\Command;

class ReleaseExpiredReservations extends Command
{
    protected $signature = 'haarsingharr:release-reservations';

    protected $description = 'Release expired stock reservations. Should be run via cron every minute.';

    public function handle(InventoryService $inventoryService): int
    {
        $count = $inventoryService->releaseExpired();
        $this->info("Released {$count} expired reservation(s).");
        return self::SUCCESS;
    }
}
