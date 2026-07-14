@extends('layouts.app')

@section('title', 'Надежные прокси для любых задач')
@section('description', 'Современный сервис прокси с понятным выбором, безопасной оплатой и быстрой выдачей данных.')

@section('content')
    <section class="hero section">
        <div class="container hero-grid">
            <div class="hero-copy">
                <span class="eyebrow"><span></span> Новая версия сервиса</span>
                <h1>Надежные прокси для повседневных и профессиональных задач</h1>
                <p>Понятный выбор, прозрачные сроки и удобное получение доступа. Мы готовим обновленный сервис, сохраняя привычную простоту.</p>
                <div class="hero-actions">
                    <a class="button button-primary button-large" href="{{ route('catalog') }}">
                        Перейти в каталог
                        <span aria-hidden="true">→</span>
                    </a>
                    <a class="button button-secondary button-large" href="{{ route('instructions') }}">Как это работает</a>
                </div>
                <ul class="hero-notes" aria-label="Основные особенности">
                    <li><span aria-hidden="true">✓</span> Выбор под разные задачи</li>
                    <li><span aria-hidden="true">✓</span> Понятные сроки доступа</li>
                    <li><span aria-hidden="true">✓</span> Поддержка пользователей</li>
                </ul>
            </div>

            <div class="hero-visual" aria-hidden="true">
                <div class="signal-orbit signal-orbit-one"></div>
                <div class="signal-orbit signal-orbit-two"></div>
                <div class="proxy-panel">
                    <div class="proxy-panel-top">
                        <span class="status-dot"></span>
                        <span>Сервис готовится к запуску</span>
                        <span class="status-pill">online</span>
                    </div>
                    <div class="proxy-location">
                        <div class="location-icon">P</div>
                        <div><small>Подключение</small><strong>Защищенный прокси</strong></div>
                        <span class="connection-bars"><i></i><i></i><i></i></span>
                    </div>
                    <div class="proxy-stats">
                        <div><small>Протоколы</small><strong>HTTP / SOCKS5</strong></div>
                        <div><small>Управление</small><strong>Личный кабинет</strong></div>
                    </div>
                    <div class="proxy-progress"><span></span></div>
                    <div class="proxy-caption"><span>Безопасное подключение</span><strong>Активно</strong></div>
                </div>
            </div>
        </div>
    </section>

    <section class="section section-muted" aria-labelledby="benefits-title">
        <div class="container">
            <div class="section-heading">
                <span class="eyebrow">Почему удобно</span>
                <h2 id="benefits-title">Всё необходимое — без лишней сложности</h2>
                <p>Новая версия проектируется так, чтобы выбор и получение прокси занимали минимум времени.</p>
            </div>
            <div class="feature-grid">
                <article class="feature-card">
                    <span class="feature-icon">01</span>
                    <h3>Понятный выбор</h3>
                    <p>Категории и параметры помогут быстро подобрать решение под конкретную задачу.</p>
                </article>
                <article class="feature-card">
                    <span class="feature-icon">02</span>
                    <h3>Быстрая выдача</h3>
                    <p>После подтверждения покупки данные будут доступны на сайте и отправлены на email.</p>
                </article>
                <article class="feature-card">
                    <span class="feature-icon">03</span>
                    <h3>Контроль сроков</h3>
                    <p>Срок действия и история покупок будут собраны в одном личном кабинете.</p>
                </article>
                <article class="feature-card">
                    <span class="feature-icon">04</span>
                    <h3>Помощь рядом</h3>
                    <p>Для вопросов по выбору и использованию будет доступен понятный канал поддержки.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="section" aria-labelledby="steps-title">
        <div class="container">
            <div class="section-heading section-heading-left">
                <span class="eyebrow">Три простых шага</span>
                <h2 id="steps-title">Как происходит покупка</h2>
            </div>
            <ol class="steps-grid">
                <li class="step-card">
                    <span class="step-number">1</span>
                    <div><h3>Выберите решение</h3><p>Откройте будущий каталог и задайте нужные параметры.</p></div>
                </li>
                <li class="step-card">
                    <span class="step-number">2</span>
                    <div><h3>Оформите заказ</h3><p>Укажите email, срок использования и удобный способ оплаты.</p></div>
                </li>
                <li class="step-card">
                    <span class="step-number">3</span>
                    <div><h3>Получите доступ</h3><p>Данные появятся в личном кабинете и будут продублированы на email.</p></div>
                </li>
            </ol>
        </div>
    </section>

    <section class="section section-compact">
        <div class="container info-grid">
            <article class="info-card info-card-accent">
                <span class="info-label">Важно знать</span>
                <h2>Выбирайте тип прокси под свою задачу</h2>
                <p>Разные сервисы предъявляют разные требования к протоколу, географии и типу подключения. Перед покупкой ознакомьтесь с инструкцией.</p>
                <a class="text-link" href="{{ route('instructions') }}">Открыть инструкцию <span aria-hidden="true">→</span></a>
            </article>
            <article class="info-card">
                <span class="info-label">Нужна помощь?</span>
                <h2>Подскажем, с чего начать</h2>
                <p>Контактные данные поддержки будут опубликованы после согласования с заказчиком и до запуска сайта.</p>
                <a class="text-link" href="{{ route('contacts') }}">Страница контактов <span aria-hidden="true">→</span></a>
            </article>
        </div>
    </section>
@endsection
