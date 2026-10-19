<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\Refund;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Easypaisa hosted-checkout adapter.
 *
 * Reference: https://easypaisa.com.pk/merchant (login required for API
 * credentials). You must have an approved merchant account before enabling
 * this gateway in production. The hash & store_id come from .env.
 */
class EasypaisaGateway implements PaymentGateway
{
    public function __construct(private readonly AuditService $audit) {}

    public function label(): string
    {
        return config('payments.gateways.easypaisa.label', 'Easypaisa');
    }

    public function enabled(): bool
    {
        return (bool) config('payments.gateways.easypaisa.enabled', false);
    }

    public function isRedirectFlow(): bool
    {
        return true;
    }

    public function currency(): string
    {
        return 'PKR';
    }

    public function initiate(Payment $payment, Order $order): array
    {
        $storeId = config('payments.gateways.easypaisa.store_id');
        $hash    = config('payments.gateways.easypaisa.hash');

        if (! $storeId || ! $hash) {
            throw new \RuntimeException('Easypaisa credentials are not configured.');
        }

        $orderRef = 'EP-'.$order->number.'-'.time();
        $payment->update([
            'provider'                => 'easypaisa',
            'method'                  => 'wallet',
            'status'                  => 'initiated',
            'initiated_at'            => now(),
            'provider_transaction_ref'=> $orderRef,
            'provider_merchant_id'    => $storeId,
        ]);

        $signature = base64_encode($storeId.$orderRef.(int) round($payment->amount * 100).$order->number.$hash);

        $postUrl = config('payments.gateways.easypaisa.post_url');
        $fields = [
            'amount'           => (int) round($payment->amount),
            'store_id'         => $storeId,
            'order_id'         => $orderRef,
            'desc'             => 'Haarsingharr Order '.$order->number,
            'autoRedirect'     => '1',
            'email'            => $order->guest_email ?? $order->user?->email,
            'phone'            => $order->guest_phone ?? $order->user?->phone,
            'return_url'       => url(config('payments.gateways.easypaisa.return_url')),
            'signature'       => $signature,
        ];

        $payment->events()->create([
            'type'         => 'initiated',
            'status'       => 'initiated',
            'amount'       => $payment->amount,
            'currency'     => $payment->currency,
            'payload'      => $fields,
            'is_verified'  => true,
            'note'         => 'Easypaisa checkout initiated',
        ]);

        return [
            'redirect'   => true,
            'gateway_url'=> $postUrl,
            'method'    => 'POST',
            'fields'    => $fields,
            'payment_id'=> $payment->uuid,
        ];
    }

    public function handleReturn(Request $request, Payment $payment, Order $order): array
    {
        try {
            $this->verify($payment, $order);
        } catch (\Throwable $e) {
            // Return page just shows a "verifying" state.
        }
        return ['next_url' => route('account.orders.show', $order->number)];
    }

    public function handleWebhook(Request $request, Payment $payment, Order $order): ?PaymentEvent
    {
        $hash   = config('payments.gateways.easypaisa.hash');
        $storeId = config('payments.gateways.easypaisa.store_id');

        $receivedSignature = $request->input('signature');
        $expectedSignature = base64_encode(
            $storeId.
            $request->input('order_id', '').
            $request->input('amount', '').
            $request->input('status', '').
            $hash
        );

        if (! hash_equals($expectedSignature, $receivedSignature ?? '')) {
            $this->audit->recordSecurity('easypaisa.webhook.signature_failed', 'failure',
                null, ['order_id' => $order->id], 'Easypaisa webhook signature mismatch');
            throw new \RuntimeException('Easypaisa webhook signature invalid.');
        }

        $eventId = $request->input('order_id').'-'.$request->input('status');
        $existing = PaymentEvent::where('provider_event_id', $eventId)->first();
        if ($existing) {
            return $existing;
        }

        $verified = $request->input('status') === 'PAID';

        $event = $payment->events()->create([
            'type'               => $verified ? 'captured' : 'verification',
            'provider_event_id'  => $eventId,
            'status'             => $request->input('status'),
            'amount'             => $request->input('amount'),
            'currency'           => $payment->currency,
            'payload'            => $request->all(),
            'is_verified'         => $verified,
            'note'               => $verified ? 'Captured via webhook' : 'Webhook received',
        ]);

        if ($verified && (float) $request->input('amount') === (float) $payment->amount) {
            $payment->markCaptured();
        }

        return $event;
    }

    public function verify(Payment $payment, Order $order): PaymentEvent
    {
        $storeId = config('payments.gateways.easypaisa.store_id');
        $hash    = config('payments.gateways.easypaisa.hash');
        $orderRef = $payment->provider_transaction_ref;

        $signature = base64_encode($storeId.$orderRef.$hash);
        $verifyUrl = config('payments.gateways.easypaisa.confirm_url');

        $response = Http::asForm()->post($verifyUrl, [
            'store_id'  => $storeId,
            'order_id'  => $orderRef,
            'signature' => $signature,
        ]);

        $body = $response->json() ?? [];
        $verified = ($body['status'] ?? null) === 'PAID'
            && (float) ($body['amount'] ?? 0) === (float) $payment->amount;

        $event = $payment->events()->create([
            'type'               => $verified ? 'captured' : 'verification',
            'provider_event_id'  => $orderRef.'-'.$body['status'] ?? Str::uuid()->toString(),
            'status'             => $body['status'] ?? 'unknown',
            'amount'             => $payment->amount,
            'currency'           => $payment->currency,
            'payload'            => $body,
            'is_verified'        => $verified,
            'note'               => $verified ? 'Verified by server' : 'Verification failed',
        ]);

        if ($verified) {
            $payment->markCaptured();
            $this->audit->record('payment.captured', $payment);
        }

        return $event;
    }

    public function refund(Payment $payment, Refund $refund): Refund
    {
        // Easypaisa refunds are submitted manually through their portal today.
        $refund->update(['status' => 'processing', 'provider_ref' => 'pending_easypaisa_refund']);
        return $refund;
    }
}
