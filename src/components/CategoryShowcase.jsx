import React from 'react';
import { ArrowLeft } from 'lucide-react';
import { getCategoryIcon } from '../utils/categoryIcons';

export const CategoryShowcase = ({ categories = [], onSelectCategory }) => {
    if (!categories.length) return null;

    return (
        <section className="wellness-container py-12 sm:py-16">
            <div className="text-center max-w-xl mx-auto mb-8 sm:mb-10">
                <span className="wellness-kicker">روتينك يبدأ من هنا</span>
                <h2 className="font-cairo font-black text-3xl sm:text-4xl mt-2">تسوّقي حسب احتياجك</h2>
                <p className="text-sm sm:text-base text-[var(--color-ink-soft)] mt-3">مسارات واضحة تساعدك على اكتشاف ما يناسب يومكِ.</p>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                {categories.map((category) => {
                    const Icon = getCategoryIcon(category.name || '');
                    const accent = category.accent_color || 'var(--color-primary)';
                    return (
                        <button
                            key={category.id}
                            type="button"
                            onClick={() => onSelectCategory?.(category.id)}
                            className="group flex items-center text-right gap-4 bg-[var(--color-surface)] hover:bg-[var(--color-surface-deep)] border border-[var(--color-line)] hover:border-[var(--color-primary)] p-4 sm:p-5 rounded-[var(--radius-lg)] transition-all duration-300 hover:-translate-y-0.5"
                        >
                            <span className="w-16 h-16 rounded-[var(--radius-md)] bg-[var(--color-bg)] border border-[rgba(223,228,216,.8)] grid place-items-center flex-none transition-transform duration-300 group-hover:scale-105">
                                <Icon className="w-7 h-7" strokeWidth={1.5} style={{ color: accent }} />
                            </span>
                            <span className="min-w-0 flex-1">
                                <span className="font-cairo font-bold text-lg text-[var(--color-ink)] block">{category.name}</span>
                                <span className="text-xs text-[var(--color-ink-soft)] mt-1 block">اختيارات مرتبة لروتين أكثر راحة</span>
                            </span>
                            <ArrowLeft size={19} className="text-[var(--color-ink-soft)] group-hover:text-[var(--color-primary)] transition-colors flex-none" strokeWidth={1.5} />
                        </button>
                    );
                })}
            </div>
        </section>
    );
};
