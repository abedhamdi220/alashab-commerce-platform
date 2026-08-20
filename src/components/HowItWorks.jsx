import React from 'react';
import { Search, ClipboardList, MessageCircle } from 'lucide-react';

const steps = [
    {
        icon: Search,
        title: 'اختاري منتجك',
        description: 'تصفحي الفئات الخمس واختاري ما يناسب احتياجك — بوضوح ودون تهويل.',
    },
    {
        icon: ClipboardList,
        title: 'أدخلي بياناتك',
        description: 'اسمك، رقم التواصل، والمدينة أو المنطقة — تعبئة سريعة بأقل من دقيقة.',
    },
    {
        icon: MessageCircle,
        title: 'تأكيد فوري عبر واتساب',
        description: 'نتواصل معك مباشرة لتأكيد التفاصيل وطريقة الدفع وموعد التوصيل.',
    },
];

export const HowItWorks = () => {
    return (
        <section className="bg-[var(--color-surface)] border-t border-b border-[var(--color-line)] py-16 px-4 sm:px-6 lg:px-8">
            <div className="max-w-7xl mx-auto">
                <div className="text-center mb-12">
                    <h2 className="font-cairo font-bold text-3xl text-[var(--color-ink)] mb-3">كيف تطلبين؟</h2>
                    <p className="text-[var(--color-ink-soft)] font-ibm text-sm">ثلاث خطوات بسيطة، بلا حسابات أو دفع إلكتروني معقّد</p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                    {steps.map((step, index) => {
                        const Icon = step.icon;
                        return (
                            <div
                                key={step.title}
                                className="relative bg-[var(--color-bg)] border border-[var(--color-line)] p-6 pt-8 flex flex-col items-center text-center gap-3"
                                style={{ borderRadius: 'var(--radius-md)' }}
                            >
                                <span className="absolute -top-4 w-8 h-8 rounded-full bg-[var(--color-primary)] text-white font-readex font-bold text-sm flex items-center justify-center shadow-[var(--shadow-soft)]">
                                    {index + 1}
                                </span>
                                <span className="w-14 h-14 rounded-full bg-[var(--color-surface)] flex items-center justify-center mt-2">
                                    <Icon className="w-6 h-6 text-[var(--color-primary)]" strokeWidth={1.5} />
                                </span>
                                <h3 className="font-cairo font-bold text-lg text-[var(--color-ink)]">{step.title}</h3>
                                <p className="text-sm font-ibm text-[var(--color-ink-soft)] leading-relaxed">{step.description}</p>
                            </div>
                        );
                    })}
                </div>
            </div>
        </section>
    );
};
