<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('description', 'Надежные прокси для повседневных и профессиональных задач.')">
    <meta name="color-scheme" content="light">

    <title>@yield('title', config('app.name')) — {{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a class="skip-link" href="#main-content">Перейти к содержимому</a>

    <header class="site-header" data-site-header>
        <div class="container header-inner">
            <a class="brand" href="{{ route('home') }}" aria-label="{{ config('app.name') }} — главная">
                <span class="brand-mark" aria-hidden="true">
                    <span></span><span></span><span></span>
                </span>
                <span class="brand-copy">
                    <strong>ПРОКСИ.НЕТ</strong>
                    <small>прокси для ваших задач</small>
                </span>
            </a>

            <nav class="desktop-nav" aria-label="Основная навигация">
                <a href="{{ route('home') }}" @class(['is-active' => request()->routeIs('home')])>Главная</a>
                <a href="{{ route('catalog') }}" @class(['is-active' => request()->routeIs('catalog*', 'products.*', 'orders.*')])>Каталог</a>
                <a href="{{ route('instructions') }}" @class(['is-active' => request()->routeIs('instructions')])>Инструкция</a>
                <a href="{{ route('contacts') }}" @class(['is-active' => request()->routeIs('contacts')])>Контакты</a>
                <a href="{{ route('terms') }}" @class(['is-active' => request()->routeIs('terms')])>Правила</a>
            </nav>

            <div class="header-actions">
                @guest
                    <a class="button button-ghost button-small" href="{{ route('login') }}">Войти</a>
                    <a class="button button-primary button-small" href="{{ route('register') }}">Регистрация</a>
                @else
                    <a class="button button-ghost button-small" href="{{ auth()->user()->must_change_password ? route('account.password.edit') : route('account') }}">
                        {{ auth()->user()->must_change_password ? 'Сменить пароль' : 'Личный кабинет' }}
                    </a>
                    <bdi class="header-email" title="{{ auth()->user()->email }}">{{ auth()->user()->email }}</bdi>
                    <form class="logout-form" method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="button button-secondary button-small" type="submit">Выйти</button>
                    </form>
                @endguest
            </div>

            <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="mobile-menu" data-menu-toggle>
                <span class="sr-only">Открыть меню</span>
                <span></span><span></span><span></span>
            </button>
        </div>

        <nav class="mobile-menu" id="mobile-menu" aria-label="Мобильная навигация" data-mobile-menu hidden>
            <div class="container mobile-menu-inner">
                <a href="{{ route('home') }}" @class(['is-active' => request()->routeIs('home')])>Главная</a>
                <a href="{{ route('catalog') }}" @class(['is-active' => request()->routeIs('catalog*', 'products.*', 'orders.*')])>Каталог</a>
                <a href="{{ route('instructions') }}" @class(['is-active' => request()->routeIs('instructions')])>Инструкция</a>
                <a href="{{ route('contacts') }}" @class(['is-active' => request()->routeIs('contacts')])>Контакты</a>
                <a href="{{ route('terms') }}" @class(['is-active' => request()->routeIs('terms')])>Правила</a>
                <a href="{{ route('privacy') }}" @class(['is-active' => request()->routeIs('privacy')])>Конфиденциальность</a>
                @guest
                    <div class="mobile-menu-actions">
                        <a class="button button-ghost" href="{{ route('login') }}">Войти</a>
                        <a class="button button-primary" href="{{ route('register') }}">Регистрация</a>
                    </div>
                @else
                    <div class="mobile-account">
                        <span>Вы вошли как</span>
                        <bdi class="header-email" title="{{ auth()->user()->email }}">{{ auth()->user()->email }}</bdi>
                        <a class="button button-primary" href="{{ auth()->user()->must_change_password ? route('account.password.edit') : route('account') }}">
                            {{ auth()->user()->must_change_password ? 'Сменить пароль' : 'Личный кабинет' }}
                        </a>
                        <form class="logout-form" method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="button button-secondary" type="submit">Выйти</button>
                        </form>
                    </div>
                @endguest
            </div>
        </nav>
    </header>

    <main id="main-content">
        @if (session('status'))
            <div class="container alert-wrap">
                <div class="alert alert-success" role="status">{{ session('status') }}</div>
            </div>
        @endif

        @if (session('success'))
            <div class="container alert-wrap">
                <div class="alert alert-success" role="status">{{ session('success') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="container alert-wrap">
                <div class="alert alert-error" role="alert">{{ session('error') }}</div>
            </div>
        @endif

        @if ($errors->any() && ! $__env->hasSection('suppressGlobalErrors'))
            <div class="container alert-wrap">
                <div class="alert alert-error" role="alert">
                    <strong>Проверьте введенные данные:</strong>
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

    <footer class="site-footer">
        <div class="container footer-grid">
            <div class="footer-brand">
                <a class="brand brand-footer" href="{{ route('home') }}">
                    <span class="brand-mark" aria-hidden="true"><span></span><span></span><span></span></span>
                    <span class="brand-copy"><strong>ПРОКСИ.НЕТ</strong></span>
                </a>
                    <p>Каталог, тестовое оформление заказа и защищённая выдача демонстрационных файлов уже доступны.</p>
            </div>
            <div>
                <h2>Навигация</h2>
                <ul class="footer-links">
                    <li><a href="{{ route('home') }}">Главная</a></li>
                    <li><a href="{{ route('catalog') }}">Каталог</a></li>
                    <li><a href="{{ route('instructions') }}">Инструкция</a></li>
                </ul>
            </div>
            <div>
                <h2>Информация</h2>
                <ul class="footer-links">
                    <li><a href="{{ route('contacts') }}">Контакты</a></li>
                    <li><a href="{{ route('terms') }}">Правила</a></li>
                    <li><a href="{{ route('privacy') }}">Конфиденциальность</a></li>
                </ul>
            </div>
        </div>
        <div class="container footer-bottom">
            <span>© {{ date('Y') }} {{ config('app.name') }}</span>
            <span>Контактные и юридические данные будут добавлены перед запуском.</span>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
