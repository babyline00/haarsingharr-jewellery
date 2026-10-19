<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Customer
            'email'               => ['required', 'email', 'max:120'],
            'phone_country'       => ['required', 'string', 'max:5'],
            'phone'               => ['required', 'string', 'max:20'],

            // Shipping
            'shipping_first_name' => ['required', 'string', 'max:60'],
            'shipping_last_name'  => ['required', 'string', 'max:60'],
            'shipping_address_1'  => ['required', 'string', 'max:200'],
            'shipping_address_2'  => ['nullable', 'string', 'max:200'],
            'shipping_landmark'   => ['nullable', 'string', 'max:200'],
            'shipping_city'        => ['required', 'string', 'max:100'],
            'shipping_province'    => ['required', 'string', Rule::in([
                'punjab', 'sindh', 'kpk', 'balochistan', 'islamabad', 'gilgit', 'ajk',
            ])],
            'shipping_country'     => ['required', 'string', 'size:2'],
            'shipping_postal_code' => ['nullable', 'string', 'max:10'],

            // Billing (optional — if not provided, copies shipping)
            'billing_first_name'  => ['nullable', 'required_with:billing_address_1', 'string', 'max:60'],
            'billing_last_name'   => ['nullable', 'required_with:billing_address_1', 'string', 'max:60'],
            'billing_address_1'   => ['nullable', 'string', 'max:200'],
            'billing_address_2'   => ['nullable', 'string', 'max:200'],
            'billing_city'         => ['nullable', 'string', 'max:100'],
            'billing_province'     => ['nullable', 'string'],
            'billing_country'      => ['nullable', 'string', 'size:2'],
            'billing_postal_code'  => ['nullable', 'string', 'max:10'],

            // Order
            'shipping_method'     => ['required', 'string', Rule::in(['standard', 'express', 'cod'])],
            'payment_method'       => ['required', 'string', Rule::in(['cod', 'jazzcash', 'easypaisa'])],
            'notes'                => ['nullable', 'string', 'max:1000'],
            'idempotency_key'      => ['nullable', 'string', 'max:80'],
            'agree_terms'          => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'agree_terms.accepted' => 'Please accept the Terms & Refund Policy to continue.',
            'shipping_province.in'  => 'Please select a valid Pakistani province.',
        ];
    }

    public function toCheckoutData(): array
    {
        return [
            'email'              => $this->input('email'),
            'phone'              => $this->input('phone'),
            'phone_country'      => $this->input('phone_country'),
            'shipping_address'   => [
                'first_name'      => $this->input('shipping_first_name'),
                'last_name'       => $this->input('shipping_last_name'),
                'phone_country'   => $this->input('phone_country'),
                'phone'           => $this->input('phone'),
                'address_line_1'  => $this->input('shipping_address_1'),
                'address_line_2'  => $this->input('shipping_address_2'),
                'landmark'         => $this->input('shipping_landmark'),
                'city'             => $this->input('shipping_city'),
                'province'         => $this->input('shipping_province'),
                'country'          => $this->input('shipping_country'),
                'postal_code'       => $this->input('shipping_postal_code'),
                'type'             => 'shipping',
            ],
            'billing_address'   => $this->input('billing_address_1') ? [
                'first_name'      => $this->input('billing_first_name'),
                'last_name'       => $this->input('billing_last_name'),
                'phone_country'   => $this->input('phone_country'),
                'phone'           => $this->input('phone'),
                'address_line_1'  => $this->input('billing_address_1'),
                'address_line_2'  => $this->input('billing_address_2'),
                'city'             => $this->input('billing_city'),
                'province'         => $this->input('billing_province'),
                'country'          => $this->input('billing_country'),
                'postal_code'       => $this->input('billing_postal_code'),
                'type'             => 'billing',
            ] : null,
            'shipping_method'    => $this->input('shipping_method'),
            'payment_method'       => $this->input('payment_method'),
            'payment_provider'    => $this->input('payment_method'),
            'notes'               => $this->input('notes'),
            'idempotency_key'      => $this->input('idempotency_key') ?: \Illuminate\Support\Str::uuid()->toString(),
        ];
    }
}
