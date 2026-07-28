@extends('layouts.admin')

@section('title', 'Новая категория')

@section('content')
    <div class="admin-container admin-page admin-page--form">
        <header class="admin-page-heading">
            <div>
                <a class="admin-back-link" href="{{ route('admin.categories.index') }}">← Категории</a>
                <h1>Новая категория</h1>
                <p>Добавьте раздел для группировки товаров.</p>
            </div>
        </header>

        <form class="admin-panel admin-form" method="POST" action="{{ route('admin.categories.store') }}">
            @include('admin.categories._form', ['category' => new \App\Models\Category()])
        </form>
    </div>
@endsection
