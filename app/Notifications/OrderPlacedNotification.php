<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class OrderPlacedNotification extends Notification
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $products = $this->productNames();

        return (new MailMessage())
            ->subject("Haarsingharr Order {$this->order->number} — Confirmation")
            ->greeting("Dear {$notifiable->first_name},")
            ->line("Thank you for your order. We've received your purchase and will process it shortly.")
            ->line("**Items:** {$products}")
            ->line("**Order Number:** {$this->order->number}")
            ->line("**Total:** Rs. " . number_format($this->order->grand_total))
            ->line("**Payment Method:** " . ucfirst($this->order->payment_method))
            ->line("**Shipping Address:** " . ($this->order->shippingAddress()?->fullAddress() ?? ''))
            ->action('View Order', route('account.orders.show', $this->order->number))
            ->line('If you have any questions, reply to this email or WhatsApp us.')
            ->line('With gratitude,')
            ->salutation('The Haarsingharr Family');
    }

    public function toArray($notifiable): array
    {
        $products = $this->productNames();
        $customer = $this->customerName($notifiable);

        return [
            'title'         => "Order {$this->order->number} placed",
            'message'       => "Hi {$customer}, your order for {$products} totalling Rs. ".number_format($this->order->grand_total)." has been received.",
            'action_url'    => route('account.orders.show', $this->order->number),
            'customer_name' => $customer,
            'products'      => $this->order->items->pluck('product_name')->filter()->values()->all(),
        ];
    }

    private function customerName($notifiable): string
    {
        $name = trim(($notifiable->first_name ?? '').' '.($notifiable->last_name ?? ''));

        return $name !== '' ? $name : ($notifiable->name ?: 'Customer');
    }

    private function productNames(): string
    {
        $names = $this->order->items->pluck('product_name')->filter()->values();

        if ($names->isEmpty()) {
            return 'your jewellery';
        }

        $preview = $names->take(3)->implode(', ');

        return $names->count() > 3
            ? $preview.' +'.($names->count() - 3).' more'
            : $preview;
    }
}
