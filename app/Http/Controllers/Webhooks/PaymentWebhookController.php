<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\CheckoutService;
use App\Services\Payment\PaymentManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __construct(
        private readonly PaymentManager $paymentManager,
        private readonly CheckoutService $checkoutService,
    ) {}

    public function handle(Request $request, string $provider): Response
    {
        if (! $this->paymentManager->hasGateway($provider)) {
            return response('Unknown gateway', 404);
        }

        $gateway = $this->paymentManager->gateway($provider);

        // Find the order/payment based on provider-specific fields.
        $paymentId = $request->input('payment_id')
            ?? $request->input('pp_TxnRefNo')
            ?? $request->input('order_id');

        $payment = Payment::where('uuid', $paymentId)
            ->orWhere('provider_transaction_ref', $paymentId)
            ->first();

        if (! $payment) {
            Log::warning('Webhook: payment not found', ['provider' => $provider, 'id' => $paymentId]);
            return response('Payment not found', 404);
        }

        $order = $payment->order;

        try {
            $event = $gateway->handleWebhook($request, $payment, $order);
            if ($event && $event->is_verified && $order->canBePaid()) {
                $this->checkoutService->confirmPayment($order, $payment);
            }
        } catch (\RuntimeException $e) {
            Log::error('Webhook signature verification failed', [
                'provider' => $provider, 'payment_id' => $payment->id, 'error' => $e->getMessage(),
            ]);
            return response('Signature verification failed', 400);
        }

        return response('OK', 200);
    }
}
