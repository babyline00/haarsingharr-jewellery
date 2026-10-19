<?php

namespace App\Console\Commands;

use App\Models\NewsletterSubscription;
use App\Notifications\NewsletterDigestNotification;
use Illuminate\Console\Command;

class SendNewsletterDigest extends Command
{
    protected $signature = 'haarsingharr:newsletter-digest';

    protected $description = 'Send daily newsletter digest to subscribed users.';

    public function handle(): int
    {
        NewsletterSubscription::active()->chunk(200, function ($subscribers) {
            foreach ($subscribers as $sub) {
                $sub->notify(new NewsletterDigestNotification());
            }
        });
        $this->info('Newsletter digest sent.');
        return self::SUCCESS;
    }
}
