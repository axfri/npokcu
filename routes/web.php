<?php

use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.home')->name('home');
Route::view('/instructions', 'pages.instructions')->name('instructions');
Route::view('/contacts', 'pages.contacts')->name('contacts');
Route::view('/terms', 'pages.terms')->name('terms');
Route::view('/privacy', 'pages.privacy')->name('privacy');

Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog');
Route::get('/catalog/{category:slug}', [CatalogController::class, 'show'])->name('catalog.category');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');

Route::view('/purchase', 'pages.coming-soon', [
    'pageTitle' => 'Покупка пока недоступна',
    'pageDescription' => 'Оформление заказа будет подключено на отдельном этапе разработки.',
])->name('purchase.placeholder');

Route::view('/login', 'pages.coming-soon', [
    'pageTitle' => 'Вход пока недоступен',
    'pageDescription' => 'Авторизация будет подключена на отдельном этапе разработки.',
])->name('login');

Route::view('/register', 'pages.coming-soon', [
    'pageTitle' => 'Регистрация пока недоступна',
    'pageDescription' => 'Регистрация будет подключена на отдельном этапе разработки.',
])->name('register');
