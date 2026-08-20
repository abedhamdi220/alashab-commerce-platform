@extends('layouts.app')

@section('content')
<div x-data="categoriesManager()" x-init="init()" class="p-6 max-w-6xl mx-auto" dir="rtl">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-black text-slate-800">إدارة الفئات</h1>
            <p class="text-sm text-slate-500 mt-1">نظّم فئات المتجر وخياراتها الديناميكية وخصائص العرض والعناية.</p>
        </div>
        <button @click="openCreate()" class="bg-teal-600 text-white px-4 py-2.5 rounded-xl font-bold hover:bg-teal-700 shadow-sm">إضافة فئة</button>
    </div>


    <div class="bg-white border border-slate-200 rounded-2xl p-4 mb-5 flex flex-col md:flex-row gap-3">
        <input x-model.debounce.250ms="search" type="search" placeholder="ابحث باسم الفئة أو وصفها..." class="flex-1 rounded-xl border-slate-300 focus:border-teal-500 focus:ring-teal-500" />
        <select x-model="status" class="rounded-xl border-slate-300">
            <option value="all">كل الحالات</option>
            <option value="active">المفعلة</option>
            <option value="inactive">المعطلة</option>
        </select>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
        <div x-show="isLoading" class="p-6 text-center text-slate-500">جارٍ تحميل الفئات...</div>
        <div x-show="!isLoading && filteredCategories.length === 0" class="p-10 text-center text-slate-500">لا توجد فئات مطابقة.</div>
        <div class="overflow-x-auto" x-show="!isLoading && filteredCategories.length > 0">
            <table class="w-full text-right text-sm">
                <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="p-4 font-bold">الفئة</th>
                        <th class="p-4 font-bold">نوع العناية</th>
                        <th class="p-4 font-bold">الخيارات</th>
                        <th class="p-4 font-bold">المنتجات</th>
                        <th class="p-4 font-bold">الحالة</th>
                        <th class="p-4 font-bold">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="category in filteredCategories" :key="category.id">
                        <tr class="hover:bg-slate-50/70">
                            <td class="p-4">
                                <div class="flex gap-3 items-center">
                                    <span class="w-4 h-4 rounded-full ring-2 ring-white shadow" :style="`background:${category.accent_color || '#94a3b8'}`"></span>
                                    <div>
                                        <p class="font-bold text-slate-800" x-text="category.name"></p>
                                        <p class="text-xs text-slate-500 line-clamp-1" x-text="category.description || 'لا يوجد وصف'"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 text-slate-600" x-text="careTypeLabel(category.care_type)"></td>
                            <td class="p-4 text-slate-600" x-text="`${(category.options || []).length} خاصية`"></td>
                            <td class="p-4 font-bold text-slate-700" x-text="category.products_count || 0"></td>
                            <td class="p-4"><button @click="toggleActive(category)" :disabled="savingId === category.id" class="px-2.5 py-1 rounded-lg text-xs font-bold" :class="category.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600'" x-text="category.is_active ? 'مفعلة' : 'معطلة'"></button></td>
                            <td class="p-4"><div class="flex gap-2"><button @click="openEdit(category)" class="text-teal-700 hover:text-teal-900 font-bold">تعديل</button><button @click="destroyCategory(category)" class="text-rose-600 hover:text-rose-800 font-bold">حذف</button></div></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <div x-show="isFormOpen" x-transition class="fixed inset-0 z-50 bg-slate-950/45 p-4 overflow-y-auto" @keydown.escape.window="closeForm()">
        <div class="bg-white max-w-3xl mx-auto rounded-2xl shadow-2xl my-6 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center sticky top-0 bg-white z-10">
                <div><h2 class="text-xl font-black text-slate-800" x-text="form.id ? 'تعديل الفئة' : 'إضافة فئة جديدة'"></h2><p class="text-xs text-slate-500 mt-1">الفئة لا تُحذف إن كانت مرتبطة بمنتجات؛ استخدم التعطيل عند الحاجة.</p></div>
                <button @click="closeForm()" class="text-slate-400 hover:text-slate-700 text-2xl">×</button>
            </div>

            <form @submit.prevent="saveCategory()" class="p-6 space-y-7">
                <div x-show="formError" role="alert" aria-live="assertive" class="p-3 rounded-lg bg-rose-50 text-rose-700 text-sm" x-text="formError"></div>

                <section class="space-y-4" aria-labelledby="category-basics-title">
                    <div class="border-b border-slate-100 pb-3"><p class="text-xs font-bold tracking-wide text-teal-700">الخطوة 1 من 2</p><h3 id="category-basics-title" class="mt-1 font-bold text-slate-800">المعلومات التي تظهر للعميل</h3><p class="mt-1 text-xs leading-5 text-slate-500">حدّد الاسم واللون والوصف حتى تظهر الفئة بشكل مفهوم في المتجر.</p></div>
                    <div class="grid grid-cols-1 md:grid-cols-[1fr_190px] gap-4">
                        <div>
                            <label for="category-name" class="block text-sm font-bold text-slate-700">اسم الفئة <span class="text-rose-600" aria-hidden="true">*</span></label>
                            <input id="category-name" x-model.trim="form.name" required maxlength="255" autocomplete="off" placeholder="مثال: العناية بالبشرة" class="mt-1 w-full rounded-xl border-slate-300 focus:border-teal-500 focus:ring-teal-500" aria-describedby="category-name-help" />
                            <p id="category-name-help" class="mt-1.5 text-xs text-slate-500">اسم قصير وواضح يظهر في قائمة الفئات وصفحات المنتجات.</p>
                        </div>
                        <div>
                            <label for="category-color-text" class="block text-sm font-bold text-slate-700">اللون المميز</label>
                            <div class="mt-1 flex gap-2"><input x-model="form.accent_color" type="color" class="h-11 w-12 cursor-pointer rounded-xl border border-slate-300 bg-white p-1" aria-label="اختيار لون الفئة" /><input id="category-color-text" x-model.trim="form.accent_color" type="text" pattern="^#[0-9A-Fa-f]{6}$" placeholder="#0d9488" dir="ltr" class="min-w-0 flex-1 rounded-xl border-slate-300 font-mono text-sm focus:border-teal-500 focus:ring-teal-500" /></div>
                            <p class="mt-1.5 text-xs text-slate-500">استخدم لوناً سداسياً مثل <span dir="ltr">#0d9488</span>.</p>
                        </div>
                    </div>
                    <div>
                        <label for="category-description" class="block text-sm font-bold text-slate-700">وصف قصير <span class="font-normal text-slate-400">(اختياري)</span></label>
                        <textarea id="category-description" x-model.trim="form.description" rows="3" maxlength="500" placeholder="اشرح باختصار ما الذي سيجده العميل داخل هذه الفئة." class="mt-1 w-full resize-y rounded-xl border-slate-300 focus:border-teal-500 focus:ring-teal-500" aria-describedby="category-description-help"></textarea>
                        <div id="category-description-help" class="mt-1.5 flex justify-between text-xs text-slate-500"><span>يساعد العميل على اختيار الفئة المناسبة.</span><span x-text="`${(form.description || '').length}/500`"></span></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="category-care-type" class="block text-sm font-bold text-slate-700">نوع العناية <span class="font-normal text-slate-400">(اختياري)</span></label>
                            <select id="category-care-type" x-model="form.care_type" class="mt-1 w-full rounded-xl border-slate-300 focus:border-teal-500 focus:ring-teal-500" aria-describedby="category-care-help"><option value="">لا ينطبق / غير محدد</option><option value="slim">رشاقة</option><option value="skincare">عناية بالبشرة</option><option value="gain">زيادة الوزن</option><option value="intimate">عناية خاصة</option><option value="beauty">تجميل</option></select>
                            <p id="category-care-help" class="mt-1.5 text-xs text-slate-500">يستخدم للتنظيم والتوصيات داخل المتجر، ولا يغيّر اسم الفئة.</p>
                        </div>
                        <label class="mt-6 flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700"><input x-model="form.is_active" type="checkbox" class="mt-0.5 rounded border-slate-300 text-teal-600 focus:ring-teal-500" /><span><strong class="block">إظهار الفئة للمتجر</strong><span class="mt-0.5 block text-xs font-normal leading-5 text-slate-500">عطّلها مؤقتاً لإخفائها عن العملاء من دون حذفها أو حذف منتجاتها.</span></span></label>
                    </div>
                </section>

                <section class="space-y-4" aria-labelledby="category-options-title">
                    <div class="flex flex-col gap-3 border-b border-slate-100 pb-3 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-xs font-bold tracking-wide text-teal-700">الخطوة 2 من 2</p><h3 id="category-options-title" class="mt-1 font-bold text-slate-800">خصائص منتجات هذه الفئة <span class="font-normal text-slate-400">(اختيارية)</span></h3><p class="mt-1 text-xs leading-5 text-slate-500">تظهر هذه الحقول عند إضافة منتج من الفئة، مثل الحجم أو النوع أو اللون.</p></div><button type="button" @click="addOption()" class="inline-flex items-center justify-center rounded-lg bg-teal-100 px-3 py-2 text-sm font-bold text-teal-700 hover:bg-teal-200">+ إضافة خاصية</button></div>
                    <p x-show="form.options.length === 0" class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm leading-6 text-slate-600">لا توجد خصائص إضافية الآن. أضف خاصية فقط عندما تحتاج أن يملأها التاجر أو العميل لكل منتج؛ لا تستخدمها للسعر أو المخزون.</p>
                    <template x-for="(option, optionIndex) in form.options" :key="optionIndex">
                        <fieldset class="rounded-xl border border-slate-200 p-4" :aria-labelledby="`option-title-${optionIndex}`"><legend class="sr-only" :id="`option-title-${optionIndex}`" x-text="`الخاصية ${optionIndex + 1}`"></legend>
                            <div class="mb-4 flex items-center justify-between gap-3"><p class="text-sm font-bold text-slate-700" x-text="`الخاصية ${optionIndex + 1}`"></p><button type="button" @click="removeOption(optionIndex)" class="rounded-lg px-2 py-1 text-sm font-bold text-rose-600 hover:bg-rose-50">حذف الخاصية</button></div>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2"><div><label :for="`option-name-${optionIndex}`" class="block text-sm font-bold text-slate-700">اسم الخاصية <span class="text-rose-600">*</span></label><input :id="`option-name-${optionIndex}`" x-model.trim="option.name" required maxlength="100" placeholder="مثال: الحجم" class="mt-1 w-full rounded-xl border-slate-300 focus:border-teal-500 focus:ring-teal-500" /><p class="mt-1 text-xs text-slate-500">الاسم الذي يظهر للمستخدم.</p></div><div><label :for="`option-type-${optionIndex}`" class="block text-sm font-bold text-slate-700">طريقة الإدخال</label><select :id="`option-type-${optionIndex}`" x-model="option.type" @change="normalizeOption(option)" class="mt-1 w-full rounded-xl border-slate-300 focus:border-teal-500 focus:ring-teal-500"><option value="text">نص حر — مثال: ملاحظة</option><option value="select">قائمة خيارات — مثال: صغير / كبير</option><option value="checkbox">مربع اختيار — مثال: تغليف هدية</option></select><p class="mt-1 text-xs text-slate-500" x-text="option.type === 'select' ? 'أضف القيم التي يمكن اختيارها أدناه.' : option.type === 'checkbox' ? 'يختار المستخدم نعم أو لا.' : 'يكتب المستخدم قيمة قصيرة.'"></p></div></div>
                            <div x-show="option.type === 'select'" x-transition class="mt-4 rounded-xl bg-slate-50 p-4"><div class="mb-2 flex items-center justify-between"><p class="text-sm font-bold text-slate-700">خيارات القائمة</p><button type="button" @click="option.values.push('')" class="text-sm font-bold text-teal-700 hover:text-teal-900">+ إضافة خيار</button></div><p class="mb-3 text-xs text-slate-500">اكتب خياراً واحداً في كل سطر، مثل: صغير، متوسط، كبير.</p><template x-for="(value, valueIndex) in option.values" :key="valueIndex"><div class="mb-2 flex items-center gap-2"><input x-model.trim="option.values[valueIndex]" class="flex-1 rounded-lg border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500" :placeholder="`الخيار ${valueIndex + 1}`" /><button type="button" @click="option.values.splice(valueIndex, 1)" class="rounded-lg px-2 py-1 text-sm font-bold text-rose-600 hover:bg-rose-50" :aria-label="`حذف الخيار ${valueIndex + 1}`">×</button></div></template></div>
                        </fieldset>
                    </template>
                </section>

                <div class="sticky bottom-0 -mx-6 flex flex-col-reverse gap-3 border-t border-slate-200 bg-white px-6 py-4 sm:flex-row sm:items-center sm:justify-end"><button type="button" @click="closeForm()" :disabled="isSaving" class="rounded-xl border border-slate-300 px-4 py-2.5 font-bold text-slate-700 hover:bg-slate-50 disabled:opacity-50">إلغاء</button><button type="submit" :disabled="isSaving" class="inline-flex items-center justify-center rounded-xl bg-teal-600 px-5 py-2.5 font-bold text-white hover:bg-teal-700 disabled:cursor-not-allowed disabled:opacity-50" x-text="isSaving ? 'جارٍ حفظ الفئة...' : (form.id ? 'حفظ التعديلات' : 'إنشاء الفئة')"></button></div>
            </form>
        </div>
    </div>
