<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use LogicException;

class OrderItemController extends Controller
{
    /**
     * إضافة عنصر إلى مسودة طلب فقط. بعد التأكيد يصبح المخزون قد حُجز ولا
     * يجوز تعديل العناصر من دون مسار أعمال صريح يعالج الأثر المخزني.
     */
    public function store(Request $request, Order $order): JsonResponse
    {
        abort_if($order->merchant_id !== $request->user()->id, 403, 'غير مصرح لك بإدارة هذا الطلب.');

        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            return DB::transaction(function () use ($order, $validated) {
                /** @var Order $lockedOrder */
                $lockedOrder = Order::query()
                    ->whereKey($order->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedOrder->status !== 'new') {
                    throw new LogicException('يمكن تعديل عناصر الطلب فقط قبل تأكيده.');
                }

                /** @var Product $product */
                $product = Product::query()
                    ->where('merchant_id', $lockedOrder->merchant_id)
                    ->whereKey($validated['product_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($product->stock_quantity !== null && $product->stock_quantity < $validated['quantity']) {
                    throw new LogicException("الكمية المطلوبة غير متوفرة. المتاح حالياً: {$product->stock_quantity}");
                }

                $unitPriceCents = (int) round(((float) $product->price) * 100);
                $totalPriceCents = $unitPriceCents * $validated['quantity'];
                $unitPrice = number_format($unitPriceCents / 100, 2, '.', '');
                $totalPrice = number_format($totalPriceCents / 100, 2, '.', '');

                $orderItem = OrderItem::create([
                    'order_id' => $lockedOrder->id,
                    'product_id' => $product->id,
                    'quantity' => $validated['quantity'],
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                ]);

                $lockedOrder->increment('total_price', $totalPrice);

                return \App\Support\ApiResponse::make([
                    'message' => 'تمت إضافة المنتج إلى مسودة الطلب.',
                    'order_item' => $orderItem->load('product'),
                    'order' => $lockedOrder->fresh()->load(['customer', 'items.product']),
                ], 201);
            }, 3);
        } catch (LogicException $e) {
            return \App\Support\ApiResponse::make(['message' => $e->getMessage()], 422);
        } catch (Exception $e) {
            report($e);

            return \App\Support\ApiResponse::make(['message' => 'تعذر إضافة المنتج إلى الطلب.'], 422);
        }
    }

    /**
     * حذف عنصر من مسودة طلب فقط مع إعادة احتساب الإجمالي من الخادم.
     */
    public function destroy(Request $request, OrderItem $orderItem): JsonResponse
    {
        $order = $orderItem->order;

        abort_if(!$order || $order->merchant_id !== $request->user()->id, 403, 'غير مصرح لك بحذف هذا العنصر.');

        try {
            return DB::transaction(function () use ($order, $orderItem) {
                /** @var Order $lockedOrder */
                $lockedOrder = Order::query()
                    ->whereKey($order->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedOrder->status !== 'new') {
                    throw new LogicException('يمكن تعديل عناصر الطلب فقط قبل تأكيده.');
                }

                /** @var OrderItem $lockedItem */
                $lockedItem = OrderItem::query()
                    ->whereKey($orderItem->id)
                    ->where('order_id', $lockedOrder->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $itemTotal = $lockedItem->total_price;
                $lockedItem->delete();
                $lockedOrder->decrement('total_price', $itemTotal);

                return \App\Support\ApiResponse::make([
                    'message' => 'تمت إزالة المنتج من مسودة الطلب.',
                    'order' => $lockedOrder->fresh()->load(['customer', 'items.product']),
                ]);
            }, 3);
        } catch (LogicException $e) {
            return \App\Support\ApiResponse::make(['message' => $e->getMessage()], 422);
        } catch (Exception $e) {
            report($e);

            return \App\Support\ApiResponse::make(['message' => 'تعذر إزالة المنتج من الطلب.'], 422);
        }
    }
}
