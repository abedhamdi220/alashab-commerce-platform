@extends('layouts.app')

@section('content')
<div x-data="customersManager()" x-init="init()" class="p-6 max-w-7xl mx-auto" dir="rtl">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-black text-slate-800">الزبائن</h1>
            <p class="text-sm text-slate-500 mt-1">ملف CRM موحّد لكل عميل، مع آخر الرسائل والطلبات وبيانات الاتصال.</p>
        </div>
        <a href="/admin/inbox" class="bg-teal-600 text-white px-4 py-2.5 rounded-xl font-bold hover:bg-teal-700 shadow-sm text-center">فتح صندوق الرسائل</a>
    </div>


    <div class="bg-white border border-slate-200 rounded-2xl p-4 mb-5 grid grid-cols-1 md:grid-cols-[1fr_180px_180px_auto] gap-3">
        <input x-model="filters.search" @keydown.enter="loadCustomers()" type="search" placeholder="ابحث بالاسم أو الهاتف أو معرف المنصة..." class="rounded-xl border-slate-300 focus:border-teal-500 focus:ring-teal-500" />
        <select x-model="filters.platform" class="rounded-xl border-slate-300"><option value="">كل المنصات</option><option value="whatsapp">واتساب</option><option value="messenger">ماسنجر</option></select>
        <select x-model="filters.hasActiveOrder" class="rounded-xl border-slate-300"><option value="">كل الزبائن</option><option value="1">لديهم طلب مفتوح</option><option value="0">بلا طلب مفتوح</option></select>
        <button @click="loadCustomers()" :disabled="isLoading" class="bg-slate-800 text-white px-4 py-2 rounded-xl font-bold hover:bg-slate-700 disabled:opacity-50">بحث</button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_440px] gap-6">
        <section class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
            <div class="px-5 py-4 border-b border-slate-200 flex justify-between items-center"><h2 class="font-black text-slate-800">قائمة الزبائن</h2><span class="text-sm text-slate-500" x-text="`${pagination.total} زبون`"></span></div>
            <div x-show="isLoading" class="p-8 text-center text-slate-500">جارٍ تحميل الزبائن...</div>
            <div x-show="!isLoading && customers.length === 0" class="p-10 text-center text-slate-500">لا يوجد زبائن مطابقون للفلاتر الحالية.</div>
            <div class="divide-y divide-slate-100" x-show="!isLoading && customers.length > 0">
                <template x-for="customer in customers" :key="customer.id">
                    <button @click="openCustomer(customer)" class="w-full p-4 text-right hover:bg-teal-50/60 transition" :class="selectedCustomer?.id === customer.id ? 'bg-teal-50 border-r-4 border-teal-500' : ''">
                        <div class="flex justify-between gap-3 items-start">
                            <div class="flex gap-3 min-w-0">
                                <div class="w-10 h-10 shrink-0 rounded-xl flex items-center justify-center font-black" :class="customer.platform === 'whatsapp' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700'" x-text="customer.name?.charAt(0) || '?' "></div>
                                <div class="min-w-0"><p class="font-bold text-slate-800 truncate" x-text="customer.name"></p><p class="text-xs text-slate-500 mt-0.5" dir="ltr" x-text="customer.phone_number || customer.platform_sender_id"></p><p class="text-xs text-slate-400 mt-1" x-text="`${customer.messages_count} رسالة · ${customer.orders_count} طلب`"></p></div>
                            </div>
                            <div class="text-left shrink-0"><span class="text-xs font-bold px-2 py-1 rounded-md" :class="customer.platform === 'whatsapp' ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-blue-700'" x-text="customer.platform === 'whatsapp' ? 'واتساب' : 'ماسنجر'"></span><p x-show="customer.active_order_id" class="mt-2 text-xs font-bold text-amber-700">طلب مفتوح</p></div>
                        </div>
                    </button>
                </template>
            </div>
            <div x-show="pagination.last_page > 1" class="p-4 border-t border-slate-200 flex justify-between items-center"><button @click="loadCustomers(pagination.current_page - 1)" :disabled="pagination.current_page <= 1 || isLoading" class="px-3 py-1.5 text-sm border rounded-lg disabled:opacity-40">السابق</button><span class="text-sm text-slate-500" x-text="`صفحة ${pagination.current_page} من ${pagination.last_page}`"></span><button @click="loadCustomers(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page || isLoading" class="px-3 py-1.5 text-sm border rounded-lg disabled:opacity-40">التالي</button></div>
        </section>

        <aside class="bg-white border border-slate-200 rounded-2xl shadow-sm min-h-96 overflow-hidden">
            <div x-show="!selectedCustomer && !profileLoading" class="h-full min-h-96 flex flex-col items-center justify-center text-center text-slate-400 p-8"><div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center text-2xl mb-4">CRM</div><p class="font-bold text-slate-600">اختر زبوناً</p><p class="text-sm mt-2">ستظهر هنا بيانات الاتصال وسجل الرسائل والطلبات.</p></div>
            <div x-show="profileLoading" class="p-10 text-center text-slate-500">جارٍ تحميل ملف الزبون...</div>
            <div x-show="selectedCustomer && !profileLoading" class="max-h-[calc(100vh-190px)] overflow-y-auto">
                <div class="p-5 border-b border-slate-200 bg-slate-50"><div class="flex justify-between gap-3"><div><p class="text-xs text-slate-500">ملف الزبون</p><h2 class="text-xl font-black text-slate-800 mt-1" x-text="selectedCustomer?.name"></h2><p class="text-sm text-slate-500 mt-1" dir="ltr" x-text="selectedCustomer?.phone_number || selectedCustomer?.platform_sender_id"></p></div><span class="h-fit text-xs font-bold px-2 py-1 rounded-md" :class="selectedCustomer?.platform === 'whatsapp' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700'" x-text="selectedCustomer?.platform === 'whatsapp' ? 'واتساب' : 'ماسنجر'"></span></div></div>

                <div class="p-5 space-y-6">
                    <section><div class="flex justify-between items-center mb-3"><h3 class="font-bold text-slate-700">بيانات الاتصال</h3><button type="button" @click="isEditing = !isEditing" class="text-sm font-bold text-teal-700" x-text="isEditing ? 'إلغاء' : 'تعديل'"></button></div><template x-if="!isEditing"><div class="text-sm text-slate-600 space-y-2"><p><span class="text-slate-400">الاسم:</span> <span x-text="selectedCustomer?.name"></span></p><p><span class="text-slate-400">الهاتف:</span> <span dir="ltr" x-text="selectedCustomer?.phone_number || 'غير مسجل'"></span></p><p><span class="text-slate-400">معرف المنصة:</span> <span class="font-mono text-xs" x-text="selectedCustomer?.platform_sender_id"></span></p></div></template><template x-if="isEditing"><form @submit.prevent="saveCustomer()" class="space-y-4"><div><label for="customer-edit-name" class="block text-sm font-bold text-slate-700">اسم الزبون</label><input id="customer-edit-name" x-model.trim="editForm.name" maxlength="255" autocomplete="name" placeholder="مثال: آية أحمد" class="mt-1 w-full text-sm" /><p class="mt-1 text-xs text-slate-500">الاسم الذي يظهر في لوحة إدارة العملاء.</p></div><div><label for="customer-edit-phone" class="block text-sm font-bold text-slate-700">رقم الهاتف</label><input id="customer-edit-phone" x-model.trim="editForm.phone_number" inputmode="tel" autocomplete="tel" placeholder="مثال: 09XXXXXXXX" class="mt-1 w-full text-sm" dir="ltr" /><p class="mt-1 text-xs text-slate-500">أدخل الرقم كما زودك به العميل.</p></div><p x-show="profileError" role="alert" class="text-xs text-rose-700" x-text="profileError"></p><button :disabled="isSaving" class="bg-teal-600 text-white text-sm font-bold px-3 py-2 rounded-lg disabled:opacity-50" x-text="isSaving ? 'جارٍ الحفظ...' : 'حفظ البيانات'"></button></form></template></section>

                    <section><div class="flex justify-between items-center mb-3"><h3 class="font-bold text-slate-700">الطلبات</h3><span class="text-xs text-slate-500" x-text="`${selectedCustomer?.orders_count || 0} إجمالاً`"></span></div><div x-show="orders.length === 0" class="text-sm text-slate-400 bg-slate-50 rounded-lg p-3">لا توجد طلبات لهذا الزبون.</div><div class="space-y-2"><template x-for="order in orders" :key="order.id"><details class="border border-slate-200 rounded-xl p-3"><summary class="cursor-pointer flex justify-between items-center text-sm"><span class="font-bold" x-text="`#${order.id}`"></span><span class="font-mono" x-text="formatMoney(order.total_price)"></span><span class="text-xs px-2 py-1 rounded-md bg-slate-100" x-text="orderStatusLabel(order.status)"></span></summary><div class="mt-3 pt-3 border-t border-slate-100 space-y-1"><template x-for="item in order.items" :key="item.id"><div class="flex justify-between text-xs text-slate-600"><span x-text="`${item.product_name} × ${item.quantity}`"></span><span x-text="formatMoney(item.total_price)"></span></div></template><p class="text-xs text-slate-400 mt-2" x-text="formatDate(order.created_at)"></p></div></details></template></div></section>

                    <section><div class="flex justify-between items-center mb-3"><h3 class="font-bold text-slate-700">آخر الرسائل</h3><a href="/admin/inbox" class="text-xs font-bold text-teal-700">صندوق الرسائل</a></div><div x-show="messages.length === 0" class="text-sm text-slate-400 bg-slate-50 rounded-lg p-3">لا توجد رسائل محفوظة.</div><div class="space-y-2"><template x-for="message in messages" :key="message.id"><div class="p-3 rounded-xl text-sm" :class="message.direction === 'outbound' ? 'bg-teal-50 mr-6' : 'bg-slate-50 ml-6'"><p class="whitespace-pre-wrap text-slate-700" x-text="message.body || (message.message_type === 'media' ? 'مرفق' : 'رسالة بلا نص')"></p><p class="text-[11px] text-slate-400 mt-2" x-text="formatDate(message.created_at)"></p></div></template></div></section>
                </div>
            </div>
        </aside>
    </div>
