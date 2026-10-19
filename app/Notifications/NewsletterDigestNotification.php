<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NewsletterDigestNotification extends Notification
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Haarsingharr Weekly — New arrivals & private invitations')
            ->greeting('Hello from Haarsingharr,')
            ->line('Here is your weekly digest of new arrivals, curated collections, and private invitations.')
            ->action('View New Arrivals', url('/new-arrivals'))
            ->salutation('The Haarsingharr Family');
    }
}
