@extends('layouts.app')

@section('title', e($product->name))
@section('description', e($product->short_description ?: 'Описание товара '.$product->name.'.'))

@section('content')
    <section class="product-page">
        <div class="container">
            <nav class="breadcrumbs" aria-label="Хлебные крошки">
                <a href="{{ route('home') }}">Главная</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('catalog') }}">Каталог</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('catalog.category', $product->category) }}">{{ $product->category->name }}</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">{{ $product->name }}</span>
            </nav>

            <div class="product-detail-grid">
                <article class="product-detail-main">
                    <span class="eyebrow">{{ $product->category->name }}</span>
                    <h1>{{ $product->name }}</h1>

                    @if ($product->short_description)
                        <p class="product-lead">{{ $product->short_description }}</p>
                    @endif

                    <div class="product-description">
                        <h2>Описание</h2>
                        <p>{{ $product->description ?: 'Подробное описание товара будет добавлено позднее.' }}</p>
                    </div>

                    <section class="duration-section" aria-labelledby="duration-title">
                        <div class="duration-section__head">
                            <div>
                                <span class="eyebrow">Варианты</span>
                                <h2 id="duration-title">Доступные сроки</h2>
                            </div>
                            <span>Выберите подходящий период</span>
                        </div>

                        @forelse ($product->durationOptions as $option)
                            @if ($loop->first)
                                <div class="duration-list">
                            @endif

                            <div class="duration-option">
                                <div>
                                    <strong>{{ $option->title }}</strong>
                                    <span>{{ $option->duration_days }} дней доступа</span>
                                </div>
                                <x-price-block :amount="$option->price" />
                            </div>

                            @if ($loop->last)
                                </div>
                            @endif
                        @empty
                            <div class="empty-state empty-state--compact">
                                <h3>Варианты срока пока не добавлены</h3>
                                <p>Базовые параметры товара указаны в блоке покупки.</p>
                            </div>
                        @endforelse
                    </section>
                </article>

                <aside class="product-purchase-panel">
                    <span class="product-purchase-panel__label">Базовая цена</span>
                    <x-price-block :amount="$product->base_price" suffix="за выбранный период" />
                    <dl class="product-facts">
                        <div>
                            <dt>Категория</dt>
                            <dd>{{ $product->category->name }}</dd>
                        </div>
                        <div>
                            <dt>Базовый срок</dt>
                            <dd>{{ $product->default_duration_days }} дней</dd>
                        </div>
                    </dl>
                    <a class="button button-primary button-wide" href="{{ route('purchase.placeholder') }}">Купить</a>
                    <p>Оформление заказа будет подключено на следующем этапе.</p>
                </aside>
            </div>
        </div>
    </section>
@endsection
