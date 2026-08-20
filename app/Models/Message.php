<?php

namespace App\Models;
use App\Traits\LogsModelActivity;

use App\Traits\BelongsToMerchant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Message extends Model implements HasMedia
{
    use LogsModelActivity;
    use InteractsWithMedia, BelongsToMerchant;

    protected $fillable = [
        'customer_id',
        'platform',
        'direction',
        'message_type',
        'body',
        'platform_message_id',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }
}
