<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * قائمة Mini-CRM للتاجر الحالي. تستخدم مؤشرات وقيم فرعية بدلاً من تحميل
     * كامل المحادثات والطلبات لكل صف.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'platform' => ['nullable', 'in:whatsapp,messenger'],
            'has_active_order' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        $activeOrder = Order::query()
            ->select('id')
            ->whereColumn('orders.customer_id', 'customers.id')
            ->whereNotIn('status', ['shipped', 'cancelled'])
            ->latest()
            ->limit(1);

        $customers = Customer::query()
            ->addSelect(['active_order_id' => $activeOrder])
            ->withCount(['orders', 'messages'])
            ->withMax('messages', 'created_at')
            ->when($validated['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $customerQuery) use ($search) {
                    $customerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%")
                        ->orWhere('platform_sender_id', 'like', "%{$search}%");
                });
            })
            ->when($validated['platform'] ?? null, fn (Builder $query, string $platform) => $query->where('platform', $platform))
            ->when(array_key_exists('has_active_order', $validated), function (Builder $query) use ($validated) {
                $hasActiveOrder = filter_var($validated['has_active_order'], FILTER_VALIDATE_BOOLEAN);
                $method = $hasActiveOrder ? 'whereHas' : 'whereDoesntHave';
                $query->{$method}('orders', fn (Builder $orderQuery) => $orderQuery->whereNotIn('status', ['shipped', 'cancelled']));
            })
            ->orderByDesc('messages_max_created_at')
            ->orderByDesc('created_at')
            ->paginate($validated['per_page'] ?? 25);

        $customers->getCollection()->transform(fn (Customer $customer) => $this->summary($customer));

        return \App\Support\ApiResponse::make([
            'data' => $customers->items(),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
            ],
        ]);
    }

    /**
     * ملف العميل: بياناته القابلة للتعديل، آخر 100 رسالة، وآخر 20 طلباً.
     */
    public function show(Request $request, Customer $customer): JsonResponse
    {
        abort_if($customer->merchant_id !== $request->user()->id, 403, 'غير مصرح لك بالوصول إلى هذا الزبون.');

        $customer->load([
            'orders' => fn ($query) => $query->latest()->limit(20)->with('items.product'),
        ])->loadCount(['orders', 'messages'])->loadMax('messages', 'created_at');

        $messages = $customer->messages()
            ->with('media')
            ->latest()
            ->limit(100)
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($message) => [
                'id' => $message->id,
                'direction' => $message->direction,
                'message_type' => $message->message_type,
                'body' => $message->body,
                'created_at' => $message->created_at?->toIso8601String(),
                'media' => $message->getMedia()->map(fn ($media) => [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'type' => $media->mime_type,
                ])->values(),
            ]);

        return \App\Support\ApiResponse::make([
            'data' => [
                'customer' => $this->summary($customer),
                'orders' => $customer->orders->map(fn (Order $order) => $this->orderPayload($order))->values(),
                'messages' => $messages,
            ],
        ]);
    }

    /**
     * يسمح للتاجر بتصحيح بيانات الاتصال التي جُلبت من المنصات، من دون تغيير
     * معرف المرسل أو المنصة لأنهما مفتاحا الربط الخارجي.
     */
    public function update(Request $request, Customer $customer): JsonResponse
    {
        abort_if($customer->merchant_id !== $request->user()->id, 403, 'غير مصرح لك بتعديل هذا الزبون.');

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:30'],
        ]);

        $customer->update($validated);
        $customer->loadCount(['orders', 'messages'])->loadMax('messages', 'created_at');

        return \App\Support\ApiResponse::make([
            'message' => 'تم تحديث بيانات الزبون بنجاح.',
            'data' => $this->summary($customer),
        ]);
    }

    private function summary(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name ?: 'عميل غير مسجل',
            'phone_number' => $customer->phone_number,
            'platform' => $customer->platform,
            'platform_sender_id' => $customer->platform_sender_id,
            'active_order_id' => $customer->active_order_id,
            'orders_count' => (int) ($customer->orders_count ?? 0),
            'messages_count' => (int) ($customer->messages_count ?? 0),
            'last_message_at' => $this->dateValue($customer->messages_max_created_at ?? null),
            'created_at' => $this->dateValue($customer->created_at),
        ];
    }

    private function dateValue(mixed $date): ?string
    {
        if ($date instanceof \DateTimeInterface) {
            return $date->format(\DateTimeInterface::ATOM);
        }

        return $date ? (string) $date : null;
    }

    private function orderPayload(Order $order): array
    {
        return [
            'id' => $order->id,
            'status' => $order->status,
            'total_price' => (float) $order->total_price,
            'created_at' => $this->dateValue($order->created_at),
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name ?? 'منتج محذوف',
                'quantity' => (int) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total_price' => (float) $item->total_price,
            ])->values(),
        ];
    }
}
