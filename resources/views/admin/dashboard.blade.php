@extends('layouts.admin')

@section('title', 'Обзор')

@section('content')
    <div class="admin-container admin-page">
        <header class="admin-page-heading">
            <div>
                <span class="admin-eyebrow">Панель управления</span>
                <h1>Обзор</h1>
                <p>Основные показатели магазина и быстрый переход к разделам.</p>
            </div>
        </header>

        <section class="admin-metrics" aria-label="Основные показатели">
            <article class="admin-metric">
                <span>Пользователи</span>
                <strong>{{ $metrics['users'] }}</strong>
                <a href="{{ route('admin.users.index') }}">Открыть список</a>
            </article>
            <article class="admin-metric">
                <span>Активные товары</span>
                <strong>{{ $metrics['activeProducts'] }}</strong>
                <a href="{{ route('admin.products.index') }}">Управлять</a>
            </article>
            <article class="admin-metric">
                <span>Все заказы</span>
                <strong>{{ $metrics['orders'] }}</strong>
                <a href="{{ route('admin.orders.index') }}">Посмотреть</a>
            </article>
            <article class="admin-metric admin-metric--success">
                <span>Оплачено</span>
                <strong>{{ $metrics['paidOrders'] }}</strong>
                <a href="{{ route('admin.orders.index', ['payment_status' => 'paid']) }}">Отфильтровать</a>
            </article>
            <article class="admin-metric admin-metric--warning">
                <span>В обработке</span>
                <strong>{{ $metrics['processingOrders'] }}</strong>
                <a href="{{ route('admin.orders.index', ['order_status' => 'processing']) }}">Отфильтровать</a>
            </article>
            <article class="admin-metric admin-metric--success">
                <span>Завершено</span>
                <strong>{{ $metrics['completedOrders'] }}</strong>
                <a href="{{ route('admin.orders.index', ['order_status' => 'completed']) }}">Отфильтровать</a>
            </article>
            <article class="admin-metric admin-metric--danger">
                <span>Ошибка или отмена</span>
                <strong>{{ $metrics['problemOrders'] }}</strong>
                <a href="{{ route('admin.orders.index') }}">Проверить заказы</a>
            </article>
        </section>

        <section class="admin-quick-links" aria-labelledby="admin-sections-title">
            <div class="admin-section-heading">
                <div>
                    <span class="admin-eyebrow">Разделы</span>
                    <h2 id="admin-sections-title">Управление данными</h2>
                </div>
            </div>
            <div class="admin-link-grid">
                <a href="{{ route('admin.categories.index') }}"><strong>Категории</strong><span>Структура каталога и порядок вывода</span></a>
                <a href="{{ route('admin.products.index') }}"><strong>Товары</strong><span>Цены, сроки и доступность</span></a>
                <a href="{{ route('admin.orders.index') }}"><strong>Заказы</strong><span>Оплата, состав и выдача</span></a>
                <a href="{{ route('admin.users.index') }}"><strong>Пользователи</strong><span>Аккаунты и история покупок</span></a>
            </div>
        </section>
    </div>
@endsection
