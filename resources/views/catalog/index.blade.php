@extends('layouts.app')

@section('title', 'Каталог прокси')
@section('description', 'Каталог доступных прокси по категориям, срокам и ценам.')

@section('content')
    <section class="catalog-hero">
        <div class="container catalog-hero__inner">
            <div>
                <span class="eyebrow">Каталог</span>
                <h1>Выберите прокси для вашей задачи</h1>
                <p>Актуальные категории и активные предложения загружаются из каталога сервиса.</p>
            </div>
            <dl class="catalog-summary" aria-label="Сводка каталога">
                <div>
                    <dt>Категорий</dt>
                    <dd>{{ $categories->count() }}</dd>
                </div>
                <div>
                    <dt>Товаров</dt>
                    <dd>{{ $productCount }}</dd>
                </div>
            </dl>
        </div>
    </section>

    <section class="section catalog-content">
        <div class="container">
            <div class="catalog-section-heading">
                <div>
                    <span class="eyebrow">Направления</span>
                    <h2>Категории товаров</h2>
                </div>
                <p>Откройте категорию, чтобы посмотреть только подходящие предложения.</p>
            </div>

            @forelse ($categories as $category)
                @if ($loop->first)
                    <div class="category-grid">
                @endif

                <x-category-card :category="$category" />

                @if ($loop->last)
                    </div>
                @endif
            @empty
                <div class="empty-state">
                    <span class="empty-state__symbol" aria-hidden="true">○</span>
                    <h2>Категории пока не добавлены</h2>
                    <p>Каталог появится здесь после добавления активных категорий.</p>
                </div>
            @endforelse

            @if ($categories->isNotEmpty())
                <div class="catalog-products">
                    <div class="catalog-section-heading">
                        <div>
                            <span class="eyebrow">Предложения</span>
                            <h2>Доступные товары</h2>
                        </div>
                        <p>В каталоге показываются только активные товары.</p>
                    </div>

                    @if ($productCount === 0)
                        <div class="empty-state">
                            <span class="empty-state__symbol" aria-hidden="true">○</span>
                            <h2>Активных товаров пока нет</h2>
                            <p>Новые предложения появятся в этом разделе после публикации.</p>
                        </div>
                    @else
                        @foreach ($categories as $category)
                            @if ($category->products->isNotEmpty())
                                <section class="catalog-category-section" aria-labelledby="category-{{ $category->id }}">
                                    <div class="catalog-category-section__head">
                                        <div>
                                            <h3 id="category-{{ $category->id }}">{{ $category->name }}</h3>
                                            <span>{{ $category->products->count() }} предложений</span>
                                        </div>
                                        <a class="text-link" href="{{ route('catalog.category', $category) }}">Вся категория →</a>
                                    </div>
                                    <div class="product-grid">
                                        @foreach ($category->products as $product)
                                            <x-product-card :product="$product" />
                                        @endforeach
                                    </div>
                                </section>
                            @endif
                        @endforeach
                    @endif
                </div>
            @endif
        </div>
    </section>
@endsection
