<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Product;
use App\Models\StoreVisitor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class FavoriteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $visitor = StoreVisitor::fromRequest($request);

        $favorites = Favorite::query()
            ->where('store_visitor_id', $visitor->id)
            ->latest()
            ->get(['product_id', 'created_at']);

        return \App\Support\ApiResponse::make([
            'data' => $favorites->map(fn (Favorite $favorite) => [
                'product_id' => (int) $favorite->product_id,
                'created_at' => $favorite->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    public function store(Request $request, string $merchant, string $productId): JsonResponse
    {
        $product = $this->findStoreProduct($productId);

        if ($product === null) {
            return $this->productUnavailableResponse($productId);
        }

        abort_unless($product->is_active, 404, 'هذا المنتج غير متاح حالياً.');

        $visitor = StoreVisitor::fromRequest($request);

        Favorite::query()->firstOrCreate([
            'merchant_id' => app('current_merchant_id'),
            'product_id' => $product->id,
            'store_visitor_id' => $visitor->id,
        ]);

        return \App\Support\ApiResponse::make([
            'message' => 'تمت إضافة المنتج إلى المفضلة.',
        ], 201);
    }

    public function destroy(Request $request, string $merchant, string $productId): JsonResponse
    {
        $product = $this->findStoreProduct($productId);

        if ($product === null) {
            return $this->productUnavailableResponse($productId);
        }

        $visitor = StoreVisitor::fromRequest($request);

        Favorite::query()
            ->where('product_id', $product->id)
            ->where('store_visitor_id', $visitor->id)
            ->delete();

        return \App\Support\ApiResponse::make([
            'message' => 'تمت إزالة المنتج من المفضلة.',
        ]);
    }

    public function adminIndex(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => [
                'nullable',
                'integer',
                Rule::exists('products', 'id')->where('merchant_id', $request->user()->id),
            ],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        $favorites = Favorite::query()
            ->with([
                'product:id,name',
                'visitor:id,display_name,visitor_identifier,last_seen_at',
            ])
            ->when($validated['product_id'] ?? null, fn (Builder $query, int $productId) => $query->where('product_id', $productId))
            ->when($validated['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $favoriteQuery) use ($search) {
                    $favoriteQuery
                        ->whereHas('product', fn (Builder $productQuery) => $productQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('visitor', fn (Builder $visitorQuery) => $visitorQuery->where('display_name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate($validated['per_page'] ?? 25);

        return \App\Support\ApiResponse::make([
            'data' => collect($favorites->items())->map(fn (Favorite $favorite) => [
                'id' => $favorite->id,
                'created_at' => $favorite->created_at?->toIso8601String(),
                'product' => [
                    'id' => $favorite->product?->id,
                    'name' => $favorite->product?->name ?? 'منتج محذوف',
                ],
                'visitor' => [
                    'id' => $favorite->visitor?->id,
                    'label' => $favorite->visitor?->display_label ?? 'زائر غير متاح',
                    'last_seen_at' => $favorite->visitor?->last_seen_at?->toIso8601String(),
                ],
            ])->values(),
            'meta' => [
                'current_page' => $favorites->currentPage(),
                'last_page' => $favorites->lastPage(),
                'per_page' => $favorites->perPage(),
                'total' => $favorites->total(),
            ],
        ]);
    }

    private function findStoreProduct(string $productId): ?Product
    {
        if (!ctype_digit($productId) || (int) $productId < 1) {
            return null;
        }

        $merchantId = (int) app('current_merchant_id');

        /*
         * نعطّل النطاقات الضمنية لهذه القراءة فقط، ثم نعيد فرض الشروط المكافئة
         * صراحةً وبأسماء أعمدة مؤهّلة. بذلك يبقى العزل محكماً ولا يتأثر بأي
         * سياق مصادقة أو Global Scope متبقٍ في الطلب العام.
         */
        return Product::withoutGlobalScopes()
            ->where('products.id', (int) $productId)
            ->where('products.merchant_id', $merchantId)
            ->whereNull('products.deleted_at')
            ->first();
    }

    private function productUnavailableResponse(string $productId): JsonResponse
    {
        $merchantId = (int) app('current_merchant_id');
        $product = Product::withoutGlobalScopes()
            ->withTrashed()
            ->find($productId, ['id', 'merchant_id', 'is_active', 'deleted_at']);

        Log::notice('Favorite request rejected because the product is unavailable in the current store.', [
            'expected_merchant_id' => $merchantId,
            'product_id' => $productId,
            'product_exists' => $product !== null,
            'product_merchant_id' => $product?->merchant_id,
            'product_is_active' => $product?->is_active,
            'product_is_trashed' => $product?->trashed() ?? false,
            'reason' => $this->productUnavailableReason($product, $merchantId),
        ]);

        return \App\Support\ApiResponse::make([
            'message' => 'هذا المنتج لم يعد متاحاً في المتجر.',
            'code' => 'PRODUCT_UNAVAILABLE',
        ], 404);
    }

    private function productUnavailableReason(?Product $product, int $merchantId): string
    {
        if ($product === null) {
            return 'product_not_found';
        }

        if ((int) $product->merchant_id !== $merchantId) {
            return 'merchant_mismatch';
        }

        if ($product->trashed()) {
            return 'product_soft_deleted';
        }

        return 'product_not_visible_to_store';
    }
}
