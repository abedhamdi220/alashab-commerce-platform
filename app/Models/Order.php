<?php

namespace App\Models;
use App\Traits\LogsModelActivity;

use App\Traits\BelongsToMerchant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use LogsModelActivity;
    use BelongsToMerchant;

    // تم إزالة merchant_id لتوحيد السلوك مع باقي النماذج التي تستخدم التريت
    protected $fillable = [
        'customer_id',
        'total_price',
        'status',
    ];

    // إضافة الـ Casts المفقودة
    protected $casts = [
        'total_price' => 'decimal:2',
        // 'status' => OrderStatus::class, // يفضل مستقبلاً ربطها بـ Enum
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    // تم إضافة Return Type Hinting للتوافق مع معاييرك في النماذج الأخرى
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
