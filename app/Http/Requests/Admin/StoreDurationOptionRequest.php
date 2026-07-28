<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Validation\Rule;

class StoreDurationOptionRequest extends AdminRequest
{
    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'title' => ['required', 'string', 'max:255'],
            'duration_days' => [
                'required',
                'integer',
                'min:1',
                'max:3650',
                Rule::unique('product_duration_options')->where(
                    'product_id',
                    $product instanceof Product ? $product->getKey() : null,
                ),
            ],
            'price' => ['required', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'duration_days.unique' => 'Вариант с таким количеством дней уже существует.',
            'price.decimal' => 'Укажите цену с точностью не более двух знаков.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'price' => str_replace(',', '.', trim((string) $this->input('price'))),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
