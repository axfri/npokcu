@extends('layouts.app')

@section('title', 'Инструкция по использованию')
@section('description', 'Общая информация о выборе и безопасном использовании прокси.')

@section('content')
    <section class="page-hero">
        <div class="container narrow-container">
            <span class="eyebrow">Справочный раздел</span>
            <h1>Инструкция по использованию прокси</h1>
            <p>Базовые рекомендации перед выбором и подключением. Точные параметры будут доступны вместе с купленным решением.</p>
        </div>
    </section>

    <section class="section content-section">
        <div class="container article-layout">
            <aside class="article-nav" aria-label="Содержание страницы">
                <strong>На этой странице</strong>
                <a href="#before-purchase">Перед покупкой</a>
                <a href="#connection">Подключение</a>
                <a href="#security">Безопасность</a>
                <a href="#support">Если нужна помощь</a>
            </aside>
            <article class="prose">
                <section id="before-purchase">
                    <span class="article-index">01</span>
                    <h2>Перед покупкой</h2>
                    <p>Определите, для какого сервиса нужен прокси, какая география подходит и поддерживает ли ваше приложение HTTP или SOCKS5. Условия конкретного предложения будут указаны в каталоге.</p>
                    <div class="notice notice-info"><strong>Обратите внимание:</strong> срок, объем трафика и ограничения могут отличаться у разных типов прокси.</div>
                </section>
                <section id="connection">
                    <span class="article-index">02</span>
                    <h2>Подключение</h2>
                    <p>После покупки пользователь получит адрес сервера, порт и данные доступа. Их необходимо добавить в настройки браузера, приложения или другого инструмента, который поддерживает прокси.</p>
                    <ol>
                        <li>Скопируйте выданные данные без лишних пробелов.</li>
                        <li>Выберите правильный протокол в настройках приложения.</li>
                        <li>Проверьте подключение и доступность нужного ресурса.</li>
                    </ol>
                </section>
                <section id="security">
                    <span class="article-index">03</span>
                    <h2>Безопасность</h2>
                    <p>Не публикуйте логин и пароль от прокси, не передавайте файл доступа посторонним и не используйте один пароль в других сервисах. Соблюдайте законодательство и правила площадок, с которыми работаете.</p>
                </section>
                <section id="support">
                    <span class="article-index">04</span>
                    <h2>Если нужна помощь</h2>
                    <p>Сохраните номер заказа и описание проблемы. Актуальный канал поддержки будет опубликован на странице контактов перед запуском.</p>
                    <a class="button button-secondary" href="{{ route('contacts') }}">Перейти к контактам</a>
                </section>
            </article>
        </div>
    </section>
@endsection
