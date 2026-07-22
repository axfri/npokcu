<?php

namespace App\Http\Requests\Auth;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->must_change_password === true;
    }

    public function rules(): array
    {
        return [
            'password' => [
                'required',
                'confirmed',
                Password::min(8),
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (is_string($value) && Hash::check($value, $this->user()->password)) {
                        $fail('Новый пароль должен отличаться от временного.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'Введите новый пароль.',
            'password.confirmed' => 'Подтверждение пароля не совпадает.',
            'password.min' => 'Пароль должен содержать не менее :min символов.',
        ];
    }
}
