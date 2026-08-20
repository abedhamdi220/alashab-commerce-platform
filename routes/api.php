<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderItemController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\WhatsAppConnectionController;
use App\Http\Controllers\WebhookController;
use App\Http\Middleware\ResolveMerchant;
use App\Http\Middleware\VerifyEvolutionWebhook;
use App\Http\Middleware\VerifyMetaSignature;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;


/*
|--------------------------------------------------------------------------
| مسارات Webhooks (استقبال رسائل خارجية — بدون تسجيل دخول ولا {merchant})
|--------------------------------------------------------------------------
| ✅ ProcessMetaMessageJob اتصلّح: صار يحدد merchant_id صراحة (عبر
| meta_phone_id/meta_page_id لـ Meta، أو اسم الـ instance لـ Evolution)
| ويستخدم forMerchant() عشان يتفادى مشكلة الـ Global Scope داخل الـ Job.
*/

Route::get('/meta/webhook', [WebhookController::class, 'verifyWebhook']);
// تم إضافة الـ Middleware هنا لحماية مسار الاستقبال
Route::post('/meta/webhook', [WebhookController::class, 'handleWebhook'])->middleware(VerifyMetaSignature::class);

// استقبال رسائل واتساب القادمة عبر Evolution API (محمي بتحقق secret بدل توقيع HMAC)
Route::post('/webhook/evolution', [WebhookController::class, 'handleEvolutionWebhook'])
    ->middleware(VerifyEvolutionWebhook::class);

// مسار عام لتسجيل الدخول
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
/*
|--------------------------------------------------------------------------
| واجهة الزبون (عامة - لكل متجر تاجر محدد صراحة عبر {merchant})
|--------------------------------------------------------------------------
| {merchant} = store_slug المتجر (أو رقم الـ id كـ fallback) — يحلّه
| ResolveMerchant middleware، وبعدها MerchantScope يعزل تلقائياً كل
| استعلام Product/Category/Setting/Review على هذا التاجر بس، بدون أي
| تغيير إضافي بمنطق الـ controllers نفسها.
*/
Route::prefix('/stores/{merchant}')
    ->middleware(ResolveMerchant::class)
    ->withoutMiddleware(SubstituteBindings::class)
    ->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/bestsellers', [ProductController::class, 'bestsellers']);
    Route::get('/products/newest', [ProductController::class, 'newest']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/settings', [SettingsController::class, 'index']);

    Route::post('/products/{productId}/reviews', [ReviewController::class, 'store'])
        ->middleware('throttle:10,1');
    Route::get('/products/{productId}/reviews', [ReviewController::class, 'index']);

    Route::get('/favorites', [FavoriteController::class, 'index'])->middleware('throttle:60,1');
    Route::post('/products/{productId}/favorite', [FavoriteController::class, 'store'])->middleware('throttle:30,1');
    Route::delete('/products/{productId}/favorite', [FavoriteController::class, 'destroy'])->middleware('throttle:30,1');

    Route::post('/checkout/build-message', [CheckoutController::class, 'buildMessage'])
        ->middleware('throttle:20,1');
});

/*
|--------------------------------------------------------------------------
| واجهات التاجر (محمية بواسطة Laravel Sanctum)
|--------------------------------------------------------------------------
| يبقى تسجيل الخروج متاحاً لكل توكن موثق حتى لو كان قديماً ولا يحمل صلاحية
| dashboard:access. أما بقية الواجهات فتتطلب الصلاحية صراحة.
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::middleware(['auth:sanctum', CheckAbilities::class.':dashboard:access'])->group(function () {
    // فئات التاجر للإدارة: تشمل المعطلة وتضيف عدد المنتجات المرتبطة.
    Route::get('/categories', [CategoryController::class, 'merchantIndex']);
    Route::post('/categories', [CategoryController::class, 'store'])->middleware('throttle:30,1');
    Route::patch('/categories/{category}', [CategoryController::class, 'update'])->middleware('throttle:30,1');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->middleware('throttle:30,1');
    // [جديد]: عرض كل منتجات التاجر الحالي (فعالة وغير فعالة) لإدارتها — كانت لوحة التحكم
    // بدون أي طريقة لجلب قائمة منتجات التاجر نفسه (index() العامة تفلتر is_active=true فقط
    // فما تصلح لعرض المنتجات المعطّلة كي يُعاد تفعيلها/تعديلها)
    Route::get('/products', [ProductController::class, 'merchantIndex']);
    // إدارة المنتجات (رفع منتجات جديدة بصورها)
    Route::post('/products', [ProductController::class, 'store'])->middleware('throttle:30,1');
    // تعديل وحذف منتج
    Route::patch('/products/{product}', [ProductController::class, 'update'])->middleware('throttle:30,1');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->middleware('throttle:30,1');

    // إرسال رسالة رد من التاجر للعميل
    Route::post('/messages/{customer}/reply', [MessageController::class, 'reply']);
    Route::get('/admin/reviews', [ReviewController::class, 'adminIndex']);
    Route::get('/admin/reviews/pending', [ReviewController::class, 'pendingReviews']);
    Route::patch('/admin/reviews/{review}/approve', [ReviewController::class, 'approve']);
    Route::patch('/admin/reviews/{review}/reject', [ReviewController::class, 'reject']);
    Route::delete('/admin/reviews/{review}', [ReviewController::class, 'destroy']);
    Route::get('/admin/favorites', [FavoriteController::class, 'adminIndex']);
    // إعدادات التاجر
    Route::get('/settings/internal', [SettingsController::class, 'internal']);
    Route::put('/settings', [SettingsController::class, 'update']);

    // ربط واتساب عبر Evolution API (QR Code) — apikey يضل سيرفر-سايد بالكامل
    Route::post('/whatsapp/connect', [WhatsAppConnectionController::class, 'connect']);
    Route::get('/whatsapp/status', [WhatsAppConnectionController::class, 'status']);

    // صندوق الرسائل الموحد وMini-CRM للزبائن
    Route::get('/messages', [MessageController::class, 'index']);
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::get('/customers/{customer}', [CustomerController::class, 'show']);
    Route::patch('/customers/{customer}', [CustomerController::class, 'update'])->middleware('throttle:30,1');

    // الـ Mini-CRM والطلبات
    Route::post('/orders/from-chat', [OrderController::class, 'storeFromChat'])->middleware('throttle:60,1');
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::patch('/orders/{order}/tag', [OrderController::class, 'updateTag']);

    // [جديد]: OrderItemController كان جاهزاً بالكامل (عزل تاجر، فحص مخزون، DB transaction)
    // بدون أي route يوصله فعلياً — إضافة/حذف عناصر الطلب كانت مستحيلة عبر الـ API
    Route::post('/orders/{order}/items', [OrderItemController::class, 'store'])->middleware('throttle:30,1');
    Route::delete('/order-items/{orderItem}', [OrderItemController::class, 'destroy'])->middleware('throttle:30,1');

    // التقارير
    Route::get('/reports/sales', [ReportController::class, 'index']);
});
