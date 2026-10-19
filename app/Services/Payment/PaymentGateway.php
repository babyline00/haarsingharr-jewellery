<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\Refund;
use Illuminate\Http\Request;

interface PaymentGateway
{
    /**
     * Display label for the checkout page.
     */
    public function label(): string;

    /**
     * Whether the gateway is enabled in the current environment.
     */
    public function enabled(): bool;

    /**
     * Initialise a new payment attempt on the provider side. Returns a Payment
     * record plus any data the storefront needs to redirect the customer.
     */
    public function initiate(Payment $payment, Order $order): array;

    /**
     * Handle the browser return URL (return/cancel). NEVER marks the order as
     * paid — only triggers a verification call.
     */
    public function handleReturn(Request $request, Payment $payment, Order $order): array;

    /**
     * Handle the provider webhook (server-to-server callback). MUST verify
     * the signature. Returns the verified PaymentEvent, or null on
     * already-processed events (idempotency).
     */
    public function handleWebhook(Request $request, Payment $payment, Order $order): ?PaymentEvent;

    /**
     * Verify a payment provider-side. Returns the verified PaymentEvent or
     * throws on signature/state mismatch.
     */
    public function verify(Payment $payment, Order $order): PaymentEvent;

    /**
     * Refund an amount for a previously captured payment. Returns the Refund
     * record. Provider integration may be skipped for COD (manual).
     */
    public function refund(Payment $payment, Refund $refund): Refund;

    /**
     * Currency the provider expects in requests.
     */
    public function currency(): string;

    /**
     * Return true if this gateway requires a redirect/hosted page flow.
     * COD does not.
     */
    public function isRedirectFlow(): bool;
}
