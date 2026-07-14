@extends('layouts.app')

@section('title', $pageTitle)
@section('description', $pageDescription)

@section('content')
    <section class="placeholder-page">
        <div class="container">
            <div class="placeholder-card">
                <span class="placeholder-symbol" aria-hidden="true">•••</span>
                <span class="eyebrow">Следующий этап</span>
                <h1>{{ $pageTitle }}</h1>
                <p>{{ $pageDescription }}</p>
                <a class="button button-primary" href="{{ route('home') }}">Вернуться на главную</a>
            </div>
        </div>
    </section>
@endsection
