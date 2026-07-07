<?php

use App\Http\Controllers\Web\RobotsController;
use App\Http\Controllers\Web\SitemapController;
use App\Http\Controllers\Web\AccountController;
use App\Http\Controllers\Web\CartController;
use App\Http\Controllers\Web\CategoryController;
use App\Http\Controllers\Web\CheckoutController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\LunchMenuController;
use App\Http\Controllers\Web\MenuController;
use App\Http\Controllers\Web\PageController;
use App\Http\Controllers\Web\ProductController;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [RobotsController::class, 'index'])->name('robots');
Route::get('/media/{path}', function (string $path) {
    abort_unless(Storage::disk('public')->exists($path), 404);

    return Storage::disk('public')->response($path);
})->where('path', '.*')->name('media.public');
Route::get('/menu', [MenuController::class, 'index'])->name('menu');
Route::get('/obedno-menyu', [LunchMenuController::class, 'index'])->name('lunch.index');
Route::post('/obedno-menyu/add-selected', [LunchMenuController::class, 'addSelected'])->name('lunch.add-selected');
Route::post('/obedno-menyu/items/{item}/add', [LunchMenuController::class, 'addItem'])->name('lunch.items.add');
Route::get('/category/{slug}', [CategoryController::class, 'show'])->name('category.show');
Route::get('/product/{slug}', [ProductController::class, 'show'])->name('product.show');
Route::get('/cart', [CartController::class, 'index'])->name('cart');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/items/{item}', [CartController::class, 'updateItem'])->name('cart.items.update');
Route::delete('/cart/items/{item}', [CartController::class, 'removeItem'])->name('cart.items.remove');
Route::post('/cart/promo', [CartController::class, 'applyPromo'])->name('cart.promo.apply');
Route::delete('/cart/promo', [CartController::class, 'removePromo'])->name('cart.promo.remove');
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/pages/{slug}', [PageController::class, 'show'])->name('pages.show');

Route::middleware('auth')->prefix('account')->name('account.')->group(function () {
    Route::get('/', [AccountController::class, 'index'])->name('index');
    Route::patch('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
    Route::patch('/password', [AccountController::class, 'updatePassword'])->name('password.update');
    Route::post('/password/reset-link', [AccountController::class, 'sendPasswordResetLink'])->name('password.reset-link');
    Route::get('/orders', [AccountController::class, 'orders'])->name('orders');
    Route::get('/orders/{order}', [AccountController::class, 'orderShow'])->name('orders.show');
    Route::post('/orders/{order}/reorder', [AccountController::class, 'reorder'])->name('orders.reorder');
    Route::get('/addresses', [AccountController::class, 'addresses'])->name('addresses');
    Route::post('/addresses', [AccountController::class, 'storeAddress'])->name('addresses.store');
    Route::patch('/addresses/{address}', [AccountController::class, 'updateAddress'])->name('addresses.update');
    Route::delete('/addresses/{address}', [AccountController::class, 'destroyAddress'])->name('addresses.destroy');
});

require __DIR__.'/auth.php';
