@extends('layouts.app')

@section('content')
<div x-data="productsManager()" x-init="init()" class="p-6 max-w-7xl mx-auto" dir="rtl">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-black text-slate-800">إدارة المنتجات</h1>
            <p class="text-sm text-slate-500 mt-1">أنشئ وعدّل وعطّل المنتجات، والأسعار، والمخزون، وخصائص العرض.</p>
        </div>
        <button @click="openCreate()" class="bg-teal-600 text-white px-4 py-2.5 rounded-xl font-bold hover:bg-teal-700 shadow-sm">إضافة منتج جديد</button>
    </div>


    <div class="bg-white border border-slate-200 rounded-2xl p-4 mb-5 flex flex-col md:flex-row gap-3">
        <input x-model.debounce.250ms="filters.search" type="search" placeholder="ابحث بالاسم أو الوصف..." class="flex-1 rounded-xl border-slate-300 focus:border-teal-500 focus:ring-teal-500" />
        <select x-model="filters.categoryId" class="rounded-xl border-slate-300">
            <option value="">كل الفئات</option>
            <template x-for="category in categories" :key="category.id">
                <option :value="category.id" x-text="category.name"></option>
            </template>
        </select>
        <select x-model="filters.status" class="rounded-xl border-slate-300">
            <option value="all">كل الحالات</option>
            <option value="active">المفعلة فقط</option>
            <option value="inactive">المعطلة فقط</option>
            <option value="out">نفد المخزون</option>
        </select>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
        <div x-show="isLoading" class="p-6 text-slate-500 text-center">جارٍ تحميل المنتجات...</div>
        <div x-show="!isLoading && filteredProducts.length === 0" class="p-10 text-center text-slate-500">لا توجد منتجات مطابقة للفلاتر الحالية.</div>
        <div class="overflow-x-auto" x-show="!isLoading && filteredProducts.length > 0">
            <table class="w-full text-right text-sm">
                <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="p-4 font-bold">المنتج</th>
                        <th class="p-4 font-bold">الفئة</th>
                        <th class="p-4 font-bold">السعر</th>
                        <th class="p-4 font-bold">المخزون</th>
                        <th class="p-4 font-bold">الحالة</th>
                        <th class="p-4 font-bold">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="product in filteredProducts" :key="product.id">
                        <tr class="hover:bg-slate-50/70">
                            <td class="p-4">
                                <div class="flex items-center gap-3 min-w-56">
                                    <img :src="product.image_url" :alt="product.name" class="w-11 h-11 rounded-lg object-cover bg-slate-100" />
                                    <div>
                                        <p class="font-bold text-slate-800" x-text="product.name"></p>
                                        <p class="text-xs text-slate-500 line-clamp-1" x-text="product.description || 'لا يوجد وصف'"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 text-slate-600" x-text="product.category?.name || 'غير مصنّف'"></td>
                            <td class="p-4">
                                <p class="font-mono font-bold" x-text="formatMoney(product.price)"></p>
                                <p x-show="product.old_price" class="text-xs text-slate-400 line-through font-mono" x-text="formatMoney(product.old_price)"></p>
                            </td>
                            <td class="p-4">
                                <span x-show="product.stock_quantity === null" class="text-slate-500">غير متتبع</span>
                                <span x-show="product.stock_quantity !== null" :class="product.in_stock ? 'text-emerald-700' : 'text-rose-700'" class="font-bold" x-text="product.stock_quantity"></span>
                            </td>
                            <td class="p-4">
                                <button @click="toggleActive(product)" :disabled="savingId === product.id" class="px-2.5 py-1 rounded-lg text-xs font-bold" :class="product.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600'" x-text="product.is_active ? 'مفعّل' : 'معطّل'"></button>
                            </td>
                            <td class="p-4">
                                <div class="flex gap-2">
                                    <button @click="openEdit(product)" class="text-teal-700 hover:text-teal-900 font-bold">تعديل</button>
                                    <button @click="destroyProduct(product)" class="text-rose-600 hover:text-rose-800 font-bold">حذف</button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <div x-show="isFormOpen" x-transition class="fixed inset-0 z-50 bg-slate-950/45 p-4 overflow-y-auto" @keydown.escape.window="closeForm()">
        <div class="bg-white max-w-4xl mx-auto rounded-2xl shadow-2xl my-6 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center sticky top-0 bg-white z-10">
                <div>
                    <h2 class="text-xl font-black text-slate-800" x-text="form.id ? 'تعديل المنتج' : 'إضافة منتج جديد'"></h2>
                    <p class="text-xs leading-5 text-slate-500 mt-1">ابدأ بالبيانات التي يراها العميل، ثم السعر والمخزون. الحقول المعلمة بـ <span class="text-rose-600">*</span> مطلوبة.</p>
                </div>
                <button type="button" @click="closeForm()" :disabled="isSaving" class="text-slate-400 hover:text-slate-700 text-2xl disabled:opacity-50" aria-label="إغلاق نموذج المنتج">×</button>
            </div>

            <form @submit.prevent="saveProduct()" class="p-6 space-y-7">
                <div x-show="formError" role="alert" aria-live="assertive" class="p-3 rounded-lg bg-rose-50 text-rose-700 text-sm" x-text="formError"></div>

                <section class="space-y-4" aria-labelledby="product-basics-title">
                    <div class="border-b border-slate-100 pb-3"><p class="text-xs font-bold tracking-wide text-teal-700">الخطوة 1 من 4</p><h3 id="product-basics-title" class="mt-1 font-bold text-slate-800">المعلومات الأساسية</h3><p class="mt-1 text-xs leading-5 text-slate-500">هذه المعلومات تظهر في بطاقة المنتج وتساعد العميل على التعرف عليه.</p></div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="product-category" class="block text-sm font-bold text-slate-700">الفئة <span class="text-rose-600">*</span></label>
                            <select id="product-category" x-model="form.category_id" required class="mt-1 w-full rounded-xl border-slate-300 focus:border-teal-500 focus:ring-teal-500" aria-describedby="product-category-help"><option value="">اختر الفئة التي ينتمي إليها المنتج</option><template x-for="category in activeCategories" :key="category.id"><option :value="category.id" x-text="category.name"></option></template></select>
                            <p id="product-category-help" class="mt-1.5 text-xs text-slate-500" x-text="activeCategories.length ? 'تظهر الفئات المفعّلة فقط. خصائص الفئة قد تنطبق على المنتج.' : 'لا توجد فئات مفعّلة. أنشئ أو فعّل فئة أولاً.'"></p>
                        </div>
                        <div>
                            <label for="product-name" class="block text-sm font-bold text-slate-700">اسم المنتج <span class="text-rose-600">*</span></label>
                            <input id="product-name" x-model.trim="form.name" required maxlength="255" autocomplete="off" placeholder="مثال: زيت الأرغان الطبيعي" class="mt-1 w-full rounded-xl border-slate-300 focus:border-teal-500 focus:ring-teal-500" aria-describedby="product-name-help" />
                            <p id="product-name-help" class="mt-1.5 text-xs text-slate-500">اكتب اسماً محدداً كما تريد أن يراه العميل في المتجر.</p>
                        </div>
                    </div>
                    <div>
                        <label for="product-description" class="block text-sm font-bold text-slate-700">وصف المنتج <span class="font-normal text-slate-400">(اختياري)</span></label>
                        <textarea id="product-description" x-model.trim="form.description" rows="4" maxlength="2000" placeholder="اذكر الفائدة الأساسية، المكونات أو الميزة التي تهم العميل." class="mt-1 w-full resize-y rounded-xl border-slate-300 focus:border-teal-500 focus:ring-teal-500" aria-describedby="product-description-help"></textarea>
                        <div id="product-description-help" class="mt-1.5 flex justify-between text-xs text-slate-500"><span>استخدم جملاً قصيرة وواضحة وتجنب الوعود الطبية غير الموثقة.</span><span x-text="`${(form.description || '').length}/2000`"></span></div>
                    </div>
                </section>

                <section class="space-y-4" aria-labelledby="product-price-title">
                    <div class="border-b border-slate-100 pb-3"><p class="text-xs font-bold tracking-wide text-teal-700">الخطوة 2 من 4</p><h3 id="product-price-title" class="mt-1 font-bold text-slate-800">السعر والمخزون</h3><p class="mt-1 text-xs leading-5 text-slate-500">أدخل السعر الذي سيدفعه العميل، ثم فعّل تتبع المخزون فقط إن كانت الكمية محدودة.</p></div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div><label for="product-price" class="block text-sm font-bold text-slate-700">سعر البيع الحالي <span class="text-rose-600">*</span></label><div class="relative mt-1"><input id="product-price" x-model.number="form.price" type="number" required min="0" step="0.01" inputmode="decimal" placeholder="0.00" class="w-full rounded-xl border-slate-300 pl-14 font-mono focus:border-teal-500 focus:ring-teal-500" /><span class="absolute inset-y-0 left-0 flex items-center px-3 text-xs font-bold text-slate-500">د.ج</span></div><p class="mt-1.5 text-xs text-slate-500">السعر الذي يظهر للعميل الآن.</p></div>
                        <div><label for="product-old-price" class="block text-sm font-bold text-slate-700">السعر قبل التخفيض <span class="font-normal text-slate-400">(اختياري)</span></label><div class="relative mt-1"><input id="product-old-price" x-model.number="form.old_price" type="number" min="0" step="0.01" inputmode="decimal" placeholder="اتركه فارغاً إن لم يوجد تخفيض" class="w-full rounded-xl border-slate-300 pl-14 font-mono focus:border-teal-500 focus:ring-teal-500" /><span class="absolute inset-y-0 left-0 flex items-center px-3 text-xs font-bold text-slate-500">د.ج</span></div><p class="mt-1.5 text-xs text-slate-500">استخدمه فقط عندما يكون أكبر من سعر البيع الحالي.</p></div>
                        <div><label for="product-discount" class="block text-sm font-bold text-slate-700">نسبة الخصم <span class="font-normal text-slate-400">(اختيارية)</span></label><div class="relative mt-1"><input id="product-discount" x-model.number="form.discount_percentage" type="number" min="0" max="100" step="1" inputmode="numeric" placeholder="مثال: 15" class="w-full rounded-xl border-slate-300 pl-10 font-mono focus:border-teal-500 focus:ring-teal-500" /><span class="absolute inset-y-0 left-0 flex items-center px-3 text-xs font-bold text-slate-500">%</span></div><p class="mt-1.5 text-xs text-slate-500">يمكن تركها فارغة إذا كان السعر السابق كافياً.</p></div>
                    </div>
                    <p x-show="form.old_price !== '' && Number(form.old_price) > 0 && Number(form.price) >= Number(form.old_price)" class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800">تنبيه: السعر السابق يجب أن يكون أكبر من سعر البيع حتى يظهر التخفيض بشكل صحيح.</p>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><label class="flex items-start gap-3 text-sm text-slate-700"><input x-model="form.track_stock" type="checkbox" class="mt-0.5 rounded border-slate-300 text-teal-600 focus:ring-teal-500" /><span><strong class="block">تتبع كمية المخزون</strong><span class="mt-0.5 block text-xs font-normal leading-5 text-slate-500">فعّله إذا كنت تريد منع الشراء عند نفاد الكمية. اتركه معطلاً للمنتجات المتوفرة دائماً أو بحسب الطلب.</span></span></label><div x-show="form.track_stock" x-transition class="mt-4 border-t border-slate-200 pt-4"><label for="product-stock" class="block text-sm font-bold text-slate-700">الكمية المتاحة الآن</label><input id="product-stock" x-model.number="form.stock_quantity" type="number" min="0" step="1" inputmode="numeric" placeholder="مثال: 24" class="mt-1 w-full max-w-xs rounded-xl border-slate-300 font-mono focus:border-teal-500 focus:ring-teal-500" /><p class="mt-1.5 text-xs text-slate-500">ضع 0 إذا نفد المنتج. ستتغير حالته تلقائياً إلى غير متوفر.</p></div></div>
                </section>

                <section class="space-y-4" aria-labelledby="product-display-title">
                    <div class="border-b border-slate-100 pb-3"><p class="text-xs font-bold tracking-wide text-teal-700">الخطوة 3 من 4</p><h3 id="product-display-title" class="mt-1 font-bold text-slate-800">العرض ومعلومات إضافية <span class="font-normal text-slate-400">(اختيارية)</span></h3><p class="mt-1 text-xs leading-5 text-slate-500">اختر ما يظهر في المتجر وأضف التفاصيل التي تفيد العميل عند اتخاذ قرار الشراء.</p></div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700"><input x-model="form.is_active" type="checkbox" class="mt-0.5 rounded text-teal-600 focus:ring-teal-500" /><span><strong class="block">إظهار المنتج في المتجر</strong><span class="mt-0.5 block text-xs font-normal leading-5 text-slate-500">عطّله لإخفائه عن العملاء من دون حذفه.</span></span></label>
                        <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700"><input x-model="form.is_bestseller" type="checkbox" class="mt-0.5 rounded text-teal-600 focus:ring-teal-500" /><span><strong class="block">وضع علامة «الأكثر طلباً»</strong><span class="mt-0.5 block text-xs font-normal leading-5 text-slate-500">استخدمها للمنتجات التي تريد إبرازها للعميل.</span></span></label>
                        <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700"><input x-model="form.is_discreet" type="checkbox" class="mt-0.5 rounded text-teal-600 focus:ring-teal-500" /><span><strong class="block">طلب بخصوصية</strong><span class="mt-0.5 block text-xs font-normal leading-5 text-slate-500">يضيف تنبيهاً داخلياً إلى الطلب، ولا يغيّر اسم المنتج للعميل.</span></span></label>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label for="product-origin" class="block text-sm font-bold text-slate-700">بلد أو مصدر المنشأ</label><input id="product-origin" x-model.trim="form.origin" maxlength="255" placeholder="مثال: المغرب" class="mt-1 w-full rounded-xl border-slate-300 focus:border-teal-500 focus:ring-teal-500" /><p class="mt-1.5 text-xs text-slate-500">اختياري؛ اذكر المصدر فقط إن كانت المعلومة مؤكدة.</p></div>
                        <div><label for="product-extraction" class="block text-sm font-bold text-slate-700">طريقة الاستخلاص أو التحضير</label><input id="product-extraction" x-model.trim="form.extraction_method" maxlength="255" placeholder="مثال: معصور على البارد" class="mt-1 w-full rounded-xl border-slate-300 focus:border-teal-500 focus:ring-teal-500" /><p class="mt-1.5 text-xs text-slate-500">اختياري؛ صف الطريقة باختصار.</p></div>
                    </div>
                    <div><label for="product-ingredients" class="block text-sm font-bold text-slate-700">المكونات</label><textarea id="product-ingredients" x-model.trim="form.ingredients" rows="2" maxlength="1500" placeholder="مثال: زيت الأرغان النقي 100%" class="mt-1 w-full resize-y rounded-xl border-slate-300 focus:border-teal-500 focus:ring-teal-500"></textarea></div>
                    <div><label for="product-usage" class="block text-sm font-bold text-slate-700">إرشادات الاستخدام</label><textarea id="product-usage" x-model.trim="form.usage_instructions" rows="3" maxlength="1500" placeholder="اشرح متى وكيف يستخدم المنتج، وأضف التحذيرات المهمة إن وجدت." class="mt-1 w-full resize-y rounded-xl border-slate-300 focus:border-teal-500 focus:ring-teal-500"></textarea></div>
                </section>

                <section class="space-y-3" aria-labelledby="product-media-title">
                    <div class="border-b border-slate-100 pb-3"><p class="text-xs font-bold tracking-wide text-teal-700">الخطوة 4 من 4</p><h3 id="product-media-title" class="mt-1 font-bold text-slate-800">صور وفيديو المنتج <span class="font-normal text-slate-400">(اختيارية)</span></h3><p class="mt-1 text-xs leading-5 text-slate-500">الصورة الأولى هي الأهم؛ اختر صورة واضحة للمنتج على خلفية مناسبة.</p></div>
                    <label for="product-media" class="block cursor-pointer rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 p-5 text-center transition hover:border-teal-400 hover:bg-teal-50"><span class="block text-sm font-bold text-slate-700">اختر صوراً أو فيديوهات</span><span class="mt-1 block text-xs text-slate-500">JPG، PNG، MP4 أو MOV — حتى 20MB للملف</span><input id="product-media" type="file" multiple accept="image/jpeg,image/png,video/mp4,video/quicktime" @change="setMediaFiles($event)" class="sr-only" /></label>
                    <p x-show="!mediaFiles.length" class="text-xs text-slate-500">عند تعديل المنتج، رفع وسائط جديدة يستبدل المعرض الحالي؛ لا ترفع ملفات إن أردت الإبقاء عليه.</p>
                    <div x-show="mediaFiles.length" class="rounded-xl bg-teal-50 p-3 text-sm text-teal-800"><strong x-text="`تم اختيار ${mediaFiles.length} ملف.`"></strong><p class="mt-1 text-xs" x-text="mediaSummary"></p></div>
                </section>

                <div class="sticky bottom-0 -mx-6 flex flex-col-reverse gap-3 border-t border-slate-200 bg-white px-6 py-4 sm:flex-row sm:items-center sm:justify-end"><button type="button" @click="closeForm()" :disabled="isSaving" class="rounded-xl border border-slate-300 px-4 py-2.5 font-bold text-slate-700 hover:bg-slate-50 disabled:opacity-50">إلغاء</button><button type="submit" :disabled="isSaving" class="inline-flex items-center justify-center rounded-xl bg-teal-600 px-5 py-2.5 font-bold text-white hover:bg-teal-700 disabled:cursor-not-allowed disabled:opacity-50" x-text="isSaving ? 'جارٍ حفظ المنتج...' : (form.id ? 'حفظ التعديلات' : 'إنشاء المنتج')"></button></div>
            </form>
        </div>
    </div>
