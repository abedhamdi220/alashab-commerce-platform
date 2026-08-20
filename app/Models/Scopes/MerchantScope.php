<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * يفرض عزل بيانات التاجر تلقائياً على أي موديل يستخدم BelongsToMerchant.
 *
 * مصدر "مين التاجر الحالي" يختلف حسب نوع الطلب:
 * - طلب محمي (auth:sanctum): التاجر هو المستخدم المسجّل دخوله.
 * - طلب عام لواجهة متجر (بدون تسجيل دخول): التاجر لازم يكون محدد صراحة
 *   من الـ route عبر middleware ResolveMerchant اللي يعبّي
 *   app('current_merchant_id').
 *
 * لو ما فيه أي مصدر معروف للتاجر، نرجّع صفر نتائج بدل ما نسرّب بيانات كل
 * التجار مع بعض — هذا هو العزل الفعلي (fail closed مو fail open).
 */
class MerchantScope implements Scope
{
   public function apply(Builder $builder, Model $model): void
{
    $column = $model->getTable() . '.merchant_id';

    // في واجهة المتجر العامة، يكون التاجر المحدد في الرابط هو المرجع دائماً.
    if (app()->bound('current_merchant_id')) {
        $builder->where($column, app('current_merchant_id'));
        return;
    }

    // في لوحة التاجر المحمية، استخدم التاجر المسجّل دخوله.
    if (Auth::check()) {
        $builder->where($column, Auth::id());
        return;
    }

    // لا نعرض أي بيانات إذا لم يتحدد تاجر.
    $builder->whereRaw('1 = 0');
}

}
