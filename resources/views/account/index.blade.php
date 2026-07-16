@extends('layouts.app')

@section('title', 'Личный кабинет')
@section('description', 'Основная информация личного кабинета.')

@section('content')
    <section class="account-page">
        <div class="container">
            <header class="account-header">
                <div>
                    <span class="eyebrow">Аккаунт</span>
                    <h1>Личный кабинет</h1>
                    <p>Здесь отображается основная информация вашего аккаунта.</p>
                </div>
                <form class="logout-form" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="button button-secondary" type="submit">Выйти</button>
                </form>
            </header>

            <div class="account-grid">
                <section class="account-card" aria-labelledby="account-details-title">
                    <div class="account-card__head">
                        <div>
                            <span class="eyebrow">Профиль</span>
                            <h2 id="account-details-title">Данные аккаунта</h2>
                        </div>
                        @if ($user->hasVerifiedEmail())
                            <span class="status-badge status-badge--success">Email подтверждён</span>
                        @else
                            <span class="status-badge status-badge--warning">Email не подтверждён</span>
                        @endif
                    </div>

                    <dl class="account-details">
                        <div>
                            <dt>Email</dt>
                            <dd><bdi class="account-email">{{ $user->email }}</bdi></dd>
                        </div>
                        <div>
                            <dt>Дата регистрации</dt>
                            <dd>
                                @if ($user->created_at)
                                    <time datetime="{{ $user->created_at->toDateString() }}">{{ $user->created_at->format('d.m.Y') }}</time>
                                @else
                                    Не указана
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt>Подтверждение email</dt>
                            <dd>{{ $user->hasVerifiedEmail() ? 'Подтверждён' : 'Ожидает подтверждения' }}</dd>
                        </div>
                    </dl>

                    @unless ($user->hasVerifiedEmail())
                        <div class="verification-actions">
                            <p>Подтвердите email сейчас или сделайте это позже. Каталог и кабинет остаются доступными.</p>
                            <form method="POST" action="{{ route('verification.send') }}">
                                @csrf
                                <button class="button button-secondary button-small" type="submit">Отправить письмо повторно</button>
                            </form>
                        </div>
                    @endunless
                </section>

                <section class="account-card account-card--notice" aria-labelledby="purchases-title">
                    <span class="account-notice-icon" aria-hidden="true">→</span>
                    <div>
                        <span class="eyebrow">Покупки</span>
                        <h2 id="purchases-title">История покупок</h2>
                        <p>Купленные товары появятся здесь после оформления заказа.</p>
                    </div>
                </section>
            </div>
        </div>
    </section>
@endsection
