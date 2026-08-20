<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    /**
     * عرض عدد الطلبات اليومية والطلبات قيد التوصيل.
     */
    public function index(): JsonResponse
    {
        $today = Carbon::today();

        // عدد الطلبات اليومية
        $dailyOrdersCount = Order::whereDate('created_at', $today)->count();

        // الطلبات قيد التوصيل (التي تم شحنها)[cite: 1]
        $outForDeliveryCount = Order::where('status', 'shipped')->count();

        return \App\Support\ApiResponse::make([
            'daily_orders' => $dailyOrdersCount,
            'out_for_delivery' => $outForDeliveryCount,
        ]);
    }
}
