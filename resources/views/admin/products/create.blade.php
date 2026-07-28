@extends('layouts.admin')

@section('title', 'Новый товар')

@section('content')
    <div class="admin-container admin-page admin-page--form">
        <header class="admin-page-heading">
            <div>
                <a class="admin-back-link" href="{{ route('admin.products.index') }}">← Товары</a>
                <h1>Новый товар</h1>
                <p>Добавьте товар в существующую категорию каталога.</p>
            </div>
        </header>

        @if ($categories->isEmpty())
            <div class="alert alert-error" role="alert">
                Сначала <a href="{{ route('admin.categories.create') }}"><strong>создайте категорию</strong></a>.
            </div>
        @else
            <form class="admin-panel admin-form" method="POST" action="{{ route('admin.products.store') }}">
                @include('admin.products._form', ['product' => new \App\Models\Product()])
            </form>
        @endif
    </div>
@endsection
