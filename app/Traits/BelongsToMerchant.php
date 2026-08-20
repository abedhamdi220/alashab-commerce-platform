<?php

namespace App\Traits;

use App\Models\Scopes\MerchantScope;
use Illuminate\Support\Facades\Auth;

/**
 * يربط الموديل بتاجر معيّن ويفرض عزل بياناته تلقائياً (Global Scope) عن
 * بقية التجار — بدل ما يكون merchant_id مجرد حقل توثيقي بدون فرض فعلي،
 * وهذا كان وضعه السابق بهذا المشروع.
 *
 * يشتغل هذا التريت بالتزامن مع:
 * - MerchantScope: يفلتر تلقائياً كل استعلام SELECT/UPDATE/DELETE حسب
 *   merchant_id، فحتى route model binding (زي Product $product) يرجع
 *   404 تلقائياً لو المورد مو تابع للتاجر الحالي.
 * - middleware ResolveMerchant: يحدد التاجر بالطلبات العامة (بدون تسجيل
 *   دخول) عبر جزء {merchant} بالرابط.
 */
trait BelongsToMerchant
{
    protected static function bootBelongsToMerchant(): void
    {
        static::addGlobalScope(new MerchantScope);

        static::creating(function ($model) {
            if (!empty($model->merchant_id)) {
                return;
            }

            if (Auth::check()) {
                $model->merchant_id = Auth::id();
                return;
            }

            if (app()->bound('current_merchant_id')) {
                $model->merchant_id = app('current_merchant_id');
            }

            // ⚠️ لو الإنشاء صار من سياق ما فيه لا تسجيل دخول ولا merchant
            // محدد بالـ route (أهم مثال: webhook من Meta بيوصل مباشرة بدون
            // أي منهما) — التريت ما يقدر يخمّن مين التاجر، ويضل merchant_id
            // فاضي. لازم أي كود يستقبل ويبات خارجية (WebhookController) يحدد
            // التاجر بنفسه صراحة (مثلاً عبر مطابقة meta_phone_id الوارد
            // بالـ webhook مع User::where('meta_phone_id', ...)) قبل ما
            // ينشئ أي Customer/Message/Order.
        });
    }

    /**
     * كسر العزل عمداً عند الحاجة الفعلية فقط (مهام إدارية/كرون تشتغل بدون
     * سياق تاجر واحد محدد) — استخدمها بوعي، ما تستخدمها بكود يتلامس مع
     * مدخلات المستخدم مباشرة.
     */
    public function scopeForMerchant($query, int $merchantId)
    {
        return $query->withoutGlobalScope(MerchantScope::class)->where('merchant_id', $merchantId);
    }

    public function scopeAllMerchants($query)
    {
        return $query->withoutGlobalScope(MerchantScope::class);
    }
}
