@extends('layouts.admin')

@section('title', 'Пользователи')

@section('content')
    <div class="admin-container admin-page">
        <header class="admin-page-heading">
            <div>
                <span class="admin-eyebrow">Аккаунты</span>
                <h1>Пользователи</h1>
                <p>Просмотр аккаунтов, заказов и типа регистрации.</p>
            </div>
        </header>

        <form class="admin-panel admin-filters admin-filters--users" method="GET" action="{{ route('admin.users.index') }}">
            <div class="form-field">
                <label class="form-label" for="email">Поиск по email</label>
                <input class="form-control" id="email" name="email" type="search" value="{{ $filters['email'] ?? '' }}" placeholder="user@example.com">
            </div>
            <div class="admin-filter-actions">
                <button class="button button-dark" type="submit">Найти</button>
                <a class="button button-secondary" href="{{ route('admin.users.index') }}">Сбросить</a>
            </div>
        </form>

        <section class="admin-panel">
            @if ($users->isEmpty())
                <div class="admin-empty"><h2>Пользователи не найдены</h2><p>Попробуйте изменить поисковый запрос.</p></div>
            @else
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead><tr><th>ID</th><th>Email</th><th>Регистрация</th><th>Email</th><th>Тип</th><th>Статус</th><th>Заказов</th><th>Роль</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr>
                                    <td data-label="ID">{{ $user->getKey() }}</td>
                                    <td data-label="Email"><strong><bdi>{{ $user->email }}</bdi></strong></td>
                                    <td data-label="Регистрация">{{ $user->created_at?->format('d.m.Y') ?? '—' }}</td>
                                    <td data-label="Email">
                                        <span class="status-badge status-badge--{{ $user->hasVerifiedEmail() ? 'success' : 'warning' }}">
                                            {{ $user->hasVerifiedEmail() ? 'Подтверждён' : 'Не подтверждён' }}
                                        </span>
                                    </td>
                                    <td data-label="Тип">{{ $user->is_auto_created ? 'Автоматический' : 'Обычный' }}</td>
                                    <td data-label="Статус">
                                        <span class="status-badge status-badge--{{ $user->status === \App\Models\User::STATUS_ACTIVE ? 'success' : 'danger' }}">
                                            {{ $user->status === \App\Models\User::STATUS_ACTIVE ? 'Активен' : 'Заблокирован' }}
                                        </span>
                                    </td>
                                    <td data-label="Заказов">{{ $user->orders_count }}</td>
                                    <td data-label="Роль">{{ $user->is_admin ? 'Администратор' : 'Пользователь' }}</td>
                                    <td class="admin-table__actions"><a class="button button-secondary button-small" href="{{ route('admin.users.show', $user) }}">Подробнее</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="admin-pagination">{{ $users->links('admin.partials.pagination') }}</div>
            @endif
        </section>
    </div>
@endsection
