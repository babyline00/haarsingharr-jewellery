<?php

namespace App\Notifications;

use App\Models\GiftClaim;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GiftWonNotification extends Notification
{
    use Queueable;

    public function __construct(public GiftClaim $claim) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $code = $this->claim->coupon?->code;

        return [
            'title'         => "You won {$this->claim->prize}!",
            'message'       => $code
                ? "Your gift coupon {$code} is ready. Use it at checkout before it expires."
                : "Your prize is ready. Open your notifications for details.",
            'url'           => route('account.notifications.index'),
            'action_url'    => route('account.notifications.index'),
            'prize'         => $this->claim->prize,
            'coupon_code'   => $code,
        ];
    }
}
