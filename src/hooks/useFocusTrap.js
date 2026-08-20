import { useEffect, useRef } from 'react';

// [جديد]: هوك مشترك بدل تكرار نفس منطق "قفل الفوكس جوا المودال" بثلاث ملفات
// (ProductDetailsModal / CartDrawer / CareAdvisorModal). كل مودال كان إما:
//  - يتعامل مع Escape فقط ويسمح لـ Tab يهرب برا المودال بالكامل لعناصر تحت الطبقة الشفافة
//    (ProductDetailsModal، CartDrawer)، أو
//  - ما فيه أي تعامل مع لوحة المفاتيح إطلاقاً (CareAdvisorModal).
// هالهوك يوفر: قفل التمرير بالخلفية، إغلاق بـ Escape، حبس Tab/Shift+Tab داخل المودال،
// تركيز أول عنصر تفاعلي عند الفتح، وإرجاع الفوكس تلقائياً للعنصر اللي كان عليه المستخدم
// قبل ما يفتح المودال (زر "إضافة للسلة" مثلاً) عند الإغلاق — مهم لقارئات الشاشة ولمستخدمي
// لوحة المفاتيح يلي بيفقدوا مكانهم بالصفحة كل ما يفتح/يسكر مودال.
const FOCUSABLE_SELECTOR = [
    'a[href]',
    'button:not([disabled])',
    'textarea:not([disabled])',
    'input:not([disabled])',
    'select:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

let activeBodyLocks = 0;
let previousBodyOverflow = '';

export const useFocusTrap = (isOpen, onClose) => {
    const containerRef = useRef(null);
    const previouslyFocusedRef = useRef(null);

    useEffect(() => {
        if (!isOpen) return undefined;

        const container = containerRef.current;
        previouslyFocusedRef.current = document.activeElement;

        // نأجل تركيز أول عنصر لإطار واحد لنعطي فرصة لحركة الدخول (fade/scale) تبدأ أولاً
        const focusFirstElement = () => {
            const focusables = container?.querySelectorAll(FOCUSABLE_SELECTOR);
            if (focusables && focusables.length > 0) {
                focusables[0].focus();
            } else {
                container?.focus();
            }
        };
        const raf = requestAnimationFrame(focusFirstElement);

        const handleKeyDown = (e) => {
            if (e.key === 'Escape') {
                onClose?.();
                return;
            }

            if (e.key !== 'Tab' || !container) return;

            const focusables = Array.from(container.querySelectorAll(FOCUSABLE_SELECTOR))
                .filter((el) => el.offsetParent !== null); // تجاهل عناصر مخفية (display:none وغيرها)

            if (focusables.length === 0) {
                e.preventDefault();
                return;
            }

            const first = focusables[0];
            const last = focusables[focusables.length - 1];

            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault();
                first.focus();
            }
        };

        if (activeBodyLocks === 0) {
            previousBodyOverflow = document.body.style.overflow;
        }
        activeBodyLocks += 1;
        document.body.style.overflow = 'hidden';
        document.addEventListener('keydown', handleKeyDown);

        return () => {
            cancelAnimationFrame(raf);
            activeBodyLocks = Math.max(0, activeBodyLocks - 1);
            if (activeBodyLocks === 0) {
                document.body.style.overflow = previousBodyOverflow;
            }
            document.removeEventListener('keydown', handleKeyDown);
            // إرجاع الفوكس للعنصر السابق — يفشل بصمت لو العنصر زال من الـ DOM (مثلاً كرت منتج
            // اختفى بعد فلترة)، ما في داعي نكسر التطبيق لأجل هيك حالة نادرة
            previouslyFocusedRef.current?.focus?.();
        };
    }, [isOpen, onClose]);

    return containerRef;
};
