<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\AuditService;
use Illuminate\Http\Request;
use RuntimeException;

class PaymentManager
{
    /**
     * @var array<string, PaymentGateway> keyed by provider code.
     */
    private array $gateways;

    public function __construct(
        CodGateway $cod,
        JazzCashGateway $jazzcash,
        EasypaisaGateway $easypaisa,
    ) {
        $this->gateways = [
            'cod'       => $cod,
            'jazzcash'  => $jazzcash,
            'easypaisa' => $easypaisa,
        ];
    }

    public function gateways(): array
    {
        return $this->gateways;
    }

    public function enabledGateways(): array
    {
        return array_filter($this->gateways, fn (PaymentGateway $g) => $g->enabled());
    }

    public function gateway(string $code): PaymentGateway
    {
        if (! isset($this->gateways[$code])) {
            throw new RuntimeException("Payment gateway '{$code}' is not registered.");
        }
        if (! $this->gateways[$code]->enabled()) {
            throw new RuntimeException("Payment gateway '{$code}' is not enabled.");
        }
        return $this->gateways[$code];
    }

    public function hasGateway(string $code): bool
    {
        return isset($this->gateways[$code]) && $this->gateways[$code]->enabled();
    }

    /**
     * Create a pending Payment record for an order before initiating a
     * gateway flow. The Order model has payment_method/payment_provider
     * columns that determine which gateway is used.
     */
    public function createPayment(Order $order): Payment
    {
        $provider = $order->payment_provider;
        if (! $provider) {
            $provider = match ($order->payment_method) {
                'cod'   => 'cod',
                default => throw new RuntimeException("Unknown payment method for order {$order->number}."),
            };
        }

        $gateway = $this->gateway($provider);

        return Payment::create([
            'order_id'  => $order->id,
            'provider'  => $provider,
            'method'    => $gateway->isRedirectFlow() ? 'wallet' : 'cash',
            'status'    => 'pending',
            'amount'    => $order->grand_total,
            'currency'  => $gateway->currency(),
            'initiated_at' => now(),
        ]);
    }
}
