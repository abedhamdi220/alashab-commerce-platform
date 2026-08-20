
@extends('layouts.app')
@section('content')
<div
    x-data="loginComponent()"
    x-init="mounted = true"
    class="merchant-login-page min-h-screen bg-slate-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans overflow-hidden"
    dir="rtl"
>
    <!-- قسم الترويسة مع حركة الدخول -->
    <div
        x-show="mounted"
        x-transition:enter="transition ease-out duration-600"
        x-transition:enter-start="opacity-0 translate-y-5"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="sm:mx-auto sm:w-full sm:max-w-md"
        style="display: none;"
    >
        <div class="flex justify-center text-teal-600 mb-2">
            <!-- حركة انبثاق الأيقونة -->
            <svg
                x-show="mounted"
                x-transition:enter="transition transform duration-700 delay-100"
                x-transition:enter-start="opacity-0 scale-50"
                x-transition:enter-end="opacity-100 scale-100"
                class="w-12 h-12"
                fill="none" stroke="currentColor" viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 4.5C14.5 4.7 9 7.9 9 14.2c0 3.7 2.7 6.3 6.2 6.3 3.8 0 5.8-3.1 5.4-6.4-.3-2.8-1.4-5.9-1.1-9.6Z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.6 8.1c3.4.7 6.2 3.2 6.7 6.7.4 3-1.6 5.2-4.3 5.2-2.9 0-4.6-2.4-3.8-5.1.5-1.8 1-4.4 1.4-6.8Z"></path>
            </svg>
        </div>
        <h2 class="text-center text-3xl font-extrabold text-slate-800">
            تسجيل دخول التاجر
        </h2>
        <p class="mt-2 text-center text-sm text-slate-500">
            قم بإدارة جميع محادثاتك وطلباتك من مكان واحد
        </p>
    </div>

    <!-- قسم النموذج (الفورم) مع حركة الدخول -->
    <div
        x-show="mounted"
        x-transition:enter="transition ease-out duration-600 delay-100"
        x-transition:enter-start="opacity-0 translate-y-8"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="mt-8 sm:mx-auto sm:w-full sm:max-w-md"
        style="display: none;"
    >
        <div class="merchant-login-card bg-white py-8 px-4 shadow-xl shadow-slate-200/50 sm:rounded-2xl sm:px-10 border border-slate-100">
            <form @submit.prevent="handleLogin" class="space-y-6">

                <!-- عرض الأخطاء -->
                <div
                    x-show="error"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 max-h-0 mb-0"
                    x-transition:enter-end="opacity-100 max-h-24 mb-6"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 max-h-24 mb-6"
                    x-transition:leave-end="opacity-0 max-h-0 mb-0"
                    class="overflow-hidden"
                    style="display: none;"
                >
                    <div class="bg-rose-50 border-r-4 border-rose-500 p-4 rounded-md flex items-center gap-3">
                        <svg class="h-5 w-5 text-rose-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <p class="text-sm text-rose-700" x-text="error"></p>
                    </div>
                </div>

                <div class="group">
                    <label class="block text-sm font-medium text-slate-700 mb-1 group-focus-within:text-teal-600 transition-colors">
                        البريد الإلكتروني
                    </label>
                    <input
                        type="email"
                        required
                        x-model="credentials.email"
                        class="appearance-none block w-full px-3 py-2.5 border border-slate-300 rounded-xl shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 sm:text-sm transition-all duration-300"
                        placeholder="merchant@example.com"
                    />
                </div>

                <div class="group">
                    <label class="block text-sm font-medium text-slate-700 mb-1 group-focus-within:text-teal-600 transition-colors">
                        كلمة المرور
                    </label>
                    <input
                        type="password"
                        required
                        x-model="credentials.password"
                        class="appearance-none block w-full px-3 py-2.5 border border-slate-300 rounded-xl shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 sm:text-sm transition-all duration-300"
                        placeholder="••••••••"
                    />
                </div>

                <!-- زر الإرسال -->
                <button
                    type="submit"
                    :disabled="isLoading"
                    class="w-full flex justify-center items-center py-2.5 px-4 border border-transparent rounded-xl shadow-md text-sm font-bold text-white bg-teal-600 hover:bg-teal-700 hover:-translate-y-0.5 hover:shadow-lg active:scale-95 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 transition-all duration-200 disabled:opacity-70 disabled:cursor-not-allowed"
                >
                    <span x-show="!isLoading">دخول إلى لوحة التحكم</span>

                    <!-- أيقونة التحميل -->
                    <svg
                        x-show="isLoading"
                        class="animate-spin h-5 w-5 text-white"
                        fill="none"
                        viewBox="0 0 24 24"
                        style="display: none;"
                    >
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function loginComponent() {
        return {
            mounted: false,
            credentials: {
                email: '',
                password: ''
            },
            error: '',
            isLoading: false,

            async handleLogin() {
                this.isLoading = true;
                this.error = '';

                try {
                    // الدخول عبر web session؛ حماية صفحات الإدارة تتحقق خادمياً بعدها.
                    const response = await fetch('/login', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        body: JSON.stringify(this.credentials)
                    });

                    const data = await response.json();

                    if (response.ok && data.token) {
                        // توكن قصير العمر لتوافق واجهات API الحالية؛ صفحة الإدارة نفسها محمية بجلسة خادمية.
                        localStorage.setItem('merchant_token', data.token);

                        if (data.user && data.user.id) {
                            localStorage.setItem('merchant_id', data.user.id);
                        }

                        // التوجيه إلى لوحة التحكم
                        window.location.href = '/dashboard';
                    } else {
                        // قراءة رسالة الخطأ من الـ API (مثل 401 أو 422)
                        this.error = data.message || 'بيانات الدخول غير صحيحة، يرجى المحاولة مرة أخرى.';
                    }
                } catch (err) {
                    console.error('Login error:', err);
                    this.error = 'فشل الاتصال بالخادم. يرجى التحقق من اتصالك بالإنترنت.';
                } finally {
                    this.isLoading = false;
                }
            }
        }
    }
</script>
@endsection
