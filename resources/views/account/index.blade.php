@extends('layouts.app')

@section('title', 'Личный кабинет')
@section('description', 'Профиль, активные покупки и история заказов.')

@section('content')
    <section class="account-page">
        <div class="container">
            <header class="account-header">
                <div>
                    <span class="eyebrow">Аккаунт</span>
                    <h1>Личный кабинет</h1>
                    <p>Управляйте профилем и скачивайте доступные покупки.</p>
                </div>
                <form class="logout-form" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="button button-secondary" type="submit">Выйти</button>
                </form>
            </header>

            <section class="account-card account-profile" aria-labelledby="account-details-title">
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

            <section class="purchases-section" aria-labelledby="purchases-title">
                <div class="purchases-heading">
                    <div>
                        <span class="eyebrow">Покупки</span>
                        <h2 id="purchases-title">Мои покупки</h2>
                    </div>
                    <a class="button button-secondary button-small" href="{{ route('catalog') }}">Перейти в каталог</a>
                </div>

                @if ($activePurchases->isEmpty() && $processingPurchases->isEmpty() && $purchaseHistory->isEmpty())
                    <div class="empty-state empty-state--compact">
                        <div class="empty-state__symbol" aria-hidden="true">→</div>
                        <h3>У вас пока нет купленных товаров.</h3>
                        <p>После тестовой оплаты товар и приватный файл появятся в этом разделе.</p>
                        <a class="button button-primary" href="{{ route('catalog') }}">Открыть каталог</a>
                    </div>
                @else
                    @unless ($activePurchases->isEmpty())
                        <section class="purchase-group" aria-labelledby="active-purchases-title">
                            <div class="purchase-group__title">
                                <h3 id="active-purchases-title">Активные</h3>
                                <span>{{ $activePurchases->count() }}</span>
                            </div>
                            <div class="purchase-list">
                                @foreach ($activePurchases as $purchase)
                                    <x-purchase-card :item="$purchase" />
                                @endforeach
                            </div>
                        </section>
                    @endunless

                    @unless ($processingPurchases->isEmpty())
                        <section class="purchase-group" aria-labelledby="processing-purchases-title">
                            <div class="purchase-group__title">
                                <h3 id="processing-purchases-title">В обработке</h3>
                                <span>{{ $processingPurchases->count() }}</span>
                            </div>
                            <div class="purchase-list">
                                @foreach ($processingPurchases as $purchase)
                                    <x-purchase-card :item="$purchase" />
                                @endforeach
                            </div>
                        </section>
                    @endunless

                    @unless ($purchaseHistory->isEmpty())
                        <section class="purchase-group" aria-labelledby="purchase-history-title">
                            <div class="purchase-group__title">
                                <h3 id="purchase-history-title">Завершённые и прочие</h3>
                                <span>{{ $purchaseHistory->count() }}</span>
                            </div>
                            <div class="purchase-list">
                                @foreach ($purchaseHistory as $purchase)
                                    <x-purchase-card :item="$purchase" />
                                @endforeach
                            </div>
                        </section>
                    @endunless
                @endif
            </section>
        </div>
    </section>
@endsection
