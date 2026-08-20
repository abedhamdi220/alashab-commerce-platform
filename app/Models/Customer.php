<?php

namespace App\Models;
use App\Traits\LogsModelActivity;

use App\Traits\BelongsToMerchant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    use LogsModelActivity;
    use BelongsToMerchant;

    protected $fillable = [
        'name',
        'platform',
        'platform_sender_id',
        'phone_number',
    ];

    protected $appends = ['active_order_id'];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    public function getActiveOrderIdAttribute(): ?int
    {
        // CustomerController::index() يضيف القيمة كـ select subquery لتفادي N+1.
        if (array_key_exists('active_order_id', $this->attributes)) {
            return $this->attributes['active_order_id'] === null ? null : (int) $this->attributes['active_order_id'];
        }

        return $this->orders()
            ->whereNotIn('status', ['shipped', 'cancelled'])
            ->latest()
            ->value('id');
    }
}
