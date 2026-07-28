<?php

namespace App\Http\Requests\Admin;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', Rule::unique('categories')],
            'description' => ['nullable', 'string', 'max:10000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'Категория с таким slug уже существует.',
            'slug.regex' => 'Slug может содержать только латинские буквы, цифры и дефисы.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug(trim((string) ($this->input('slug') ?: $this->input('name')))),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
