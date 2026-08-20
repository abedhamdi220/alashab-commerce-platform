@extends('layouts.app')

@section('content')

<div
    x-data="settingsComponent()"
    x-init="init(); setTimeout(() => mounted = true, 100)"
    class="p-6 md:p-10 bg-slate-50 min-h-full font-sans"
    dir="rtl"
>
    <div
        x-show="mounted"
        x-transition:enter="transition ease-out duration-500"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        class="max-w-3xl mx-auto"
        style="display: none;"
    >
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-slate-800">إعدادات الربط (SaaS)</h2>
            <p class="text-slate-500 mt-1 text-sm">اضبط رقم استقبال طلبات المتجر، ثم إعدادات الربط عبر QR وMeta والتوصيل بشكل منفصل.</p>
        </div>

        <!-- رسالة التنبيه (Alert بديل AnimatePresence) -->
        <div
            x-show="message"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-y-2 max-h-0"
            x-transition:enter-end="opacity-100 translate-y-0 max-h-24"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 max-h-24"
            x-transition:leave-end="opacity-0 -translate-y-2 max-h-0"
            class="overflow-hidden mb-6"
            style="display: none;"
        >
            <div :class="status === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200'" class="p-4 rounded-xl flex items-center gap-3 shadow-sm border">

                <!-- أيقونة النجاح -->
                <svg x-show="status === 'success'" class="w-5 h-5 text-emerald-500 shrink-0 transform transition-transform duration-300" :class="message ? 'scale-100' : 'scale-0'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>

                <!-- أيقونة الخطأ -->
                <svg x-show="status === 'error'" class="w-5 h-5 text-rose-500 shrink-0 transform transition-transform duration-300" :class="message ? 'scale-100' : 'scale-0'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>

                <span class="text-sm font-medium" x-text="message"></span>
            </div>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-6">

            <!-- قسم إعدادات Meta -->
            <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-slate-200 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-center gap-2 mb-6 border-b border-slate-100 pb-4">
                    <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    <h3 class="text-lg font-bold text-slate-800">دمج منصات التواصل (Meta APIs)</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="group">
                        <label class="block text-sm font-bold text-slate-700 mb-1 group-focus-within:text-teal-600 transition-colors">رقم هاتف WhatsApp API (Phone ID)</label>
                        <input
                            type="text" placeholder="مثال: 104829302..."
                            class="w-full p-3 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 font-mono text-sm transition-all duration-300"
                            x-model="settings.meta_phone_id"
                        />
                    </div>

                    <div class="group">
                        <label class="block text-sm font-bold text-slate-700 mb-1 group-focus-within:text-teal-600 transition-colors">معرف صفحة Facebook (Page ID)</label>
                        <input
                            type="text" placeholder="مثال: 839203948..."
                            class="w-full p-3 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 font-mono text-sm transition-all duration-300"
                            x-model="settings.meta_page_id"
                        />
                    </div>
                </div>
            </div>

            <!-- رقم استقبال الطلبات: هذا هو المصدر الذي تستخدمه سلة المتجر لإنشاء رابط wa.me -->
            <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-teal-200 ring-1 ring-teal-50 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-start gap-3 mb-6 border-b border-slate-100 pb-4">
                    <div class="w-10 h-10 shrink-0 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">رقم واتساب استقبال الطلبات</h3>
                        <p class="mt-1 text-sm leading-6 text-slate-500">كل طلب من سلة هذا المتجر ينشئ رابطاً إلى هذا الرقم تحديداً.</p>
                    </div>
                </div>

                <div class="group">
                    <label class="block text-sm font-bold text-slate-700 mb-1 group-focus-within:text-teal-600 transition-colors">رقم واتساب المتجر الدولي</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-teal-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16h6m-9 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </span>
                        <input
                            type="tel" inputmode="tel" autocomplete="tel" maxlength="30" placeholder="مثال: 09XXXXXXXX أو +963 9XX XXX XXX" dir="ltr"
                            class="w-full p-3 pr-10 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 font-mono text-sm transition-all duration-300 text-right"
                            x-model.trim="settings.whatsapp_number"
                        />
                    </div>
                    <p class="mt-2 text-xs leading-5 text-slate-500">يمكنك كتابة 09XXXXXXXX أو الرقم الدولي. بعد الحفظ يتحول تلقائياً إلى الصيغة الدولية المناسبة لرابط الطلب. هذا الرقم لا يساوي Phone ID الخاص بـMeta، ولا رقم مندوب التوصيل، ولا يتطلب مسح QR.</p>
                    <p x-show="settings.whatsapp_number" class="mt-2 text-xs font-mono text-teal-700" style="display: none;">الرقم المحفوظ: +<span x-text="settings.whatsapp_number"></span></p>
                </div>
            </div>

            <!-- قسم إعدادات التوصيل -->
            <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-slate-200 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-center gap-2 mb-6 border-b border-slate-100 pb-4">
                    <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path></svg>
                    <h3 class="text-lg font-bold text-slate-800">إدارة التوصيل</h3>
                </div>

                <div class="group">
                    <label class="block text-sm font-bold text-slate-700 mb-1 group-focus-within:text-teal-600 transition-colors">رقم واتساب مندوب التوصيل</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3">
                            <svg class="w-5 h-5 text-slate-400 group-focus-within:text-teal-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                        </span>
                        <input
                            type="text" placeholder="مثال: +963999999999" dir="ltr"
                            class="w-full p-3 pr-10 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 font-mono text-sm transition-all duration-300 text-right"
                            x-model="settings.delivery_driver_number"
                        />
                    </div>
                </div>
            </div>

            <!-- زر الإرسال -->
            <button
                type="submit" :disabled="isSaving"
                class="w-full flex justify-center items-center gap-2 bg-teal-600 text-white p-3.5 rounded-xl font-bold hover:bg-teal-700 hover:-translate-y-0.5 active:scale-95 hover:shadow-lg transition-all duration-200 disabled:opacity-70 disabled:cursor-not-allowed"
            >
                <!-- أيقونة التحميل -->
                <svg x-show="isSaving" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" style="display: none;">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>

                <!-- أيقونة الحفظ -->
                <svg x-show="!isSaving" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                </svg>

                <span x-text="isSaving ? 'جاري الحفظ...' : 'حفظ الإعدادات'"></span>
            </button>
        </form>
    </div>
