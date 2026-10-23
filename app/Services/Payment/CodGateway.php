<?php

namespace App\Services\Payment;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\Refund;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Cash-on-Delivery gateway. The "payment" is collected by the courier on
 * delivery, so the order remains unpaid until the order is marked delivered.
 */
class CodGateway implements PaymentGateway
{
    public function __construct(private readonly AuditService $audit) {}

    public function label(): string
    {
        return config('payments.gateways.cod.label', 'Cash on Delivery');
    }

    public function enabled(): bool
    {
        return (bool) \App\Models\StoreSetting::get('payment.cod.enabled', config('payments.gateways.cod.enabled', true));
    }

    public function isRedirectFlow(): bool
    {
        return false;
    }

    public function currency(): string
    {
        return config('payments.default_currency', 'PKR');
    }

    public function initiate(Payment $payment, Order $order): array
    {
        $maxValue = (float) config('payments.gateways.cod.max_value', 200000);
        if ((float) $order->grand_total > $maxValue) {
            throw new \DomainException(
                "Cash on Delivery is not available for orders over Rs. {$maxValue}."
            );
        }

        $payment->update([
            'provider'             => 'cod',
            'method'               => 'cash',
            'status'               => 'pending',
            'initiated_at'         => now(),
            'provider_status'      => 'awaiting_collection',
        ]);

        $payment->events()->create([
            'type'     => 'initiated',
            'status'   => 'pending',
            'amount'   => $payment->amount,
            'currency' => $payment->currency,
            'note'     => 'COD order placed — awaiting courier collection on delivery.',
        ]);

        // COD orders skip payment verification — payment is collected at door.
        $order->update([
            'payment_status' => PaymentStatus::Pending,
        ]);

        return [
            'redirect'   => false,
            'payment_id' => $payment->uuid,
            'order_id'   => $order->uuid,
            'next_url'   => route('order.confirmation', $order->number),
        ];
    }

    public function handleReturn(Request $request, Payment $payment, Order $order): array
    {
        // No browser return for COD — confirmation is handled synchronously.
        return ['next_url' => route('order.confirmation', $order->number)];
    }

    public function handleWebhook(Request $request, Payment $payment, Order $order): ?PaymentEvent
    {
        // COD does not receive server-to-server webhooks. Manual reconciliation
        // is performed by the Order Manager when the courier returns funds.
        return null;
    }

    public function verify(Payment $payment, Order $order): PaymentEvent
    {
        // No remote verification possible — the courier settlement is the source
        // of truth. Staff manually mark the payment as captured when cash is
        // received. We emit a synthetic verified event for audit trail.
        return $payment->events()->create([
            'type'     => 'verification',
            'status'   => 'pending',
            'amount'   => $payment->amount,
            'currency' => $payment->currency,
            'note'     => 'COD settlement verified manually by staff.',
            'is_verified' => false,
        ]);
    }

    /**
     * Staff mark a COD payment as collected on delivery.
     */
    public function markCollected(Payment $payment, Order $order): void
    {
        $payment->update([
            'status'        => 'captured',
            'captured_at'   => now(),
            'amount_captured' => $payment->amount,
        ]);

        $order->update([
            'paid_amount'    => $order->grand_total,
            'payment_status' => PaymentStatus::Paid,
        ]);

        $this->audit->record('payment.captured', $payment);
    }

    public function refund(Payment $payment, Refund $refund): Refund
    {
        // COD refunds are processed manually via bank transfer / EasyPaisa /
        // JazzCash wallet by the Order Manager. We just mark it processed.
        $refund->update([
            'status'      => 'completed',
            'processed_at'=> now(),
            'provider_ref'=> 'manual_cod_refund',
        ]);

        $payment->increment('amount_refunded', $refund->amount);
        $order = $payment->order;
        $order->increment('refunded_amount', $refund->amount);

        if ((float) $order->refunded_amount >= (float) $order->grand_total) {
            $order->update(['payment_status' => PaymentStatus::Refunded]);
        } else {
            $order->update(['payment_status' => PaymentStatus::PartiallyRefunded]);
        }

        return $refund;
    }
}
