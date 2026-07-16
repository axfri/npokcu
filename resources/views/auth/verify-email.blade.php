@extends('layouts.app')

@section('title', 'Подтверждение email')
@section('description', 'Подтверждение email пользователя.')

@section('content')
    <section class="auth-page">
        <div class="container">
            <div class="auth-card">
                <header class="auth-card__header">
                    <span class="eyebrow">Подтверждение email</span>
                    <h1>Проверьте почту</h1>
                    <p>Мы отправили ссылку на <bdi class="inline-email">{{ auth()->user()->email }}</bdi>. Подтверждение пока не ограничивает доступ к сайту.</p>
                </header>

                <div class="auth-form">
                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button class="button button-primary button-wide" type="submit">Отправить письмо повторно</button>
                    </form>
                    <a class="button button-secondary button-wide" href="{{ route('account') }}">Перейти в кабинет</a>
                </div>
            </div>
        </div>
    </section>
@endsection
