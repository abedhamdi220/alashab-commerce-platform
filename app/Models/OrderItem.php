<?php

namespace App\Models;
use App\Traits\LogsModelActivity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property int $product_id
 * @property int $quantity
 * @property string $unit_price
 * @property string $total_price
 */
class OrderItem extends Model
{
    use LogsModelActivity;
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
