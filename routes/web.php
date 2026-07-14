<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.home')->name('home');
Route::view('/instructions', 'pages.instructions')->name('instructions');
Route::view('/contacts', 'pages.contacts')->name('contacts');
Route::view('/terms', 'pages.terms')->name('terms');
Route::view('/privacy', 'pages.privacy')->name('privacy');

Route::view('/catalog', 'pages.coming-soon', [
    'pageTitle' => 'Каталог готовится',
    'pageDescription' => 'Каталог прокси будет доступен на следующем этапе разработки.',
])->name('catalog');

Route::view('/login', 'pages.coming-soon', [
    'pageTitle' => 'Вход пока недоступен',
    'pageDescription' => 'Авторизация будет подключена на отдельном этапе разработки.',
])->name('login');

Route::view('/register', 'pages.coming-soon', [
    'pageTitle' => 'Регистрация пока недоступна',
    'pageDescription' => 'Регистрация будет подключена на отдельном этапе разработки.',
])->name('register');
