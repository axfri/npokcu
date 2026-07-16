@props(['product'])

<article class="product-card">
    <div class="product-card__body">
        <div class="product-card__meta">
            <span>Прокси</span>
            <span>{{ $product->default_duration_days }} дней</span>
        </div>
        <h3><a href="{{ route('products.show', $product) }}">{{ $product->name }}</a></h3>
        <p>{{ $product->short_description ?: 'Подробная информация о товаре будет добавлена позднее.' }}</p>
    </div>
    <div class="product-card__footer">
        <x-price-block :amount="$product->base_price" label="от" />
        <a class="button button-secondary button-small" href="{{ route('products.show', $product) }}">Подробнее</a>
    </div>
</article>
