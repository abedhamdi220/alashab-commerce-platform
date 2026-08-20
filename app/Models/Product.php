<?php

namespace App\Models;
use App\Traits\LogsModelActivity;

use App\Traits\BelongsToMerchant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use LogsModelActivity;
    use InteractsWithMedia, BelongsToMerchant, SoftDeletes;

    // كانت مقتصرة على category_id/name/description/price/is_active فقط،
    // وكل هذي الحقول كانت تُرسل فعلياً من ProductController::store()/update()
    // وتُتجاهل بصمت لأنها مو بالـ $fillable: old_price, discount_percentage,
    // is_discreet, is_bestseller, origin, extraction_method,
    // usage_instructions, ingredients, stock_quantity.
    // ملاحظة: merchant_id مقصود تركه برا هالقائمة — يتعبّى تلقائياً عبر
    // BelongsToMerchant، مو من مدخلات الطلب مباشرة (دفاع إضافي).
    protected $fillable = [
        'category_id',
        'name',
        'description',
        'price',
        'old_price',
        'discount_percentage',
        'is_active',
        'is_discreet',
        'is_bestseller',
        'origin',
        'extraction_method',
        'ingredients',
        'usage_instructions',
        'stock_quantity',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_discreet' => 'boolean',
        'is_bestseller' => 'boolean',
        'price' => 'decimal:2',
        'old_price' => 'decimal:2',
        'discount_percentage' => 'integer',
        'stock_quantity' => 'integer',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('product_gallery')
             // تخزين الوسائط الجديدة على قرص Laravel العام القياسي.
             ->useDisk('public')
             ->acceptsMimeTypes(['image/jpeg', 'image/png', 'video/mp4', 'video/quicktime']);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    // جميع الآراء الخاصة بالمنتج
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    // الآراء المقبولة فقط من قبل الأدمن
    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('status', 'approved');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }
}
