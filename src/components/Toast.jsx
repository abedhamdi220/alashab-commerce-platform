import React, { useEffect, useState } from 'react';
import { CheckCircle, X, AlertCircle, Info } from 'lucide-react';

export const Toast = ({ message, isOpen, onClose, duration = 3000, type = 'success' }) => {
    const [progress, setProgress] = useState(100);

    // [تصحيح]: كان في setInterval يحدّث الـ state كل 10ms (~100 render بالثانية) بس عشان يرسم
    // شريط تقدم بسيط — استهلاك أداء وبطارية بدون داعي. الآن نعتمد على CSS transition واحد:
    // نبدأ من 100% ثم بعد إطار واحد (requestAnimationFrame) ننزل لـ 0%، والمتصفح نفسه يرسم
    // الحركة على مدار "duration" بدل ما يعيد React يحسبها يدوياً كل 10ms
    useEffect(() => {
        if (isOpen) {
            setProgress(100);
            const frame = requestAnimationFrame(() => setProgress(0));

            const timer = setTimeout(() => {
                onClose();
            }, duration);

            return () => {
                clearTimeout(timer);
                cancelAnimationFrame(frame);
            };
        }
    }, [isOpen, duration, onClose]);

    if (!isOpen) return null;

    // تم التحديث ليتوافق مع ألوان الهوية البصرية العصرية (Wellness)
    const styles = {
        success: {
            icon: <CheckCircle className="w-5 h-5 text-[var(--color-primary)] shrink-0" strokeWidth={1.5} />,
            bar: 'bg-[var(--color-primary)]' // الأخضر الزمردي
        },
        error: {
            icon: <AlertCircle className="w-5 h-5 text-[var(--color-energy)] shrink-0" strokeWidth={1.5} />,
            bar: 'bg-[var(--color-energy)]' // البرتقالي الطاقي للتنبيهات
        },
        info: {
            icon: <Info className="w-5 h-5 text-[var(--color-trust)] shrink-0" strokeWidth={1.5} />,
            bar: 'bg-[var(--color-trust)]' // الأزرق للثقة والمعلومات
        }
    };

    const currentStyle = styles[type] || styles.success;

    return (
        <div
            // استخدام متغيرات الهوية الجديدة: لون الخلفية النظيف، الظل الناعم، والحدود الهادئة
            className="fixed bottom-6 right-6 z-50 flex flex-col overflow-hidden min-w-[280px] bg-[var(--color-bg)] text-[var(--color-ink)] shadow-[var(--shadow-soft)] border border-[var(--color-line)] transition-all duration-300 ease-out"
            style={{ borderRadius: 'var(--radius-md)' }}
        >
            <div className="flex items-center gap-3 px-4 py-3.5">
                {currentStyle.icon}
                <span className="text-sm font-ibm font-medium flex-1">{message}</span>
                <button
                    onClick={onClose}
                    className="ml-1 opacity-50 hover:opacity-100 p-1 transition-opacity min-w-[28px] min-h-[28px] flex items-center justify-center text-[var(--color-ink-soft)]"
                    aria-label="إغلاق التنبيه"
                >
                    <X className="w-4 h-4" strokeWidth={1.5} />
                </button>
            </div>

            {/* شريط التقدم المرئي */}
            <div className="h-[3px] bg-[var(--color-surface)] w-full">
                <div
                    className={`h-full ease-linear ${currentStyle.bar}`}
                    style={{ width: `${progress}%`, transitionProperty: 'width', transitionDuration: `${duration}ms` }}
                />
            </div>
        </div>
    );
};
