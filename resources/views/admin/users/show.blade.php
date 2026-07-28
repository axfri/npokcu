@extends('layouts.admin')

@section('title', 'Пользователь '.$user->email)

@section('content')
    <div class="admin-container admin-page">
        <header class="admin-page-heading">
            <div>
                <a class="admin-back-link" href="{{ route('admin.users.index') }}">← Пользователи</a>
                <h1><bdi>{{ $user->email }}</bdi></h1>
                <p>Аккаунт #{{ $user->getKey() }} и связанная история покупок.</p>
            </div>
            @if ($user->is_admin)
                <span class="status-badge status-badge--success">Администратор</span>
            @endif
        </header>

        <section class="admin-panel admin-detail-card">
            <h2>Основная информация</h2>
            <dl class="admin-details admin-details--columns">
                <div><dt>ID</dt><dd>{{ $user->getKey() }}</dd></div>
                <div><dt>Email</dt><dd><bdi>{{ $user->email }}</bdi></dd></div>
                <div><dt>Регистрация</dt><dd>{{ $user->created_at?->format('d.m.Y H:i') ?? '—' }}</dd></div>
                <div><dt>Подтверждение email</dt><dd>{{ $user->hasVerifiedEmail() ? $user->email_verified_at->format('d.m.Y H:i') : 'Не подтверждён' }}</dd></div>
                <div><dt>Тип аккаунта</dt><dd>{{ $user->is_auto_created ? 'Создан автоматически' : 'Обычная регистрация' }}</dd></div>
                <div><dt>Статус</dt><dd>{{ $user->status === \App\Models\User::STATUS_ACTIVE ? 'Активен' : 'Заблокирован' }}</dd></div>
                <div><dt>Смена пароля</dt><dd>{{ $user->must_change_password ? 'Требуется' : 'Не требуется' }}</dd></div>
                <div><dt>Заказов</dt><dd>{{ $user->orders->count() }}</dd></div>
            </dl>
        </section>

        <section class="admin-panel admin-detail-section">
            <div class="admin-section-heading">
                <div><span class="admin-eyebrow">История</span><h2>Заказы</h2></div>
            </div>
            @if ($user->orders->isEmpty())
                <p class="admin-muted">Заказов пока нет.</p>
            @else
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead><tr><th>Номер</th><th>Сумма</th><th>Оплата</th><th>Заказ</th><th>Дата</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($user->orders as $order)
                                <tr>
                                    <td data-label="Номер"><strong>{{ $order->order_number }}</strong></td>
                                    <td data-label="Сумма">{{ \App\Support\MoneyFormatter::format($order->total, $order->currency) }}</td>
                                    <td data-label="Оплата">{{ \App\Support\AdminLabels::paymentStatus($order->payment_status) }}</td>
                                    <td data-label="Заказ">{{ \App\Support\AdminLabels::orderStatus($order->order_status) }}</td>
                                    <td data-label="Дата">{{ $order->created_at->format('d.m.Y H:i') }}</td>
                                    <td class="admin-table__actions"><a class="button button-secondary button-small" href="{{ route('admin.orders.show', $order) }}">Открыть</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="admin-panel admin-detail-section">
            <div class="admin-section-heading">
                <div><span class="admin-eyebrow">Доступ</span><h2>Активные покупки</h2></div>
            </div>
            @if ($activePurchases->isEmpty())
                <p class="admin-muted">Активных покупок нет.</p>
            @else
                <div class="admin-purchase-grid">
                    @foreach ($activePurchases as $purchase)
                        <article class="admin-purchase">
                            <strong>{{ $purchase->product_name }}</strong>
                            <span>{{ $purchase->duration_days }} дней · до {{ $purchase->expires_at?->format('d.m.Y') ?? 'без даты' }}</span>
                            <span class="status-badge status-badge--success">{{ $purchase->purchaseStatusLabel() }}</span>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="admin-panel admin-detail-section">
            <div class="admin-section-heading">
                <div><span class="admin-eyebrow">Архив</span><h2>Завершённые и прочие покупки</h2></div>
            </div>
            @if ($purchaseHistory->isEmpty())
                <p class="admin-muted">История покупок пуста.</p>
            @else
                <div class="admin-purchase-grid">
                    @foreach ($purchaseHistory as $purchase)
                        <article class="admin-purchase">
                            <strong>{{ $purchase->product_name }}</strong>
                            <span>{{ $purchase->duration_days }} дней · заказ {{ $purchase->order->order_number }}</span>
                            <span class="status-badge status-badge--{{ $purchase->purchaseStatusVariant() }}">{{ $purchase->purchaseStatusLabel() }}</span>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection
