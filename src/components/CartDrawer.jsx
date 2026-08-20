import React, { useEffect, useMemo, useRef, useState } from 'react';
import { X, Trash2, Plus, Minus, ShoppingBag, Send, PlusCircle, Lock, Loader2, Truck, CheckCircle2, ExternalLink, ArrowRight } from 'lucide-react';
import { useSettings } from '../hooks/useSettings';
import { useFocusTrap } from '../hooks/useFocusTrap';
import apiClient from '../services/api';
import { useToast } from '../context/ToastContext';

export const CartDrawer = ({ isOpen, onClose, cart, onUpdateQuantity, onRemoveFromCart, onAddToCart, onCheckoutSuccess, products = [] }) => {
    const [customerName, setCustomerName] = useState('');
    const [customerPhone, setCustomerPhone] = useState('');
    const [customerAddress, setCustomerAddress] = useState('');
    const [orderNote, setOrderNote] = useState('');
    const [hasAcceptedOrderPrivacy, setHasAcceptedOrderPrivacy] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [submittedOrder, setSubmittedOrder] = useState(null);
    const [isCheckoutStep, setIsCheckoutStep] = useState(false);
    const [removedItem, setRemovedItem] = useState(null);
    const removalTimerRef = useRef(null);
    const { currency, freeShippingThreshold } = useSettings();
    const { showError } = useToast();

    // [تصحيح]: نفس ملاحظة ProductDetailsModal — كان Escape بس بدون حبس Tab. أصبح موحّداً
    // بالهوك المشترك، ومربوط بحاوية المودال (role="dialog") أدناه عبر modalRef
    const modalRef = useFocusTrap(isOpen, onClose);

    // [تصحيح]: كانت هذي مصفوفة ثابتة بمنتجات وهمية (up1/up2) بصور placeholder ثابتة.
    // الآن نقترح منتجات حقيقية من الكتالوج الفعلي غير الموجودة أصلاً بالسلة.
    const upsellProducts = useMemo(() => {
        const cartIds = new Set(cart.map((item) => item.id));
        return products.filter((p) => !cartIds.has(p.id)).slice(0, 2);
    }, [cart, products]);

    const subtotal = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
    const freeShippingProgress = freeShippingThreshold > 0
        ? Math.min((subtotal / freeShippingThreshold) * 100, 100)
        : 100;
    const remainingForFreeShipping = Math.max(freeShippingThreshold - subtotal, 0);

    useEffect(() => () => {
        if (removalTimerRef.current) window.clearTimeout(removalTimerRef.current);
    }, []);

    useEffect(() => {
        if (cart.length === 0) setIsCheckoutStep(false);
    }, [cart.length]);

    const handleRemoveItem = (item) => {
        onRemoveFromCart(item.id);
        setRemovedItem(item);
        if (removalTimerRef.current) window.clearTimeout(removalTimerRef.current);
        removalTimerRef.current = window.setTimeout(() => {
            setRemovedItem(null);
            removalTimerRef.current = null;
        }, 6000);
    };

    const handleUndoRemove = () => {
        if (!removedItem) return;
        if (removalTimerRef.current) window.clearTimeout(removalTimerRef.current);
        onAddToCart?.({ ...removedItem, quantity: removedItem.quantity });
        setRemovedItem(null);
        removalTimerRef.current = null;
    };

    const handleCheckout = async (e) => {
        e.preventDefault();
        if (cart.length === 0) return;

        // [تصحيح]: ما كان في أي تحقق بالفرونت — كان ممكن تتأكد الطلب بحقول فاضية بالكامل.
        // هذا تحقق أساسي فقط لتحسين التجربة؛ الباك اند يبقى المرجع النهائي والملزم للتحقق دائماً
        const trimmedName = customerName.trim();
        const trimmedPhone = customerPhone.trim();
        const trimmedAddress = customerAddress.trim();
        if (!trimmedName || !trimmedPhone || !trimmedAddress) {
            showError('الرجاء تعبئة الاسم ورقم التواصل والمدينة/المنطقة قبل تأكيد الطلب.');
            return;
        }
        if (!hasAcceptedOrderPrivacy) {
            showError('يرجى تأكيد اطلاعك على كيفية استخدام بيانات الطلب قبل المتابعة.');
            return;
        }

        setIsSubmitting(true);

        try {
            // الـ backend هو المسؤول عن فحص is_discreet وحساب السعر الحقيقي وتوليد رابط واتساب النهائي
            // موحّدة الآن عبر apiClient بدل fetch خام بمسار '/api/...' مكتوب يدوياً، لضمان اتفاقها
            // مع باقي الطلبات على نفس baseURL والـ interceptors (بدل فشل صامت لو اختلف الـ origin)
            const { data } = await apiClient.post('/checkout/build-message', {
                customer: {
                    name: trimmedName,
                    phone: trimmedPhone,
                    address: trimmedAddress,
                    note: orderNote.trim()
                },
                // نرسل المعرفات والكميات فقط للـ backend لمطابقتها مع قاعدة البيانات
                items: cart.map(item => ({ id: item.id, quantity: item.quantity }))
            });

            if (!data?.whatsappUrl) {
                showError(data?.message || 'رقم واتساب هذا المتجر غير مضبوط. يرجى أن يضيف التاجر رقماً دولياً صالحاً من الإعدادات.');
                return;
            }

            // نعرض خطوة نجاح صريحة؛ يبقى فتح واتساب إجراءً مباشرًا من المستخدم
            // ولا تضيع حالة الطلب إذا حظر المتصفح النوافذ المنبثقة.
            setSubmittedOrder({
                whatsappUrl: data.whatsappUrl,
                reference: data.order_number || data.orderNumber || data.order_id || data.id || null,
            });
        } catch (error) {
            console.error("حدث خطأ أثناء معالجة الطلب:", error);
            // [تصحيح]: كانت رسالة عامة واحدة تخفي سبب الرفض الحقيقي (مثلاً رقم هاتف بصيغة غلط
            // برجعها الباك اند بخطأ 422) — الآن نعرض رسالة الباك اند الفعلية لو متوفرة
            const backendMessage =
                error.response?.data?.message ||
                Object.values(error.response?.data?.errors || {})?.[0]?.[0];
            const fallbackMessage = error.response?.status === 404
                ? 'تعذر الوصول إلى خدمة تأكيد الطلب. يرجى التحقق من إعداد اتصال المتجر ثم المحاولة.'
                : 'تعذر إتمام الطلب. يرجى المحاولة مرة أخرى.';
            showError(backendMessage || fallbackMessage);
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <div className={`fixed inset-0 z-50 overflow-hidden transition-opacity duration-300 ${isOpen ? 'opacity-100' : 'opacity-0 pointer-events-none'}`} aria-hidden={!isOpen}>
            {/* طبقة التعتيم تنسحب مع اللوحة بدلاً من اختفائها فوراً */}
            <div
                className={`absolute inset-0 bg-[var(--color-ink)]/40 backdrop-blur-sm transition-opacity duration-300 ${isOpen ? 'opacity-100' : 'opacity-0'}`}
                onClick={onClose}
                aria-label="إغلاق السلة"
            />

            <div className="fixed inset-y-0 left-0 max-w-full flex pl-0 sm:pl-10">
                <div
                    ref={modalRef}
                    tabIndex={-1}
                    role="dialog"
                    aria-modal="true"
                    aria-label="سلة المشتريات"
                    className={`w-screen max-w-md bg-[var(--color-bg)] shadow-[var(--shadow-soft)] border-l border-[var(--color-line)] flex min-h-0 flex-col transform transition-transform duration-300 ease-out ${isOpen ? 'translate-x-0' : '-translate-x-full'}`}
                >
                    {/* رأس السلة العصري */}
                    <div className="p-5 border-b border-[var(--color-line)] bg-[var(--color-surface)] flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <ShoppingBag className="w-6 h-6 text-[var(--color-primary)]" strokeWidth={2} />
                            <h2 className="font-cairo font-black text-2xl text-[var(--color-ink)]">سلة المشتريات</h2>
                            <span className="bg-[var(--color-primary)] text-white font-readex font-bold text-xs px-2.5 py-1" style={{ borderRadius: 'var(--radius-md)' }} aria-live="polite" aria-label={`عدد عناصر السلة: ${cart.reduce((acc, i) => acc + i.quantity, 0)} `}>
                                {cart.reduce((acc, i) => acc + i.quantity, 0)}
                            </span>
                        </div>
                        <button
                            type="button"
                            onClick={onClose}
                            className="p-2 text-[var(--color-ink-soft)] hover:text-[var(--color-primary)] bg-[var(--color-bg)] border border-[var(--color-line)] transition-colors"
                            style={{ borderRadius: 'var(--radius-md)' }}
                        >
                            <X className="w-5 h-5" strokeWidth={2} />
                        </button>
                    </div>

                    {/* شريط تقدم التوصيل المجاني يظهر أثناء مراجعة السلة فقط. */}
                    {!isCheckoutStep && !submittedOrder && cart.length > 0 && (
                    <div className="bg-[var(--color-surface)] p-5 border-b border-[var(--color-line)] space-y-3">
                        <div className="flex justify-between text-sm items-center font-ibm font-medium">
                            {remainingForFreeShipping > 0 ? (
                                <span className="text-[var(--color-ink)]">
                                    أضيفي بقيمة <span className="text-[var(--color-energy)] font-bold font-readex">{remainingForFreeShipping} {currency}</span> لشحن مجاني
                                </span>
                            ) : (
                                <span className="text-[var(--color-primary)] font-bold flex items-center gap-2">
                                    ألف مبروك! الشحن المجاني مُفعل
                                </span>
                            )}
                            {/* نسبة مئوية بخط Readex Pro كما هو مطلوب */}
                            <span className="text-[var(--color-ink-soft)] font-readex font-bold text-xs bg-[var(--color-bg)] px-2 py-0.5 rounded-full border border-[var(--color-line)]">
                                {Math.round(freeShippingProgress)}%
                            </span>
                        </div>
                        <div className="w-full bg-[var(--color-line)] h-2 overflow-hidden" style={{ borderRadius: 'var(--radius-md)' }}>
                            <div
                                className="h-full transition-all duration-700 ease-out"
                                style={{
                                    width: `${freeShippingProgress}%`,
                                    backgroundColor: 'var(--color-primary)',
                                }}
                            />
                        </div>
                    </div>
                    )}

                    {/* قائمة عناصر السلة — لا تُعرض بجانب النموذج حتى لا تُضغط أو تتداخل معه. */}
                    <div className={`flex-1 min-h-0 overflow-y-auto p-5 space-y-4 no-scrollbar bg-[var(--color-bg)] ${isCheckoutStep && !submittedOrder ? 'hidden' : ''}`}>
                        {submittedOrder ? (
                            <div className="max-w-sm mx-auto text-center py-14 space-y-5" role="status" aria-live="polite">
                                <div className="w-16 h-16 rounded-full bg-[var(--color-primary-soft)] text-[var(--color-primary)] grid place-items-center mx-auto border border-[var(--color-line)]">
                                    <CheckCircle2 size={34} strokeWidth={1.8} />
                                </div>
                                <div className="space-y-2">
                                    <h3 className="font-cairo font-black text-2xl text-[var(--color-ink)]">تم تجهيز طلبك</h3>
                                    <p className="font-ibm text-sm text-[var(--color-ink-soft)] leading-7">
                                        افتحي واتساب لإرسال تفاصيل الطلب إلينا وإتمام التأكيد مع فريق الدعم.
                                    </p>
                                    {submittedOrder.reference && (
                                        <p className="font-readex text-xs text-[var(--color-ink-soft)]">مرجع الطلب: {submittedOrder.reference}</p>
                                    )}
                                </div>
                                <button
                                    type="button"
                                    onClick={() => window.open(submittedOrder.whatsappUrl, '_blank', 'noopener,noreferrer')}
                                    className="w-full bg-[var(--color-primary)] text-white font-ibm font-medium py-3.5 px-4 transition-all inline-flex justify-center items-center gap-2"
                                    style={{ borderRadius: 'var(--radius-lg)' }}
                                >
                                    <ExternalLink size={18} strokeWidth={2} />
                                    فتح واتساب لإرسال الطلب
                                </button>
                                <button
                                    type="button"
                                    onClick={() => {
                                        onCheckoutSuccess?.();
                                        setSubmittedOrder(null);
                                        setCustomerName('');
                                        setCustomerPhone('');
                                        setCustomerAddress('');
                                        setOrderNote('');
                                        setHasAcceptedOrderPrivacy(false);
                                        setIsCheckoutStep(false);
                                        onClose();
                                    }}
                                    className="w-full py-3 text-sm font-ibm font-medium text-[var(--color-primary)] border border-[var(--color-line)] hover:border-[var(--color-primary)] transition-colors"
                                    style={{ borderRadius: 'var(--radius-lg)' }}
                                >
                                    العودة للتسوق وبدء طلب جديد
                                </button>
                            </div>
                        ) : cart.length === 0 ? (
                            <div className="text-center py-20 space-y-5">
                                <div className="w-20 h-20 bg-[var(--color-surface)] rounded-full flex items-center justify-center mx-auto border border-[var(--color-line)]">
                                    <ShoppingBag className="w-8 h-8 text-[var(--color-line)]" strokeWidth={1.5} />
                                </div>
                                <div className="space-y-2">
                                    <p className="font-cairo font-black text-2xl text-[var(--color-ink)]">سلتك فارغة حالياً</p>
                                    <p className="font-ibm text-[var(--color-ink-soft)] text-sm">تصفحي منتجاتنا واختاري ما يناسبك.</p>
                                </div>
                                <button
                                    onClick={onClose}
                                    className="inline-block mt-4 text-[var(--color-primary)] font-ibm font-medium text-sm border-b-2 border-transparent hover:border-[var(--color-primary)] transition-colors"
                                >
                                    العودة للتسوق
                                </button>
                            </div>
                        ) : (
                            <>
                                <div className="space-y-4">
                                    {cart.map((item) => (
                                        <div
                                            key={item.id}
                                            className="bg-[var(--color-surface)] p-3 border border-[var(--color-line)] flex gap-4 items-center transition-all hover:shadow-[var(--shadow-soft)] relative overflow-hidden"
                                            style={{ borderRadius: 'var(--radius-md)' }}
                                        >
                                            {/* شريط لوني جانبي رفيع للفئة */}
                                            {item.category?.accent_color && (
                                                <div className="absolute right-0 top-0 bottom-0 w-1" style={{ backgroundColor: item.category.accent_color }} />
                                            )}

                                            <div className="w-20 h-20 bg-[var(--color-bg)] border border-[var(--color-line)] flex items-center justify-center shrink-0 overflow-hidden" style={{ borderRadius: 'var(--radius-md)' }}>
                                                {item.is_discreet ? (
                                                    <div className="flex flex-col items-center gap-1 text-[var(--color-ink-soft)]">
                                                        <Lock strokeWidth={1.5} size={24} />
                                                        <span className="font-ibm text-[9px]">فئة خاصة</span>
                                                    </div>
                                                ) : (
                                                    <img src={item.images?.[0]?.url || item.image_url || '/placeholder.png'} alt={item.name} loading="lazy" className="w-full h-full object-cover" />
                                                )}
                                            </div>
                                            <div className="flex-1 min-w-0 py-1">
                                                <h4 className="font-cairo font-black text-[var(--color-ink)] text-base truncate">
                                                    {item.is_discreet ? 'منتج عناية (فئة خاصة)' : item.name}
                                                </h4>
                                                <p className="font-readex font-bold text-[var(--color-primary)] text-sm mt-1">{item.price} {currency}</p>

                                                <div className="flex items-center gap-3 mt-3">
                                                    <div className="flex items-center bg-[var(--color-bg)] border border-[var(--color-line)] h-8 overflow-hidden" style={{ borderRadius: 'var(--radius-md)' }}>
                                                        <button
                                                            type="button"
                                                            onClick={() => onUpdateQuantity(item.id, item.quantity - 1)}
                                                            className="w-8 h-full flex items-center justify-center text-[var(--color-ink-soft)] hover:text-[var(--color-primary)] hover:bg-[var(--color-surface)] transition-colors disabled:opacity-40"
                                                            disabled={item.quantity <= 1}
                                                            aria-label={`تقليل كمية ${item.is_discreet ? 'المنتج' : item.name}`}
                                                        >
                                                            <Minus size={14} strokeWidth={2} />
                                                        </button>
                                                        <span className="w-8 text-center text-sm font-readex font-bold text-[var(--color-ink)]" aria-live="polite" aria-label={`الكمية: ${item.quantity}`}>
                                                            {item.quantity}
                                                        </span>
                                                        <button
                                                            type="button"
                                                            onClick={() => onUpdateQuantity(item.id, item.quantity + 1)}
                                                            className="w-8 h-full flex items-center justify-center text-[var(--color-ink-soft)] hover:text-[var(--color-primary)] hover:bg-[var(--color-surface)] transition-colors disabled:opacity-40"
                                                            disabled={item.stock_quantity !== null && item.stock_quantity !== undefined && Number.isFinite(Number(item.stock_quantity)) && item.quantity >= Number(item.stock_quantity)}
                                                            aria-label={`زيادة كمية ${item.is_discreet ? 'المنتج' : item.name}`}
                                                        >
                                                            <Plus size={14} strokeWidth={2} />
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <button
                                                type="button"
                                                onClick={() => handleRemoveItem(item)}
                                                className="p-2.5 text-[var(--color-ink-soft)] hover:text-[var(--color-energy)] hover:bg-[var(--color-energy)]/10 rounded-full transition-colors mr-2"
                                                aria-label="إزالة المنتج"
                                            >
                                                <Trash2 className="w-5 h-5" strokeWidth={1.5} />
                                            </button>
                                        </div>
                                    ))}
                                </div>

                                {/* قسم المنتجات المقترحة (Upsell) — منتجات حقيقية من الكتالوج بدل بيانات ثابتة */}
                                {upsellProducts.length > 0 && (
                                    <div className="pt-6 pb-2 mt-6 border-t border-[var(--color-line)]">
                                        <h4 className="font-cairo font-black text-lg text-[var(--color-ink)] mb-4">منتجات قد تهمك:</h4>
                                        <div className="grid grid-cols-2 gap-3">
                                            {upsellProducts.map(product => (
                                                <div key={product.id} className="bg-[var(--color-surface)] border border-[var(--color-line)] p-3 text-center flex flex-col items-center gap-2 hover:border-[var(--color-primary)] transition-colors" style={{ borderRadius: 'var(--radius-md)' }}>
                                                    <div className="w-14 h-14 bg-[var(--color-bg)] border border-[var(--color-line)] rounded-full overflow-hidden mb-1">
                                                        <img src={product.images?.[0]?.url || product.image_url || '/placeholder.png'} alt={product.name} loading="lazy" className="w-full h-full object-cover" />
                                                    </div>
                                                    <div className="w-full">
                                                        <h5 className="font-ibm font-medium text-[var(--color-ink)] text-xs truncate mb-1">{product.name}</h5>
                                                        <span className="font-readex font-bold text-[var(--color-primary)] text-xs block">{product.price} {currency}</span>
                                                    </div>
                                                    <button
                                                        onClick={() => onAddToCart && onAddToCart(product)}
                                                        className="mt-2 w-full py-2 bg-[var(--color-bg)] border border-[var(--color-line)] text-[var(--color-ink)] text-xs font-ibm font-medium hover:border-[var(--color-primary)] hover:text-[var(--color-primary)] transition-colors flex justify-center items-center gap-1.5"
                                                        style={{ borderRadius: 'var(--radius-md)' }}
                                                    >
                                                        <PlusCircle size={14} strokeWidth={1.5} /> إضافة للسلة
                                                    </button>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </>
                        )}
                    </div>

                    {removedItem && !submittedOrder && !isCheckoutStep && (
                        <div className="mx-5 mb-3 rounded-[var(--radius-md)] border border-[var(--color-line)] bg-[var(--color-primary-soft)] px-3 py-2.5 flex items-center justify-between gap-3" role="status" aria-live="polite">
                            <span className="text-xs font-ibm text-[var(--color-ink)] truncate">تمت إزالة {removedItem.is_discreet ? 'المنتج' : removedItem.name}</span>
                            <button type="button" onClick={handleUndoRemove} className="shrink-0 text-xs font-ibm font-bold text-[var(--color-primary)] hover:underline underline-offset-4">
                                تراجع
                            </button>
                        </div>
                    )}

                    {cart.length > 0 && !submittedOrder && !isCheckoutStep && (
                        <div className="shrink-0 p-5 bg-[var(--color-surface)] border-t border-[var(--color-line)] space-y-4 shadow-[0_-8px_24px_rgba(28,61,49,.06)]">
                            <div className="flex items-end justify-between gap-4">
                                <div>
                                    <p className="text-xs font-ibm text-[var(--color-ink-soft)]">الإجمالي الحالي</p>
                                    <p className="mt-1 text-[var(--color-ink)] font-readex font-bold text-2xl tracking-tight">{subtotal} <span className="text-sm font-normal text-[var(--color-ink-soft)]">{currency}</span></p>
                                </div>
                                <span className="text-xs font-ibm text-[var(--color-ink-soft)]">يمكنك مراجعة البيانات قبل الإرسال</span>
                            </div>
                            <button type="button" onClick={() => setIsCheckoutStep(true)} className="w-full bg-[var(--color-primary)] hover:brightness-95 active:scale-[0.98] text-white font-ibm font-medium py-4 px-4 transition-all flex items-center justify-center gap-2 shadow-lg shadow-[var(--color-primary)]/20" style={{ borderRadius: 'var(--radius-lg)' }}>
                                متابعة لبيانات التوصيل
                                <ArrowRight size={20} strokeWidth={2} />
                            </button>
                        </div>
                    )}

                    {/* نموذج الطلب: خطوة مستقلة قابلة للتمرير على الشاشات القصيرة. */}
                    {cart.length > 0 && !submittedOrder && isCheckoutStep && (
                        <form onSubmit={handleCheckout} className="flex-1 min-h-0 overflow-y-auto p-5 bg-[var(--color-surface)] border-t border-[var(--color-line)] space-y-4">
                            <div className="space-y-3">
                                <div className="flex items-start justify-between gap-3 border-b border-[var(--color-line)] pb-3 mb-3">
                                    <div>
                                        <h3 className="font-cairo font-black text-base text-[var(--color-ink)]">بيانات التوصيل السريع</h3>
                                        <p className="mt-1 text-xs font-ibm text-[var(--color-ink-soft)]">الطلب: {cart.reduce((total, item) => total + item.quantity, 0)} منتجات — {subtotal} {currency}</p>
                                    </div>
                                    <button type="button" onClick={() => setIsCheckoutStep(false)} className="inline-flex shrink-0 items-center gap-1 text-xs font-ibm font-bold text-[var(--color-primary)] hover:underline underline-offset-4">
                                        <ArrowRight size={15} /> مراجعة السلة
                                    </button>
                                </div>
                                <div className="space-y-1.5">
                                    <label htmlFor="customer-name" className="block text-sm font-ibm font-medium text-[var(--color-ink)]">الاسم الكامل</label>
                                    <input
                                        id="customer-name"
                                        name="name"
                                        type="text"
                                        autoComplete="name"
                                        required
                                        placeholder="مثال: آية أحمد"
                                        value={customerName}
                                        onChange={(e) => setCustomerName(e.target.value)}
                                        className="w-full bg-[var(--color-bg)] border border-[var(--color-line)] px-4 py-3 text-sm font-ibm text-[var(--color-ink)] focus:outline-none focus:border-[var(--color-primary)] focus:ring-1 focus:ring-[var(--color-primary)] transition-all placeholder:text-[var(--color-ink-soft)]"
                                        style={{ borderRadius: 'var(--radius-md)' }}
                                    />
                                </div>
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div className="space-y-1.5">
                                        <label htmlFor="customer-phone" className="block text-sm font-ibm font-medium text-[var(--color-ink)]">رقم للتواصل</label>
                                        <input
                                            id="customer-phone"
                                            name="tel"
                                            type="tel"
                                            inputMode="tel"
                                            autoComplete="tel"
                                            aria-describedby="customer-phone-hint"
                                            required
                                            placeholder="مثال: 09XXXXXXXX"
                                            value={customerPhone}
                                            onChange={(e) => setCustomerPhone(e.target.value)}
                                            className="w-full bg-[var(--color-bg)] border border-[var(--color-line)] px-4 py-3 text-sm font-ibm text-[var(--color-ink)] focus:outline-none focus:border-[var(--color-primary)] focus:ring-1 focus:ring-[var(--color-primary)] transition-all placeholder:text-[var(--color-ink-soft)]"
                                            style={{ borderRadius: 'var(--radius-md)' }}
                                        />
                                        <p id="customer-phone-hint" className="text-xs text-[var(--color-ink-soft)]">اكتبي رقماً متاحاً للتأكيد عبر واتساب أو الاتصال.</p>
                                    </div>
                                    <div className="space-y-1.5">
                                        <label htmlFor="customer-address" className="block text-sm font-ibm font-medium text-[var(--color-ink)]">المدينة أو المنطقة</label>
                                        <input
                                            id="customer-address"
                                            name="address-level2"
                                            type="text"
                                            autoComplete="address-level2"
                                            required
                                            placeholder="مثال: دمشق — المزة"
                                            value={customerAddress}
                                            onChange={(e) => setCustomerAddress(e.target.value)}
                                            className="w-full bg-[var(--color-bg)] border border-[var(--color-line)] px-4 py-3 text-sm font-ibm text-[var(--color-ink)] focus:outline-none focus:border-[var(--color-primary)] focus:ring-1 focus:ring-[var(--color-primary)] transition-all placeholder:text-[var(--color-ink-soft)]"
                                            style={{ borderRadius: 'var(--radius-md)' }}
                                        />
                                    </div>
                                </div>
                                <div className="space-y-1.5">
                                    <label htmlFor="order-note" className="block text-sm font-ibm font-medium text-[var(--color-ink)]">ملاحظة للطلب <span className="text-[var(--color-ink-soft)] font-normal">(اختيارية)</span></label>
                                    <textarea
                                        id="order-note"
                                        name="note"
                                        placeholder="أي تفاصيل تساعدنا في تأكيد طلبك"
                                        value={orderNote}
                                        onChange={(e) => setOrderNote(e.target.value)}
                                        className="w-full bg-[var(--color-bg)] border border-[var(--color-line)] px-4 py-3 text-sm font-ibm text-[var(--color-ink)] focus:outline-none focus:border-[var(--color-primary)] focus:ring-1 focus:ring-[var(--color-primary)] transition-all resize-none h-16 placeholder:text-[var(--color-ink-soft)]"
                                        style={{ borderRadius: 'var(--radius-md)' }}
                                    />
                                </div>
                                <div className="rounded-[var(--radius-md)] border border-[var(--color-line)] bg-[var(--color-bg)] p-3 space-y-2">
                                    <p className="flex items-center gap-1.5 text-xs font-ibm text-[var(--color-ink-soft)]">
                                        <Truck className="w-3.5 h-3.5 text-[var(--color-primary)] shrink-0" strokeWidth={1.5} />
                                        نوصل لجميع المحافظات السورية — توصيل سريع داخل دمشق
                                    </p>
                                    <p className="text-xs font-ibm text-[var(--color-ink-soft)] leading-5">
                                        نستخدم الاسم ورقم التواصل والمنطقة لتجهيز رسالة الطلب والتواصل لتأكيده. لا تشاركي بيانات بطاقة أو معلومات حساسة في خانة الملاحظات.
                                    </p>
                                    <label htmlFor="order-privacy-consent" className="flex items-start gap-2 cursor-pointer text-xs font-ibm text-[var(--color-ink)] leading-5">
                                        <input
                                            id="order-privacy-consent"
                                            name="order-privacy-consent"
                                            type="checkbox"
                                            required
                                            checked={hasAcceptedOrderPrivacy}
                                            onChange={(event) => setHasAcceptedOrderPrivacy(event.target.checked)}
                                            className="mt-1 size-4 accent-[var(--color-primary)]"
                                        />
                                        <span>أفهم أن هذه البيانات ستُستخدم لتأكيد الطلب والتواصل بشأن التوصيل.</span>
                                    </label>
                                </div>
                            </div>

                            <div className="pt-4 border-t border-[var(--color-line)] flex items-center justify-between">
                                <span className="text-[var(--color-ink-soft)] font-ibm font-medium text-sm">الإجمالي المطلوب:</span>
                                <span className="text-[var(--color-ink)] font-readex font-bold text-2xl tracking-tight">{subtotal} <span className="text-sm font-normal text-[var(--color-ink-soft)]">{currency}</span></span>
                            </div>

                            <button
                                type="submit"
                                disabled={isSubmitting}
                                className="w-full bg-[var(--color-primary)] hover:brightness-95 active:scale-[0.98] disabled:opacity-70 disabled:active:scale-100 text-white font-ibm font-medium py-4 px-4 transition-all flex items-center justify-center gap-2 shadow-lg shadow-[var(--color-primary)]/20"
                                style={{ borderRadius: 'var(--radius-lg)' }}
                            >
                                {isSubmitting ? (
                                    <Loader2 size={20} className="animate-spin" strokeWidth={2} />
                                ) : (
                                    <>
                                        <Send size={20} strokeWidth={2} />
                                        <span>تأكيد الطلب الآن</span>
                                    </>
                                )}
                            </button>
                        </form>
                    )}
                </div>
            </div>
        </div>
    );
};
