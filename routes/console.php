<?php

use App\Console\Commands\InstallSuperAdmin;
use App\Console\Commands\ReleaseExpiredReservations;
use App\Console\Commands\SendNewsletterDigest;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Inspiring quote for the artisan tinker welcome screen.
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote.');

// Scheduled tasks. On cPanel hosts without terminal, configure these via
// Cron Jobs → "php /home/USER/jewellery-store/artisan schedule:run >> /dev/null 2>&1"
Schedule::command(ReleaseExpiredReservations::class)
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('haarsingharr:cleanup-reservations --all')
    ->hourly();

Schedule::command('haarsingharr:newsletter-digest')
    ->dailyAt('09:00');

Schedule::command('telescope:prune')
    ->daily()
    ->when(fn () => class_exists(\Laravel\Telescope\Telescope::class));

Schedule::command('queue:work --stop-when-empty')
    ->everyFiveMinutes()
    ->withoutOverlapping();
