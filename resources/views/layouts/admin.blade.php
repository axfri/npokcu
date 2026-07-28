<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('description', 'Административная панель сайта.')">
    <meta name="color-scheme" content="light">

    <title>@yield('title', 'Админ-панель') — {{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-body">
    <a class="skip-link" href="#admin-content">Перейти к содержимому</a>

    <header class="admin-header" data-site-header data-menu-breakpoint="1121px">
        <div class="admin-container admin-header__inner">
            <a class="admin-brand" href="{{ route('admin.dashboard') }}">
                <span class="admin-brand__mark" aria-hidden="true">A</span>
                <span>
                    <strong>ПРОКСИ.НЕТ</strong>
                    <small>управление сайтом</small>
                </span>
            </a>

            <nav class="admin-nav" aria-label="Разделы админ-панели">
                <a href="{{ route('admin.dashboard') }}" @class(['is-active' => request()->routeIs('admin.dashboard')])>Обзор</a>
                <a href="{{ route('admin.categories.index') }}" @class(['is-active' => request()->routeIs('admin.categories.*')])>Категории</a>
                <a href="{{ route('admin.products.index') }}" @class(['is-active' => request()->routeIs('admin.products.*')])>Товары</a>
                <a href="{{ route('admin.orders.index') }}" @class(['is-active' => request()->routeIs('admin.orders.*')])>Заказы</a>
                <a href="{{ route('admin.users.index') }}" @class(['is-active' => request()->routeIs('admin.users.*')])>Пользователи</a>
            </nav>

            <div class="admin-header__actions">
                <a class="button button-secondary button-small" href="{{ route('home') }}">На сайт</a>
                <bdi class="admin-email" title="{{ auth()->user()->email }}">{{ auth()->user()->email }}</bdi>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="button button-dark button-small" type="submit">Выйти</button>
                </form>
            </div>

            <button class="menu-toggle admin-menu-toggle" type="button" aria-expanded="false" aria-controls="admin-mobile-menu" data-menu-toggle>
                <span class="sr-only">Открыть меню</span>
                <span></span><span></span><span></span>
            </button>
        </div>

        <nav class="admin-mobile-menu" id="admin-mobile-menu" aria-label="Мобильное меню админ-панели" data-mobile-menu hidden>
            <div class="admin-container admin-mobile-menu__inner">
                <bdi class="admin-mobile-email">{{ auth()->user()->email }}</bdi>
                <a href="{{ route('admin.dashboard') }}" @class(['is-active' => request()->routeIs('admin.dashboard')])>Обзор</a>
                <a href="{{ route('admin.categories.index') }}" @class(['is-active' => request()->routeIs('admin.categories.*')])>Категории</a>
                <a href="{{ route('admin.products.index') }}" @class(['is-active' => request()->routeIs('admin.products.*')])>Товары</a>
                <a href="{{ route('admin.orders.index') }}" @class(['is-active' => request()->routeIs('admin.orders.*')])>Заказы</a>
                <a href="{{ route('admin.users.index') }}" @class(['is-active' => request()->routeIs('admin.users.*')])>Пользователи</a>
                <div class="admin-mobile-menu__actions">
                    <a class="button button-secondary" href="{{ route('home') }}">На сайт</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="button button-dark" type="submit">Выйти</button>
                    </form>
                </div>
            </div>
        </nav>
    </header>

    <main class="admin-main" id="admin-content">
        @if (session('success'))
            <div class="admin-container alert-wrap">
                <div class="alert alert-success" role="status">{{ session('success') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="admin-container alert-wrap">
                <div class="alert alert-error" role="alert">{{ session('error') }}</div>
            </div>
        @endif

        @if ($errors->any() && ! $__env->hasSection('suppressGlobalErrors'))
            <div class="admin-container alert-wrap">
                <div class="alert alert-error" role="alert">
                    <strong>Проверьте введённые данные:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
