<?php

namespace App\Models;
use App\Traits\LogsModelActivity;

use App\Traits\BelongsToMerchant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use LogsModelActivity;
    use BelongsToMerchant;

    protected $fillable = [
        'product_id',
        'store_visitor_id',
        'customer_name',
        'customer_identifier',
        'rating',
        'comment',
        'status',
        'is_approved',
        'rejection_reason',
        'moderated_by',
        'moderated_at',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'rating' => 'integer',
        'moderated_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(StoreVisitor::class, 'store_visitor_id');
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }
}
