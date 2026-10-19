<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CustomerWelcomeNotification extends Notification
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Welcome to Haarsingharr.pk — Jewelry For Every You')
            ->greeting("Dear {$notifiable->first_name},")
            ->line('Welcome to the Haarsingharr Circle. You now have early access to our collections, private events, and a curated jewellery concierge.')
            ->action('Explore the Collection', url('/shop'))
            ->line('If you ever need a helping hand, our concierge team is a single WhatsApp message away.')
            ->salutation('Warmly, The Haarsingharr Family');
    }
}
