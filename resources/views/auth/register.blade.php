@extends('layouts.app')

@section('title', 'Регистрация')
@section('description', 'Регистрация нового пользователя по email.')
@section('suppressGlobalErrors', true)

@section('content')
    <section class="auth-page">
        <div class="container">
            <div class="auth-card">
                <header class="auth-card__header">
                    <span class="eyebrow">Новый аккаунт</span>
                    <h1>Создать аккаунт</h1>
                    <p>Для регистрации достаточно email и надёжного пароля.</p>
                </header>

                <form class="auth-form" method="POST" action="{{ route('register.store') }}" novalidate>
                    @csrf

                    <div class="form-field">
                        <label class="form-label" for="email">Email</label>
                        <input
                            class="form-control"
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            autocomplete="email"
                            inputmode="email"
                            required
                            autofocus
                            @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                        >
                        @error('email')
                            <p class="form-error" id="email-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="password">Пароль</label>
                        <input
                            class="form-control"
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            required
                            @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                        >
                        <span class="form-help">Не менее 8 символов.</span>
                        @error('password')
                            <p class="form-error" id="password-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="password_confirmation">Подтверждение пароля</label>
                        <input
                            class="form-control"
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            required
                        >
                    </div>

                    <button class="button button-primary button-wide" type="submit">Зарегистрироваться</button>
                </form>

                <p class="auth-card__footer">Уже есть аккаунт? <a class="text-link" href="{{ route('login') }}">Войти</a></p>
            </div>
        </div>
    </section>
@endsection
