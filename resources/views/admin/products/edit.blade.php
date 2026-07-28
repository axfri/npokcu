@extends('layouts.admin')

@section('title', 'Редактирование товара')

@section('content')
    <div class="admin-container admin-page admin-page--form">
        <header class="admin-page-heading">
            <div>
                <a class="admin-back-link" href="{{ route('admin.products.index') }}">← Товары</a>
                <h1>{{ $product->name }}</h1>
                <p>Основные данные товара и варианты срока действия.</p>
            </div>
            <div class="admin-heading-actions">
                <a class="button button-secondary button-small" href="{{ route('products.show', $product) }}">Открыть на сайте</a>
                <span class="status-badge status-badge--{{ $product->is_active ? 'success' : 'neutral' }}">
                    {{ $product->is_active ? 'Активен' : 'Отключён' }}
                </span>
            </div>
        </header>

        <form class="admin-panel admin-form" method="POST" action="{{ route('admin.products.update', $product) }}">
            @include('admin.products._form')
        </form>

        <section class="admin-panel admin-options" aria-labelledby="duration-options-title">
            <div class="admin-section-heading">
                <div>
                    <span class="admin-eyebrow">Цены</span>
                    <h2 id="duration-options-title">Варианты срока</h2>
                    <p>Изменения не затрагивают данные уже созданных заказов.</p>
                </div>
            </div>

            @if ($product->durationOptions->isEmpty())
                <div class="admin-empty admin-empty--compact">
                    <h3>Вариантов пока нет</h3>
                    <p>Добавьте хотя бы один срок, чтобы товар можно было купить.</p>
                </div>
            @else
                <div class="admin-option-list">
                    @foreach ($product->durationOptions as $option)
                        <div class="admin-option-card">
                            <form class="admin-option-form" method="POST" action="{{ route('admin.products.duration-options.update', [$product, $option]) }}">
                                @csrf
                                @method('PUT')
                                <div class="form-field">
                                    <label class="form-label" for="option-title-{{ $option->getKey() }}">Название</label>
                                    <input class="form-control" id="option-title-{{ $option->getKey() }}" name="title" type="text" value="{{ $option->title }}" required>
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="option-days-{{ $option->getKey() }}">Дней</label>
                                    <input class="form-control" id="option-days-{{ $option->getKey() }}" name="duration_days" type="number" value="{{ $option->duration_days }}" min="1" max="3650" required>
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="option-price-{{ $option->getKey() }}">Цена, ₽</label>
                                    <input class="form-control" id="option-price-{{ $option->getKey() }}" name="price" type="text" inputmode="decimal" value="{{ $option->price }}" required>
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="option-sort-{{ $option->getKey() }}">Порядок</label>
                                    <input class="form-control" id="option-sort-{{ $option->getKey() }}" name="sort_order" type="number" value="{{ $option->sort_order }}" min="0" required>
                                </div>
                                <label class="admin-check admin-check--compact">
                                    <input name="is_active" type="checkbox" value="1" @checked($option->is_active)>
                                    <span><strong>Активен</strong></span>
                                </label>
                                <button class="button button-secondary button-small" type="submit">Сохранить</button>
                            </form>
                            <form class="admin-option-delete" method="POST" action="{{ route('admin.products.duration-options.destroy', [$product, $option]) }}">
                                @csrf
                                @method('DELETE')
                                <button class="button button-danger button-small" type="submit">Удалить</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif

            <form class="admin-option-create" method="POST" action="{{ route('admin.products.duration-options.store', $product) }}">
                @csrf
                <h3>Добавить вариант</h3>
                <div class="admin-option-create__grid">
                    <div class="form-field">
                        <label class="form-label" for="new-option-title">Название</label>
                        <input class="form-control" id="new-option-title" name="title" type="text" value="{{ old('title') }}" placeholder="30 дней" required>
                    </div>
                    <div class="form-field">
                        <label class="form-label" for="new-option-days">Дней</label>
                        <input class="form-control" id="new-option-days" name="duration_days" type="number" value="{{ old('duration_days') }}" min="1" max="3650" required>
                    </div>
                    <div class="form-field">
                        <label class="form-label" for="new-option-price">Цена, ₽</label>
                        <input class="form-control" id="new-option-price" name="price" type="text" inputmode="decimal" value="{{ old('price') }}" placeholder="1000.00" required>
                    </div>
                    <div class="form-field">
                        <label class="form-label" for="new-option-sort">Порядок</label>
                        <input class="form-control" id="new-option-sort" name="sort_order" type="number" value="{{ old('sort_order', 0) }}" min="0" required>
                    </div>
                    <label class="admin-check admin-check--compact">
                        <input name="is_active" type="checkbox" value="1" @checked(old('is_active', true))>
                        <span><strong>Активен</strong></span>
                    </label>
                </div>
                <button class="button button-dark" type="submit">Добавить вариант</button>
            </form>
        </section>
    </div>
@endsection
