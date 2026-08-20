<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    public function index(): JsonResponse
    {
        $merchantId = (int) app('current_merchant_id');
        $settings = $this->settingsForMerchant($merchantId);

        return \App\Support\ApiResponse::make([
            'success' => true,
            'data' => $this->publicPayload($settings),
        ]);
    }

    public function internal(Request $request): JsonResponse
    {
        $merchant = $request->user();

        return \App\Support\ApiResponse::make([
            'success' => true,
            'data' => $this->integrationPayload($merchant),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $merchant = $request->user();

        $validated = $request->validate([
            'currency' => ['sometimes', 'string', 'max:10'],
            // نقبل الصيغة المحلية أو الدولية، ثم نحوّلها إلى E.164 قبل التخزين.
            'whatsapp_number' => ['sometimes', 'nullable', 'string', 'max:30'],
            'free_shipping_threshold' => ['sometimes', 'numeric', 'min:0'],
            'meta_phone_id' => [
                'sometimes', 'nullable', 'string', 'max:100',
                Rule::unique('users', 'meta_phone_id')->ignore($merchant->id),
            ],
            'meta_page_id' => [
                'sometimes', 'nullable', 'string', 'max:100',
                Rule::unique('users', 'meta_page_id')->ignore($merchant->id),
            ],
            'delivery_driver_number' => ['sometimes', 'nullable', 'string', 'max:20'],
            'whatsapp_access_token' => ['sometimes', 'nullable', 'string', 'max:4096'],
            'messenger_access_token' => ['sometimes', 'nullable', 'string', 'max:4096'],
        ]);

        if (array_key_exists('whatsapp_number', $validated)) {
            $normalizedNumber = $this->normalizeWhatsAppNumber($validated['whatsapp_number']);

            // null هنا يعني أن التاجر اختار مسح الحقل. أما قيمة غير فارغة وغير صالحة فترفض.
            if (filled($validated['whatsapp_number']) && $normalizedNumber === null) {
                throw ValidationException::withMessages([
                    'whatsapp_number' => 'أدخل رقم واتساب صالحاً. يقبل النظام 09XXXXXXXX أو +9639XXXXXXXX أو 9639XXXXXXXX.',
                ]);
            }

            $validated['whatsapp_number'] = $normalizedNumber;
        }

        $integrationKeys = [
            'meta_phone_id',
            'meta_page_id',
            'delivery_driver_number',
            'whatsapp_access_token',
            'messenger_access_token',
        ];

        DB::transaction(function () use ($merchant, $validated, $integrationKeys): void {
            $integrationData = array_intersect_key($validated, array_flip($integrationKeys));

            if ($integrationData !== []) {
                $merchant->fill($integrationData);
                $merchant->save();
            }

            foreach (['currency', 'whatsapp_number', 'free_shipping_threshold'] as $key) {
                if (! array_key_exists($key, $validated)) {
                    continue;
                }

                // لا نعتمد هنا على guard الافتراضي أو Global Scope: كل قراءة وكتابة مرتبطة
                // صراحةً بـ merchant_id للتاجر الذي يملك token لوحة التحكم الحالي.
                // نعيّن merchant_id يدوياً عند الإنشاء لأن Setting لا يضعه ضمن $fillable.
                $setting = Setting::withoutGlobalScopes()
                    ->where('merchant_id', $merchant->id)
                    ->where('key', $key)
                    ->first();

                if ($setting === null) {
                    $setting = new Setting();
                    $setting->merchant_id = $merchant->id;
                    $setting->key = $key;
                }

                $setting->value = $validated[$key];
                $setting->save();
            }
        }, 3);

        $merchant->refresh();
        $settings = $this->settingsForMerchant($merchant->id);

        return \App\Support\ApiResponse::make([
            'success' => true,
            'message' => 'تم حفظ إعدادات التاجر بنجاح.',
            'data' => [
                'public' => $this->publicPayload($settings),
                'integration' => $this->integrationPayload($merchant, $settings),
            ],
        ]);
    }

    /** @return \Illuminate\Support\Collection<string, mixed> */
    private function settingsForMerchant(int $merchantId)
    {
        return Setting::withoutGlobalScopes()
            ->where('merchant_id', $merchantId)
            ->pluck('value', 'key');
    }

    /** @param \Illuminate\Support\Collection<string, mixed> $settings */
    private function publicPayload($settings): array
    {
        return [
            'currency' => $settings->get('currency') ?? 'د.ج',
            'whatsapp_number' => $this->normalizeWhatsAppNumber($settings->get('whatsapp_number')),
            'free_shipping_threshold' => $settings->get('free_shipping_threshold') ?? 15000,
        ];
    }

    /** @param \Illuminate\Support\Collection<string, mixed>|null $settings */
    private function integrationPayload($merchant, $settings = null): array
    {
        $settings ??= $this->settingsForMerchant((int) $merchant->id);

        return [
            // هذا الرقم هو وجهة الطلبات من سلة المتجر، وليس رقم QR أو Meta Phone ID.
            'whatsapp_number' => $this->normalizeWhatsAppNumber($settings->get('whatsapp_number')),
            'meta_phone_id' => $merchant->meta_phone_id,
            'meta_page_id' => $merchant->meta_page_id,
            'delivery_driver_number' => $merchant->delivery_driver_number,
            'has_whatsapp_access_token' => filled($merchant->whatsapp_access_token),
            'has_messenger_access_token' => filled($merchant->messenger_access_token),
        ];
    }

    private function normalizeWhatsAppNumber(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        // يدعم الرقم العربي والإنجليزي والمسافات والشرطات وعلامة +.
        $value = strtr((string) $value, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
        $number = preg_replace('/\D+/', '', $value);

        if (str_starts_with($number, '00')) {
            $number = substr($number, 2);
        }

        // الإعداد الافتراضي لسوريا، ويمكن تغييره من merchant_integrations.default_country_code.
        if (str_starts_with($number, '0')) {
            $countryCode = preg_replace('/\D+/', '', (string) config('merchant_integrations.default_country_code', '963')) ?: '963';
            $number = $countryCode . ltrim($number, '0');
        }

        // wa.me يتطلب رقم E.164 من دون + أو أصفار بادئة.
        return preg_match('/^[1-9]\d{7,14}$/', $number) ? $number : null;
    }
}
