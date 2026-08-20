{{-- مكون إضافة منتج طبي --}}
@extends('layouts.app')

@section('content')
<div x-data="addProductForm()" class="p-6 font-sans" dir="rtl">
    <form
        @submit.prevent="handleSubmit"
        class="bg-white p-8 rounded-2xl shadow-sm border border-slate-200 w-full max-w-2xl mx-auto relative overflow-hidden transition-all duration-300"
    >
        <!-- شريط التحميل العلوي -->
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
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <h2 class="text-xl font-bold text-slate-800">إضافة منتج طبي للكتالوج</h2>
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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
            <!-- اختيار القسم -->
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">القسم الطبي</label>
                <select
                    x-model="formData.category_id"
                    required
                    class="w-full p-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500 bg-slate-50 hover:bg-white text-slate-700 transition-colors"
                >
                    <option value="" disabled>-- اختر القسم --</option>
                    <template x-for="category in categories" :key="category.id">
                        <option :value="category.id" x-text="category.name"></option>
                    </template>
                </select>
            </div>

            <!-- اسم المنتج -->
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">اسم المنتج</label>
                <input
                    type="text" placeholder="مثال: كريم مرطب" required
                    x-model="formData.name"
                    class="w-full p-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500 hover:bg-slate-50 transition-colors"
                />
            </div>
        </div>

        <!-- السعر -->
        <div class="mb-5">
            <label class="block text-sm font-bold text-slate-700 mb-1">السعر (بالعملة المحلية)</label>
            <input
                type="number" placeholder="0.00" required step="0.01" min="0"
                x-model="formData.price"
                class="w-full p-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500 hover:bg-slate-50 transition-colors font-mono"
            />
        </div>

        <!-- الوصف -->
        <div class="mb-5">
            <label class="block text-sm font-bold text-slate-700 mb-1">تفاصيل المنتج</label>
            <textarea
                placeholder="وصف المنتج، دواعي الاستعمال، أو الفوائد..."
                x-model="formData.description"
                class="w-full p-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500 h-28 resize-none hover:bg-slate-50 transition-colors"
            ></textarea>
        </div>

        <!-- منطقة رفع الوسائط -->
        <div class="mb-8">
            <label class="block text-sm font-bold text-slate-700 mb-2">صور وفيديوهات المنتج</label>
            <div
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="handleDrop($event)"
                @click="$refs.fileInput.click()"
                :class="isDragging ? 'border-teal-500 bg-teal-50 scale-[1.02]' : 'border-slate-300 bg-slate-50 hover:border-teal-400'"
                class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-dashed rounded-xl transition-all duration-300 relative group cursor-pointer"
            >
                <div class="space-y-2 text-center">
                    <svg
                        :class="isDragging ? 'text-teal-500 -translate-y-1 scale-110' : 'text-slate-400 group-hover:text-teal-400'"
                        class="mx-auto h-12 w-12 transition-all duration-300"
                        stroke="currentColor" fill="none" viewBox="0 0 48 48"
                    >
                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <div class="flex flex-col text-sm text-slate-600 justify-center items-center">
                        <span class="font-bold text-teal-600">انقر لاختيار الملفات</span>
                        <p class="mt-1">أو قم بالسحب والإفلات هنا</p>
                        <input
                            x-ref="fileInput"
                            type="file" multiple accept="image/*, video/*"
                            @change="handleFileChange"
                            class="hidden"
                        />
                    </div>
                    <p class="text-xs text-slate-400 font-medium">PNG, JPG, MP4 حتى 10MB</p>
                </div>
            </div>

            <!-- قائمة الملفات المحددة -->
            <div
                x-show="mediaFiles.length > 0"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="flex items-center gap-2 mt-3 p-3 bg-slate-50 border border-slate-200 rounded-lg"
                style="display: none;"
            >
                <span class="text-teal-600 bg-teal-100 p-1.5 rounded-md">📁</span>
                <p class="text-sm text-slate-700 font-bold">
                    تم تحديد <span class="text-teal-600" x-text="mediaFiles.length"></span> ملف/ملفات جاهزة للرفع.
                </p>
            </div>
        </div>

        <button
            type="submit"
            :disabled="isLoading"
            class="w-full bg-slate-900 text-white p-3.5 rounded-xl font-bold hover:bg-teal-700 shadow-lg shadow-slate-200 transition-all duration-200 active:scale-95 disabled:opacity-70 disabled:cursor-not-allowed flex justify-center items-center gap-2"
        >
            <svg x-show="isLoading" class="animate-spin h-5 w-5 text-teal-400" fill="none" viewBox="0 0 24 24" style="display: none;">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span x-text="isLoading ? 'جاري الرفع والمعالجة...' : 'حفظ ونشر المنتج'"></span>
        </button>
    </form>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('addProductForm', () => ({
        formData: { category_id: '', name: '', description: '', price: '' },
        mediaFiles: [],
        categories: [],
        isLoading: false,
        status: { type: null, message: '' },
        isDragging: false,

        async init() {
            try {
                const token = localStorage.getItem('merchant_token');
                const response = await fetch('/api/categories', {
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    }
                });
                const data = await response.json();
                this.categories = Array.isArray(data) ? data : (data?.data || []);
            } catch (error) {
                console.error("Error fetching categories:", error);
                this.categories = [];
            }
        },

        handleFileChange(e) {
            if (e.target.files && e.target.files.length > 0) {
                this.mediaFiles = Array.from(e.target.files);
            }
        },

        handleDrop(e) {
            this.isDragging = false;
            if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                this.mediaFiles = Array.from(e.dataTransfer.files);
            }
        },

        async handleSubmit() {
            this.status = { type: null, message: '' };

            if (!this.formData.category_id) {
                this.status = { type: 'error', message: 'يرجى اختيار قسم للمنتج الطبي' };
                return;
            }

            this.isLoading = true;
            const data = new FormData();
            data.append('category_id', this.formData.category_id);
            data.append('name', this.formData.name);
            data.append('description', this.formData.description);
            data.append('price', this.formData.price);

            this.mediaFiles.forEach((file) => {
                data.append('media[]', file);
            });

            try {
                const token = localStorage.getItem('merchant_token');
                const response = await fetch('/api/products', {
                    method: 'POST',
                    body: data,
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    }
                });

                if(!response.ok) {
                    if (response.status === 401) {
                        throw new Error('انتهت جلسة الدخول، يرجى تسجيل الدخول من جديد');
                    }
                    throw new Error('فشل الرفع');
                }

                this.status = { type: 'success', message: 'تم رفع المنتج الطبي مع الوسائط بنجاح!' };
                this.formData = { category_id: '', name: '', description: '', price: '' };
                this.mediaFiles = [];
                if (this.$refs.fileInput) this.$refs.fileInput.value = "";

            } catch (error) {
                console.error("Error uploading product:", error);
                this.status = { type: 'error', message: 'حدث خطأ أثناء رفع المنتج. يرجى المحاولة مرة أخرى.' };
            } finally {
                this.isLoading = false;
            }
        }
    }));
});
</script>
@endsection
