{{-- مكون إضافة قسم (فئة) --}}
@extends('layouts.app')

@section('content')
<div x-data="addCategoryForm()" class="p-6 font-sans" dir="rtl">
    <form
        @submit.prevent="handleSubmit"
        class="bg-white p-8 rounded-2xl shadow-sm border border-slate-200 w-full max-w-3xl mx-auto relative overflow-hidden"
    >
        <!-- شريط التحميل -->
        <div
            x-show="isLoading"
            x-transition:enter="transition ease-linear duration-2000"
            x-transition:enter-start="w-0"
            x-transition:enter-end="w-full"
            class="absolute top-0 right-0 h-1 bg-teal-500 z-10 w-full"
            style="display: none;"
        ></div>

        <div class="flex items-center gap-3 mb-6 border-b border-slate-100 pb-4">
            <div class="bg-teal-50 p-2 rounded-lg text-teal-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
            </div>
            <div>
                <h2 class="text-xl font-bold text-slate-800">إضافة قسم (فئة) جديد</h2>
                <p class="text-sm text-slate-500">قم بتعريف الفئات والخيارات الديناميكية الخاصة بها.</p>
            </div>
        </div>

        <!-- رسائل التنبيه -->
        <div
            x-show="status.message"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 max-h-0 mb-0"
            x-transition:enter-end="opacity-100 max-h-24 mb-6"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 max-h-24 mb-6"
            x-transition:leave-end="opacity-0 max-h-0 mb-0"
            class="overflow-hidden"
            style="display: none;"
        >
            <div :class="status.type === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'" class="p-4 rounded-xl text-sm font-bold flex items-center gap-3 border">
                <span class="text-xl" x-text="status.type === 'success' ? '✅' : '⚠️'"></span>
                <span x-text="status.message"></span>
            </div>
        </div>

        <!-- البيانات الأساسية -->
        <div class="space-y-5 mb-8 bg-slate-50 p-5 rounded-xl border border-slate-100">
            <div class="flex justify-between items-center mb-2">
                <h3 class="font-bold text-slate-700">المعلومات الأساسية</h3>
                <label class="flex items-center cursor-pointer">
                    <div class="relative">
                        <input type="checkbox" class="sr-only" x-model="formData.is_active" />
                        <div :class="formData.is_active ? 'bg-teal-500' : 'bg-slate-300'" class="block w-10 h-6 rounded-full transition-colors"></div>
                        <div :class="formData.is_active ? '-translate-x-4' : 'translate-x-0'" class="dot absolute right-1 top-1 bg-white w-4 h-4 rounded-full transition-transform"></div>
                    </div>
                    <div class="mr-3 text-sm font-medium text-slate-700 ml-2">مُفعل للزوار</div>
                </label>
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">اسم القسم</label>
                <input
                    type="text" placeholder="مثال: العناية بالبشرة، المكملات..." required
                    x-model="formData.name"
                    class="w-full p-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500"
                />
            </div>
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">وصف القسم (اختياري)</label>
                <textarea
                    placeholder="وصف مختصر لمحتوى هذا القسم..."
                    x-model="formData.description"
                    class="w-full p-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500 h-20 resize-none"
                ></textarea>
            </div>
        </div>

        <!-- الخيارات الديناميكية -->
        <div class="mb-8">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-slate-700">خصائص القسم الديناميكية (Options)</h3>
                <button type="button" @click="addOption" class="text-sm bg-teal-100 text-teal-700 px-3 py-1.5 rounded-lg font-bold hover:bg-teal-200 transition-colors flex items-center gap-1">
                    <span>+</span> إضافة خاصية
                </button>
            </div>

            <div x-show="formData.options.length === 0" x-transition class="text-sm text-slate-500 text-center py-4 bg-slate-50 rounded-xl border border-dashed border-slate-300">
                لا توجد خصائص إضافية. المنتجات في هذا القسم لن تطلب تفاصيل إضافية عند الشراء.
            </div>

            <template x-for="(option, optIndex) in formData.options" :key="optIndex">
                <div
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 -translate-y-4 max-h-0"
                    x-transition:enter-end="opacity-100 translate-y-0 max-h-screen"
                    class="mb-4 p-4 border border-slate-200 rounded-xl bg-white shadow-sm relative overflow-hidden"
                >
                    <button type="button" @click="removeOption(optIndex)" class="absolute top-4 left-4 text-rose-400 hover:text-rose-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pr-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">اسم الخاصية</label>
                            <input
                                type="text" placeholder="مثال: الحجم، النوع، اللون" required
                                x-model="option.name"
                                class="w-full p-2.5 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-teal-500 focus:outline-none"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">نوع الإدخال</label>
                            <select
                                x-model="option.type"
                                @change="handleTypeChange(optIndex)"
                                class="w-full p-2.5 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-teal-500 focus:outline-none"
                            >
                                <option value="text">نص حر (Text)</option>
                                <option value="select">قائمة منسدلة (Select)</option>
                                <option value="checkbox">مربع اختيار (Checkbox)</option>
                            </select>
                        </div>
                    </div>

                    <!-- إدارة القيم في حال كان النوع قائمة منسدلة -->
                    <div
                        x-show="option.type === 'select'"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 mt-0 max-h-0"
                        x-transition:enter-end="opacity-100 mt-4 max-h-screen"
                        class="border-t border-slate-100 overflow-hidden pt-4 mt-4"
                        style="display: none;"
                    >
                        <label class="block text-xs font-bold text-slate-500 mb-2">القيم المتاحة (خيارات القائمة)</label>
                        <div class="space-y-2">
                            <template x-for="(val, valIndex) in option.values" :key="valIndex">
                                <div class="flex items-center gap-2">
                                    <input
                                        type="text" :placeholder="`القيمة ${valIndex + 1} (مثال: 50ml, كبير)`" required
                                        x-model="option.values[valIndex]"
                                        class="flex-1 p-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-teal-500 focus:outline-none"
                                    />
                                    <button type="button" @click="removeOptionValue(optIndex, valIndex)" class="p-2 text-slate-400 hover:text-rose-500 bg-slate-50 hover:bg-rose-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                            </template>
                            <button type="button" @click="addOptionValue(optIndex)" class="text-xs text-teal-600 font-bold hover:underline mt-1">
                                + إضافة قيمة أخرى
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <button
            type="submit" :disabled="isLoading"
            class="w-full bg-slate-900 text-white p-3.5 rounded-xl font-bold hover:bg-teal-700 shadow-lg shadow-slate-200 transition-all duration-200 active:scale-95 disabled:opacity-70 flex justify-center items-center gap-2"
        >
            <svg x-show="isLoading" class="animate-spin h-5 w-5 text-teal-400" fill="none" viewBox="0 0 24 24" style="display: none;">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span x-text="isLoading ? 'جاري الحفظ...' : 'حفظ وإنشاء القسم'"></span>
        </button>
    </form>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('addCategoryForm', () => ({
        formData: {
            name: '',
            description: '',
            is_active: true,
            options: []
        },
        isLoading: false,
        status: { type: null, message: '' },

        addOption() {
            this.formData.options.push({ name: '', type: 'text', values: [] });
        },

        removeOption(index) {
            this.formData.options.splice(index, 1);
        },

        handleTypeChange(index) {
            let option = this.formData.options[index];
            if (option.type === 'select' && option.values.length === 0) {
                option.values.push('');
            }
            if (option.type !== 'select') {
                option.values = [];
            }
        },

        addOptionValue(optIndex) {
            this.formData.options[optIndex].values.push('');
        },

        removeOptionValue(optIndex, valIndex) {
            this.formData.options[optIndex].values.splice(valIndex, 1);
        },

        async handleSubmit() {
            this.isLoading = true;
            this.status = { type: null, message: '' };

            try {
                const cleanOptions = this.formData.options
                    .filter(opt => {
                        const optName = opt.name.trim().toLowerCase();
                        return optName !== 'السعر' && optName !== 'price' && optName !== 'الصعر';
                    })
                    .map(opt => ({
                        ...opt,
                        values: opt.type === 'select' ? opt.values.filter(v => v.trim() !== '') : []
                    }));

                const payload = {
                    ...this.formData,
                    options: cleanOptions.length > 0 ? cleanOptions : null
                };

                const token = localStorage.getItem('merchant_token');
                const response = await fetch('/api/categories', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify(payload)
                });

                if (!response.ok) {
                    if (response.status === 401) {
                        throw new Error('انتهت جلسة الدخول، يرجى تسجيل الدخول من جديد');
                    }
                    const errData = await response.json();
                    throw new Error(errData.message || 'حدث خطأ أثناء حفظ القسم. تأكد من صحة البيانات.');
                }

                this.status = { type: 'success', message: 'تم إنشاء القسم وخياراته بنجاح!' };
                this.formData = { name: '', description: '', is_active: true, options: [] };
            } catch (error) {
                console.error("Error creating category:", error);
                this.status = { type: 'error', message: error.message };
            } finally {
                this.isLoading = false;
            }
        }
    }));
});
</script>
@endsection
