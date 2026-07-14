@extends('layouts.app')

@section('title', 'Контакты')
@section('description', 'Контактная информация сервиса ПРОКСИ.НЕТ.')

@section('content')
    <section class="page-hero">
        <div class="container narrow-container">
            <span class="eyebrow">Связь с нами</span>
            <h1>Контакты</h1>
            <p>Контактные каналы проходят согласование и будут опубликованы перед запуском сервиса.</p>
        </div>
    </section>

    <section class="section content-section">
        <div class="container contact-grid">
            {{-- TODO: заказчик должен заменить демонстрационные блоки подтвержденными контактами перед запуском. --}}
            <article class="contact-card">
                <span class="contact-icon" aria-hidden="true">@</span>
                <h2>Электронная почта</h2>
                <p>Адрес поддержки будет добавлен после согласования с заказчиком.</p>
                <span class="placeholder-value">support@example.com</span>
            </article>
            <article class="contact-card">
                <span class="contact-icon" aria-hidden="true">↗</span>
                <h2>Мессенджер</h2>
                <p>Официальная ссылка будет опубликована и дополнительно проверена перед запуском.</p>
                <span class="placeholder-value">Ссылка готовится</span>
            </article>
            <article class="contact-card contact-card-wide">
                <div>
                    <span class="info-label">Безопасность</span>
                    <h2>Проверяйте адрес сайта</h2>
                    <p>Используйте только контакты, опубликованные на этой странице. Администрация никогда не просит передавать пароль от аккаунта.</p>
                </div>
                <a class="button button-secondary" href="{{ route('instructions') }}">Читать инструкцию</a>
            </article>
        </div>
    </section>
@endsection
