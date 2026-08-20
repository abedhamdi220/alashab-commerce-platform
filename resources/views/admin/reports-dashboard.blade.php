@extends('layouts.app')

@section('content')

<div
    x-data="reportsDashboard()"
    x-init="init()"
    class="reports-container p-6 md:p-8 max-w-7xl mx-auto"
    dir="rtl"
>
    <!-- ترويسة اللوحة مع مؤشر المزامنة الحية وحركة الدخول -->
    <div
        x-show="mounted"
        x-transition:enter="transition ease-out duration-500"
        x-transition:enter-start="opacity-0 -translate-x-5"
        x-transition:enter-end="opacity-100 translate-x-0"
        class="merchant-page-intro flex items-center justify-between mb-8"
        style="display: none;"
    >
        <div>
            <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">نظرة عامة على النشاط</h2>
            <p class="text-slate-500 text-sm mt-1">متابعة لحظية لطلبات المتجر ومسار التوصيل</p>
        </div>
        <div class="flex items-center gap-2 bg-white px-4 py-2 rounded-full shadow-sm border border-slate-200">
            <span class="flex h-2.5 w-2.5 relative">
                <!-- حالة الخطأ: نقطة حمراء ثابتة -->
                <span x-show="error" class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500" style="display: none;"></span>

                <!-- حالة الاتصال الناجح: نقطة خضراء تنبض -->
                <template x-if="!error">
                    <span>
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-teal-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-teal-500"></span>
                    </span>
                </template>
            </span>
            <span class="text-xs font-bold text-slate-600 tracking-wide" x-text="error ? 'غير متصل' : 'مزامنة مباشرة'"></span>
        </div>
    </div>

    <!-- رسالة الخطأ إن وجدت -->
    <div
        x-show="error"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-md"
        style="display: none;"
    >
        <p class="text-sm text-red-700" x-text="error"></p>
    </div>

    <!-- شبكة الإحصائيات -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- 1. بطاقة طلبات اليوم -->
        <div
            x-show="mounted"
            x-transition:enter="transition ease-out duration-500 delay-100"
            x-transition:enter-start="opacity-0 translate-y-5"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="merchant-stat-card bg-white p-6 rounded-2xl shadow-sm hover:shadow-lg border border-slate-100 flex items-center justify-between group transition-shadow duration-300 relative overflow-hidden"
            style="display: none;"
        >
            <div class="absolute top-0 left-0 w-1 h-full bg-teal-500 rounded-l-2xl transform origin-left transition-transform duration-300 group-hover:scale-y-110"></div>
            <div>
                <h3 class="text-sm font-bold text-slate-400 mb-2 tracking-wide uppercase">طلبات اليوم</h3>
                <div class="flex items-baseline gap-3">
                    <!-- حالة التحميل (Skeleton) -->
                    <div x-show="isLoading" class="h-10 w-24 bg-slate-200 animate-pulse rounded-lg"></div>

                    <!-- عرض الرقم بعد التحميل -->
                    <p x-show="!isLoading" class="text-5xl font-black text-slate-800 tracking-tighter" x-text="reports.daily_orders" style="display: none;"></p>

                    <span class="text-xs text-teal-700 font-bold bg-teal-100 px-2.5 py-1 rounded-md flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                        جديد
                    </span>
                </div>
            </div>
            <div class="bg-slate-50 text-teal-600 p-4 rounded-2xl group-hover:scale-110 group-hover:bg-teal-50 transition-all duration-300 shadow-sm border border-slate-100">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                </svg>
            </div>
        </div>

        <!-- 2. بطاقة قيد التوصيل -->
        <div
            x-show="mounted"
            x-transition:enter="transition ease-out duration-500 delay-200"
            x-transition:enter-start="opacity-0 translate-y-5"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="merchant-stat-card bg-white p-6 rounded-2xl shadow-sm hover:shadow-lg border border-slate-100 flex items-center justify-between group transition-shadow duration-300 relative overflow-hidden"
            style="display: none;"
        >
            <div class="absolute top-0 left-0 w-1 h-full bg-emerald-500 rounded-l-2xl transform origin-left transition-transform duration-300 group-hover:scale-y-110"></div>
            <div>
                <h3 class="text-sm font-bold text-slate-400 mb-2 tracking-wide uppercase">قيد التوصيل</h3>
                <div class="flex items-baseline gap-3">
                    <!-- حالة التحميل (Skeleton) -->
                    <div x-show="isLoading" class="h-10 w-24 bg-slate-200 animate-pulse rounded-lg"></div>

                    <!-- عرض الرقم بعد التحميل -->
                    <p x-show="!isLoading" class="text-5xl font-black text-slate-800 tracking-tighter" x-text="reports.out_for_delivery" style="display: none;"></p>

                    <span class="text-xs text-emerald-700 font-bold bg-emerald-100 px-2.5 py-1 rounded-md flex items-center gap-1">
                        <span class="relative flex h-2 w-2">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        نشط
                    </span>
                </div>
            </div>
            <div class="bg-slate-50 text-emerald-500 p-4 rounded-2xl group-hover:scale-110 group-hover:bg-emerald-50 transition-all duration-300 shadow-sm border border-slate-100">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path>
                </svg>
            </div>
        </div>

    </div>
</div>

<script>
    function reportsDashboard() {
        return {
            reports: { daily_orders: 0, out_for_delivery: 0 },
            isLoading: true,
            error: null,
            mounted: false,
            abortController: null,

            async fetchReports() {
                this.isLoading = true;
                this.error = null;

                // إلغاء الطلب السابق إن وجد (مثل وظيفة التنظيف في useEffect)
                if (this.abortController) {
                    this.abortController.abort();
                }
                this.abortController = new AbortController();

                try {
                    // جلب التوكن المحفوظ لإرساله مع الطلب للـ API المحمي بـ Sanctum
                    const token = localStorage.getItem('merchant_token');

                    const response = await fetch('{{ url("/api/reports/sales") }}', {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'Authorization': token ? `Bearer ${token}` : ''
                        },
                        signal: this.abortController.signal
                    });

                    if (!response.ok) {
                        throw new Error('فشل في جلب البيانات');
                    }

                    this.reports = await response.json();
                } catch (err) {
                    if (err.name !== 'AbortError') {
                        console.error("Error fetching reports:", err);
                        this.error = "تعذر جلب التقارير الحية. يرجى التحقق من الاتصال.";
                    }
                } finally {
                    this.isLoading = false;
                }
            },

            init() {
                this.fetchReports();

                // تفعيل حالة mounted بعد فترة قصيرة لتشغيل الحركات (Animations)
                setTimeout(() => {
                    this.mounted = true;
                }, 100);
            }
        }
    }
</script>
@endsection
