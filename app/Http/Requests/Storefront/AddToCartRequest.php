<?php

namespace App\Http\Requests\Storefront;

use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_variant_id' => 'required|exists:product_variants,id',
            'quantity'           => 'sometimes|integer|min:1|max:20',
            'metadata'            => 'sometimes|array',
        ];
    }

    public function getVariant(): ProductVariant
    {
        return ProductVariant::with('product')->findOrFail($this->input('product_variant_id'));
    }
}
