<?php

namespace App\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\CheckoutService;
use App\Services\Payment\PaymentManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentManager $paymentManager,
        private readonly CheckoutService $checkoutService,
    ) {}

    /**
     * (Re)initiate an online payment for an existing order. Used by the
     * "Pay now / retry payment" action on the order confirmation page.
     */
    public function initiate(Request $request, Payment $payment): RedirectResponse
    {
        $order = $payment->order;
        abort_if($order === null, 404);

        $user = $request->user();
        abort_unless(
            $order->user_id === $user?->id
            || ($order->guest_email !== null && $order->guest_email === $user?->email)
            || ($user?->can('payments.configure') ?? false),
            403,
        );

        $gateway = $this->paymentManager->gateway($order->payment_provider);
        $response = $gateway->initiate($payment, $order);

        if ($response['redirect'] ?? false) {
            $request->session()->flash('payment_redirect', $response);

            return redirect()->route('payments.redirect.form');
        }

        return redirect()->route('order.confirmation', $order->number)
            ->with('success', 'Payment initiated.');
    }

    /**
     * Render a hosted-page redirect form (used by JazzCash / Easypaisa).
     */
    public function redirectForm(): View
    {
        $data = session('payment_redirect');
        if (! $data) {
            return view('storefront.checkout.payment-error', [
                'message' => 'Payment session expired. Please contact support.',
            ]);
        }
        return view('storefront.checkout.gateway-redirect', $data);
    }

    public function return(Request $request, string $provider): View
    {
        $payment = Payment::where('uuid', $request->input('payment_id'))
            ->orWhere('provider_transaction_ref', $request->input('order_id'))
            ->firstOrFail();
        $order = $payment->order;
        $gateway = $this->paymentManager->gateway($provider);
        $response = $gateway->handleReturn($request, $payment, $order);

        return view('storefront.checkout.payment-return', [
            'payment' => $payment,
            'order'   => $order,
            'status'  => $response['status'] ?? 'pending',
            'next_url' => $response['next_url'] ?? null,
        ]);
    }
}
