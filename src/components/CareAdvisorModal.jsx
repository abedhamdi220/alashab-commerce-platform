import React, { useState, useEffect } from 'react';
import { X, ArrowLeft, Check, Share2, ChevronRight, Target, Sparkles, Scale, Heart, Lock } from 'lucide-react';
import { useSettings } from '../hooks/useSettings';
import { useFocusTrap } from '../hooks/useFocusTrap';

// بناء الفئات بناءً على توجه العشّاب كشركة منتجات صحية حديثة
const CARE_TYPES = [
    { id: 'slim', label: 'التنحيف وحرق الدهون', color: 'var(--accent-slim)', icon: <Target strokeWidth={1.5} /> },
    { id: 'skincare', label: 'العناية بالبشرة', color: 'var(--accent-skincare)', icon: <Sparkles strokeWidth={1.5} /> },
    { id: 'gain', label: 'التسمين وزيادة الوزن', color: 'var(--accent-gain)', icon: <Scale strokeWidth={1.5} /> },
    { id: 'intimate', label: 'الصحة الخاصة', color: 'var(--accent-intimate)', icon: <Heart strokeWidth={1.5} /> },
];

const CONCERNS_MAP = {
    slim: [
        { id: 'stubborn_fat', label: 'صعوبة في حرق الدهون الموضعية' },
        { id: 'appetite', label: 'شهية مفتوحة وصعوبة في الالتزام' },
        { id: 'metabolism', label: 'بطء في معدل الحرق الأيضي' }
    ],
    skincare: [
        { id: 'acne', label: 'بثور وشوائب ظاهرة' },
        { id: 'dry_skin', label: 'جفاف شديد وافتقار للنضارة' },
        { id: 'aging', label: 'تصبغات وعدم توحيد في اللون' }
    ],
    gain: [
        { id: 'appetite_low', label: 'ضعف شديد في الشهية' },
        { id: 'weight_stuck', label: 'ثبات الوزن رغم الأكل' },
        { id: 'energy', label: 'نقص في الطاقة والكتلة العضلية' }
    ],
    intimate: [
        { id: 'performance', label: 'دعم النشاط والطاقة العامة' },
        { id: 'care', label: 'عناية وروتين شخصي' }
    ]
};

const LIFESTYLE_MAP = {
    slim: { question: 'كيف تصفين مستوى نشاطك الحركي اليومي؟', answers: ['جلوس معظم الوقت (عمل مكتبي)', 'نشاط خفيف إلى متوسط', 'نشاط بدني عالي ومستمر'] },
    skincare: { question: 'ما هو روتين تعرضك للعوامل الخارجية؟', answers: ['تعرض مباشر للشمس/خارج المنزل', 'أغلب الوقت في بيئة داخلية', 'مختلط'] },
    gain: { question: 'كم عدد وجباتك الأساسية في اليوم؟', answers: ['وجبة واحدة إلى اثنتين', 'ثلاث وجبات رئيسية', 'أكثر من ثلاث وجبات مع سناكس'] },
    intimate: { question: 'هل تبحثين عن نتيجة فورية أم روتين مستدام؟', answers: ['دعم فوري', 'روتين عناية يومي/أسبوعي'] }
};

