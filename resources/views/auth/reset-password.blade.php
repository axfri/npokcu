@extends('layouts.app')

@section('title', 'Новый пароль')
@section('description', 'Установка нового пароля для аккаунта.')
@section('suppressGlobalErrors', true)

@section('content')
    <section class="auth-page">
        <div class="container">
            <div class="auth-card">
                <header class="auth-card__header">
                    <span class="eyebrow">Безопасность</span>
                    <h1>Задайте новый пароль</h1>
                    <p>После сохранения используйте новый пароль для входа.</p>
                </header>

                <form class="auth-form" method="POST" action="{{ route('password.update') }}" novalidate>
                    @csrf
                    <input name="token" type="hidden" value="{{ $token }}">

                    <div class="form-field">
                        <label class="form-label" for="email">Email</label>
                        <input
                            class="form-control"
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email', $email) }}"
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
                        <label class="form-label" for="password">Новый пароль</label>
                        <input
                            class="form-control"
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            required
                            @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                        >
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

                    <button class="button button-primary button-wide" type="submit">Сохранить новый пароль</button>
                </form>
            </div>
        </div>
    </section>
@endsection
