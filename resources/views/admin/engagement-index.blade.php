@extends('layouts.app')

@section('content')
<div x-data="engagementManager()" x-init="init()" class="mx-auto max-w-7xl p-6" dir="rtl">
    <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-xs font-bold tracking-wide text-teal-700">ثقة العملاء</p>
            <h1 class="mt-1 text-2xl font-black text-slate-800">الآراء والمفضلة</h1>
            <p class="mt-1 text-sm text-slate-500">راجِع آراء المنتجات قبل عرضها، وتعرّف إلى المنتجات التي يحفظها الزوار.</p>
        </div>
        <button @click="reload()" :disabled="isLoading" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50 disabled:opacity-50">تحديث البيانات</button>
    </div>

    <div class="mb-5 flex flex-wrap gap-2 border-b border-slate-200">
        <button @click="tab = 'reviews'; loadReviews()" :class="tab === 'reviews' ? 'border-teal-600 text-teal-700' : 'border-transparent text-slate-500 hover:text-slate-800'" class="border-b-2 px-4 py-3 text-sm font-bold transition">آراء المنتجات</button>
        <button @click="tab = 'favorites'; loadFavorites()" :class="tab === 'favorites' ? 'border-teal-600 text-teal-700' : 'border-transparent text-slate-500 hover:text-slate-800'" class="border-b-2 px-4 py-3 text-sm font-bold transition">المفضلة</button>
    </div>

    <section x-show="tab === 'reviews'" x-cloak>
        <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-bold text-slate-800">مراجعة الآراء</h2>
                <p class="mt-1 text-xs text-slate-500">الرفض لا يحذف الرأي؛ يبقى القرار محفوظاً في السجل الداخلي.</p>
            </div>
            <select x-model="reviewStatus" @change="loadReviews()" class="rounded-xl border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500">
                <option value="pending">بانتظار المراجعة</option>
                <option value="approved">المعتمدة</option>
                <option value="rejected">المرفوضة</option>
                <option value="all">كل الآراء</option>
            </select>
        </div>
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div x-show="isLoading" class="p-8 text-center text-sm text-slate-500">جارٍ تحميل الآراء...</div>
            <div x-show="!isLoading && reviews.length === 0" class="p-10 text-center text-sm text-slate-500">لا توجد آراء ضمن الحالة المحددة.</div>
            <div class="divide-y divide-slate-100" x-show="!isLoading && reviews.length">
                <template x-for="review in reviews" :key="review.id">
                    <article class="p-5">
                        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2"><h3 class="font-bold text-slate-800" x-text="review.product.name"></h3><span class="rounded-full px-2.5 py-1 text-xs font-bold" :class="statusClass(review.status)" x-text="statusLabel(review.status)"></span></div>
                                <p class="mt-2 text-sm text-slate-600" x-text="review.comment"></p>
                                <div class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-xs text-slate-500"><span><strong class="text-slate-700">الاسم:</strong> <span x-text="review.visitor.label"></span></span><span><strong class="text-slate-700">التقييم:</strong> <span x-text="`${review.rating}/5`"></span></span><span x-text="formatDate(review.created_at)"></span></div>
                                <p x-show="review.rejection_reason" class="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-700"><strong>سبب الرفض:</strong> <span x-text="review.rejection_reason"></span></p>
                            </div>
                            <div x-show="review.status === 'pending'" class="flex shrink-0 gap-2"><button @click="approve(review)" :disabled="actionId === review.id" class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-700 disabled:opacity-50">قبول</button><button @click="reject(review)" :disabled="actionId === review.id" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 hover:bg-rose-100 disabled:opacity-50">رفض</button></div>
                        </div>
                    </article>
                </template>
            </div>
        </div>
    </section>

    <section x-show="tab === 'favorites'" x-cloak>
        <div class="mb-4 rounded-2xl border border-slate-200 bg-white p-4"><label for="favorite-search" class="sr-only">ابحث في المفضلة</label><input id="favorite-search" x-model.debounce.350ms="favoriteSearch" @input="loadFavorites()" type="search" placeholder="ابحث باسم منتج أو اسم ظاهر للزائر..." class="w-full rounded-xl border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500" /></div>
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div x-show="isLoading" class="p-8 text-center text-sm text-slate-500">جارٍ تحميل بيانات المفضلة...</div>
            <div x-show="!isLoading && favorites.length === 0" class="p-10 text-center text-sm text-slate-500">لا توجد منتجات محفوظة ضمن بحثك الحالي.</div>
            <div class="overflow-x-auto" x-show="!isLoading && favorites.length">
                <table class="w-full text-right text-sm"><thead class="border-b border-slate-200 bg-slate-50 text-slate-500"><tr><th class="p-4 font-bold">المنتج</th><th class="p-4 font-bold">الزائر</th><th class="p-4 font-bold">أضيفت في</th><th class="p-4 font-bold">آخر نشاط</th></tr></thead><tbody class="divide-y divide-slate-100"><template x-for="favorite in favorites" :key="favorite.id"><tr class="hover:bg-slate-50/70"><td class="p-4 font-bold text-slate-800" x-text="favorite.product.name"></td><td class="p-4 text-slate-600" x-text="favorite.visitor.label"></td><td class="p-4 text-slate-500" x-text="formatDate(favorite.created_at)"></td><td class="p-4 text-slate-500" x-text="favorite.visitor.last_seen_at ? formatDate(favorite.visitor.last_seen_at) : '—'"></td></tr></template></tbody></table>
            </div>
        </div>
    </section>

    <div x-show="rejectionReview" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/45 p-4" @keydown.escape.window="closeReject()" @click.self="closeReject()">
        <form @submit.prevent="submitReject()" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between gap-4"><div><h2 class="text-lg font-black text-slate-800">رفض الرأي</h2><p class="mt-1 text-sm text-slate-500">سيبقى الرأي محفوظاً في السجل الداخلي ولن يظهر للعميل.</p></div><button type="button" @click="closeReject()" class="text-2xl leading-none text-slate-400 hover:text-slate-700" aria-label="إغلاق">×</button></div>
            <label for="rejection-reason" class="mt-5 block text-sm font-bold text-slate-700">سبب الرفض <span class="font-normal text-slate-400">(اختياري)</span></label>
            <textarea id="rejection-reason" x-model.trim="rejectionReason" maxlength="500" rows="4" placeholder="مثال: يحتوي على معلومات غير مرتبطة بالمنتج." class="mt-2 w-full resize-y rounded-xl border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500"></textarea>
            <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"><button type="button" @click="closeReject()" :disabled="actionId" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 disabled:opacity-50">إلغاء</button><button type="submit" :disabled="actionId" class="rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-rose-700 disabled:opacity-50" x-text="actionId ? 'جارٍ الحفظ...' : 'تأكيد الرفض'"></button></div>
        </form>
    </div>
