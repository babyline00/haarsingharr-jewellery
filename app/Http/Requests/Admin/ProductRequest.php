<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('products.create') || auth()->user()->can('products.update');
    }

    public function rules(): array
    {
        $rules = [
            'name'              => 'required|string|max:200',
            'sku'               => 'nullable|string|max:60|unique:products,sku',
            'barcode'           => 'nullable|string|max:60',
            'category_id'       => 'nullable|exists:categories,id',
            'subcategory_id'    => 'nullable|exists:categories,id',
            'short_description' => 'nullable|string|max:500',
            'description'        => 'nullable|string',
            'regular_price'     => 'required|numeric|min:0|max:99999999',
            'sale_price'        => 'nullable|numeric|min:0|max:99999999|lt:regular_price',
            'cost_price'        => 'nullable|numeric|min:0',
            'sale_starts_at'    => 'nullable|date',
            'sale_ends_at'      => 'nullable|date|after_or_equal:sale_starts_at',
            'status'            => 'required|in:draft,active,archived',
            'is_featured'       => 'sometimes|boolean',
            'is_best_seller'    => 'sometimes|boolean',
            'is_new_arrival'    => 'sometimes|boolean',
            'is_bridal'         => 'sometimes|boolean',
            'is_men'            => 'sometimes|boolean',
            'has_variants'      => 'sometimes|boolean',
            'weight_grams'      => 'nullable|numeric|min:0|max:100000',
            'metal'             => 'nullable|string|max:60',
            'gemstone'          => 'nullable|string|max:60',
            'carat'             => 'nullable|numeric|min:0|max:999',
            'dimensions'        => 'nullable|string|max:100',
            'material'          => 'nullable|string|max:100',
            'care_instructions'=> 'nullable|string|max:2000',
            'seo_title'          => 'nullable|string|max:200',
            'seo_description'   => 'nullable|string|max:500',
            'seo_image'         => 'nullable|string|max:300',
            'stock_quantity'    => 'nullable|integer|min:0|max:1000000',
            'low_stock_threshold' => 'nullable|integer|min:0|max:1000000',
            'allow_backorder'   => 'sometimes|boolean',
        ];

        // Cost price requires elevated permission
        if (! auth()->user()->can('products.adjust_cost')) {
            unset($rules['cost_price']);
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'regular_price.required' => 'Regular price is required.',
            'sale_price.lt'           => 'Sale price must be lower than the regular price.',
            'sale_ends_at.after_or_equal' => 'Sale end date cannot be before the start date.',
        ];
    }
}