export const CareAdvisorModal = ({ isOpen, onClose, products = [], onSelectProduct }) => {
    const [step, setStep] = useState(1);
    const [selections, setSelections] = useState({ type: null, concern: null, lifestyle: null });
    const { currency } = useSettings();

    // إعادة ضبط الحالة عند الإغلاق بحركة هادئة
    useEffect(() => {
        if (!isOpen) {
            const timer = setTimeout(() => {
                setStep(1);
                setSelections({ type: null, concern: null, lifestyle: null });
            }, 300);
            return () => clearTimeout(timer);
        }
    }, [isOpen]);

    // [جديد]: هاي المودال ما كان فيها أي تعامل مع لوحة المفاتيح إطلاقاً — لا Escape، ولا حبس
    // Tab، ولا حتى قفل تمرير الخلفية. نفس الهوك المستخدم بـ ProductDetailsModal وCartDrawer
    const modalRef = useFocusTrap(isOpen, onClose);

    if (!isOpen) return null;

    const handleSelection = (key, value) => {
        setSelections(prev => ({ ...prev, [key]: value }));

        if (step < 3) {
            setStep(prev => prev + 1);
        } else {
            setStep(5);
        }
    };

    const handleShare = () => {
        const text = `اطلعت على اقتراح منتجات ضمن فئة العناية التي اخترتها في العشّاب.\nتعرفوا على المتجر:\nhttps://alashab.com`;
        // [تصحيح]: أُضيف 'noopener,noreferrer' لحماية الصفحة من reverse tabnabbing، نفس الإصلاح
        // المطبّق على رابط الواتساب بـ CartDrawer
        window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank', 'noopener,noreferrer');
    };

    // تحديد المنتج المناسب وتطبيق الفئة
    const activeCategory = CARE_TYPES.find(c => c.id === selections.type);
    // [تصحيح]: كانت .find() ترجع أول منتج مطابق بترتيب الوصول من الـ API فقط (ما فيه ترتيب
    // حسب الجودة)، ولو ما فيه أي منتج مطابق لنوع العناية المختار كانت تسقط لـ products[0] —
    // يعني ممكن توصي بمنتج من فئة مختلفة تماماً بينما النص يقول "بناءً على اختياراتك". الآن:
    // نجمع كل المنتجات المطابقة لـ care_type ونختار الأعلى تقييماً بينها، ولو ما فيه أي تطابق
    // نعرض حالة صريحة "لا توجد توصية" بدل التوصية بمنتج غير ذي علاقة كأنه مطابق.
    //
    // ملاحظة مهمة: إجابتي الخطوة 2 (التحدي الرئيسي / selections.concern) والخطوة 3 (نمط الحياة /
    // selections.lifestyle) لا تؤثران فعلياً على اختيار المنتج — يتم جمعهما بالـ state لكن ما فيه
    // أي حقل بالمنتج (tag/attribute) يقابلهما بالباك اند عشان نفلتر أو نرجّح على أساسهما. حالياً
    // التوصية مبنية فقط على care_type. هذا قرار منتج/مخطط بيانات، مو خطأ ربط بالفرونت — لو حابين
    // الإجابتين فعلاً يأثرن بالنتيجة، لازم يضاف حقل مثل "tags" أو "concern_type" للمنتج بالباك اند.
    const matchingProducts = selections.type
        ? products.filter(p => p.category?.care_type === selections.type)
        : [];
    const recommendedProduct = matchingProducts.length > 0
        ? [...matchingProducts].sort((a, b) => (b.rating || 0) - (a.rating || 0))[0]
        : null;

    const totalSteps = 3;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            {/* طبقة التعتيم */}
            <div
                className="absolute inset-0 bg-[var(--color-ink)]/40 backdrop-blur-sm transition-opacity duration-300"
                onClick={onClose}
            />

            <div
                ref={modalRef}
                tabIndex={-1}
                role="dialog"
                aria-modal="true"
                aria-label="استشارة العناية المخصصة"
                className="bg-[var(--color-bg)] border border-[var(--color-line)] w-full max-w-lg shadow-[var(--shadow-soft)] flex flex-col relative z-10 animate-in fade-in zoom-in-95 duration-300"
                style={{ borderRadius: 'var(--radius-md)' }}
            >
                {/* الهيدر العصري النظيف */}
                <div className="flex items-center justify-between p-5 border-b border-[var(--color-line)] bg-[var(--color-surface)]" style={{ borderTopLeftRadius: 'var(--radius-md)', borderTopRightRadius: 'var(--radius-md)' }}>
                    <div className="flex items-center gap-3">
                        {step > 1 && step < 4 && (
                            <button
                                onClick={() => setStep(prev => prev - 1)}
                                className="p-1 text-[var(--color-ink-soft)] hover:text-[var(--color-primary)] transition-colors"
                                aria-label="العودة للصفحة السابقة"
                            >
                                <ChevronRight className="w-5 h-5" strokeWidth={1.5} />
                            </button>
                        )}
                        <Target className="w-5 h-5 text-[var(--color-primary)]" strokeWidth={2} />
                        <h3 className="font-cairo font-black text-xl text-[var(--color-ink)]">استشارة سريعة</h3>
                    </div>
                    <button
                        onClick={onClose}
                        className="p-2 text-[var(--color-ink-soft)] hover:text-[var(--color-primary)] transition-colors"
                        aria-label="إغلاق"
                    >
                        <X className="w-5 h-5" strokeWidth={1.5} />
                    </button>
                </div>

                <div className="p-6 min-h-[350px] flex flex-col">
                    {/* مؤشر التقدم الحديث */}
                    {(step <= 3) && (
                        <div className="mb-6 flex items-center gap-2">
                            {[1, 2, 3].map((num) => (
                                <div
                                    key={num}
                                    className={`h-1.5 flex-1 rounded-full transition-colors duration-300 ${step >= num ? 'bg-[var(--color-primary)]' : 'bg-[var(--color-line)]'}`}
                                />
                            ))}
                        </div>
                    )}

                    {/* الخطوة 1: نوع العناية */}
                    {step === 1 && (
                        <div className="space-y-4 animate-in fade-in slide-in-from-bottom-4 duration-500">
                            <p className="text-[var(--color-ink)] font-cairo font-black text-2xl mb-6">وش هدفك الأساسي؟</p>
                            <div className="grid gap-3">
                                {CARE_TYPES.map((item) => (
                                    <button
                                        key={item.id}
                                        onClick={() => handleSelection('type', item.id)}
                                        className="w-full text-right p-4 bg-[var(--color-surface)] border border-[var(--color-line)] hover:border-[var(--color-primary)] hover:-translate-y-[2px] hover:shadow-[var(--shadow-soft)] transition-all duration-200 flex items-center gap-4 group"
                                        style={{ borderRadius: 'var(--radius-md)' }}
                                    >
                                        <div className="p-2 bg-[var(--color-bg)] border border-[var(--color-line)]" style={{ borderRadius: 'var(--radius-md)', color: item.color }}>
                                            {item.icon}
                                        </div>
                                        <span className="font-ibm font-medium text-[var(--color-ink)] text-base flex-1">{item.label}</span>
                                        <ArrowLeft className="w-5 h-5 text-[var(--color-line)] group-hover:text-[var(--color-primary)] transition-colors" />
                                    </button>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* الخطوة 2: المشكلة */}
                    {step === 2 && selections.type && (
                        <div className="space-y-4 animate-in fade-in slide-in-from-right-8 duration-500">
                            <p className="text-[var(--color-ink)] font-cairo font-black text-2xl mb-6">أيش التحدي الرئيسي اللي يواجهك؟</p>
                            <div className="space-y-3">
                                {CONCERNS_MAP[selections.type].map((item) => (
                                    <button
                                        key={item.id}
                                        onClick={() => handleSelection('concern', item.id)}
                                        className="w-full text-right p-4 bg-[var(--color-surface)] border border-[var(--color-line)] hover:border-[var(--color-primary)] transition-colors flex items-center justify-between group"
                                        style={{ borderRadius: 'var(--radius-md)' }}
                                    >
                                        <span className="font-ibm font-medium text-[var(--color-ink)]">{item.label}</span>
                                        <div className="w-5 h-5 rounded-full border-2 border-[var(--color-line)] group-hover:border-[var(--color-primary)] flex items-center justify-center transition-colors">
                                            <div className="w-2 h-2 rounded-full bg-[var(--color-primary)] opacity-0 group-hover:opacity-100 transition-opacity" />
                                        </div>
                                    </button>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* الخطوة 3: نمط الحياة */}
                    {step === 3 && selections.type && (
                        <div className="space-y-4 animate-in fade-in slide-in-from-right-8 duration-500">
                            <p className="text-[var(--color-ink)] font-cairo font-black text-2xl mb-6">{LIFESTYLE_MAP[selections.type].question}</p>
                            <div className="space-y-3">
                                {LIFESTYLE_MAP[selections.type].answers.map((answer, idx) => (
                                    <button
                                        key={idx}
                                        onClick={() => handleSelection('lifestyle', answer)}
                                        className="w-full text-right p-4 bg-[var(--color-surface)] border border-[var(--color-line)] hover:border-[var(--color-primary)] transition-colors flex items-center justify-between group"
                                        style={{ borderRadius: 'var(--radius-md)' }}
                                    >
                                        <span className="font-ibm font-medium text-[var(--color-ink)]">{answer}</span>
                                        <div className="w-5 h-5 rounded-full border-2 border-[var(--color-line)] group-hover:border-[var(--color-primary)] flex items-center justify-center transition-colors">
                                            <div className="w-2 h-2 rounded-full bg-[var(--color-primary)] opacity-0 group-hover:opacity-100 transition-opacity" />
                                        </div>
                                    </button>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* الخطوة 5: النتيجة */}
                    {step === 5 && (
                        <div className="space-y-6 animate-in fade-in duration-700">
                            <div className="text-center space-y-2 border-b border-[var(--color-line)] pb-5">
                                <h3 className="text-2xl font-cairo font-black text-[var(--color-ink)]">
                                    {recommendedProduct ? 'اختيار مقترح ضمن الفئة' : 'لا يوجد منتج متاح ضمن الفئة'}
                                </h3>
                                <p className="text-[var(--color-ink-soft)] font-ibm text-sm">
                                    {recommendedProduct
                                        ? 'اعتمد الاقتراح على فئة العناية التي اخترتِها وترتيب المنتجات المتاحة داخلها. لا يمثل ذلك تشخيصاً أو بديلاً عن المختص.'
                                        : 'لا يتوفر حالياً منتج في الفئة التي اخترتِها. يمكنك تصفح بقية المنتجات أو التواصل معنا للمساعدة.'}
                                </p>
                            </div>

                            {recommendedProduct ? (
                                <div
                                    className="bg-[var(--color-surface)] p-4 border border-[var(--color-line)] relative group hover:-translate-y-1 hover:shadow-[var(--shadow-soft)] transition-all duration-300"
                                    style={{ borderRadius: 'var(--radius-md)' }}
                                >
                                    {/* شريط الفئة اللوني العلوي — نفضّل لون الفئة الحقيقي القادم من الـ API */}
                                    <div className="absolute top-0 left-0 right-0 h-1 opacity-80" style={{ backgroundColor: recommendedProduct.category?.accent_color || activeCategory?.color || 'var(--color-primary)', borderTopLeftRadius: 'var(--radius-md)', borderTopRightRadius: 'var(--radius-md)' }} />

                                    <div className="flex gap-4 items-center mt-2">
                                        <div className="w-24 h-24 bg-[var(--color-bg)] border border-[var(--color-line)] flex-shrink-0 flex items-center justify-center overflow-hidden" style={{ borderRadius: 'var(--radius-md)' }}>
                                            {/* تطبيق الحماية (is_discreet) على صورة المنتج */}
                                            {recommendedProduct.is_discreet ? (
                                                <div className="flex flex-col items-center gap-2 text-[var(--color-ink-soft)]">
                                                    <Lock strokeWidth={1.5} size={28} />
                                                    <span className="font-ibm text-[10px]">فئة خاصة</span>
                                                </div>
                                            ) : (
                                                <img
                                                    src={recommendedProduct.images?.[0]?.url || recommendedProduct.image_url || '/placeholder.png'}
                                                    alt={recommendedProduct.name}
                                                    loading="lazy"
                                                    className="w-full h-full object-cover"
                                                />
                                            )}
                                        </div>
                                        <div className="space-y-2 flex-1">
                                            {/* تطبيق الحماية (is_discreet) على اسم المنتج */}
                                            <h4 className="font-cairo font-black text-[var(--color-ink)] text-lg leading-tight">
                                                {recommendedProduct.is_discreet ? 'منتج عناية (فئة خاصة)' : recommendedProduct.name}
                                            </h4>
                                            {!recommendedProduct.is_discreet && (
                                                <p className="text-[var(--color-ink-soft)] text-xs font-ibm line-clamp-2 leading-relaxed">{recommendedProduct.description}</p>
                                            )}
                                            <div className="font-readex font-bold text-[var(--color-ink)] text-lg mt-1">
                                                {recommendedProduct.price} <span className="text-sm font-normal">{currency}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <button
                                        onClick={() => {
                                            onSelectProduct(recommendedProduct);
                                            onClose();
                                        }}
                                        className="w-full mt-5 bg-[var(--color-primary)] text-white py-3 font-ibm font-medium hover:brightness-95 active:scale-95 transition-all flex items-center justify-center gap-2 shadow-lg shadow-[var(--color-primary)]/20"
                                        style={{ borderRadius: 'var(--radius-lg)' }}
                                    >
                                        <Check className="w-5 h-5" strokeWidth={2} />
                                        عرض تفاصيل المنتج
                                    </button>
                                </div>
                            ) : (
                                /* [جديد]: حالة صريحة لما ما فيه أي منتج بنفس care_type المختار،
                                   بدل ما نوصي بمنتج عشوائي من فئة ثانية ونوهمها إنه "الأنسب لها" */
                                <div className="text-center py-8 space-y-2 bg-[var(--color-surface)] border border-[var(--color-line)]" style={{ borderRadius: 'var(--radius-md)' }}>
                                    <p className="font-ibm text-sm text-[var(--color-ink)]">
                                        ما لقينا حالياً منتجاً ضمن هذا التصنيف بالذات.
                                    </p>
                                    <p className="font-ibm text-xs text-[var(--color-ink-soft)]">
                                        تصفحي بقية المنتجات أو تواصلي معنا مباشرة وبنساعدك نلقى الأنسب.
                                    </p>
                                </div>
                            )}

                            <div className="pt-2 flex flex-col gap-3">
                                <button
                                    onClick={handleShare}
                                    className="w-full py-3 bg-[var(--color-bg)] border border-[var(--color-line)] text-[var(--color-ink)] font-ibm text-sm hover:border-[var(--color-primary)] hover:text-[var(--color-primary)] transition-colors flex items-center justify-center gap-2"
                                    style={{ borderRadius: 'var(--radius-lg)' }}
                                >
                                    <Share2 className="w-4 h-4" strokeWidth={2} />
                                    مشاركة النتيجة
                                </button>
                                <button
                                    onClick={() => setStep(1)}
                                    className="text-xs font-ibm text-[var(--color-ink-soft)] hover:text-[var(--color-primary)] underline text-center w-full transition-colors"
                                >
                                    بدء استشارة جديدة
                                </button>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};
