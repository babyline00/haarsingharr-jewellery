<?php

namespace App\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\PlaceOrderRequest;
use App\Models\Order;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\Payment\PaymentManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use App\Exceptions\InsufficientStockException;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CheckoutService $checkoutService,
        private readonly PaymentManager $paymentManager,
    ) {}

    public function show(): View|RedirectResponse
    {
        $cart = $this->cartService->current()->load(['items.productVariant.product.images']);
        if ($cart->isEmpty()) {
            return redirect()->route('cart.index')->with('error',
                'Your cart is empty. Add some pieces before checkout.');
        }
        $cart->recalculate();

        return view('storefront.checkout.show', [
            'cart'     => $cart,
            'gateways' => $this->paymentManager->enabledGateways(),
            'user'     => auth()->user(),
        ]);
    }

    public function store(PlaceOrderRequest $request): RedirectResponse
    {
        try {
            $order = $this->checkoutService->placeOrder($request->toCheckoutData());
        } catch (InsufficientStockException $e) {
            return redirect()->route('cart.index')->with('error', $e->getMessage());
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['checkout' => $e->getMessage()]);
        }

        $payment = $this->paymentManager->createPayment($order);
        $gateway = $this->paymentManager->gateway($order->payment_provider);

        $response = $gateway->initiate($payment, $order);

        if ($response['redirect'] ?? false) {
            // Render an automatic redirect form (Stripe-style hosted page).
            session()->flash('payment_redirect', $response);
            return redirect()->route('payments.redirect.form');
        }

        return redirect()->route('order.confirmation', $order->number);
    }

    public function confirmation(string $orderNumber): View
    {
        $order = Order::where('number', $orderNumber)->firstOrFail();

        // Customers can only see their own orders or guest orders they placed.
        if ($order->user_id !== auth()->id() && $order->guest_email !== auth()->user()?->email) {
            if (! request()->hasValidSignature()) {
                abort(403);
            }
        }

        return view('storefront.checkout.confirmation', [
            'order' => $order->load(['items', 'addresses', 'payments']),
        ]);
    }
}
