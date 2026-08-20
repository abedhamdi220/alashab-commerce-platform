<?php

namespace App\Models;
use App\Traits\LogsModelActivity;

use App\Traits\BelongsToMerchant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use LogsModelActivity;
    use BelongsToMerchant;

    // أضفنا accent_color و care_type: مستخدمين فعلياً بـ
    // CategoryController::store() (بقواعد التحقق) وبـ
    // ProductController::formatProduct() لكن كانا غائبين عن $fillable
    // وعن الجدول أصلاً (شوف ميغريشن 2026_08_06_000001).
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'options',
        'accent_color',
        'care_type',
    ];

    protected $casts = [
        'options' => 'array',
        'is_active' => 'boolean',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    // القسم يحتوي على عدة منتجات
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
