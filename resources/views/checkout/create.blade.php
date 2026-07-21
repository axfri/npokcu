@extends('layouts.app')

@section('title', 'Оформление заказа')
@section('description', e('Оформление заказа на '.$product->name.'.'))
@section('suppressGlobalErrors', true)

@php
    $requestedOptionId = (int) old('duration_option_id', $product->durationOptions->first()?->getKey());
    $selectedOption = $product->durationOptions->firstWhere('id', $requestedOptionId)
        ?? $product->durationOptions->first();
    $testPaymentEnabled = config('payments.test_mode');
@endphp

@section('content')
    <section class="checkout-page">
        <div class="container">
            <nav class="breadcrumbs" aria-label="Хлебные крошки">
                <a href="{{ route('home') }}">Главная</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('catalog') }}">Каталог</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('products.show', $product) }}">{{ $product->name }}</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">Оформление</span>
            </nav>

            <header class="checkout-heading">
                <span class="eyebrow">Безопасное оформление</span>
                <h1>Оформление заказа</h1>
                <p>Выберите срок и проверьте email. Цена будет повторно рассчитана на сервере.</p>
            </header>

            <form class="checkout-grid" method="POST" action="{{ route('products.checkout.store', $product) }}" data-checkout-form>
                @csrf
                <input type="hidden" name="checkout_token" value="{{ $checkoutToken }}">

                <div class="checkout-card">
                    @error('checkout_token')
                        <div class="alert alert-error" role="alert">{{ $message }}</div>
                    @enderror

                    <section class="checkout-section" aria-labelledby="checkout-product-title">
                        <div class="checkout-section__head">
                            <span class="checkout-step">01</span>
                            <div>
                                <span class="eyebrow">Товар</span>
                                <h2 id="checkout-product-title">{{ $product->name }}</h2>
                            </div>
                        </div>
                        <p class="checkout-product-description">
                            {{ $product->short_description ?: 'Параметры выбранного товара будут зафиксированы в заказе.' }}
                        </p>
                    </section>

                    <section class="checkout-section" aria-labelledby="checkout-duration-title">
                        <div class="checkout-section__head">
                            <span class="checkout-step">02</span>
                            <div>
                                <span class="eyebrow">Срок</span>
                                <h2 id="checkout-duration-title">Выберите период</h2>
                            </div>
                        </div>

                        @forelse ($product->durationOptions as $option)
                            @if ($loop->first)
                                <fieldset class="checkout-fieldset">
                                    <legend class="sr-only">Доступные сроки</legend>
                                    <div class="checkout-options">
                            @endif

                            <label class="checkout-option" data-checkout-option>
                                <input
                                    class="checkout-option__input"
                                    type="radio"
                                    name="duration_option_id"
                                    value="{{ $option->getKey() }}"
                                    data-option-label="{{ $option->title }} — {{ $option->duration_days }} дней"
                                    @checked($selectedOption?->getKey() === $option->getKey())
                                    required
                                >
                                <span class="checkout-option__marker" aria-hidden="true"></span>
                                <span class="checkout-option__copy">
                                    <strong>{{ $option->title }}</strong>
                                    <span>{{ $option->duration_days }} дней доступа</span>
                                </span>
                                <x-price-block class="checkout-option__price" data-option-price :amount="$option->price" />
                            </label>

                            @if ($loop->last)
                                    </div>
                                </fieldset>
                            @endif
                        @empty
                            <div class="empty-state empty-state--compact">
                                <h3>Сейчас нет доступных сроков</h3>
                                <p>Вернитесь к товару позже или выберите другое предложение.</p>
                            </div>
                        @endforelse

                        @error('duration_option_id')
                            <p class="form-error" role="alert">{{ $message }}</p>
                        @enderror
                    </section>

                    <section class="checkout-section" aria-labelledby="checkout-contact-title">
                        <div class="checkout-section__head">
                            <span class="checkout-step">03</span>
                            <div>
                                <span class="eyebrow">Контакты</span>
                                <h2 id="checkout-contact-title">Email покупателя</h2>
                            </div>
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="email">Email</label>
                            <input
                                class="form-control"
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email', auth()->user()?->email) }}"
                                autocomplete="email"
                                maxlength="255"
                                aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                                @readonly(auth()->check())
                                required
                            >
                            @auth
                                <p class="form-help">Заказ будет привязан к email текущего аккаунта.</p>
                            @else
                                <p class="form-help">На следующем этапе этот email будет использоваться для выдачи заказа.</p>
                            @endauth
                            @error('email')
                                <p class="form-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                    </section>

                    <section class="checkout-section" aria-labelledby="checkout-payment-title">
                        <div class="checkout-section__head">
                            <span class="checkout-step">04</span>
                            <div>
                                <span class="eyebrow">Оплата</span>
                                <h2 id="checkout-payment-title">Способ оплаты</h2>
                            </div>
                        </div>

                        <label class="checkout-payment-option">
                            <input type="radio" name="payment_method" value="test" @checked($testPaymentEnabled) @disabled(! $testPaymentEnabled) required>
                            <span class="checkout-payment-option__icon" aria-hidden="true">T</span>
                            <span>
                                <strong>Тестовая оплата</strong>
                                <small>Без списания денежных средств</small>
                            </span>
                            <span class="status-badge status-badge--{{ $testPaymentEnabled ? 'success' : 'warning' }}">{{ $testPaymentEnabled ? 'Доступно' : 'Недоступно' }}</span>
                        </label>
                        @error('payment_method')
                            <p class="form-error" role="alert">{{ $message }}</p>
                        @enderror

                        <label class="checkbox-field checkout-terms">
                            <input type="checkbox" name="terms" value="1" @checked(old('terms')) required>
                            <span>Я принимаю <a class="text-link" href="{{ route('terms') }}">правила сервиса</a>.</span>
                        </label>
                        @error('terms')
                            <p class="form-error" role="alert">{{ $message }}</p>
                        @enderror
                    </section>
                </div>

                <aside class="checkout-summary" aria-labelledby="checkout-summary-title">
                    <span class="eyebrow">Ваш заказ</span>
                    <h2 id="checkout-summary-title">Итого</h2>

                    <dl class="checkout-summary__details">
                        <div>
                            <dt>Товар</dt>
                            <dd>{{ $product->name }}</dd>
                        </div>
                        <div>
                            <dt>Срок</dt>
                            <dd data-checkout-duration>{{ $selectedOption?->title ?? 'Не выбран' }}</dd>
                        </div>
                        <div>
                            <dt>Количество</dt>
                            <dd>1</dd>
                        </div>
                    </dl>

                    <div class="checkout-summary__total">
                        <span>К оплате</span>
                        @if ($selectedOption)
                            <x-price-block data-checkout-total :amount="$selectedOption->price" />
                        @else
                            <strong data-checkout-total>—</strong>
                        @endif
                    </div>

                    <button
                        class="button button-primary button-wide"
                        type="submit"
                        data-checkout-submit
                        @disabled($product->durationOptions->isEmpty() || ! $testPaymentEnabled)
                    >
                        Оформить заказ
                    </button>
                    <p class="checkout-summary__note">Цена и срок проверяются по базе данных перед созданием заказа.</p>
                </aside>
            </form>
        </div>
    </section>
@endsection
