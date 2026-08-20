import React, { useState } from 'react';
import { ChevronDown } from 'lucide-react';

const faqs = [
    {
        question: 'هل التغليف يحافظ على خصوصيتي فعلاً؟',
        answer: 'نعم. المنتجات الحساسة تُغلَّف بشكل محايد تماماً دون أي إشارة لمحتوى الطرد من الخارج، حفاظاً على خصوصيتك الكاملة من الطلب حتى الاستلام.',
    },
    {
        question: 'لأي مناطق سوريا توصلون؟',
        answer: 'نوصل إلى جميع المحافظات السورية، مع توصيل أسرع داخل دمشق.',
    },
    {
        question: 'كيف أدفع؟ هل الدفع عند الاستلام متاح؟',
        answer: 'يتم تحديد طريقة الدفع المناسبة لمنطقتك عند تأكيد الطلب مباشرة عبر واتساب.',
    },
    {
        question: 'شو بصير لو حبيت أستبدل أو أرجع منتج؟',
        answer: 'تواصلي معنا عبر واتساب فور استلام الطلب وسنساعدك بأقرب وقت ممكن.',
    },
    {
        question: 'هل كل المنتجات المعروضة مفحوصة فعلاً؟',
        answer: 'نعم — نفحص كل منتج قبل عرضه بالمتجر، ولا نُدرج إلا ما نثق به فعلياً.',
    },
];

export const FAQ = () => {
    const [openIndex, setOpenIndex] = useState(null);

    return (
        <section className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div className="text-center mb-10">
                <h2 className="font-cairo font-bold text-3xl text-[var(--color-ink)] mb-3">أسئلة شائعة</h2>
                <div className="h-1 w-12 bg-[var(--color-primary)] mx-auto rounded-full"></div>
            </div>

            <div className="space-y-3">
                {faqs.map((item, index) => {
                    const isOpen = openIndex === index;
                    return (
                        <div
                            key={item.question}
                            className="bg-[var(--color-bg)] border border-[var(--color-line)] overflow-hidden"
                            style={{ borderRadius: 'var(--radius-md)' }}
                        >
                            <button
                                onClick={() => setOpenIndex(isOpen ? null : index)}
                                className="w-full flex items-center justify-between gap-4 p-5 text-right"
                                aria-expanded={isOpen}
                            >
                                <span className="font-cairo font-bold text-[var(--color-ink)] text-base">{item.question}</span>
                                <ChevronDown
                                    className={`w-5 h-5 shrink-0 transition-transform duration-300 ${isOpen ? 'rotate-180 text-[var(--color-primary)]' : 'text-[var(--color-ink-soft)]'}`}
                                    strokeWidth={1.5}
                                />
                            </button>
                            <div
                                className="grid transition-all duration-300 ease-out"
                                style={{ gridTemplateRows: isOpen ? '1fr' : '0fr' }}
                            >
                                <div className="overflow-hidden">
                                    <p className="px-5 pb-5 text-sm font-ibm text-[var(--color-ink-soft)] leading-relaxed">
                                        {item.answer}
                                    </p>
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>
        </section>
    );
};
