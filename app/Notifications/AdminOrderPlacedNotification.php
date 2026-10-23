<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AdminOrderPlacedNotification extends Notification
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $customer = $this->customerName();
        $products = $this->productNames();

        return [
            'title'         => "New order from {$customer}",
            'message'       => "{$customer} bought {$products} — Rs. ".number_format($this->order->grand_total).".",
            'url'           => route('admin.orders.show', $this->order),
            'action_url'    => route('admin.orders.show', $this->order),
            'customer_name' => $customer,
            'products'      => $this->order->items->pluck('product_name')->filter()->values()->all(),
            'order_number'  => $this->order->number,
            'total'         => (float) $this->order->grand_total,
        ];
    }

    private function customerName(): string
    {
        $user = $this->order->user;

        $name = trim(($user?->first_name ?? '').' '.($user?->last_name ?? ''));

        return $name !== '' ? $name : ($user?->name ?: 'Guest customer');
    }

    private function productNames(): string
    {
        $names = $this->order->items->pluck('product_name')->filter()->values();

        if ($names->isEmpty()) {
            return 'an order';
        }

        $preview = $names->take(3)->implode(', ');

        return $names->count() > 3
            ? $preview.' +'.($names->count() - 3).' more'
            : $preview;
    }
}
