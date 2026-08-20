<?php

use App\Http\Controllers\AdminWebController;
use App\Http\Controllers\MediaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| وسائط المنتجات العامة
|--------------------------------------------------------------------------
| لا نعتمد على public/storage هنا؛ المتحكم يقرأ الملف من القرص المسجل في
| جدول media ويقصر الاستجابة على collection الخاصة بمنتجات المتجر.
*/
Route::get('/media/{media}', [MediaController::class, 'show'])
    ->whereNumber('media')
    ->name('media.show');

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| مصادقة لوحة التاجر عبر جلسة خادمية
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AdminWebController::class, 'login'])->name('login');
    Route::post('/login', [AdminWebController::class, 'authenticate'])
        ->middleware('throttle:6,1')
        ->name('login.attempt');
});

Route::post('/logout', [AdminWebController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| صفحات لوحة التاجر — الحماية هنا خادمية وليست اعتماداً على JavaScript فقط
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminWebController::class, 'dashboard'])->name('admin.dashboard');
        Route::get('/inbox', [AdminWebController::class, 'inbox'])->name('admin.inbox');
        Route::get('/customers', [AdminWebController::class, 'customers'])->name('admin.customers.index');
        Route::get('/categories', [AdminWebController::class, 'categories'])->name('admin.categories.index');
        Route::get('/categories/create', [AdminWebController::class, 'createCategory'])->name('admin.categories.create');
        Route::get('/products', [AdminWebController::class, 'products'])->name('admin.products.index');
        Route::get('/products/create', [AdminWebController::class, 'createProduct'])->name('admin.products.create');
        Route::get('/engagement', [AdminWebController::class, 'engagement'])->name('admin.engagement.index');
        Route::get('/settings', [AdminWebController::class, 'settings'])->name('admin.settings');
        Route::get('/whatsapp', [AdminWebController::class, 'whatsappConnect'])->name('admin.whatsapp');
    });

    // مسار مختصر متوافق مع التوجيه الحالي بعد تسجيل الدخول.
    Route::get('/dashboard', [AdminWebController::class, 'dashboard'])->name('dashboard');
});
