<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderPrepared
{
    use Dispatchable, SerializesModels;

    public Order $order;

    /**
     * إطلاق حدث (Event) عند تغير حالة الطلب إلى "تم التجهيز"[cite: 1].
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }
}
