<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'sku' => ['sometimes', 'required', 'string', 'max:64', Rule::unique('products', 'sku')->ignore($product)],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'stock_quantity' => ['sometimes', 'required', 'integer', 'min:0'],
            'reorder_level' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'supplier_ids' => ['sometimes', 'array'],
            'supplier_ids.*' => ['integer', 'distinct', 'exists:suppliers,id'],
        ];
    }
}