</div>

<script>
function engagementManager() {
    return {
        tab: 'reviews', reviews: [], favorites: [], reviewStatus: 'pending', favoriteSearch: '', isLoading: false, actionId: null, rejectionReview: null, rejectionReason: '',
        init() { this.loadReviews(); },
        headers() { const token = localStorage.getItem('merchant_token'); return token ? { Accept: 'application/json', Authorization: `Bearer ${token}` } : { Accept: 'application/json' }; },
        async request(url, options = {}) { const response = await fetch(url, { credentials: 'same-origin', ...options, headers: { ...this.headers(), ...(options.headers || {}) } }); let data = {}; try { data = await response.json(); } catch (_) {} if (!response.ok) throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || 'تعذر إتمام العملية.'); return data; },
        async reload() { return this.tab === 'reviews' ? this.loadReviews() : this.loadFavorites(); },
        async loadReviews() { this.isLoading = true; try { const data = await this.request(`/api/admin/reviews?status=${encodeURIComponent(this.reviewStatus)}`); this.reviews = data.data || []; } catch (error) { window.merchantNotify('error', error.message); } finally { this.isLoading = false; } },
        async loadFavorites() { this.isLoading = true; try { const query = new URLSearchParams(); if (this.favoriteSearch.trim()) query.set('search', this.favoriteSearch.trim()); const data = await this.request(`/api/admin/favorites?${query.toString()}`); this.favorites = data.data || []; } catch (error) { window.merchantNotify('error', error.message); } finally { this.isLoading = false; } },
        async approve(review) { this.actionId = review.id; try { const data = await this.request(`/api/admin/reviews/${review.id}/approve`, { method: 'PATCH' }); window.merchantNotify('success', data.message); await this.loadReviews(); } catch (error) { window.merchantNotify('error', error.message); } finally { this.actionId = null; } },
        reject(review) { this.rejectionReview = review; this.rejectionReason = ''; },
        closeReject() { if (!this.actionId) { this.rejectionReview = null; this.rejectionReason = ''; } },
        async submitReject() { if (!this.rejectionReview) return; this.actionId = this.rejectionReview.id; try { const data = await this.request(`/api/admin/reviews/${this.rejectionReview.id}/reject`, { method: 'PATCH', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ reason: this.rejectionReason }) }); window.merchantNotify('success', data.message); this.rejectionReview = null; this.rejectionReason = ''; await this.loadReviews(); } catch (error) { window.merchantNotify('error', error.message); } finally { this.actionId = null; } },
        statusLabel(status) { return { pending: 'بانتظار المراجعة', approved: 'معتمد', rejected: 'مرفوض' }[status] || status; },
        statusClass(status) { return { pending: 'bg-amber-100 text-amber-800', approved: 'bg-emerald-100 text-emerald-800', rejected: 'bg-rose-100 text-rose-800' }[status] || 'bg-slate-100 text-slate-700'; },
        formatDate(value) { return value ? new Intl.DateTimeFormat('ar', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '—'; },
    };
}
</script>
@endsection
