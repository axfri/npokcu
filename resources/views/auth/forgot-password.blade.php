@extends('layouts.app')

@section('title', 'Восстановление пароля')
@section('description', 'Запрос ссылки для восстановления пароля.')
@section('suppressGlobalErrors', true)

@section('content')
    <section class="auth-page">
        <div class="container">
            <div class="auth-card">
                <header class="auth-card__header">
                    <span class="eyebrow">Доступ к аккаунту</span>
                    <h1>Восстановить пароль</h1>
                    <p>Укажите email. Если аккаунт существует, мы отправим защищённую ссылку для смены пароля.</p>
                </header>

                <form class="auth-form" method="POST" action="{{ route('password.email') }}" novalidate>
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

                    <button class="button button-primary button-wide" type="submit">Отправить ссылку</button>
                </form>

                <p class="auth-card__footer"><a class="text-link" href="{{ route('login') }}">Вернуться ко входу</a></p>
            </div>
        </div>
    </section>
@endsection
