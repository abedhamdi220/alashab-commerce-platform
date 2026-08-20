<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService)
    {
    }

    /**
     * إنشاء مسودة طلب من محادثة. السعر الإجمالي لا يأتي من المتصفح؛
     * يحتسبه الخادم حصراً عند إضافة عناصر الطلب.
     */
    public function storeFromChat(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'platform_sender_id' => 'required|string|max:255',
            'platform' => 'required|in:whatsapp,messenger',
            'name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:30',
        ]);

        try {
            $order = $this->orderService->createOrderFromChat($validatedData);

            return \App\Support\ApiResponse::make([
                'message' => 'تم إنشاء مسودة الطلب. أضف المنتجات قبل التأكيد.',
                'order' => $order,
            ], 201);
        } catch (Exception $e) {
            report($e);

            return \App\Support\ApiResponse::make([
                'message' => 'تعذر إنشاء مسودة الطلب.',
            ], 422);
        }
    }

    /**
     * جلب مسودة/طلب التاجر الحالية بعناصرها. هذا يمنع الواجهة من تخمين
     * الإجمالي أو الاعتماد على active_order_id وحده.
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        if ($order->merchant_id !== $request->user()->id) {
            return \App\Support\ApiResponse::make(['message' => 'غير مصرح لك بالوصول لهذا الطلب.'], 403);
        }

        return \App\Support\ApiResponse::make([
            'order' => $order->load(['customer', 'items.product']),
        ]);
    }

    /**
     * انتقال آمن في دورة حياة الطلب. الاسم أبقي للتوافق مع المسار الحالي.
     */
    public function updateTag(Request $request, Order $order): JsonResponse
    {
        if ($order->merchant_id !== $request->user()->id) {
            return \App\Support\ApiResponse::make(['message' => 'غير مصرح لك بالوصول لهذا الطلب.'], 403);
        }

        $validatedData = $request->validate([
            'tag' => 'required|in:confirmed,prepared,shipped,cancelled',
        ]);

        try {
            $updatedOrder = $this->orderService->transition($order, $validatedData['tag']);

            return \App\Support\ApiResponse::make([
                'message' => 'تم تحديث حالة الطلب بنجاح.',
                'order' => $updatedOrder,
            ]);
        } catch (Exception $e) {
            report($e);

            return \App\Support\ApiResponse::make([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
