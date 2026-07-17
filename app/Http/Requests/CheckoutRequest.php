<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        abort_unless(
            $product instanceof Product
            && $product->is_active
            && $product->category()->where('is_active', true)->exists(),
            404
        );

        return true;
    }

    public function rules(): array
    {
        /** @var Product $product */
        $product = $this->route('product');

        return [
            'duration_option_id' => [
                'bail',
                'required',
                'integer',
                Rule::exists('product_duration_options', 'id')
                    ->where(fn ($query) => $query
                        ->where('product_id', $product->getKey())
                        ->where('is_active', true)),
            ],
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'payment_method' => ['required', 'string', Rule::in(['test'])],
            'terms' => ['accepted'],
            'checkout_token' => ['required', 'string', 'size:64', 'alpha_num'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! config('payments.test_mode')) {
                    $validator->errors()->add(
                        'payment_method',
                        'Тестовая оплата сейчас недоступна.'
                    );
                }

                $context = $this->session()->get(
                    'checkout_tokens.'.$this->checkoutTokenHash()
                );
                $product = $this->route('product');
                $issuedAt = (int) ($context['issued_at'] ?? 0);

                if (
                    ! $product instanceof Product
                    || (int) ($context['product_id'] ?? 0) !== $product->getKey()
                    || $issuedAt < now()->subHours(2)->getTimestamp()
                ) {
                    $validator->errors()->add(
                        'checkout_token',
                        'Форма оформления устарела. Обновите страницу и попробуйте снова.'
                    );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'duration_option_id.required' => 'Выберите срок действия.',
            'duration_option_id.exists' => 'Выбранный срок недоступен для этого товара.',
            'email.required' => 'Укажите email.',
            'email.email' => 'Укажите корректный email.',
            'payment_method.required' => 'Выберите способ оплаты.',
            'payment_method.in' => 'Выбранный способ оплаты недоступен.',
            'terms.accepted' => 'Подтвердите согласие с правилами.',
            'checkout_token.required' => 'Обновите страницу оформления и попробуйте снова.',
            'checkout_token.size' => 'Обновите страницу оформления и попробуйте снова.',
            'checkout_token.alpha_num' => 'Обновите страницу оформления и попробуйте снова.',
        ];
    }

    public function checkoutTokenHash(): string
    {
        return hash('sha256', $this->string('checkout_token')->toString());
    }

    protected function prepareForValidation(): void
    {
        $email = $this->user()?->email
            ?? Str::lower(trim((string) $this->input('email')));

        $this->merge(['email' => $email]);
    }
}
