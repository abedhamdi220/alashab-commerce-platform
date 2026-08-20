import React from 'react';
import { MessageCircle, ShieldCheck, Truck, Headphones, CheckCircle, MapPin } from 'lucide-react';
import { useSettings } from '../hooks/useSettings';

export const Footer = () => {
    const { whatsappNumber } = useSettings();

    return (
        <footer className="bg-[var(--color-bg)] text-[var(--color-ink)] pt-16 pb-8 border-t border-[var(--color-line)]">
            <div className="max-w-7xl mx-auto px-4 mb-16">
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 justify-items-center">
                    {/* بطاقات المزايا بطابع تطبيق صحي حديث */}
                    <div className="w-full max-w-[160px] bg-[var(--color-surface)] rounded-[var(--radius-md)] flex flex-col items-center justify-center p-4">
                        <Truck className="w-6 h-6 text-[var(--color-trust)] mb-2" strokeWidth={1.5} />
                        <h4 className="font-ibm font-medium text-sm text-[var(--color-ink)]">شحن سريع</h4>
                    </div>
                    <div className="w-full max-w-[160px] bg-[var(--color-surface)] rounded-[var(--radius-md)] flex flex-col items-center justify-center p-4">
                        <CheckCircle className="w-6 h-6 text-[var(--color-trust)] mb-2" strokeWidth={1.5} />
                        <h4 className="font-ibm font-medium text-sm text-[var(--color-ink)]">ضمان الجودة</h4>
                    </div>
                    <div className="w-full max-w-[160px] bg-[var(--color-surface)] rounded-[var(--radius-md)] flex flex-col items-center justify-center p-4">
                        <ShieldCheck className="w-6 h-6 text-[var(--color-trust)] mb-2" strokeWidth={1.5} />
                        <h4 className="font-ibm font-medium text-sm text-[var(--color-ink)]">منتجات مفحوصة</h4>
                    </div>
                    <div className="w-full max-w-[160px] bg-[var(--color-surface)] rounded-[var(--radius-md)] flex flex-col items-center justify-center p-4">
                        <Headphones className="w-6 h-6 text-[var(--color-trust)] mb-2" strokeWidth={1.5} />
                        <h4 className="font-ibm font-medium text-sm text-[var(--color-ink)]">دعم متواصل</h4>
                    </div>
                </div>
            </div>

            {/* شريط تغطية الشحن — رسالة واضحة وقابلة للمسح البصري السريع، منفصلة عن نص "من نحن" */}
            <div className="max-w-7xl mx-auto px-4 mb-16">
                <div className="flex flex-wrap items-center justify-center gap-x-8 gap-y-3 bg-[var(--color-surface)] border border-[var(--color-line)] rounded-[var(--radius-md)] py-4 px-6">
                    <span className="flex items-center gap-2 text-sm font-ibm font-medium text-[var(--color-ink)]">
                        <Truck className="w-4 h-4 text-[var(--color-primary)]" strokeWidth={1.5} />
                        شحن إلى جميع المحافظات السورية
                    </span>
                    <span className="hidden sm:inline text-[var(--color-line)]">|</span>
                    <span className="flex items-center gap-2 text-sm font-ibm font-medium text-[var(--color-ink)]">
                        <MapPin className="w-4 h-4 text-[var(--color-primary)]" strokeWidth={1.5} />
                        توصيل سريع داخل دمشق
                    </span>
                </div>
            </div>

            <div className="max-w-7xl mx-auto px-4 grid grid-cols-1 md:grid-cols-4 gap-10 pb-12 border-b border-[var(--color-line)]">
                <div className="space-y-4 md:col-span-2">
                    <span className="text-2xl font-cairo font-black text-[var(--color-ink)]">
                        عن العشّاب
                    </span>
                    {/* النص التسويقي الرسمي المعتمد */}
                    <p className="text-sm font-ibm leading-relaxed text-[var(--color-ink-soft)] max-w-3xl">
                        العشّاب شركة سورية متخصصة في تسويق منتجات العناية الصحية والتجميلية — التنحيف، التسمين، العناية بالبشرة، العناية بالشعر، والصحة الجنسية. مهمتنا بسيطة: نفحص كل منتج قبل أن يصلك، ونعرضه بوضوح دون تهويل. لا نَعِد برقم لا نملك دليلاً عليه، ولا نُخفي فئة تخصّ خصوصيتك. نحن شركة تسويق، لا نُخفي ذلك — لكننا نُسوّق لما نثق به فقط.
                    </p>
                </div>

                <div className="space-y-4 md:col-span-1">
                    <h5 className="font-cairo font-bold text-xl text-[var(--color-ink)]">معلومات الطلب</h5>
                    <ul className="space-y-3 text-sm font-ibm text-[var(--color-ink-soft)] leading-6">
                        <li>تُراجع تفاصيل الطلب عبر واتساب قبل إتمام التأكيد.</li>
                        <li>تواصلي معنا فور الاستلام إذا احتجتِ مساعدة بخصوص الاستبدال أو الطلب.</li>
                        <li>لا تشاركي بيانات بطاقة أو معلومات حساسة ضمن الملاحظات أو المحادثة.</li>
                    </ul>
                    <details className="group rounded-[var(--radius-md)] border border-[var(--color-line)] bg-[var(--color-surface)] px-4 py-3">
                        <summary className="cursor-pointer list-none text-sm font-ibm font-medium text-[var(--color-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-primary)]">
                            كيف نستخدم بيانات الطلب؟
                        </summary>
                        <p className="pt-3 text-xs font-ibm leading-6 text-[var(--color-ink-soft)]">
                            نطلب الاسم ورقم التواصل والمنطقة لتجهيز الطلب والتواصل بشأن التوصيل. لا نطلب بيانات دفع إلكتروني داخل المتجر.
                        </p>
                    </details>
                </div>

                <div className="space-y-4 md:col-span-1">
                    <h5 className="font-cairo font-bold text-xl text-[var(--color-ink)]">
                        للتواصل معنا
                    </h5>
                    {whatsappNumber ? (
                        <a
                            href={`https://wa.me/${whatsappNumber}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center gap-2 text-[var(--color-primary)] hover:opacity-80 font-ibm transition-colors"
                        >
                            <MessageCircle className="w-5 h-5" strokeWidth={1.5} />
                            محادثة عبر واتساب
                        </a>
                    ) : (
                        <p className="text-sm font-ibm text-[var(--color-ink-soft)]">يتوفر دعم واتساب عند تفعيل رقم المتجر.</p>
                    )}
                </div>
            </div>

            <div className="max-w-7xl mx-auto px-4 pt-8 flex flex-col md:flex-row items-center justify-between gap-4">
                <p className="text-xs font-ibm text-[var(--color-ink-soft)]">
                    © {new Date().getFullYear()} العشّاب. نتائج تثقين بها.
                </p>
            </div>
        </footer>
    );
};