</div>

<script>
    function settingsComponent() {
        return {
            mounted: false,
            settings: {
                whatsapp_number: '',
                meta_phone_id: '',
                meta_page_id: '',
                delivery_driver_number: ''
            },
            message: '',
            status: null,
            isSaving: false,

            async apiResponse(response) {
                const body = await response.text();
                let payload = {};

                try {
                    payload = body ? JSON.parse(body) : {};
                } catch {
                    throw new Error(`الخادم أعاد استجابة غير صالحة (HTTP ${response.status}). راجع سجل Laravel.`);
                }

                if (!response.ok) {
                    throw new Error(payload.message || `تعذر إتمام العملية (HTTP ${response.status}).`);
                }

                return payload;
            },

            async init() {
                try {
                    const token = localStorage.getItem('merchant_token');
                    const response = await fetch('{{ url("/api/settings/internal") }}', {
                        headers: {
                            'Accept': 'application/json',
                            'Authorization': `Bearer ${token}`
                        }
                    });
                    const result = await this.apiResponse(response);
                    if (result.data) {
                        this.settings = {
                            whatsapp_number: result.data.whatsapp_number || '',
                            meta_phone_id: result.data.meta_phone_id || '',
                            meta_page_id: result.data.meta_page_id || '',
                            delivery_driver_number: result.data.delivery_driver_number || ''
                        };
                    }
                } catch (e) {
                    console.error('Error fetching settings:', e);
                }
            },

            async handleSubmit() {
                this.message = '';
                this.status = null;
                this.isSaving = true;

                try {
                    const token = localStorage.getItem('merchant_token');

                    const response = await fetch('{{ url("/api/settings") }}', {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'Authorization': `Bearer ${token}`
                        },
                        body: JSON.stringify(this.settings)
                    });

                    const result = await this.apiResponse(response);
                    const saved = result.data?.integration || {};

                    // نعرض القيمة الراجعة من Laravel نفسها؛ بذلك يعرف التاجر أن الرقم حُفظ
                    // وبأي صيغة دولية سيُستخدم في رابط الطلب.
                    this.settings = {
                        ...this.settings,
                        whatsapp_number: saved.whatsapp_number || '',
                        meta_phone_id: saved.meta_phone_id || this.settings.meta_phone_id,
                        meta_page_id: saved.meta_page_id || this.settings.meta_page_id,
                        delivery_driver_number: saved.delivery_driver_number || this.settings.delivery_driver_number
                    };

                    this.status = 'success';
                    this.message = this.settings.whatsapp_number
                        ? `تم حفظ رقم استقبال الطلبات: +${this.settings.whatsapp_number}`
                        : 'تم حفظ الإعدادات. لم يتم ضبط رقم لاستقبال طلبات السلة.';
                } catch (error) {
                    this.status = 'error';
                    this.message = error.message || 'حدث خطأ أثناء حفظ الإعدادات. يرجى التحقق من الاتصال بالخادم.';
                } finally {
                    this.isSaving = false;
                }
            }
        }
    }
</script>
@endsection
