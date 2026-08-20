@extends('layouts.app')

@section('content')

<div
    x-data="whatsappConnect()"
    x-init="init(); setTimeout(() => mounted = true, 100)"
    class="p-6 md:p-10 bg-slate-50 min-h-full font-sans"
    dir="rtl"
>
    <div
        x-show="mounted"
        x-transition:enter="transition ease-out duration-500"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        class="max-w-xl mx-auto"
        style="display: none;"
    >
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-slate-800">ربط رقم واتساب</h2>
            <p class="text-slate-500 mt-1 text-sm">اربط حساب واتساب بصندوق الرسائل عبر مسح كود QR. هذا الربط مستقل عن رقم استقبال طلبات السلة.</p>
            <a href="{{ url('/admin/settings') }}" class="mt-3 inline-flex items-center gap-2 text-sm font-bold text-teal-700 hover:text-teal-800 hover:underline underline-offset-4">
                ضبط رقم استقبال طلبات المتجر من الإعدادات
                <span aria-hidden="true">←</span>
            </a>
        </div>

        <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-slate-200">

            <!-- حالة الاتصال -->
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-100">
                <span class="text-sm font-bold text-slate-600">حالة الاتصال</span>
                <span
                    :class="{
                        'bg-emerald-100 text-emerald-700': state === 'open',
                        'bg-amber-100 text-amber-700': state === 'connecting',
                        'bg-slate-100 text-slate-500': state === 'close'
                    }"
                    class="text-xs font-bold px-3 py-1.5 rounded-full flex items-center gap-1.5"
                >
                    <span
                        :class="{
                            'bg-emerald-500': state === 'open',
                            'bg-amber-500': state === 'connecting',
                            'bg-slate-400': state === 'close'
                        }"
                        class="w-2 h-2 rounded-full"
                    ></span>
                    <span x-text="state === 'open' ? 'متصل' : state === 'connecting' ? 'بانتظار المسح' : 'غير متصل'"></span>
                </span>
            </div>

            <!-- رسالة خطأ -->
            <div x-show="error" class="mb-6 bg-rose-50 border border-rose-200 text-rose-700 text-sm p-4 rounded-xl" style="display: none;" x-text="error"></div>

            <!-- حالة متصل بنجاح -->
            <template x-if="state === 'open'">
                <div class="text-center py-8">
                    <div class="text-5xl mb-3">✅</div>
                    <p class="font-bold text-slate-800">رقم الواتساب مربوط ويعمل الآن</p>
                    <p class="text-sm text-slate-500 mt-1">تقدر تستقبل وترسل رسائل مباشرة من صندوق البريد الموحد. رقم استقبال طلبات السلة يُضبط بشكل مستقل من الإعدادات.</p>
                </div>
            </template>

            <!-- عرض QR للمسح -->
            <template x-if="state !== 'open' && qrcode">
                <div class="text-center py-4">
                    <img :src="qrcode" alt="QR Code" class="mx-auto rounded-xl border border-slate-200 shadow-sm w-64 h-64 object-contain" />
                    <p class="text-sm text-slate-500 mt-4">افتح واتساب على هاتف المتجر ← الأجهزة المرتبطة ← ربط جهاز، وامسح الكود.</p>
                </div>
            </template>

            <!-- زر البدء -->
            <template x-if="state !== 'open' && !qrcode">
                <div class="text-center py-6">
                    <button
                        @click="startConnect"
                        :disabled="isLoading"
                        class="bg-slate-900 text-white px-6 py-3 rounded-xl font-bold hover:bg-teal-700 shadow-lg shadow-slate-200 transition-all duration-200 active:scale-95 disabled:opacity-70 disabled:cursor-not-allowed"
                    >
                        <span x-text="isLoading ? 'جاري التجهيز...' : 'ربط واتساب'"></span>
                    </button>
                </div>
            </template>

            <template x-if="state !== 'open' && qrcode">
                <button
                    @click="reset"
                    class="w-full mt-2 text-sm text-slate-500 hover:text-slate-700 font-medium"
                >
                    إعادة توليد الكود
                </button>
            </template>
        </div>
    </div>
</div>

<script>
    function whatsappConnect() {
        return {
            mounted: false,
            state: 'close',
            qrcode: null,
            isLoading: false,
            error: '',
            pollTimer: null,

            init() {
                this.checkStatus();
            },

            authHeaders() {
                const token = localStorage.getItem('merchant_token');
                return {
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token}`
                };
            },

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

            async checkStatus() {
                try {
                    const response = await fetch('{{ url("/api/whatsapp/status") }}', {
                        headers: this.authHeaders()
                    });
                    const result = await this.apiResponse(response);
                    this.state = result.state || 'close';
                    if (this.state === 'open') {
                        this.qrcode = null;
                        this.stopPolling();
                    }
                } catch (e) {
                    console.error('Error checking WhatsApp status:', e);
                }
            },

            async startConnect() {
                this.isLoading = true;
                this.error = '';

                try {
                    const response = await fetch('{{ url("/api/whatsapp/connect") }}', {
                        method: 'POST',
                        headers: this.authHeaders()
                    });
                    const result = await this.apiResponse(response);

                    if (!result.success) {
                        throw new Error(result.message || 'تعذر إنشاء جلسة واتساب');
                    }

                    this.qrcode = result.qrcode;
                    this.state = 'connecting';
                    this.startPolling();
                } catch (e) {
                    console.error('Error connecting WhatsApp:', e);
                    this.error = e.message || 'حدث خطأ أثناء الاتصال بمحرك واتساب';
                } finally {
                    this.isLoading = false;
                }
            },

            startPolling() {
                this.stopPolling();
                this.pollTimer = setInterval(() => this.checkStatus(), 4000);
            },

            stopPolling() {
                if (this.pollTimer) {
                    clearInterval(this.pollTimer);
                    this.pollTimer = null;
                }
            },

            reset() {
                this.qrcode = null;
                this.error = '';
            }
        }
    }
</script>
@endsection
