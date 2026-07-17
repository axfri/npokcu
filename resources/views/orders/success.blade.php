@extends('layouts.app')

@section('title', 'Заказ принят')
@section('description', 'Заказ успешно принят и оплачен в тестовом режиме.')

@php
    $item = $order->items->first();
    $payment = $order->paymentTransactions->first();
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
                    </dl>
                @endif

                <div class="order-success-notice">
                    <strong>Что дальше?</strong>
                    <p>Выдача файла будет добавлена на следующем этапе. Сейчас создавать или отправлять файл не требуется.</p>
                </div>

                <div class="order-success-actions">
                    <a class="button button-primary" href="{{ route('catalog') }}">Вернуться в каталог</a>
                    <a class="button button-secondary" href="{{ route('home') }}">На главную</a>
                </div>
            </article>
        </div>
    </section>
@endsection
