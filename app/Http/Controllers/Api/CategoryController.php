<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * واجهة المتجر: الفئات المفعلة فقط.
     */
    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->select(['id', 'name', 'slug', 'description', 'options', 'accent_color', 'care_type'])
            ->orderBy('name')
            ->get();

        return \App\Support\ApiResponse::make([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * واجهة التاجر: جميع الفئات، بما فيها المعطلة، مع عدد المنتجات المرتبطة.
     */
    public function merchantIndex(): JsonResponse
    {
        $categories = Category::query()
            ->withCount('products')
            ->orderBy('name')
            ->get();

        return \App\Support\ApiResponse::make([
            'success' => true,
            'data' => $categories,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules($request));
        $validated['slug'] = $this->uniqueSlug($validated['name'], $request->user()->id);
        $validated['is_active'] = $request->boolean('is_active', true);

        $category = Category::create($validated);

        return \App\Support\ApiResponse::make([
            'success' => true,
            'message' => 'تم إنشاء الفئة بنجاح.',
            'data' => $category->loadCount('products'),
        ], 201);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        abort_if($category->merchant_id !== $request->user()->id, 403, 'غير مصرح لك بتعديل هذه الفئة.');

        $validated = $request->validate($this->rules($request, $category));

        if (array_key_exists('name', $validated) && $validated['name'] !== $category->name) {
            $validated['slug'] = $this->uniqueSlug($validated['name'], $request->user()->id, $category->id);
        }

        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }

        $category->update($validated);

        return \App\Support\ApiResponse::make([
            'success' => true,
            'message' => 'تم تحديث الفئة بنجاح.',
            'data' => $category->fresh()->loadCount('products'),
        ]);
    }

    public function destroy(Request $request, Category $category): JsonResponse
    {
        abort_if($category->merchant_id !== $request->user()->id, 403, 'غير مصرح لك بحذف هذه الفئة.');

        if ($category->products()->exists()) {
            return \App\Support\ApiResponse::make([
                'message' => 'لا يمكن حذف فئة مرتبطة بمنتجات. عطّلها أو انقل المنتجات إلى فئة أخرى أولاً.',
            ], 422);
        }

        $category->delete();

        return \App\Support\ApiResponse::make([
            'success' => true,
            'message' => 'تم حذف الفئة بنجاح.',
        ]);
    }

    private function rules(Request $request, ?Category $category = null): array
    {
        $uniqueName = Rule::unique('categories', 'name')
            ->where('merchant_id', $request->user()->id);

        if ($category) {
            $uniqueName->ignore($category->id);
        }

        $nameRules = [
            $category ? 'sometimes' : 'required',
            'required',
            'string',
            'max:255',
            $uniqueName,
        ];

        return [
            'name' => $nameRules,
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'accent_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'care_type' => ['nullable', Rule::in(['slim', 'skincare', 'gain', 'intimate', 'beauty'])],
            'options' => ['nullable', 'array'],
            'options.*.name' => ['required_with:options', 'string', 'max:255'],
            'options.*.type' => ['required_with:options', Rule::in(['text', 'select', 'checkbox'])],
            'options.*.values' => ['required_if:options.*.type,select', 'array', 'min:1'],
            'options.*.values.*' => ['string', 'max:255'],
        ];
    }

    private function uniqueSlug(string $name, int $merchantId, ?int $ignoreCategoryId = null): string
    {
        $baseSlug = Str::slug($name, '-') ?: 'category';
        $slug = $baseSlug;
        $counter = 2;

        while (Category::query()
            ->where('merchant_id', $merchantId)
            ->when($ignoreCategoryId, fn ($query) => $query->where('id', '!=', $ignoreCategoryId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
