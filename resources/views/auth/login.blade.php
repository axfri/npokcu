@extends('layouts.app')

@section('title', 'Вход')
@section('description', 'Вход в личный кабинет по email и паролю.')
@section('suppressGlobalErrors', true)

@section('content')
    <section class="auth-page">
        <div class="container">
            <div class="auth-card">
                <header class="auth-card__header">
                    <span class="eyebrow">Личный кабинет</span>
                    <h1>Войти в аккаунт</h1>
                    <p>Используйте email и пароль, указанные при регистрации.</p>
                </header>

                <form class="auth-form" method="POST" action="{{ route('login.store') }}" novalidate>
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
                        <div class="form-label-row">
                            <label class="form-label" for="password">Пароль</label>
                            <a class="text-link" href="{{ route('password.request') }}">Забыли пароль?</a>
                        </div>
                        <input
                            class="form-control"
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            required
                            @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                        >
                        @error('password')
                            <p class="form-error" id="password-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="checkbox-field">
                        <input name="remember" type="checkbox" value="1" @checked(old('remember'))>
                        <span>Запомнить меня</span>
                    </label>

                    <button class="button button-primary button-wide" type="submit">Войти</button>
                </form>

                <p class="auth-card__footer">Нет аккаунта? <a class="text-link" href="{{ route('register') }}">Зарегистрироваться</a></p>
            </div>
        </div>
    </section>
@endsection
