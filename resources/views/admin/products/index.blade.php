@extends('layouts.admin')

@section('title', 'Товары')

@section('content')
    <div class="admin-container admin-page">
        <header class="admin-page-heading">
            <div>
                <span class="admin-eyebrow">Каталог</span>
                <h1>Товары</h1>
                <p>Управляйте ассортиментом, базовыми ценами и доступностью.</p>
            </div>
            <a class="button button-dark" href="{{ route('admin.products.create') }}">Добавить товар</a>
        </header>

        <section class="admin-panel">
            @if ($products->isEmpty())
                <div class="admin-empty">
                    <h2>Товаров пока нет</h2>
                    <p>Создайте товар и добавьте для него варианты срока и цены.</p>
                </div>
            @else
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Товар</th>
                                <th>Категория</th>
                                <th>Цена от</th>
                                <th>Сроков</th>
                                <th>Порядок</th>
                                <th>Статус</th>
                                <th><span class="sr-only">Действия</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $product)
                                <tr>
                                    <td data-label="Товар">
                                        <strong>{{ $product->name }}</strong>
                                        <small class="admin-cell-note">{{ $product->slug }}</small>
                                    </td>
                                    <td data-label="Категория">{{ $product->category->name }}</td>
                                    <td data-label="Цена от"><x-price-block :amount="$product->base_price" /></td>
                                    <td data-label="Сроков">{{ $product->duration_options_count }}</td>
                                    <td data-label="Порядок">{{ $product->sort_order }}</td>
                                    <td data-label="Статус">
                                        <span class="status-badge status-badge--{{ $product->is_active ? 'success' : 'neutral' }}">
                                            {{ $product->is_active ? 'Активен' : 'Отключён' }}
                                        </span>
                                    </td>
                                    <td class="admin-table__actions">
                                        <a class="button button-secondary button-small" href="{{ route('admin.products.edit', $product) }}">Изменить</a>
                                        <form method="POST" action="{{ route('admin.products.toggle', $product) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="button button-ghost button-small" type="submit">
                                                {{ $product->is_active ? 'Отключить' : 'Включить' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="admin-pagination">{{ $products->links('admin.partials.pagination') }}</div>
            @endif
        </section>
    </div>
@endsection
