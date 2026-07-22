@extends('layouts.app')

@section('title', 'Смена временного пароля')
@section('description', 'Установка постоянного пароля для автоматически созданного аккаунта.')
@section('suppressGlobalErrors', true)

@section('content')
    <section class="auth-page">
        <div class="container">
            <div class="auth-card">
                <header class="auth-card__header">
                    <span class="eyebrow">Безопасность аккаунта</span>
                    <h1>Установите новый пароль</h1>
                    <p>Временный пароль использовался только для первого входа. Придумайте постоянный пароль длиной не менее 8 символов.</p>
                </header>

                <form class="auth-form" method="POST" action="{{ route('account.password.update') }}" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="form-field">
                        <label class="form-label" for="password">Новый пароль</label>
                        <input
                            class="form-control"
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            required
                            autofocus
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

                <form class="auth-card__footer" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="button button-secondary button-wide" type="submit">Выйти из аккаунта</button>
                </form>
            </div>
        </div>
    </section>
@endsection
