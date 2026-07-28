<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProductDurationOptionController as AdminProductDurationOptionController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderSuccessController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProxyDeliveryDownloadController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.home')->name('home');
Route::view('/instructions', 'pages.instructions')->name('instructions');
Route::view('/contacts', 'pages.contacts')->name('contacts');
Route::view('/terms', 'pages.terms')->name('terms');
Route::view('/privacy', 'pages.privacy')->name('privacy');

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'admin'])
    ->group(function (): void {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        Route::resource('categories', AdminCategoryController::class)->except('show');
        Route::patch('/categories/{category}/toggle', [AdminCategoryController::class, 'toggle'])
            ->name('categories.toggle');

        Route::resource('products', AdminProductController::class)
            ->except(['show', 'destroy']);
        Route::patch('/products/{product}/toggle', [AdminProductController::class, 'toggle'])
            ->name('products.toggle');
        Route::post(
            '/products/{product}/duration-options',
            [AdminProductDurationOptionController::class, 'store'],
        )->name('products.duration-options.store');
        Route::put(
            '/products/{product}/duration-options/{duration_option}',
            [AdminProductDurationOptionController::class, 'update'],
        )->name('products.duration-options.update');
        Route::delete(
            '/products/{product}/duration-options/{duration_option}',
            [AdminProductDurationOptionController::class, 'destroy'],
        )->name('products.duration-options.destroy');

        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');

        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    });

Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog');
Route::get('/catalog/{category:slug}', [CatalogController::class, 'show'])->name('catalog.category');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');

Route::middleware(['active', 'password.changed', 'auth.session'])->group(function (): void {
    Route::get('/products/{product:slug}/checkout', [CheckoutController::class, 'create'])
        ->name('products.checkout');
    Route::post('/products/{product:slug}/checkout', [CheckoutController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('products.checkout.store');
    Route::get('/orders/{order:order_number}/success', OrderSuccessController::class)
        ->name('orders.success');
});

Route::view('/purchase', 'pages.coming-soon', [
    'pageTitle' => 'Покупка пока недоступна',
    'pageDescription' => 'Оформление заказа будет подключено на отдельном этапе разработки.',
])->name('purchase.placeholder');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login-endpoint')
        ->name('login.store');

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:password-reset')
        ->name('password.email');

    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:password-update')
        ->name('password.update');
});

Route::middleware(['auth', 'active', 'password.changed', 'auth.session'])->group(function (): void {
    Route::get('/account/change-password', [ChangePasswordController::class, 'edit'])
        ->name('account.password.edit');
    Route::put('/account/change-password', [ChangePasswordController::class, 'update'])
        ->middleware('throttle:password-update')
        ->name('account.password.update');

    Route::get('/account', AccountController::class)->name('account');
    Route::get(
        '/account/purchases/{proxyDelivery}/download',
        ProxyDeliveryDownloadController::class,
    )->name('account.purchases.download');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});
