@props(['category'])

<a class="category-card" href="{{ route('catalog.category', $category) }}">
    <span class="category-card__top">
        <span class="category-symbol" aria-hidden="true">{{ mb_strtoupper(mb_substr($category->name, 0, 1)) }}</span>
        <span class="category-count">Товаров: {{ $category->products->count() }}</span>
    </span>
    <strong>{{ $category->name }}</strong>
    <span class="category-card__description">
        {{ $category->description ?: 'Описание категории будет добавлено позднее.' }}
    </span>
    <span class="category-card__link">Открыть категорию <span aria-hidden="true">→</span></span>
</a>
