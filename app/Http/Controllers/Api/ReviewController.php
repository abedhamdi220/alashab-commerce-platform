<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use App\Models\StoreVisitor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReviewController extends Controller
{
    public function index(string $merchant, string $productId): JsonResponse
    {
        $product = $this->findStoreProduct($productId);

        if ($product === null) {
            return $this->productUnavailableResponse($productId);
        }

        $reviews = Review::query()
            ->where('product_id', $product->id)
            ->where('status', 'approved')
            ->latest()
            ->get()
            ->map(fn (Review $review) => $this->publicPayload($review));

        return \App\Support\ApiResponse::make(['data' => $reviews]);
    }

    public function store(Request $request, string $merchant, string $productId): JsonResponse
    {
        $product = $this->findStoreProduct($productId);

        if ($product === null) {
            return $this->productUnavailableResponse($productId);
        }

        abort_unless($product->is_active, 404, 'هذا المنتج غير متاح حالياً.');

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:100'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'min:8', 'max:1000'],
        ]);

        $visitor = StoreVisitor::fromRequest($request);
        $visitor->rememberDisplayName($validated['customer_name']);

        $existingReview = Review::query()
            ->where('product_id', $product->id)
            ->where('customer_identifier', $visitor->visitor_identifier)
            ->first();

        if ($existingReview) {
            return \App\Support\ApiResponse::make([
                'message' => 'لقد أرسلت رأياً لهذا المنتج سابقاً. يمكنك التواصل مع المتجر إذا احتجت إلى تعديله.',
            ], 422);
        }

        $review = $product->reviews()->create([
            'store_visitor_id' => $visitor->id,
            'customer_name' => $validated['customer_name'],
            'customer_identifier' => $visitor->visitor_identifier,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
            'status' => 'pending',
            'is_approved' => false,
        ]);

        return \App\Support\ApiResponse::make([
            'message' => 'تم إرسال رأيك للمراجعة. سيظهر بعد اعتماده من الإدارة.',
            'data' => $this->publicPayload($review),
        ], 201);
    }

    public function adminIndex(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected,all'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        $status = $validated['status'] ?? 'pending';
        $reviews = Review::query()
            ->with([
                'product:id,name',
                'visitor:id,display_name,visitor_identifier,last_seen_at',
                'moderator:id,name',
            ])
            ->when($status !== 'all', fn (Builder $query) => $query->where('status', $status))
            ->latest()
            ->paginate($validated['per_page'] ?? 25);

        return \App\Support\ApiResponse::make([
            'data' => collect($reviews->items())->map(fn (Review $review) => $this->moderationPayload($review))->values(),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    public function pendingReviews(Request $request): JsonResponse
    {
        $request->merge(['status' => 'pending']);

        return $this->adminIndex($request);
    }

    public function approve(Request $request, Review $review): JsonResponse
    {
        abort_if($review->merchant_id !== $request->user()->id, 403, 'غير مصرح لك.');

        $review->update([
            'status' => 'approved',
            'is_approved' => true,
            'rejection_reason' => null,
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
        ]);

        return \App\Support\ApiResponse::make([
            'message' => 'تم اعتماد الرأي وإظهاره في صفحة المنتج.',
        ]);
    }

    public function reject(Request $request, Review $review): JsonResponse
    {
        abort_if($review->merchant_id !== $request->user()->id, 403, 'غير مصرح لك.');

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $review->update([
            'status' => 'rejected',
            'is_approved' => false,
            'rejection_reason' => $validated['reason'] ?? null,
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
        ]);

        return \App\Support\ApiResponse::make([
            'message' => 'تم رفض الرأي وحفظ القرار في السجل الداخلي.',
        ]);
    }

    public function destroy(Request $request, Review $review): JsonResponse
    {
        abort_if($review->merchant_id !== $request->user()->id, 403, 'غير مصرح لك.');

        $review->delete();

        return \App\Support\ApiResponse::make(['message' => 'تم حذف الرأي نهائياً.']);
    }

    private function publicPayload(Review $review): array
    {
        return [
            'id' => $review->id,
            'customer_name' => $review->customer_name,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'created_at' => $review->created_at?->format('Y-m-d'),
        ];
    }

    private function findStoreProduct(string $productId): ?Product
    {
        if (!ctype_digit($productId) || (int) $productId < 1) {
            return null;
        }

        $merchantId = (int) app('current_merchant_id');

        return Product::withoutGlobalScopes()
            ->where('products.id', (int) $productId)
            ->where('products.merchant_id', $merchantId)
            ->whereNull('products.deleted_at')
            ->first();
    }

    private function productUnavailableResponse(string $productId): JsonResponse
    {
        Log::notice('Review request rejected because the product is unavailable in the current store.', [
            'merchant_id' => app('current_merchant_id'),
            'product_id' => $productId,
        ]);

        return \App\Support\ApiResponse::make([
            'message' => 'هذا المنتج لم يعد متاحاً في المتجر.',
            'code' => 'PRODUCT_UNAVAILABLE',
        ], 404);
    }

    private function moderationPayload(Review $review): array
    {
        return [
            'id' => $review->id,
            'customer_name' => $review->customer_name,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'status' => $review->status,
            'rejection_reason' => $review->rejection_reason,
            'created_at' => $review->created_at?->toIso8601String(),
            'moderated_at' => $review->moderated_at?->toIso8601String(),
            'product' => [
                'id' => $review->product?->id,
                'name' => $review->product?->name ?? 'منتج محذوف',
            ],
            'visitor' => [
                'id' => $review->visitor?->id,
                'label' => $review->visitor?->display_label ?? $review->customer_name,
                'last_seen_at' => $review->visitor?->last_seen_at?->toIso8601String(),
            ],
            'moderator_name' => $review->moderator?->name,
        ];
    }
}
