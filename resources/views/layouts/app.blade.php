<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رَوَاء | مساحة التاجر</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1f5a43">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        :root {
            --rawaa-ink: #163b2d;
            --rawaa-forest: #1f5a43;
            --rawaa-olive: #718164;
            --rawaa-sage: #dfe6d9;
            --rawaa-mist: #f5f5ee;
            --rawaa-sand: #eee9dc;
            --rawaa-line: #e5e2d6;
            --rawaa-copper: #bc8754;
        }

        * { box-sizing: border-box; }
        [x-cloak] { display: none !important; }
        body.merchant-app {
            min-height: 100vh;
            margin: 0;
            color: #293b33;
            background:
                radial-gradient(circle at 90% -8%, rgba(211, 224, 204, .72), transparent 28rem),
                radial-gradient(circle at -7% 54%, rgba(238, 233, 220, .78), transparent 27rem),
                var(--rawaa-mist);
            font-family: 'Tajawal', ui-sans-serif, system-ui, sans-serif;
        }

        .merchant-topbar {
            min-height: 38px;
            padding: 8px max(1.25rem, calc((100vw - 1320px) / 2));
            background: rgba(248, 248, 243, .96);
            border-bottom: 1px solid rgba(229, 226, 214, .88);
            color: #667168;
            font-size: .74rem;
            font-weight: 600;
            letter-spacing: .01em;
        }
        .merchant-topbar__inner { display: flex; align-items: center; justify-content: space-between; gap: 1rem; max-width: 1320px; margin: auto; }
        .merchant-topbar__items { display: flex; align-items: center; gap: 1.35rem; }
        .merchant-topbar__item { display: inline-flex; align-items: center; gap: .38rem; white-space: nowrap; }
        .merchant-topbar__dot { width: .42rem; height: .42rem; border-radius: 999px; background: #6f9c78; box-shadow: 0 0 0 4px rgba(111, 156, 120, .13); }

        .merchant-header {
            position: sticky;
            top: 0;
            z-index: 30;
            background: rgba(255, 255, 252, .94);
            border-bottom: 1px solid rgba(229, 226, 214, .95);
            backdrop-filter: blur(18px);
        }
        .merchant-nav { display: flex; align-items: center; gap: 2rem; min-height: 80px; max-width: 1320px; margin: auto; padding: 0 1.25rem; }
        .merchant-brand { display: inline-flex; align-items: center; gap: .7rem; color: var(--rawaa-ink); text-decoration: none; flex-shrink: 0; }
        .merchant-brand__mark { width: 43px; height: 43px; display: grid; place-items: center; border-radius: 15px 15px 15px 6px; overflow: hidden; background: linear-gradient(145deg, #1b674b, #0f3d2d); box-shadow: 0 9px 20px rgba(22, 59, 45, .18); }
        .merchant-brand__mark img { width: 100%; height: 100%; display: block; object-fit: cover; }
        .merchant-brand__name { font-size: 1.16rem; line-height: 1; font-weight: 900; letter-spacing: -.02em; }
        .merchant-brand__sub { display: block; margin-top: .28rem; color: #879087; font-size: .66rem; font-weight: 700; letter-spacing: .06em; }
        .merchant-links { display: flex; align-items: center; gap: .18rem; flex: 1; overflow-x: auto; scrollbar-width: none; }
        .merchant-links::-webkit-scrollbar { display: none; }
        .merchant-link { position: relative; display: inline-flex; align-items: center; gap: .42rem; padding: .7rem .78rem; color: #5e6d63; border-radius: .75rem; text-decoration: none; font-size: .84rem; font-weight: 700; white-space: nowrap; transition: color .2s ease, background .2s ease; }
        .merchant-link:hover { color: var(--rawaa-forest); background: #f0f3ed; }
        .merchant-link.is-active { color: var(--rawaa-ink); }
        .merchant-link.is-active::after { content: ''; position: absolute; right: .78rem; left: .78rem; bottom: -.55rem; height: 2px; border-radius: 4px; background: var(--rawaa-forest); }
        .merchant-actions { display: flex; align-items: center; gap: .6rem; flex-shrink: 0; }
        .merchant-help { display: inline-flex; align-items: center; gap: .42rem; color: #68766b; font-size: .77rem; font-weight: 700; white-space: nowrap; }
        .merchant-logout { display: inline-flex; align-items: center; justify-content: center; gap: .45rem; min-height: 39px; padding: .55rem .82rem; color: #8f3f42; border: 1px solid #eddbd7; border-radius: .75rem; background: #fffafa; font-family: inherit; font-size: .77rem; font-weight: 800; transition: .2s ease; }
        .merchant-logout:hover { color: #fff; background: #a64f50; border-color: #a64f50; transform: translateY(-1px); }

        .merchant-main { min-height: calc(100vh - 118px); }
        .merchant-main > [class*="bg-slate-50"] { background-color: transparent !important; }
        .merchant-main .bg-white { border-color: var(--rawaa-line) !important; box-shadow: 0 10px 30px rgba(41, 59, 51, .045); }
        .merchant-main .border-slate-200, .merchant-main .border-slate-100 { border-color: var(--rawaa-line) !important; }
        .merchant-main .text-slate-800, .merchant-main .text-slate-900 { color: var(--rawaa-ink) !important; }
        .merchant-main .text-slate-500, .merchant-main .text-slate-600 { color: #6f7a71 !important; }
        .merchant-main .bg-slate-900 { background-color: var(--rawaa-ink) !important; }
        .merchant-main .bg-slate-50 { background-color: #fafaf6 !important; }
        .merchant-main .bg-teal-600 { background-color: var(--rawaa-forest) !important; }
        .merchant-main .hover\\:bg-teal-700:hover { background-color: #164834 !important; }
        .merchant-main .text-teal-600, .merchant-main .text-teal-700 { color: var(--rawaa-forest) !important; }
        .merchant-main .bg-teal-50 { background-color: #eff5ed !important; }
        .merchant-main .bg-teal-100 { background-color: #deeadf !important; }
        .merchant-main .border-teal-500 { border-color: var(--rawaa-forest) !important; }
        .merchant-main .focus\\:border-teal-500:focus { border-color: var(--rawaa-forest) !important; }
        .merchant-main .focus\:ring-teal-500:focus { --tw-ring-color: rgba(31, 90, 67, .24) !important; }

        /* حقول واضحة دائماً: لا نعتمد على التركيز وحده لإظهار موضع الإدخال. */
        .merchant-main :where(input:not([type='checkbox']):not([type='radio']):not([type='color']):not(.sr-only), select, textarea) {
            width: 100%;
            min-height: 2.9rem;
            padding: .68rem .82rem;
            color: #1f3329 !important;
            background: #fff !important;
            border: 1px solid #b7c4ba !important;
            border-radius: .78rem !important;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.92), 0 1px 2px rgba(22,59,45,.04);
            transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
        }
        .merchant-main textarea { min-height: 6.75rem; line-height: 1.65; }
        .merchant-main :where(input, textarea)::placeholder { color: #87948b !important; opacity: 1; }
        .merchant-main :where(input:not([type='checkbox']):not([type='radio']):not([type='color']):not(.sr-only), select, textarea):hover { border-color: #8fa99a !important; }
        .merchant-main :where(input:not([type='checkbox']):not([type='radio']):not([type='color']):not(.sr-only), select, textarea):focus-visible {
            outline: 0;
            border-color: var(--rawaa-forest) !important;
            box-shadow: 0 0 0 4px rgba(31,90,67,.14), inset 0 1px 0 rgba(255,255,255,.96) !important;
        }
        .merchant-main :where(input, select, textarea):disabled { cursor: not-allowed; color: #89948c !important; background: #f1f3ee !important; border-color: #d5ddd4 !important; }
        .merchant-main input[type='checkbox'], .merchant-main input[type='radio'] { width: 1.1rem; height: 1.1rem; accent-color: var(--rawaa-forest); }
        .merchant-main input[type='color'] { width: 3rem; min-height: 2.9rem; padding: .22rem; background: #fff; border: 1px solid #b7c4ba; border-radius: .78rem; }
        .merchant-main label { color: #30483a; }

        .merchant-page-intro { position: relative; overflow: hidden; padding: 1.5rem 1.6rem; border: 1px solid var(--rawaa-line); border-radius: 1.25rem; background: linear-gradient(115deg, rgba(255,255,252,.96), rgba(230,238,225,.9)); box-shadow: 0 12px 28px rgba(32, 65, 49, .05); }
        .merchant-page-intro::after { content: ''; position: absolute; width: 9.5rem; height: 9.5rem; border: 18px solid rgba(111, 142, 96, .12); border-radius: 50%; left: -3.8rem; top: -4.2rem; }
        .merchant-page-intro > * { position: relative; z-index: 1; }
        .merchant-stat-card { border-color: var(--rawaa-line) !important; background: rgba(255,255,252,.94) !important; }
        .merchant-stat-card:hover { border-color: #cad8c5 !important; box-shadow: 0 16px 34px rgba(32, 65, 49, .09) !important; }

        .merchant-login-page { background: transparent !important; }
        .merchant-login-page::before { content: ''; position: fixed; width: 28rem; height: 28rem; border-radius: 50%; top: -15rem; right: -10rem; background: rgba(211, 224, 204, .68); filter: blur(1px); pointer-events: none; }
        .merchant-login-page::after { content: ''; position: fixed; width: 22rem; height: 22rem; border-radius: 50%; bottom: -12rem; left: -8rem; background: rgba(234, 223, 202, .75); pointer-events: none; }
        .merchant-login-card { position: relative; overflow: hidden; border-color: var(--rawaa-line) !important; box-shadow: 0 22px 55px rgba(32, 65, 49, .12) !important; }
        .merchant-login-card::before { content: ''; position: absolute; top: 0; right: 0; left: 0; height: 4px; background: linear-gradient(90deg, #b98b59, #1f5a43, #a9bc99); }

        /* مركز إشعارات موحد: رسائل قصيرة، قابلة للإغلاق، ولا تقاطع سير العمل. */
        .merchant-toast-stack { position: fixed; z-index: 80; top: 1rem; left: 1rem; width: min(24rem, calc(100vw - 2rem)); display: grid; gap: .65rem; pointer-events: none; }
        .merchant-toast { display: grid; grid-template-columns: 2rem 1fr auto; align-items: start; gap: .65rem; padding: .85rem; border: 1px solid; border-radius: 1rem; background: rgba(255,255,252,.98); box-shadow: 0 18px 42px rgba(22,59,45,.16); pointer-events: auto; backdrop-filter: blur(12px); }
        .merchant-toast__icon { width: 2rem; height: 2rem; display: grid; place-items: center; border-radius: .7rem; }
        .merchant-toast__title { margin: .1rem 0 .18rem; color: #1d3428; font-size: .84rem; font-weight: 900; }
        .merchant-toast__message { margin: 0; color: #516057; font-size: .78rem; font-weight: 600; line-height: 1.55; }
        .merchant-toast__close { width: 1.75rem; height: 1.75rem; display: grid; place-items: center; color: #6b786f; border: 0; border-radius: .55rem; background: transparent; font-size: 1.25rem; line-height: 1; }
        .merchant-toast__close:hover { color: #263c30; background: #edf1ec; }
        .merchant-toast--success { border-color: #b9dbbe; }.merchant-toast--success .merchant-toast__icon { color: #23713d; background: #e6f5e8; }
        .merchant-toast--error { border-color: #f1c8c8; }.merchant-toast--error .merchant-toast__icon { color: #a43d3d; background: #fff0f0; }
        .merchant-toast--warning { border-color: #ecd79e; }.merchant-toast--warning .merchant-toast__icon { color: #8b6519; background: #fff8df; }
        .merchant-toast--info { border-color: #b7d5d0; }.merchant-toast--info .merchant-toast__icon { color: #1f5a43; background: #e6f2ef; }
        .merchant-confirm-backdrop { position: fixed; z-index: 90; inset: 0; display: grid; place-items: center; padding: 1rem; background: rgba(18,35,27,.48); backdrop-filter: blur(3px); }
        .merchant-confirm { width: min(100%, 27rem); overflow: hidden; border: 1px solid rgba(255,255,255,.3); border-radius: 1.25rem; background: #fff; box-shadow: 0 28px 72px rgba(10,25,18,.3); }
        .merchant-confirm__body { padding: 1.45rem; }.merchant-confirm__badge { width: 2.7rem; height: 2.7rem; display: grid; place-items: center; color: #a33f40; border-radius: .9rem; background: #fff0f0; }
        .merchant-confirm__title { margin: 1rem 0 .45rem; color: #24382d; font-size: 1.08rem; font-weight: 900; }.merchant-confirm__message { margin: 0; color: #627067; font-size: .86rem; font-weight: 600; line-height: 1.65; }
        .merchant-confirm__actions { display: flex; flex-direction: column-reverse; gap: .65rem; padding: 1rem 1.45rem 1.35rem; border-top: 1px solid #e5ebe3; background: #fbfcfa; }.merchant-confirm__actions button { min-height: 2.7rem; padding: .65rem 1rem; border-radius: .78rem; font: inherit; font-size: .84rem; font-weight: 800; }.merchant-confirm__cancel { color: #526158; border: 1px solid #cad5cc; background: #fff; }.merchant-confirm__accept { color: #fff; border: 1px solid #a34748; background: #a34748; }.merchant-confirm__accept:hover { background: #8c393a; }
        @media (min-width: 480px) { .merchant-confirm__actions { flex-direction: row; justify-content: flex-end; } }

        @media (max-width: 980px) {
            .merchant-nav { gap: 1rem; }
            .merchant-help { display: none; }
            .merchant-link { padding-inline: .62rem; }
        }
        @media (max-width: 640px) {
            .merchant-topbar__items { gap: .7rem; }
            .merchant-topbar__item:nth-child(2) { display: none; }
            .merchant-nav { min-height: 70px; padding-inline: 1rem; gap: .8rem; }
            .merchant-brand__sub { display: none; }
            .merchant-links { order: 3; position: absolute; top: 70px; right: 0; left: 0; padding: .5rem 1rem; background: rgba(255, 255, 252, .98); border-bottom: 1px solid var(--rawaa-line); }
            .merchant-header { margin-bottom: 46px; }
            .merchant-link.is-active::after { bottom: .2rem; }
            .merchant-logout span { display: none; }
            .merchant-logout { width: 39px; padding: 0; }
        }
    </style>

    <script>
        // حماية HTML تنفذ خادمياً عبر middleware('auth') في routes/web.php.
        // يبقى token القصير فقط لمصادقة /api الحالية إلى حين ترحيلها إلى Sanctum SPA cookies.
        function merchantUi() {
            return {
                notices: [],
                confirm: { open: false, title: '', message: '', confirmText: 'تأكيد', resolve: null },
                notify(detail = {}) {
                    const id = `${Date.now()}-${Math.random().toString(16).slice(2)}`;
                    const type = ['success', 'error', 'warning', 'info'].includes(detail.type) ? detail.type : 'info';
                    const item = { id, type, title: detail.title || this.noticeTitle(type), message: detail.message || '' };
                    this.notices.push(item);
                    window.setTimeout(() => this.dismiss(id), detail.duration || (type === 'error' ? 6500 : 4500));
                },
                noticeTitle(type) { return { success: 'تم بنجاح', error: 'تعذر إتمام العملية', warning: 'تنبيه', info: 'معلومة' }[type]; },
                dismiss(id) { this.notices = this.notices.filter(item => item.id !== id); },
                openConfirm(detail = {}) {
                    this.confirm = { open: true, title: detail.title || 'هل أنت متأكد؟', message: detail.message || '', confirmText: detail.confirmText || 'تأكيد', resolve: detail.resolve || null };
                },
                answerConfirm(result) {
                    const resolve = this.confirm.resolve;
                    this.confirm.open = false;
                    this.confirm.resolve = null;
                    if (typeof resolve === 'function') resolve(result);
                },
            };
        }

        window.merchantNotify = (type, message, title = null) => window.dispatchEvent(new CustomEvent('merchant:notify', { detail: { type, message, title } }));
        window.merchantConfirm = (options = {}) => new Promise(resolve => window.dispatchEvent(new CustomEvent('merchant:confirm', { detail: { ...options, resolve } })));

        async function logoutMerchant() {
            const token = localStorage.getItem('merchant_token');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            try {
                if (token) {
                    await fetch('/api/logout', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'Authorization': `Bearer ${token}`
                        }
                    });
                }
            } catch (error) {
                console.error('API logout failed:', error);
            }

            try {
                await fetch('/logout', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
            } catch (error) {
                console.error('Session logout failed:', error);
            } finally {
                localStorage.removeItem('merchant_token');
                localStorage.removeItem('merchant_id');
                window.location.href = '/login';
            }
        }
    </script>
</head>
<body class="merchant-app" x-data="merchantUi()" @merchant:notify.window="notify($event.detail)" @merchant:confirm.window="openConfirm($event.detail)">
    <div class="merchant-toast-stack" aria-live="polite" aria-atomic="true">
        <template x-for="item in notices" :key="item.id">
            <div class="merchant-toast" :class="`merchant-toast--${item.type}`" :role="item.type === 'error' ? 'alert' : 'status'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 -translate-y-2">
                <span class="merchant-toast__icon" aria-hidden="true"><template x-if="item.type === 'success'"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m5 12 4 4L19 6"/></svg></template><template x-if="item.type === 'error'"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="9"/><path d="M12 8v5m0 3h.01"/></svg></template><template x-if="item.type === 'warning'"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 3 2.8 20h18.4L12 3Z"/><path d="M12 9v4m0 3h.01"/></svg></template><template x-if="item.type === 'info'"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="9"/><path d="M12 11v5m0-8h.01"/></svg></template></span>
                <div><p class="merchant-toast__title" x-text="item.title"></p><p class="merchant-toast__message" x-text="item.message"></p></div>
                <button type="button" class="merchant-toast__close" @click="dismiss(item.id)" aria-label="إغلاق الإشعار">×</button>
            </div>
        </template>
    </div>
    <div x-show="confirm.open" x-cloak class="merchant-confirm-backdrop" @keydown.escape.window="answerConfirm(false)" @click.self="answerConfirm(false)">
        <section class="merchant-confirm" role="alertdialog" aria-modal="true" aria-labelledby="merchant-confirm-title" aria-describedby="merchant-confirm-message" x-transition>
            <div class="merchant-confirm__body"><div class="merchant-confirm__badge" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M9 6V4h6v2m-9 0 1 14h10l1-14M10 10v6m4-6v6"/></svg></div><h2 id="merchant-confirm-title" class="merchant-confirm__title" x-text="confirm.title"></h2><p id="merchant-confirm-message" class="merchant-confirm__message" x-text="confirm.message"></p></div>
            <div class="merchant-confirm__actions"><button type="button" class="merchant-confirm__cancel" @click="answerConfirm(false)">إلغاء</button><button type="button" class="merchant-confirm__accept" @click="answerConfirm(true)" x-text="confirm.confirmText"></button></div>
        </section>
    </div>
    @if(!request()->is('login'))
        <header class="merchant-header">
            <div class="merchant-topbar">
                <div class="merchant-topbar__inner">
                    <div class="merchant-topbar__items">
                        <span class="merchant-topbar__item"><span class="merchant-topbar__dot"></span> مساحة عملك متاحة الآن</span>
                        <span class="merchant-topbar__item">إدارة أوضح، خدمة أقرب</span>
                    </div>
                    <span class="merchant-topbar__item">رَوَاء للتجارة الصحية</span>
                </div>
            </div>

            <nav class="merchant-nav" aria-label="التنقل الرئيسي للوحة التاجر">
                <a href="/dashboard" class="merchant-brand" aria-label="العودة إلى لوحة التحكم">
                    <span class="merchant-brand__mark" aria-hidden="true"><img src="{{ asset('brand/rawaa-mark.svg') }}" alt=""></span>
                    <span><span class="merchant-brand__name">رَوَاء</span><span class="merchant-brand__sub">مساحة التاجر</span></span>
                </a>

                <div class="merchant-links">
                    <a href="/dashboard" class="merchant-link {{ request()->is('dashboard') ? 'is-active' : '' }}">التقارير</a>
                    <a href="/admin/inbox" class="merchant-link {{ request()->is('admin/inbox') ? 'is-active' : '' }}">صندوق الرسائل</a>
                    <a href="/admin/customers" class="merchant-link {{ request()->is('admin/customers') ? 'is-active' : '' }}">الزبائن</a>
                    <a href="/admin/products" class="merchant-link {{ request()->is('admin/products*') ? 'is-active' : '' }}">المنتجات</a>
                    <a href="/admin/engagement" class="merchant-link {{ request()->is('admin/engagement') ? 'is-active' : '' }}">الآراء والمفضلة</a>
                    <a href="/admin/categories" class="merchant-link {{ request()->is('admin/categories*') ? 'is-active' : '' }}">الفئات</a>
                    <a href="/admin/whatsapp" class="merchant-link {{ request()->is('admin/whatsapp') ? 'is-active' : '' }}">واتساب</a>
                    <a href="/admin/settings" class="merchant-link {{ request()->is('admin/settings') ? 'is-active' : '' }}">الإعدادات</a>
                </div>

                <div class="merchant-actions">
                    <span class="merchant-help">مركز مساعدة التاجر</span>
                    <button type="button" onclick="logoutMerchant()" class="merchant-logout" aria-label="تسجيل الخروج">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        <span>خروج</span>
                    </button>
                </div>
            </nav>
        </header>
    @endif

    <main class="merchant-main">
        @yield('content')
    </main>
</body>
</html>
