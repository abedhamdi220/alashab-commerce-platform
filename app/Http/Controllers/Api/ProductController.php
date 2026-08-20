<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    /**
     * واجهة الزبون: عرض المنتجات المفعلة للتاجر المحدد.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where('merchant_id', app('current_merchant_id')),
            ],
        ]);

        $query = Product::with(['category', 'media', 'approvedReviews'])
            ->withAvg(['approvedReviews as avg_rating' => function ($q) {
                $q->where('is_approved', true);
            }], 'rating')
            ->withCount(['approvedReviews as reviews_count' => function ($q) {
                $q->where('is_approved', true);
            }])
            ->where('is_active', true);

        if (isset($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }

        $products = $query->get()->map(fn ($product) => $this->formatProduct($product));

        return \App\Support\ApiResponse::make(['data' => $products]);
    }

    /**
     * واجهة الزبون: المنتجات "الأكثر مبيعاً" للتاجر المحدد.
     */
    public function bestsellers(Request $request): JsonResponse
    {
        $products = Product::with(['category', 'media', 'approvedReviews'])
            ->withAvg(['approvedReviews as avg_rating' => function ($q) {
                $q->where('is_approved', true);
            }], 'rating')
            ->withCount(['approvedReviews as reviews_count' => function ($q) {
                $q->where('is_approved', true);
            }])
            ->where('is_active', true)
            ->where('is_bestseller', true)
            ->get()
            ->map(fn ($product) => $this->formatProduct($product));

        return \App\Support\ApiResponse::make(['data' => $products]);
    }

    /**
     * واجهة الزبون: المنتجات الأحدث.
     */
    public function newest(Request $request): JsonResponse
    {
        $products = Product::with(['category', 'media', 'approvedReviews'])
            ->withAvg(['approvedReviews as avg_rating' => function ($q) {
                $q->where('is_approved', true);
            }], 'rating')
            ->withCount(['approvedReviews as reviews_count' => function ($q) {
                $q->where('is_approved', true);
            }])
            ->where('is_active', true)
            ->where('created_at', '>=', now()->subDays(14))
            ->latest()
            ->get()
            ->map(fn ($product) => $this->formatProduct($product));

        return \App\Support\ApiResponse::make(['data' => $products]);
    }

    /**
     * واجهة التاجر: عرض كل منتجاته (فعالة وغير فعالة) لإدارتها من لوحة التحكم.
     * بخلاف index() العامة (تحت /stores/{merchant})، ما فيه فلترة is_active
     * هون — التاجر لازم يشوف حتى المنتجات المعطّلة عشان يقدر يعدّلها أو
     * يعيد تفعيلها. MerchantScope بيعزل النتائج تلقائياً حسب Auth::id().
     */
    public function merchantIndex(Request $request): JsonResponse
    {
        $products = Product::with(['category', 'media', 'approvedReviews'])
            ->withAvg(['approvedReviews as avg_rating' => function ($q) {
                $q->where('is_approved', true);
            }], 'rating')
            ->withCount(['approvedReviews as reviews_count' => function ($q) {
                $q->where('is_approved', true);
            }])
            ->withCount('favorites')
            ->latest()
            ->get()
            ->map(fn ($product) => $this->formatProduct($product));

        return \App\Support\ApiResponse::make(['data' => $products]);
    }

    /**
     * واجهة التاجر: إضافة منتج جديد.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where('merchant_id', $request->user()->id),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'old_price' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|integer|min:0|max:100',
            'is_discreet' => 'boolean',
            'is_bestseller' => 'boolean',
            'is_active' => 'boolean',
            'origin' => 'nullable|string|max:255',
            'extraction_method' => 'nullable|string|max:255',
            'ingredients' => 'nullable|string',
            'usage_instructions' => 'nullable|string',
            'stock_quantity' => 'nullable|integer|min:0',
            'media' => 'nullable|array|max:5',
            'media.*' => 'file|mimes:jpeg,png,mp4,mov|max:5120',
        ]);

        $this->assertPricingIsConsistent($validated);

        $product = Product::create($validated);

        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $file) {
                $product->addMedia($file)->toMediaCollection('product_gallery');
            }
        }

        return \App\Support\ApiResponse::make([
            'message' => 'تم إنشاء المنتج بنجاح',
            'product' => $this->formatProduct($product)
        ], 201);
    }

    /**
     * واجهة التاجر: تعديل منتج موجود ومعالجة تراكم الوسائط.
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        // حماية عزل المستأجر: منع التاجر من تعديل منتجات غيره
        abort_if($product->merchant_id !== $request->user()->id, 403, 'غير مصرح لك بتعديل هذا المنتج.');

        $validated = $request->validate([
            'category_id' => [
                'sometimes',
                'required',
                Rule::exists('categories', 'id')->where('merchant_id', $request->user()->id),
            ],
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'old_price' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|integer|min:0|max:100',
            'is_discreet' => 'boolean',
            'is_bestseller' => 'boolean',
            'is_active' => 'boolean',
            'origin' => 'nullable|string|max:255',
            'extraction_method' => 'nullable|string|max:255',
            'ingredients' => 'nullable|string',
            'usage_instructions' => 'nullable|string',
            'stock_quantity' => 'nullable|integer|min:0',
            'media' => 'nullable|array|max:5',
            'media.*' => 'file|mimes:jpeg,png,mp4,mov|max:5120',
        ]);

        $this->assertPricingIsConsistent(array_replace(
            $product->only(['price', 'old_price', 'discount_percentage']),
            $validated,
        ));

        $product->update($validated);

        if ($request->hasFile('media')) {
            // حل مشكلة الـ Media Bloat بمسح الصور القديمة قبل إضافة الجديدة
            $product->clearMediaCollection('product_gallery');

            foreach ($request->file('media') as $file) {
                $product->addMedia($file)->toMediaCollection('product_gallery');
            }
        }

        // جلب المنتج المحدث مع حساب التقييمات حتى تكون البيانات المرجعة كاملة
        $product = Product::with(['category', 'media', 'approvedReviews'])
            ->withAvg(['approvedReviews as avg_rating' => function ($q) {
                $q->where('is_approved', true);
            }], 'rating')
            ->withCount(['approvedReviews as reviews_count' => function ($q) {
                $q->where('is_approved', true);
            }])
            ->find($product->id);

        return \App\Support\ApiResponse::make([
            'message' => 'تم تحديث المنتج بنجاح',
            'product' => $this->formatProduct($product)
        ]);
    }

    /** @param array<string, mixed> $data */
    private function assertPricingIsConsistent(array $data): void
    {
        $price = (float) ($data['price'] ?? 0);
        $oldPrice = $data['old_price'] ?? null;
        $discount = $data['discount_percentage'] ?? null;

        if ($oldPrice !== null && (float) $oldPrice < $price) {
            throw ValidationException::withMessages([
                'old_price' => ['السعر الأصلي يجب أن يكون أكبر من السعر الحالي أو مساوياً له.'],
            ]);
        }

        if ($discount !== null && $oldPrice === null) {
            throw ValidationException::withMessages([
                'discount_percentage' => ['لا يمكن تحديد نسبة خصم من دون السعر الأصلي.'],
            ]);
        }
    }

    /**
     * واجهة التاجر: حذف منتج.
     */
    public function destroy(Request $request, Product $product): JsonResponse
    {
        abort_if($product->merchant_id !== $request->user()->id, 403, 'غير مصرح لك بحذف هذا المنتج.');

        $product->delete();

        return \App\Support\ApiResponse::make(['message' => 'تم حذف المنتج بنجاح']);
    }

    /**
     * تنسيق بيانات المنتج لواجهة المتجر.
     */
    protected function formatProduct(Product $product): array
    {
        // أخذ المتوسط المحسوب والعدد مباشرة من استعلام SQL بدلاً من المعالجة في الـ Memory
        $avgRating = $product->avg_rating ?? 5.0;
        $reviewsCount = $product->reviews_count ?? 0;

        $images = $product->getMedia('product_gallery')->map(function ($media) {
            return [
                'id' => $media->id,
                // يمر الطلب عبر Laravel؛ فلا يعتمد العرض على وجود public/storage في بيئة النشر.
                'url' => route('media.show', ['media' => $media->getKey()]),
                'mime_type' => $media->mime_type,
                'is_image' => str_starts_with((string) $media->mime_type, 'image/'),
            ];
        })->values();

        if ($images->isEmpty()) {
            $images = collect([[
                'id' => 'placeholder',
                'url' => 'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?auto=format&fit=crop&w=800&q=80',
            ]]);
        }

        // لتجنب الـ N+1 Problem: نعيد مصفوفة المراجعات فارغة إلا إذا تم جلب العلاقة صراحة
        $reviewsArray = [];
        if ($product->relationLoaded('approvedReviews')) {
            $reviewsArray = $product->approvedReviews->map(function ($r) {
                return [
                    'id' => $r->id,
                    'customer_name' => $r->customer_name,
                    'rating' => $r->rating,
                    'comment' => $r->comment,
                    'created_at' => $r->created_at->format('Y-m-d'),
                ];
            })->values();
        }

        return [
            'id' => $product->id,
            'category_id' => $product->category_id,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
                'slug' => $product->category->slug,
                'accent_color' => $product->category->accent_color,
                'care_type' => $product->category->care_type,
            ] : null,
            'name' => $product->name,
            'description' => $product->description,
            'price' => (float) $product->price,
            'old_price' => $product->old_price ? (float) $product->old_price : null,
            'discount_percentage' => $product->discount_percentage,
            'is_discreet' => (bool) $product->is_discreet,
            'is_bestseller' => (bool) $product->is_bestseller,
            'is_active' => (bool) $product->is_active,
            'origin' => $product->origin,
            'extraction_method' => $product->extraction_method,
            'ingredients' => $product->ingredients,
            'usage_instructions' => $product->usage_instructions,
            'in_stock' => is_null($product->stock_quantity) ? true : $product->stock_quantity > 0,
            'stock_quantity' => $product->stock_quantity,
            'rating' => round((float) $avgRating, 1),
            'reviews_count' => (int) $reviewsCount,
            'favorites_count' => (int) ($product->favorites_count ?? 0),
            'reviews' => $reviewsArray, // محمي من مشكلة تحميل النماذج الزائدة
            // الواجهة تستخدم <img>، ولذلك نفضّل أول ملف صورة عند وجود فيديو في المعرض.
            'image' => $images->firstWhere('is_image', true)['url'] ?? $images->first()['url'],
            'image_url' => $images->firstWhere('is_image', true)['url'] ?? $images->first()['url'],
            'images' => $images,
        ];
    }
}
