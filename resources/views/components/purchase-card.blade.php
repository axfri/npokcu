@props(['item'])

@php
    $delivery = $item->proxyDelivery;
    $order = $item->order;
    $startsAt = $delivery?->starts_at ?? $item->starts_at;
    $expiresAt = $delivery?->expires_at ?? $item->expires_at;
@endphp

<article class="purchase-card">
    <div class="purchase-card__top">
        <div>
            <span class="purchase-card__order">Заказ <bdi>{{ $order->order_number }}</bdi></span>
            <h4>{{ $item->product_name }}</h4>
        </div>
        <span class="status-badge status-badge--{{ $item->purchaseStatusVariant() }}">
            {{ $item->purchaseStatusLabel() }}
        </span>
    </div>

    <dl class="purchase-meta">
        <div>
            <dt>Дата покупки</dt>
            <dd>
                @if ($order->paid_at)
                    <time datetime="{{ $order->paid_at->toAtomString() }}">{{ $order->paid_at->format('d.m.Y') }}</time>
                @else
                    Не указана
                @endif
            </dd>
        </div>
        <div>
            <dt>Начало</dt>
            <dd>
                @if ($startsAt)
                    <time datetime="{{ $startsAt->toAtomString() }}">{{ $startsAt->format('d.m.Y') }}</time>
                @else
                    Не указано
                @endif
            </dd>
        </div>
        <div>
            <dt>Окончание</dt>
            <dd>
                @if ($expiresAt)
                    <time datetime="{{ $expiresAt->toAtomString() }}">{{ $expiresAt->format('d.m.Y') }}</time>
                @else
                    Не указано
                @endif
            </dd>
        </div>
        <div>
            <dt>Срок</dt>
            <dd>{{ $item->duration_days }} дней</dd>
        </div>
        <div>
            <dt>Скачивания</dt>
            <dd>{{ $delivery?->download_count ?? 0 }}</dd>
        </div>
    </dl>

    <div class="purchase-card__footer">
        <div class="purchase-expiry">
            @if ($item->purchaseStatus() === \App\Models\OrderItem::PURCHASE_EXPIRED)
                <strong>Срок действия завершён</strong>
                <span>Файл сохранён, но скачивание отключено.</span>
            @elseif ($item->isPurchaseActive())
                <strong>Осталось дней: {{ $item->remainingDays() }}</strong>
                <span>Доступ действует до указанной даты.</span>
            @elseif ($item->purchaseStatus() === \App\Models\OrderItem::PURCHASE_DELIVERY_FAILED)
                <strong>Файл временно недоступен</strong>
                <span>Выдачу можно безопасно запустить повторно.</span>
            @else
                <strong>{{ $item->purchaseStatusLabel() }}</strong>
                <span>Кнопка появится после подготовки файла.</span>
            @endif
        </div>

        @if ($delivery)
            @can('download', $delivery)
                <a class="button button-primary button-small" href="{{ route('account.purchases.download', $delivery) }}">
                    Скачать
                </a>
            @else
                <span class="button button-secondary button-small button-disabled" aria-disabled="true">Недоступно</span>
            @endcan
        @else
            <span class="button button-secondary button-small button-disabled" aria-disabled="true">Недоступно</span>
        @endif
    </div>
</article>
