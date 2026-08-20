<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    /**
     * يبني رابط واتساب آمن لإتمام الطلب من سلة الزبون.
     * السعر والاسم وحالة is_discreet لكل عنصر تُقرأ من قاعدة البيانات حصراً —
     * لا نثق بأي سعر أو اسم منتج قادم من الفرونت، ونخفي اسم/تفاصيل أي منتج
     * مصنّف كفئة خاصة عن نص الرسالة نفسه.
     * هذا الـ endpoint كان مستدعى فعلياً من CartDrawer.jsx (POST /api/checkout/build-message)
     * بدون أي route أو controller مقابل له — الزر كان يفشل صامتاً بكل مرة.
     */
    public function buildMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer.name' => 'required|string|max:255',
            'customer.phone' => 'required|string|max:30',
            'customer.address' => 'nullable|string|max:255',
            'customer.note' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')
                    ->where('merchant_id', app('current_merchant_id'))
                    ->where('is_active', true),
            ],
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $customer = $validated['customer'];
        $products = Product::whereIn('id', collect($validated['items'])->pluck('id'))
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $lines = [];
        $subtotal = 0;
        $hasDiscreetItem = false;

        foreach ($validated['items'] as $item) {
            $product = $products->get($item['id']);
                        if (!$product) {
                return \App\Support\ApiResponse::make([
                    'success' => false,
                    'message' => 'أحد المنتجات لم يعد متاحاً في هذا المتجر.',
                ], 422);
            }

            if (!is_null($product->stock_quantity) && $item['quantity'] > $product->stock_quantity) {
                return \App\Support\ApiResponse::make([
                    'success' => false,
                    'message' => 'الكمية المطلوبة غير متاحة لأحد المنتجات. يرجى تحديث السلة.',
                ], 422);
            }

            $lineTotal = $product->price * $item['quantity'];
            $subtotal += $lineTotal;

            if ($product->is_discreet) {
                $hasDiscreetItem = true;
                $lines[] = "- منتج عناية (فئة خاصة) × {$item['quantity']}";
            } else {
                $lines[] = "- {$product->name} × {$item['quantity']} = {$lineTotal}";
            }
        }

        if (empty($lines)) {
            return \App\Support\ApiResponse::make(['success' => false, 'message' => 'لا يوجد منتجات صالحة بالطلب'], 422);
        }

        // نقرأ إعدادات التاجر الذي حُلّ من {merchant} صراحةً، ولا نعتمد على رقم عام.
        $merchantId = (int) app('current_merchant_id');
        $settings = Setting::withoutGlobalScopes()
            ->where('merchant_id', $merchantId)
            ->pluck('value', 'key');
        $currency = $settings->get('currency') ?? 'د.ج';
        $whatsappNumber = $this->normalizeWhatsAppNumber($settings->get('whatsapp_number'));

        if ($whatsappNumber === null) {
            return \App\Support\ApiResponse::make([
                'success' => false,
                'code' => 'merchant_whatsapp_unavailable',
                'message' => 'رقم واتساب هذا المتجر غير مضبوط. يرجى أن يضيف التاجر رقماً دولياً صالحاً من الإعدادات.',
            ], 422);
        }

        $messageParts = [
            'طلب جديد من العشّاب',
            "الاسم: {$customer['name']}",
            "الهاتف: {$customer['phone']}",
        ];

        if (!empty($customer['address'])) {
            $messageParts[] = "العنوان: {$customer['address']}";
        }

        $messageParts[] = "\nالمنتجات:";
        $messageParts = array_merge($messageParts, $lines);
        $messageParts[] = "\nالإجمالي: {$subtotal} {$currency}";

        if (!empty($customer['note'])) {
            $messageParts[] = "\nملاحظة: {$customer['note']}";
        }

        if ($hasDiscreetItem) {
            $messageParts[] = "\n(الطلب يتضمن منتج من فئة خاصة)";
        }

        $text = implode("\n", $messageParts);

        $whatsappUrl = 'https://wa.me/' . $whatsappNumber . '?text=' . rawurlencode($text);

        return \App\Support\ApiResponse::make([
            'success' => true,
            'whatsappUrl' => $whatsappUrl,
            'subtotal' => $subtotal,
        ]);
    }

    private function normalizeWhatsAppNumber(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $value = strtr((string) $value, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
        $number = preg_replace('/\\D+/', '', $value);

        if (str_starts_with($number, '00')) {
            $number = substr($number, 2);
        }

        if (str_starts_with($number, '0')) {
            $countryCode = preg_replace('/\\D+/', '', (string) config('merchant_integrations.default_country_code', '963')) ?: '963';
            $number = $countryCode . ltrim($number, '0');
        }

        return preg_match('/^[1-9]\\d{7,14}$/', $number) ? $number : null;
    }
}
