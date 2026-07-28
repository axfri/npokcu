@extends('layouts.admin')

@section('title', 'Редактирование категории')

@section('content')
    <div class="admin-container admin-page admin-page--form">
        <header class="admin-page-heading">
            <div>
                <a class="admin-back-link" href="{{ route('admin.categories.index') }}">← Категории</a>
                <h1>{{ $category->name }}</h1>
                <p>Настройки категории и её отображения в каталоге.</p>
            </div>
            <span class="status-badge status-badge--{{ $category->is_active ? 'success' : 'neutral' }}">
                {{ $category->is_active ? 'Активна' : 'Отключена' }}
            </span>
        </header>

        <form class="admin-panel admin-form" method="POST" action="{{ route('admin.categories.update', $category) }}">
            @include('admin.categories._form')
        </form>

        <section class="admin-panel admin-danger-zone">
            <div>
                <h2>Удаление категории</h2>
                @if ($category->products_count)
                    <p>В категории есть товары: {{ $category->products_count }}. Сначала перенесите их или отключите категорию.</p>
                @else
                    <p>Категория не используется и может быть удалена без влияния на товары.</p>
                @endif
            </div>
            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}">
                @csrf
                @method('DELETE')
                <button class="button button-danger" type="submit" @disabled($category->products_count)>Удалить</button>
            </form>
        </section>
    </div>
@endsection