</div>

<script>
    function categoriesManager() {
        return {
            categories: [], search: '', status: 'all', isLoading: true, isSaving: false, savingId: null, isFormOpen: false,
            formError: '', form: {},

            init() { this.resetForm(); this.loadCategories(); if (new URLSearchParams(window.location.search).has('create')) this.openCreate(); },
            get filteredCategories() {
                const query = this.search.trim().toLowerCase();
                return this.categories.filter(category => {
                    const textMatches = !query || `${category.name} ${category.description || ''}`.toLowerCase().includes(query);
                    const statusMatches = this.status === 'all' || (this.status === 'active' && category.is_active) || (this.status === 'inactive' && !category.is_active);
                    return textMatches && statusMatches;
                });
            },
            headers() { const token = localStorage.getItem('merchant_token'); return token ? { 'Accept': 'application/json', 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` } : { 'Accept': 'application/json', 'Content-Type': 'application/json' }; },
            async request(url, options = {}) { const response = await fetch(url, { credentials: 'same-origin', ...options, headers: { ...this.headers(), ...(options.headers || {}) } }); let data = {}; try { data = await response.json(); } catch (_) {} if (!response.ok) throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || 'تعذر إتمام العملية.'); return data; },
            async loadCategories() { this.isLoading = true; try { const data = await this.request('/api/categories'); this.categories = data.data || []; } catch (error) { this.showNotice('error', error.message); } finally { this.isLoading = false; } },
            emptyForm() { return { id: null, name: '', description: '', accent_color: '#0d9488', care_type: '', is_active: true, options: [] }; },
            resetForm() { this.form = this.emptyForm(); this.formError = ''; },
            openCreate() { this.resetForm(); this.isFormOpen = true; },
            openEdit(category) { this.form = { id: category.id, name: category.name || '', description: category.description || '', accent_color: category.accent_color || '#0d9488', care_type: category.care_type || '', is_active: Boolean(category.is_active), options: JSON.parse(JSON.stringify(category.options || [])) }; this.form.options.forEach(option => this.normalizeOption(option)); this.formError = ''; this.isFormOpen = true; },
            closeForm() { if (!this.isSaving) this.isFormOpen = false; },
            addOption() { this.form.options.push({ name: '', type: 'text', values: [] }); },
            removeOption(index) { this.form.options.splice(index, 1); },
            normalizeOption(option) { if (option.type === 'select' && (!option.values || option.values.length === 0)) option.values = ['']; if (option.type !== 'select') option.values = []; },
            payload() { const options = this.form.options.map(option => ({ name: option.name.trim(), type: option.type, values: option.type === 'select' ? option.values.map(value => value.trim()).filter(Boolean) : [] })).filter(option => option.name); return { name: this.form.name, description: this.form.description || null, accent_color: this.form.accent_color || null, care_type: this.form.care_type || null, is_active: this.form.is_active, options: options.length ? options : null }; },
            async saveCategory() { this.isSaving = true; this.formError = ''; try { const editing = Boolean(this.form.id); const url = editing ? `/api/categories/${this.form.id}` : '/api/categories'; const data = await this.request(url, { method: editing ? 'PATCH' : 'POST', body: JSON.stringify(this.payload()) }); const category = data.data; if (editing) this.categories = this.categories.map(item => item.id === category.id ? category : item); else this.categories = [...this.categories, category]; this.isFormOpen = false; this.showNotice('success', data.message || (editing ? 'تم حفظ تعديلات الفئة بنجاح.' : 'تم إنشاء الفئة بنجاح.')); } catch (error) { this.formError = error.message; } finally { this.isSaving = false; } },
            async toggleActive(category) { this.savingId = category.id; try { const data = await this.request(`/api/categories/${category.id}`, { method: 'PATCH', body: JSON.stringify({ is_active: !category.is_active }) }); this.categories = this.categories.map(item => item.id === category.id ? data.data : item); this.showNotice('success', data.message); } catch (error) { this.showNotice('error', error.message); } finally { this.savingId = null; } },
            async destroyCategory(category) { const approved = await window.merchantConfirm({ title: 'حذف الفئة؟', message: `سيتم حذف الفئة «${category.name}». لا يمكن إتمام الحذف إذا كانت مرتبطة بمنتجات، ويمكنك تعطيلها بدلاً من ذلك.`, confirmText: 'حذف الفئة' }); if (!approved) return; this.savingId = category.id; try { const data = await this.request(`/api/categories/${category.id}`, { method: 'DELETE' }); this.categories = this.categories.filter(item => item.id !== category.id); this.showNotice('success', data.message || 'تم حذف الفئة بنجاح.'); } catch (error) { this.showNotice('error', error.message); } finally { this.savingId = null; } },
            careTypeLabel(value) { return { slim: 'رشاقة', skincare: 'عناية بالبشرة', gain: 'زيادة الوزن', intimate: 'عناية خاصة', beauty: 'تجميل' }[value] || 'غير محدد'; },
            showNotice(type, message) { window.merchantNotify(type, message); }
        }
    }
</script>
@endsection