</div>

<script>
    function customersManager() {
        return {
            customers: [], selectedCustomer: null, orders: [], messages: [],
            filters: { search: '', platform: '', hasActiveOrder: '' },
            pagination: { current_page: 1, last_page: 1, total: 0 },
            isLoading: true, profileLoading: false, isSaving: false, isEditing: false,
            profileError: '', editForm: { name: '', phone_number: '' },

            init() { this.loadCustomers(); },
            headers() { const token = localStorage.getItem('merchant_token'); return token ? { 'Accept': 'application/json', 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` } : { 'Accept': 'application/json', 'Content-Type': 'application/json' }; },
            async request(url, options = {}) { const response = await fetch(url, { credentials: 'same-origin', ...options, headers: { ...this.headers(), ...(options.headers || {}) } }); let data = {}; try { data = await response.json(); } catch (_) {} if (!response.ok) throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || 'تعذر إتمام العملية.'); return data; },
            async loadCustomers(page = 1) { this.isLoading = true; const params = new URLSearchParams({ page, per_page: '25' }); if (this.filters.search.trim()) params.set('search', this.filters.search.trim()); if (this.filters.platform) params.set('platform', this.filters.platform); if (this.filters.hasActiveOrder !== '') params.set('has_active_order', this.filters.hasActiveOrder); try { const data = await this.request(`/api/customers?${params.toString()}`); this.customers = data.data || []; this.pagination = data.meta || this.pagination; } catch (error) { this.showNotice('error', error.message); } finally { this.isLoading = false; } },
            async openCustomer(customer) { this.selectedCustomer = customer; this.orders = []; this.messages = []; this.profileLoading = true; this.isEditing = false; this.profileError = ''; try { const data = await this.request(`/api/customers/${customer.id}`); this.selectedCustomer = data.data.customer; this.orders = data.data.orders || []; this.messages = data.data.messages || []; this.editForm = { name: this.selectedCustomer.name || '', phone_number: this.selectedCustomer.phone_number || '' }; } catch (error) { this.profileError = error.message; this.showNotice('error', error.message); } finally { this.profileLoading = false; } },
            async saveCustomer() { if (!this.selectedCustomer) return; this.isSaving = true; this.profileError = ''; try { const data = await this.request(`/api/customers/${this.selectedCustomer.id}`, { method: 'PATCH', body: JSON.stringify(this.editForm) }); this.selectedCustomer = { ...this.selectedCustomer, ...data.data }; this.customers = this.customers.map(customer => customer.id === data.data.id ? { ...customer, ...data.data } : customer); this.isEditing = false; this.showNotice('success', data.message); } catch (error) { this.profileError = error.message; } finally { this.isSaving = false; } },
            formatMoney(value) { return Number(value || 0).toFixed(2); },
            formatDate(value) { if (!value) return ''; const date = new Date(value); return Number.isNaN(date.getTime()) ? value : new Intl.DateTimeFormat('ar', { dateStyle: 'medium', timeStyle: 'short' }).format(date); },
            orderStatusLabel(status) { return { new: 'مسودة', confirmed: 'مؤكد', prepared: 'قيد التجهيز', shipped: 'تم الشحن', cancelled: 'ملغى' }[status] || status; },
            showNotice(type, message) { window.merchantNotify(type, message); }
        }
    }
</script>
@endsection
