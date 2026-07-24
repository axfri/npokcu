@extends('layouts.app')

@section('title', 'Заказ принят')
@section('description', 'Заказ успешно принят и оплачен в тестовом режиме.')

@php
    $item = $order->items->first();
    $payment = $order->paymentTransactions->first();
    $delivery = $item?->proxyDelivery;
@endphp

@section('content')
    <section class="order-success-page">
        <div class="container">
            <article class="order-success-card">
                <div class="order-success-icon" aria-hidden="true">✓</div>
                <span class="eyebrow">Тестовая оплата выполнена</span>
                <h1>Заказ принят</h1>
                <p class="order-success-lead">Мы зафиксировали заказ. Обновление этой страницы не создаст новый заказ или платёж.</p>

                <div class="order-success-number">
                    <span>Номер заказа</span>
                    <bdi>{{ $order->order_number }}</bdi>
                </div>

                @if ($item)
                    <dl class="order-success-details">
                        <div>
                            <dt>Товар</dt>
                            <dd>{{ $item->product_name }}</dd>
                        </div>
                        <div>
                            <dt>Срок</dt>
                            <dd>{{ $item->duration_days }} дней</dd>
                        </div>
                        <div>
                            <dt>Сумма</dt>
                            <dd><x-price-block :amount="$order->total" /></dd>
                        </div>
                        <div>
                            <dt>Email</dt>
                            <dd><bdi>{{ $order->customer_email }}</bdi></dd>
                        </div>
                        <div>
                            <dt>Статус оплаты</dt>
                            <dd>
                                <span class="status-badge status-badge--success">
                                    {{ $payment?->status === 'paid' ? 'Оплачено' : 'Обрабатывается' }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt>Статус файла</dt>
                            <dd>
                                @if ($delivery?->isDownloadable())
                                    <span class="status-badge status-badge--success">Файл подготовлен</span>
                                @elseif ($delivery?->status === \App\Models\ProxyDelivery::STATUS_FAILED)
                                    <span class="status-badge status-badge--danger">Ошибка выдачи</span>
                                @else
                                    <span class="status-badge status-badge--warning">Обрабатывается</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                @endif

                <div class="order-success-notice">
                    <strong>Файл и аккаунт</strong>
                    @if (! $isOwner)
                        <p>Файл отправлен на вашу почту. Для скачивания на сайте войдите в созданный аккаунт.</p>
                        @if ($order->guest_account_status === \App\Models\Order::GUEST_ACCOUNT_CREATED)
                            <p>Аккаунт создан автоматически. Данные для входа отправлены отдельным письмом.</p>
                        @else
                            <p>Заказ закреплён за аккаунтом с указанной почтой.</p>
                        @endif
                    @else
                        <p>Заказ закреплён за вашим аккаунтом. Подготовленный файл доступен здесь и в разделе покупок.</p>
                    @endif
                </div>

                <div class="order-success-actions">
                    @if ($delivery)
                        @can('download', $delivery)
                            <a class="button button-primary" href="{{ route('account.purchases.download', $delivery) }}">
                                Скачать файл
                            </a>
                        @endcan
                    @endif
                    <a class="button button-secondary" href="{{ route('account') }}">
                        {{ $isOwner ? 'Личный кабинет' : 'Войти в аккаунт' }}
                    </a>
                    <a class="button button-ghost" href="{{ route('catalog') }}">Вернуться в каталог</a>
                </div>
            </article>
        </div>
    </section>
@endsection