</div>

<script>
    function productsManager() {
        return {
            products: [],
            categories: [],
            filters: { search: '', categoryId: '', status: 'all' },
            isLoading: true,
            isSaving: false,
            savingId: null,
            isFormOpen: false,
            mediaFiles: [],
            formError: '',
            form: {},

            init() {
                this.resetForm();
                Promise.all([this.loadProducts(), this.loadCategories()]);
                if (new URLSearchParams(window.location.search).has('create')) this.openCreate();
            },

            get filteredProducts() {
                const query = this.filters.search.trim().toLowerCase();
                return this.products.filter(product => {
                    const matchesText = !query || `${product.name} ${product.description || ''}`.toLowerCase().includes(query);
                    const matchesCategory = !this.filters.categoryId || String(product.category_id) === String(this.filters.categoryId);
                    const matchesStatus = this.filters.status === 'all'
                        || (this.filters.status === 'active' && product.is_active)
                        || (this.filters.status === 'inactive' && !product.is_active)
                        || (this.filters.status === 'out' && product.stock_quantity !== null && !product.in_stock);
                    return matchesText && matchesCategory && matchesStatus;
                });
            },

            get activeCategories() {
                return this.categories.filter(category => category.is_active);
            },

            get mediaSummary() {
                return this.mediaFiles.map(file => file.name).slice(0, 3).join('، ') + (this.mediaFiles.length > 3 ? ` و${this.mediaFiles.length - 3} ملفات أخرى` : '');
            },

            headers() {
                const token = localStorage.getItem('merchant_token');
                return token ? { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` } : { 'Accept': 'application/json' };
            },

            async request(url, options = {}) {
                const response = await fetch(url, { credentials: 'same-origin', ...options, headers: { ...this.headers(), ...(options.headers || {}) } });
                let data = {};
                try { data = await response.json(); } catch (_) {}
                if (!response.ok) throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || 'تعذر إتمام العملية.');
                return data;
            },

            async loadProducts() {
                this.isLoading = true;
                try {
                    const data = await this.request('/api/products');
                    this.products = data.data || [];
                } catch (error) {
                    this.showNotice('error', error.message);
                } finally {
                    this.isLoading = false;
                }
            },

            async loadCategories() {
                try {
                    const data = await this.request('/api/categories');
                    this.categories = data.data || [];
                } catch (error) {
                    this.showNotice('error', 'تعذر تحميل الفئات.');
                }
            },

            emptyForm() {
                return {
                    id: null, category_id: '', name: '', description: '', price: '', old_price: '', discount_percentage: '',
                    track_stock: false, stock_quantity: '', is_active: true, is_bestseller: false, is_discreet: false,
                    origin: '', extraction_method: '', ingredients: '', usage_instructions: ''
                };
            },

            resetForm() {
                this.form = this.emptyForm();
                this.mediaFiles = [];
                this.formError = '';
            },

            openCreate() {
                this.resetForm();
                this.isFormOpen = true;
            },

            openEdit(product) {
                this.form = {
                    id: product.id, category_id: String(product.category_id || ''), name: product.name || '', description: product.description || '',
                    price: product.price ?? '', old_price: product.old_price ?? '', discount_percentage: product.discount_percentage ?? '',
                    track_stock: product.stock_quantity !== null, stock_quantity: product.stock_quantity ?? '',
                    is_active: Boolean(product.is_active), is_bestseller: Boolean(product.is_bestseller), is_discreet: Boolean(product.is_discreet),
                    origin: product.origin || '', extraction_method: product.extraction_method || '', ingredients: product.ingredients || '', usage_instructions: product.usage_instructions || ''
                };
                this.mediaFiles = [];
                this.formError = '';
                this.isFormOpen = true;
            },

            closeForm() {
                if (this.isSaving) return;
                this.isFormOpen = false;
            },

            setMediaFiles(event) {
                this.mediaFiles = Array.from(event.target.files || []);
            },

            appendFormData() {
                const data = new FormData();
                const scalarFields = ['category_id', 'name', 'description', 'price', 'old_price', 'discount_percentage', 'origin', 'extraction_method', 'ingredients', 'usage_instructions'];
                scalarFields.forEach(field => {
                    const value = this.form[field];
                    // إرسال الحقل الفارغ مقصود: Laravel يحوله إلى null للحقول nullable عند التعديل.
                    data.append(field, value ?? '');
                });
                if (this.form.track_stock) data.append('stock_quantity', this.form.stock_quantity === '' ? 0 : this.form.stock_quantity);
                else data.append('stock_quantity', '');
                data.append('is_active', this.form.is_active ? '1' : '0');
                data.append('is_bestseller', this.form.is_bestseller ? '1' : '0');
                data.append('is_discreet', this.form.is_discreet ? '1' : '0');
                this.mediaFiles.forEach(file => data.append('media[]', file));
                return data;
            },

            async saveProduct() {
                this.isSaving = true;
                this.formError = '';
                try {
                    const data = this.appendFormData();
                    const editing = Boolean(this.form.id);
                    let url = '/api/products';
                    if (editing) {
                        url += `/${this.form.id}`;
                        data.append('_method', 'PATCH');
                    }
                    const response = await this.request(url, { method: 'POST', body: data });
                    const product = response.product;
                    if (editing) this.products = this.products.map(item => item.id === product.id ? product : item);
                    else this.products = [product, ...this.products];
                    this.isFormOpen = false;
                    this.showNotice('success', response.message || (editing ? 'تم حفظ تعديلات المنتج بنجاح.' : 'تم إنشاء المنتج بنجاح.'));
                } catch (error) {
                    this.formError = error.message;
                } finally {
                    this.isSaving = false;
                }
            },

            async toggleActive(product) {
                this.savingId = product.id;
                try {
                    const data = new FormData();
                    data.append('_method', 'PATCH');
                    data.append('is_active', product.is_active ? '0' : '1');
                    const response = await this.request(`/api/products/${product.id}`, { method: 'POST', body: data });
                    this.products = this.products.map(item => item.id === product.id ? response.product : item);
                    this.showNotice('success', response.message);
                } catch (error) {
                    this.showNotice('error', error.message);
                } finally {
                    this.savingId = null;
                }
            },

            async destroyProduct(product) {
                const approved = await window.merchantConfirm({ title: 'حذف المنتج؟', message: `سيتم حذف «${product.name}» نهائياً مع وسائطه المرتبطة. لا يمكن التراجع عن هذا الإجراء.`, confirmText: 'حذف المنتج' });
                if (!approved) return;
                this.savingId = product.id;
                try {
                    const response = await this.request(`/api/products/${product.id}`, { method: 'DELETE' });
                    this.products = this.products.filter(item => item.id !== product.id);
                    this.showNotice('success', response.message || 'تم حذف المنتج بنجاح.');
                } catch (error) {
                    this.showNotice('error', error.message);
                } finally {
                    this.savingId = null;
                }
            },

            formatMoney(value) {
                return Number(value || 0).toFixed(2);
            },

            showNotice(type, message) {
                window.merchantNotify(type, message);
            }
        }
    }
</script>
@endsection
