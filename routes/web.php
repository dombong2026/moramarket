<?php

use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\VendorController as AdminVendorController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Dashboard\WorkspaceController;
use App\Http\Controllers\Media\ProductImageController;
use App\Http\Controllers\Media\MediaLibraryController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\Vendor\OrderController as VendorOrderController;
use App\Http\Controllers\Vendor\ProductController;
use App\Http\Middleware\DetectLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

$localePattern = implode('|', DetectLocale::SUPPORTED);

Route::get('/', fn () => redirect('/'.app()->getLocale()))->name('home');
Route::get('/{locale}', [StorefrontController::class, 'home'])->where('locale', $localePattern)->name('locale.home');
Route::get('/{locale}/products', [StorefrontController::class, 'catalog'])->where('locale', $localePattern)->name('locale.catalog');
Route::get('/{locale}/category/{category:slug}', [StorefrontController::class, 'category'])->where('locale', $localePattern)->name('locale.category');
Route::get('/{locale}/product/{product:slug}', [StorefrontController::class, 'product'])->where('locale', $localePattern)->name('locale.product');
Route::get('/language/{locale}', function (Request $request, string $locale) {
    $request->session()->put('locale', $locale);
    return redirect()->route('locale.home', ['locale' => $locale]);
})->where('locale', $localePattern)->name('language.choose');

Route::get('/listing-image/{product}', [ProductImageController::class, 'show'])->name('media.product');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/{product}', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/{product}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{product}', [CartController::class, 'remove'])->name('cart.remove');

Route::middleware('guest')->group(function () {
    Route::get('/login', [SessionController::class, 'loginForm'])->name('login');
    Route::post('/login', [SessionController::class, 'login'])->middleware('throttle:8,1')->name('login.submit');
    Route::get('/register', [SessionController::class, 'registerForm'])->name('register');
    Route::post('/register', [SessionController::class, 'register'])->middleware('throttle:5,1')->name('register.submit');
    Route::get('/forgot-password', [SessionController::class, 'forgotForm'])->name('password.request');
    Route::post('/forgot-password', [SessionController::class, 'forgot'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/reset-password/{token}', [SessionController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [SessionController::class, 'reset'])->middleware('throttle:5,1')->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [SessionController::class, 'logout'])->name('logout');
    Route::get('/media-preview/{asset}', [MediaLibraryController::class, 'preview'])->name('media.preview');
    Route::get('/checkout', [CheckoutController::class, 'form'])->name('checkout.form');
    Route::post('/checkout', [CheckoutController::class, 'place'])->middleware('throttle:5,1')->name('checkout.place');

    Route::get('/account', [AccountController::class, 'home'])->name('account.home');
    Route::get('/account/orders', [AccountController::class, 'orders'])->name('account.orders');
    Route::patch('/account/profile', [AccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::post('/account/vendor-application', [AccountController::class, 'apply'])->name('account.vendor.apply');

    Route::prefix('dashboard')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/', [WorkspaceController::class, 'admin'])->name('home');
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::patch('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.delete');
        Route::get('/vendors', [AdminVendorController::class, 'index'])->name('vendors');
        Route::patch('/vendors/{vendor}', [AdminVendorController::class, 'changeStatus'])->name('vendors.status');
        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders');
        Route::get('/settings', [SettingsController::class, 'general'])->name('settings');
        Route::patch('/settings', [SettingsController::class, 'saveGeneral'])->name('settings.update');
        Route::get('/appearance', [SettingsController::class, 'appearance'])->name('appearance');
        Route::patch('/appearance', [SettingsController::class, 'saveAppearance'])->name('appearance.update');
        Route::get('/system-health', [SettingsController::class, 'health'])->name('health');
        Route::get('/media', [MediaLibraryController::class, 'index'])->name('media');
        Route::post('/media', [MediaLibraryController::class, 'store'])->name('media.upload');
        Route::patch('/media/{asset}', [MediaLibraryController::class, 'update'])->name('media.update');
        Route::delete('/media/{asset}', [MediaLibraryController::class, 'destroy'])->name('media.delete');
    });

    Route::prefix('vendor')->name('vendor.')->middleware('role:vendor')->group(function () {
        Route::get('/', [WorkspaceController::class, 'vendor'])->name('home');
        Route::get('/products', [ProductController::class, 'index'])->name('products');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::patch('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.delete');
        Route::get('/orders', [VendorOrderController::class, 'index'])->name('orders');
        Route::get('/media', [MediaLibraryController::class, 'index'])->name('media');
        Route::post('/media', [MediaLibraryController::class, 'store'])->name('media.upload');
        Route::patch('/media/{asset}', [MediaLibraryController::class, 'update'])->name('media.update');
        Route::delete('/media/{asset}', [MediaLibraryController::class, 'destroy'])->name('media.delete');
        Route::patch('/orders/{order}', [VendorOrderController::class, 'update'])->name('orders.update');
    });
});
