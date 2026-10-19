<?php

namespace App\Services\Payment;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\Refund;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * JazzCash merchant gateway adapter.
 *
 * Implementation notes:
 * - Sandbox and production credentials are stored only in .env.
 * - Signed using the documented Integrity Salt (pp_AuthKey / Integrity Salt).
 * - The provider's transaction management API is used for both initiation
 *   and verification — never the browser return URL alone.
 * - This adapter intentionally avoids storing card numbers, CVVs, or PINs.
 *
 * Documentation reference for production integration is available from
 * the JazzCash merchant support portal; you must have an approved merchant
 * account before enabling this gateway in production.
 */
class JazzCashGateway implements PaymentGateway
{
    public function __construct(private readonly AuditService $audit) {}

    public function label(): string
    {
        return config('payments.gateways.jazzcash.label', 'JazzCash');
    }

    public function enabled(): bool
    {
        return (bool) config('payments.gateways.jazzcash.enabled', false);
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
        $merchantId   = config('payments.gateways.jazzcash.merchant_id');
        $password     = config('payments.gateways.jazzcash.password');
        $integritySalt= config('payments.gateways.jazzcash.integrity_salt');

        if (! $merchantId || ! $password || ! $integritySalt) {
            throw new \RuntimeException('JazzCash credentials are not configured.');
        }

        $transactionRef = 'JC-'.$order->number.'-'.time();
        $payment->update([
            'provider'               => 'jazzcash',
            'method'                 => 'wallet',
            'status'                 => 'initiated',
            'initiated_at'           => now(),
            'provider_transaction_ref'=> $transactionRef,
            'provider_merchant_id'    => $merchantId,
        ]);

        $postData = [
            'pp_Version'        => '1.1',
            'pp_TxnType'         => 'MWALLET',
            'pp_Language'        => 'EN',
            'pp_MerchantID'      => $merchantId,
            'pp_SubMerchantID'   => '',
            'pp_Password'        => $password,
            'pp_TxnRefNo'        => $transactionRef,
            'pp_Amount'          => (int) round($payment->amount * 100),
            'pp_TxnCurrency'     => $payment->currency,
            'pp_TxnDateTime'     => now()->format('YmdHis'),
            'pp_BillReference'   => $order->number,
            'pp_Description'     => 'Haarsingharr Order '.$order->number,
            'pp_TxnExpiryDateTime'=> now()->addMinutes(config('payments.redirect_return_seconds', 600))->format('YmdHis'),
            'pp_ReturnURL'       => url(config('payments.gateways.jazzcash.return_url')),
            'ppmpf_1'            => 'order_id='.$order->id,
            'ppmpf_2'            => 'payment_id='.$payment->uuid,
        ];

        $postData['pp_SecureHash'] = $this->computeHash($postData, $integritySalt);

        $payment->update([
            'provider_payload' => $postData,
            'provider_status'  => 'initiated',
        ]);

        $payment->events()->create([
            'type'        => 'initiated',
            'status'      => 'initiated',
            'amount'      => $payment->amount,
            'currency'    => $payment->currency,
            'payload'     => $postData,
            'is_verified' => true,
            'note'        => 'Initiated JazzCash checkout for order '.$order->number,
        ]);

        return [
            'redirect'    => true,
            'gateway_url'=> config('payments.gateways.jazzcash.api_url'),
            'method'     => 'POST',
            'fields'     => $postData,
            'payment_id' => $payment->uuid,
        ];
    }

    public function handleReturn(Request $request, Payment $payment, Order $order): array
    {
        // The return URL is informational only — never mark paid here.
        // Kick off asynchronous verification; the customer sees a "verifying" page.
        try {
            $this->verify($payment, $order);
        } catch (\Throwable $e) {
            Log::warning('JazzCash return verify failed', ['payment' => $payment->id, 'error' => $e->getMessage()]);
        }

        return [
            'next_url'  => route('account.orders.show', $order->number),
            'status'    => $payment->fresh()->status,
        ];
    }

