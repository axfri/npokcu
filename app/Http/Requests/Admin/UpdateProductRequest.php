<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends AdminRequest
{
    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/',
                Rule::unique('products')->ignore($product instanceof Product ? $product->getKey() : null),
            ],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:50000'],
            'base_price' => ['required', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
            'default_duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.exists' => 'Выбранная категория не существует.',
            'slug.unique' => 'Товар с таким slug уже существует.',
            'slug.regex' => 'Slug может содержать только латинские буквы, цифры и дефисы.',
            'base_price.decimal' => 'Укажите цену с точностью не более двух знаков.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug(trim((string) ($this->input('slug') ?: $this->input('name')))),
            'base_price' => str_replace(',', '.', trim((string) $this->input('base_price'))),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
