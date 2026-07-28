@extends('layouts.admin')

@section('title', 'Заказы')

@section('content')
    <div class="admin-container admin-page">
        <header class="admin-page-heading">
            <div>
                <span class="admin-eyebrow">Продажи</span>
                <h1>Заказы</h1>
                <p>Просмотр оплаты, состава и текущего состояния заказов.</p>
            </div>
        </header>

        <form class="admin-panel admin-filters" method="GET" action="{{ route('admin.orders.index') }}">
            <div class="form-field">
                <label class="form-label" for="order_number">Номер заказа</label>
                <input class="form-control" id="order_number" name="order_number" type="search" value="{{ $filters['order_number'] ?? '' }}" placeholder="NPK-...">
            </div>
            <div class="form-field">
                <label class="form-label" for="email">Email</label>
                <input class="form-control" id="email" name="email" type="search" value="{{ $filters['email'] ?? '' }}" placeholder="user@example.com">
            </div>
            <div class="form-field">
                <label class="form-label" for="payment_status">Статус оплаты</label>
                <select class="form-control" id="payment_status" name="payment_status">
                    <option value="">Все</option>
                    @foreach (['pending' => 'Ожидает оплаты', 'paid' => 'Оплачено', 'failed' => 'Ошибка', 'cancelled' => 'Отменено'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['payment_status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="order_status">Статус заказа</label>
                <select class="form-control" id="order_status" name="order_status">
                    <option value="">Все</option>
                    @foreach (['pending', 'awaiting_payment', 'paid', 'processing', 'completed', 'cancelled', 'failed'] as $value)
                        <option value="{{ $value }}" @selected(($filters['order_status'] ?? '') === $value)>{{ \App\Support\AdminLabels::orderStatus($value) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="admin-filter-actions">
                <button class="button button-dark" type="submit">Применить</button>
                <a class="button button-secondary" href="{{ route('admin.orders.index') }}">Сбросить</a>
            </div>
        </form>

        <section class="admin-panel">
            @if ($orders->isEmpty())
                <div class="admin-empty">
                    <h2>Заказы не найдены</h2>
                    <p>Измените фильтры или дождитесь новых заказов.</p>
                </div>
            @else
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Номер</th>
                                <th>Покупатель</th>
                                <th>Сумма</th>
                                <th>Оплата</th>
                                <th>Заказ</th>
                                <th>Дата</th>
                                <th><span class="sr-only">Действия</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $order)
                                <tr>
                                    <td data-label="Номер"><strong>{{ $order->order_number }}</strong></td>
                                    <td data-label="Покупатель">
                                        <bdi>{{ $order->customer_email }}</bdi>
                                        @if ($order->user)
                                            <small class="admin-cell-note">ID {{ $order->user->getKey() }}</small>
                                        @else
                                            <small class="admin-cell-note">Без аккаунта</small>
                                        @endif
                                    </td>
                                    <td data-label="Сумма">{{ \App\Support\MoneyFormatter::format($order->total, $order->currency) }}</td>
                                    <td data-label="Оплата">
                                        <span class="status-badge status-badge--{{ \App\Support\AdminLabels::variant($order->payment_status) }}">
                                            {{ \App\Support\AdminLabels::paymentStatus($order->payment_status) }}
                                        </span>
                                    </td>
                                    <td data-label="Заказ">
                                        <span class="status-badge status-badge--{{ \App\Support\AdminLabels::variant($order->order_status) }}">
                                            {{ \App\Support\AdminLabels::orderStatus($order->order_status) }}
                                        </span>
                                    </td>
                                    <td data-label="Дата"><time datetime="{{ $order->created_at->toISOString() }}">{{ $order->created_at->format('d.m.Y H:i') }}</time></td>
                                    <td class="admin-table__actions">
                                        <a class="button button-secondary button-small" href="{{ route('admin.orders.show', $order) }}">Подробнее</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="admin-pagination">{{ $orders->links('admin.partials.pagination') }}</div>
            @endif
        </section>
    </div>
@endsection