    public function handleWebhook(Request $request, Payment $payment, Order $order): ?PaymentEvent
    {
        $integritySalt = config('payments.gateways.jazzcash.integrity_salt');
        $payload = $request->all();

        // Verify signature by recomputing the Secure Hash.
        $receivedHash = Arr::pull($payload, 'pp_SecureHash');
        $computedHash = $this->computeHash($payload, $integritySalt);
        if (! hash_equals($computedHash, $receivedHash ?? '')) {
            $this->audit->recordSecurity('jazzcash.webhook.signature_failed', 'failure',
                null, ['order_id' => $order->id, 'payment_id' => $payment->id],
                'JazzCash webhook signature mismatch');
            throw new \RuntimeException('JazzCash webhook signature invalid.');
        }

        // Idempotency check — provider event reference (pp_TxnRefNo + response code).
        $eventId = $payload['pp_TxnRefNo'] ?? null;
        if (! $eventId) {
            return null;
        }
        $existing = PaymentEvent::where('provider_event_id', $eventId)->first();
        if ($existing) {
            return $existing; // already processed — idempotent
        }

        return $this->recordEvent($payment, $payload, true);
    }

    public function verify(Payment $payment, Order $order): PaymentEvent
    {
        $merchantId   = config('payments.gateways.jazzcash.merchant_id');
        $password     = config('payments.gateways.jazzcash.password');
        $integritySalt= config('payments.gateways.jazzcash.integrity_salt');
        $txnRef       = $payment->provider_transaction_ref;

        $postData = [
            'pp_Version'   => '1.1',
            'pp_Language'  => 'EN',
            'pp_MerchantID'=> $merchantId,
            'pp_Password'  => $password,
            'pp_TxnRefNo'  => $txnRef,
        ];
        $postData['pp_SecureHash'] = $this->computeHash($postData, $integritySalt);

        $response = Http::asForm()->post(config('payments.gateways.jazzcash.verify_url'), $postData);
        $body = $response->json() ?? [];
        $verified = ($body['pp_ResponseCode'] ?? '') === '000' && $this->amountMatches($payment, $body);

        $event = $this->recordEvent($payment, $body, $verified);

        if ($verified) {
            $payment->markCaptured();
            $this->audit->record('payment.captured', $payment);
        } elseif (($body['pp_ResponseCode'] ?? '') !== '124') {
            // 124 = pending; anything else is a failure
            $payment->markFailed('JazzCash verification failed with code '.$body['pp_ResponseCode']);
        }

        return $event;
    }

    public function refund(Payment $payment, Refund $refund): Refund
    {
        // JazzCash refunds are submitted through their merchant portal manually
        // until the API is opened. We record the refund as "pending" and mark
        // "completed" when the staff has confirmed settlement.
        $refund->update(['status' => 'processing', 'provider_ref' => 'pending_jazzcash_refund']);
        return $refund;
    }

    // --------------------------------------------------------------------------
    // Helpers
    // --------------------------------------------------------------------------

    private function computeHash(array $data, string $integritySalt): string
    {
        ksort($data);
        $string = '';
        foreach ($data as $key => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            $string .= '&'.$value;
        }
        $string .= '&'.$integritySalt;
        return hash_hmac('sha256', ltrim($string, '&'), $integritySalt);
    }

    private function amountMatches(Payment $payment, array $body): bool
    {
        if (! config('payments.verification.verify_amount')) {
            return true;
        }
        $received = (int) ($body['pp_Amount'] ?? 0);
        $expected = (int) round($payment->amount * 100);
        return $received === $expected;
    }

    private function recordEvent(Payment $payment, array $body, bool $verified): PaymentEvent
    {
        return $payment->events()->create([
            'type'               => $verified ? 'captured' : 'verification',
            'provider_event_id'  => $body['pp_TxnRefNo'] ?? Str::uuid()->toString(),
            'status'             => $body['pp_ResponseCode'] ?? 'unknown',
            'amount'             => $payment->amount,
            'currency'           => $payment->currency,
            'payload'            => $body,
            'is_verified'        => $verified,
            'note'               => $verified ? 'Verified by server' : 'Verification failed',
        ]);
    }
}
