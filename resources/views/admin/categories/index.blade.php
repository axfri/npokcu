@extends('layouts.admin')

@section('title', 'Категории')

@section('content')
    <div class="admin-container admin-page">
        <header class="admin-page-heading">
            <div>
                <span class="admin-eyebrow">Каталог</span>
                <h1>Категории</h1>
                <p>Управляйте структурой и порядком разделов публичного каталога.</p>
            </div>
            <a class="button button-dark" href="{{ route('admin.categories.create') }}">Добавить категорию</a>
        </header>

        <section class="admin-panel">
            @if ($categories->isEmpty())
                <div class="admin-empty">
                    <h2>Категорий пока нет</h2>
                    <p>Создайте первую категорию, чтобы затем добавить в неё товары.</p>
                </div>
            @else
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Категория</th>
                                <th>Slug</th>
                                <th>Порядок</th>
                                <th>Товаров</th>
                                <th>Статус</th>
                                <th><span class="sr-only">Действия</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($categories as $category)
                                <tr>
                                    <td data-label="Категория"><strong>{{ $category->name }}</strong></td>
                                    <td data-label="Slug"><code>{{ $category->slug }}</code></td>
                                    <td data-label="Порядок">{{ $category->sort_order }}</td>
                                    <td data-label="Товаров">{{ $category->products_count }}</td>
                                    <td data-label="Статус">
                                        <span class="status-badge status-badge--{{ $category->is_active ? 'success' : 'neutral' }}">
                                            {{ $category->is_active ? 'Активна' : 'Отключена' }}
                                        </span>
                                    </td>
                                    <td class="admin-table__actions">
                                        <a class="button button-secondary button-small" href="{{ route('admin.categories.edit', $category) }}">Изменить</a>
                                        <form method="POST" action="{{ route('admin.categories.toggle', $category) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="button button-ghost button-small" type="submit">
                                                {{ $category->is_active ? 'Отключить' : 'Включить' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="admin-pagination">{{ $categories->links('admin.partials.pagination') }}</div>
            @endif
        </section>
    </div>
@endsection
