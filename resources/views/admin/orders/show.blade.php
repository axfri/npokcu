@extends('layouts.admin')

@section('title', 'Заказ '.$order->order_number)

@section('content')
    <div class="admin-container admin-page">
        <header class="admin-page-heading">
            <div>
                <a class="admin-back-link" href="{{ route('admin.orders.index') }}">← Заказы</a>
                <h1>{{ $order->order_number }}</h1>
                <p>Заказ создан {{ $order->created_at->format('d.m.Y в H:i') }}.</p>
            </div>
            <div class="admin-heading-actions">
                <span class="status-badge status-badge--{{ \App\Support\AdminLabels::variant($order->payment_status) }}">
                    {{ \App\Support\AdminLabels::paymentStatus($order->payment_status) }}
                </span>
                <span class="status-badge status-badge--{{ \App\Support\AdminLabels::variant($order->order_status) }}">
                    {{ \App\Support\AdminLabels::orderStatus($order->order_status) }}
                </span>
            </div>
        </header>

        <div class="admin-detail-grid">
            <section class="admin-panel admin-detail-card">
                <h2>Покупатель</h2>
                <dl class="admin-details">
                    <div><dt>Email</dt><dd><bdi>{{ $order->customer_email }}</bdi></dd></div>
                    <div>
                        <dt>Аккаунт</dt>
                        <dd>
                            @if ($order->user)
                                <a href="{{ route('admin.users.show', $order->user) }}">Пользователь #{{ $order->user->getKey() }}</a>
                            @else
                                Не привязан
                            @endif
                        </dd>
                    </div>
                    <div><dt>Метод оплаты</dt><dd>{{ $order->payment_method ?: 'Не указан' }}</dd></div>
                </dl>
            </section>

            <section class="admin-panel admin-detail-card">
                <h2>Итого</h2>
                <dl class="admin-details">
                    <div><dt>Сумма позиций</dt><dd>{{ \App\Support\MoneyFormatter::format($order->subtotal, $order->currency) }}</dd></div>
                    <div><dt>К оплате</dt><dd><strong>{{ \App\Support\MoneyFormatter::format($order->total, $order->currency) }}</strong></dd></div>
                    <div><dt>Оплачен</dt><dd>{{ $order->paid_at?->format('d.m.Y H:i') ?? 'Нет' }}</dd></div>
                    <div><dt>Завершён</dt><dd>{{ $order->completed_at?->format('d.m.Y H:i') ?? 'Нет' }}</dd></div>
                </dl>
            </section>
        </div>

        <section class="admin-panel admin-detail-section">
            <div class="admin-section-heading">
                <div><span class="admin-eyebrow">Снимок покупки</span><h2>Позиции заказа</h2></div>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Товар</th><th>Срок</th><th>Количество</th><th>Цена</th><th>Сумма</th></tr></thead>
                    <tbody>
                        @forelse ($order->items as $item)
                            <tr>
                                <td data-label="Товар"><strong>{{ $item->product_name }}</strong></td>
                                <td data-label="Срок">{{ $item->duration_days }} дней</td>
                                <td data-label="Количество">{{ $item->quantity }}</td>
                                <td data-label="Цена">{{ \App\Support\MoneyFormatter::format($item->unit_price, $order->currency) }}</td>
                                <td data-label="Сумма">{{ \App\Support\MoneyFormatter::format($item->total_price, $order->currency) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5">Позиции отсутствуют.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="admin-panel admin-detail-section">
            <div class="admin-section-heading">
                <div><span class="admin-eyebrow">Оплата</span><h2>Платёжные операции</h2></div>
            </div>
            @if ($order->paymentTransactions->isEmpty())
                <p class="admin-muted">Платёжных операций нет.</p>
            @else
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead><tr><th>Провайдер</th><th>Сумма</th><th>Статус</th><th>Оплачено</th><th>Создано</th></tr></thead>
                        <tbody>
                            @foreach ($order->paymentTransactions as $transaction)
                                <tr>
                                    <td data-label="Провайдер">{{ $transaction->provider }}</td>
                                    <td data-label="Сумма">{{ \App\Support\MoneyFormatter::format($transaction->amount, $transaction->currency) }}</td>
                                    <td data-label="Статус">
                                        <span class="status-badge status-badge--{{ \App\Support\AdminLabels::variant($transaction->status) }}">
                                            {{ \App\Support\AdminLabels::paymentStatus($transaction->status) }}
                                        </span>
                                    </td>
                                    <td data-label="Оплачено">{{ $transaction->paid_at?->format('d.m.Y H:i') ?? 'Нет' }}</td>
                                    <td data-label="Создано">{{ $transaction->created_at->format('d.m.Y H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="admin-panel admin-detail-section">
            <div class="admin-section-heading">
                <div><span class="admin-eyebrow">Выдача</span><h2>Файлы покупателя</h2></div>
            </div>
            @if ($order->proxyDeliveries->isEmpty())
                <p class="admin-muted">Данные выдачи ещё не созданы.</p>
            @else
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead><tr><th>Файл</th><th>Статус</th><th>Начало</th><th>Окончание</th><th>Выдан</th><th>Скачиваний</th></tr></thead>
                        <tbody>
                            @foreach ($order->proxyDeliveries as $delivery)
                                <tr>
                                    <td data-label="Файл">{{ $delivery->original_filename }}</td>
                                    <td data-label="Статус">
                                        <span class="status-badge status-badge--{{ \App\Support\AdminLabels::variant($delivery->status) }}">
                                            {{ \App\Support\AdminLabels::deliveryStatus($delivery->status) }}
                                        </span>
                                    </td>
                                    <td data-label="Начало">{{ $delivery->starts_at?->format('d.m.Y H:i') ?? '—' }}</td>
                                    <td data-label="Окончание">{{ $delivery->expires_at?->format('d.m.Y H:i') ?? '—' }}</td>
                                    <td data-label="Выдан">{{ $delivery->delivered_at?->format('d.m.Y H:i') ?? 'Нет' }}</td>
                                    <td data-label="Скачиваний">{{ $delivery->download_count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
