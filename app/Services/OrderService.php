<?php

namespace App\Services;

use App\Events\OrderPrepared;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Exception;
use Illuminate\Support\Facades\DB;
use LogicException;

class OrderService
{
    private const ALLOWED_TRANSITIONS = [
        'new' => ['confirmed', 'cancelled'],
        'confirmed' => ['prepared', 'cancelled'],
        'prepared' => ['shipped', 'cancelled'],
        'shipped' => [],
        'cancelled' => [],
    ];

    /**
     * @param array{platform_sender_id:string,platform:string,name?:string|null,phone_number?:string|null} $data
     * @throws Exception
     */
    public function createOrderFromChat(array $data, ?int $merchantId = null): Order
    {
        $merchantId ??= auth()->id();

        if (! $merchantId) {
            throw new Exception('تعذر تحديد التاجر لإنشاء الطلب.');
        }

        return DB::transaction(function () use ($data, $merchantId): Order {
            $customer = Customer::forMerchant($merchantId)->firstOrCreate(
                [
                    'platform' => $data['platform'],
                    'platform_sender_id' => $data['platform_sender_id'],
                ],
                [
                    'merchant_id' => $merchantId,
                    'name' => $data['name'] ?? 'عميل محتمل',
                    'phone_number' => $data['phone_number'] ?? null,
                ],
            );

            $order = new Order([
                'customer_id' => $customer->id,
                'total_price' => 0,
                'status' => 'new',
            ]);
            $order->merchant_id = $merchantId;
            $order->save();

            return $order->load('customer');
        }, 3);
    }

    /** @throws Exception */
    public function transition(Order $order, string $targetStatus): Order
    {
        return DB::transaction(function () use ($order, $targetStatus): Order {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $currentStatus = $lockedOrder->status;

            if ($currentStatus === $targetStatus) {
                return $lockedOrder->load(['customer', 'items.product']);
            }

            if (! in_array($targetStatus, self::ALLOWED_TRANSITIONS[$currentStatus] ?? [], true)) {
                throw new LogicException("لا يمكن نقل الطلب من {$currentStatus} إلى {$targetStatus}.");
            }

            if ($targetStatus === 'confirmed') {
                $this->decreaseStockForOrder($lockedOrder);
            }

            if ($targetStatus === 'cancelled' && in_array($currentStatus, ['confirmed', 'prepared'], true)) {
                $this->restoreStockForOrder($lockedOrder);
            }

            $lockedOrder->update(['status' => $targetStatus]);

            if ($targetStatus === 'prepared') {
                $preparedOrder = $lockedOrder->fresh(['customer', 'merchant']);

                DB::afterCommit(function () use ($preparedOrder): void {
                    event(new OrderPrepared($preparedOrder));
                });
            }

            return $lockedOrder->fresh()->load(['customer', 'items.product']);
        }, 3);
    }

    private function decreaseStockForOrder(Order $order): void
    {
        $items = $order->items()->lockForUpdate()->get();

        if ($items->isEmpty()) {
            throw new LogicException('لا يمكن تأكيد طلب لا يحتوي على أي منتجات.');
        }

        foreach ($items as $item) {
            /** @var Product $product */
            $product = Product::query()
                ->where('merchant_id', $order->merchant_id)
                ->whereKey($item->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($product->stock_quantity === null) {
                continue;
            }

            if ($product->stock_quantity < $item->quantity) {
                throw new LogicException("المخزون غير كافٍ لتأكيد المنتج: {$product->name}.");
            }

            $product->decrement('stock_quantity', $item->quantity);
        }
    }

    private function restoreStockForOrder(Order $order): void
    {
        foreach ($order->items()->lockForUpdate()->get() as $item) {
            /** @var Product|null $product */
            $product = Product::query()
                ->where('merchant_id', $order->merchant_id)
                ->whereKey($item->product_id)
                ->lockForUpdate()
                ->first();

            if ($product && $product->stock_quantity !== null) {
                $product->increment('stock_quantity', $item->quantity);
            }
        }
    }
}
