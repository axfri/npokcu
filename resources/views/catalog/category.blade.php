@extends('layouts.app')

@section('title', e($category->name))
@section('description', e($category->description ?: 'Товары категории '.$category->name.'.'))

@section('content')
    <section class="catalog-hero catalog-hero--compact">
        <div class="container">
            <nav class="breadcrumbs" aria-label="Хлебные крошки">
                <a href="{{ route('home') }}">Главная</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('catalog') }}">Каталог</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">{{ $category->name }}</span>
            </nav>
            <span class="eyebrow">Категория</span>
            <h1>{{ $category->name }}</h1>
            <p>{{ $category->description ?: 'Описание категории будет добавлено позднее.' }}</p>
        </div>
    </section>

    <section class="section catalog-content">
        <div class="container">
            <div class="catalog-section-heading">
                <div>
                    <span class="eyebrow">Товары</span>
                    <h2>Доступные предложения</h2>
                </div>
                <p>Найдено активных товаров: {{ $category->products->count() }}</p>
            </div>

            @forelse ($category->products as $product)
                @if ($loop->first)
                    <div class="product-grid">
                @endif

                <x-product-card :product="$product" />

                @if ($loop->last)
                    </div>
                @endif
            @empty
                <div class="empty-state">
                    <span class="empty-state__symbol" aria-hidden="true">○</span>
                    <h2>В этой категории пока нет товаров</h2>
                    <p>Вернитесь в общий каталог или проверьте раздел позднее.</p>
                    <a class="button button-secondary" href="{{ route('catalog') }}">Вернуться в каталог</a>
                </div>
            @endforelse
        </div>
    </section>
@endsection
