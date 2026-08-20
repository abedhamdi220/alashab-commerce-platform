<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * يحدد "أي تاجر" لأي طلب عام (بدون تسجيل دخول) عبر جزء {merchant} من الرابط،
 * مثال: /stores/{merchant}/products
 *
 * {merchant} ممكن يكون store_slug (المفضّل للروابط العامة المنشورة) أو رقم
 * الـ id مباشرة (fallback). لو ما لقى تاجر مطابق يرجع 404 صريح بدل ما يكمل
 * صامتاً ويخلي MerchantScope يرجع صفر نتائج بدون تفسير واضح.
 *
 * بعد ما يتحدد التاجر، يعبّي app('current_merchant_id') اللي يعتمد عليه
 * MerchantScope وBelongsToMerchant::creating() لعزل/تعبئة merchant_id
 * تلقائياً على كل استعلام وإنشاء بهذا الطلب.
 */
class ResolveMerchant
{
    public function handle(Request $request, Closure $next): Response
    {
        $identifier = $request->route('merchant');

        $merchant = is_numeric($identifier)
            ? User::find($identifier)
            : User::where('store_slug', $identifier)->first();

        if (!$merchant) {
            abort(404, 'المتجر غير موجود');
        }

        app()->instance('current_merchant_id', $merchant->id);
        app()->instance('current_merchant', $merchant);
        $request->attributes->set('merchant', $merchant);

        return $next($request);
    }
}
