/** عناية هادئة: عرض مراجعات معتمدة فقط مع نموذج إرسال واضح لا يكشف هوية الزائر التقنية. */
import React, { useEffect, useState, useMemo, useRef } from "react";
import {
  X,
  Lock,
  Info,
  Plus,
  Minus,
  ShoppingBag,
  Star,
  PackageX,
} from "lucide-react";
import { useSettings } from "../hooks/useSettings";
import { useFocusTrap } from "../hooks/useFocusTrap";
import { useProductReviews, useSubmitProductReview } from "../hooks/useApi";
import { useToast } from "../context/ToastContext";

export const ProductDetailsModal = ({
  product,
  isOpen,
  onClose,
  onAddToCart,
  onSelectProduct,
  products = [],
}) => {
  const [activeTab, setActiveTab] = useState("description");
  const [quantity, setQuantity] = useState(1);
  const [activeImageIndex, setActiveImageIndex] = useState(0);
  const [reviewForm, setReviewForm] = useState({
    customer_name: localStorage.getItem("alashab_reviewer_name") || "",
    rating: 5,
    comment: "",
  });
  const { currency } = useSettings();
  const { showError, showToast } = useToast();
  const { data: reviews = [], isLoading: isReviewsLoading } = useProductReviews(
    product?.id,
  );
  const submitReview = useSubmitProductReview(product?.id);
  const scrollContainerRef = useRef(null);

  // [تصحيح]: الباك اند الآن يرجع images[] جاهزة (مع صورة placeholder افتراضية لو ما فيه صور)
  // بدل الاعتماد على image_url وحده كحل بديل غير مضمون
  const images = useMemo(() => {
    if (!product) return [];
    if (product.images && product.images.length > 0) return product.images;
    if (product.image_url) return [{ url: product.image_url, id: "main" }];
    return [{ url: "/placeholder.png", id: "placeholder" }];
  }, [product]);

  // يعمل صح الآن لأن category أصبح كائن فيه id حقيقي بدل نص فقط
  const relatedProducts = useMemo(() => {
    if (!product || !products.length) return [];
    return products
      .filter(
        (p) => p.id !== product.id && p.category?.id === product.category?.id,
      )
      .slice(0, 4);
  }, [product, products]);

  useEffect(() => {
    if (isOpen) {
      setActiveTab("description");
      setQuantity(1);
      setActiveImageIndex(0);
      setReviewForm((previous) => ({ ...previous, comment: "" }));
      // [جديد]: بدون هذا، لو المستخدمة تنقلت لمنتج ذي صلة وهي نازلة بآخر الصفحة
      // (تبويبات/منتجات ذات صلة)، بيظل السكرول بنفس المكان ويبدو المودال "ما تغيّر"
      scrollContainerRef.current?.scrollTo({ top: 0, behavior: "auto" });
    }
  }, [isOpen, product]);

  // [تصحيح]: كان في Escape فقط بدون حبس Tab — يعني Tab كانت "تهرب" من المودال لعناصر
  // خلف طبقة التعتيم. الآن نستخدم الهوك المشترك (نفس المستخدم بـ CartDrawer وCareAdvisorModal)
  const modalRef = useFocusTrap(isOpen, onClose);

  if (!isOpen || !product) return null;

  // [جديد]: مؤشر مخزون حقيقي مبني على in_stock القادم من الباك اند
  // (in_stock = false فقط لما stock_quantity محدد فعلياً ووصل للصفر، وإلا نعتبره متوفر دائماً)
  const isOutOfStock = product.in_stock === false;
  const stockLimit = Number.parseInt(product.stock_quantity, 10);
  const hasStockLimit = Number.isInteger(stockLimit) && stockLimit >= 0;
  const reachedStockLimit = hasStockLimit && quantity >= stockLimit;
  const productTabs = [
    { id: "description", label: "تفاصيل المنتج" },
    { id: "ingredients", label: "المكونات" },
    { id: "usage", label: "طريقة الاستخدام" },
    { id: "reviews", label: `التقييمات (${product.reviews_count || 0})` },
  ];

  const handleTabKeyDown = (event, currentIndex) => {
    const totalTabs = productTabs.length;
    let nextIndex = null;

    if (event.key === "ArrowRight") nextIndex = (currentIndex + 1) % totalTabs;
    if (event.key === "ArrowLeft")
      nextIndex = (currentIndex - 1 + totalTabs) % totalTabs;
    if (event.key === "Home") nextIndex = 0;
    if (event.key === "End") nextIndex = totalTabs - 1;
    if (nextIndex === null) return;

    event.preventDefault();
    setActiveTab(productTabs[nextIndex].id);
    event.currentTarget.parentElement
      ?.querySelectorAll('[role="tab"]')
      [nextIndex]?.focus();
  };

  const handleAddToCart = () => {
    if (isOutOfStock) return;
    onAddToCart({ ...product, quantity });
    onClose();
  };

  const handleReviewSubmit = async (event) => {
    event.preventDefault();

    try {
      const response = await submitReview.mutateAsync(reviewForm);
      localStorage.setItem(
        "alashab_reviewer_name",
        reviewForm.customer_name.trim(),
      );
      setReviewForm((previous) => ({ ...previous, comment: "" }));
      showToast(response.message || "تم إرسال رأيك للمراجعة.");
    } catch (error) {
      showError(
        error?.response?.data?.message ||
          Object.values(error?.response?.data?.errors || {}).flat()[0] ||
          "تعذر إرسال الرأي. يرجى المحاولة مرة أخرى.",
      );
    }
  };

  const hasDiscount = product.discount_percentage > 0 || product.old_price;
  const oldPrice =
    product.old_price ||
    (product.discount_percentage
      ? Math.round(product.price / (1 - product.discount_percentage / 100))
      : null);

  // متغيرات الهوية الحديثة
  const isDiscreet = product.is_discreet;
  // [تصحيح]: نعتمد على care_type القادم صراحةً من الباك اند بدل category?.slug
  // (الـ slug الفعلي مبني على الاسم العربي وما كان يطابق أبداً القيم الإنجليزية الثابتة)
  const isMedicalCategory = ["slim", "gain", "intimate"].includes(
    product.category?.care_type,
  );

  return (
    <div
      className="fixed inset-0 z-[60] flex items-center justify-center p-4 sm:p-6 bg-[var(--color-ink)]/40 backdrop-blur-sm transition-opacity duration-300"
      onMouseDown={(event) => {
        if (event.target === event.currentTarget) onClose();
      }}
    >
      <div
        ref={modalRef}
        tabIndex={-1}
        className="bg-[var(--color-bg)] w-full max-w-4xl flex flex-col max-h-[90vh] shadow-[var(--shadow-soft)] relative transform transition-transform duration-300 scale-100 border border-[var(--color-line)]"
        style={{ borderRadius: "var(--radius-lg)" }}
        role="dialog"
        aria-modal="true"
        aria-label={product.name}
      >
        {/* زر الإغلاق العصري */}
        <button
          onClick={onClose}
          className="absolute top-4 left-4 z-20 p-2 text-[var(--color-ink-soft)] hover:text-[var(--color-primary)] hover:bg-[var(--color-surface)] transition-all rounded-full bg-[var(--color-bg)]/80 backdrop-blur-sm border border-[var(--color-line)]"
          aria-label="إغلاق"
        >
          <X className="w-5 h-5" strokeWidth={2} />
        </button>

        <div
          ref={scrollContainerRef}
          className="flex-1 overflow-y-auto no-scrollbar"
        >
          <div className="flex flex-col md:flex-row border-b border-[var(--color-line)]">
            {/* معرض الصور أو وضع الحماية */}
            <div className="w-full md:w-1/2 p-6 flex flex-col gap-4 border-b md:border-b-0 md:border-l border-[var(--color-line)] bg-[var(--color-surface)]">
              <div
                className="aspect-[4/5] bg-[var(--color-bg)] relative overflow-hidden flex items-center justify-center border border-[var(--color-line)] group"
                style={{ borderRadius: "var(--radius-md)" }}
              >
                {product.discount_percentage > 0 && (
                  <span
                    className="absolute top-4 right-4 z-10 bg-[var(--color-energy)] text-white font-readex font-bold text-xs tracking-wider px-3 py-1.5 shadow-sm"
                    style={{ borderRadius: "var(--radius-sm)" }}
                  >
                    خصم {product.discount_percentage}%
                  </span>
                )}

                {/* شارة نفاد المخزون */}
                {isOutOfStock && (
                  <span
                    className="absolute top-4 left-4 z-10 bg-[var(--color-ink)] text-white font-ibm font-medium text-xs px-3 py-1.5 shadow-sm flex items-center gap-1.5"
                    style={{ borderRadius: "var(--radius-sm)" }}
                  >
                    <PackageX size={14} strokeWidth={2} /> نفدت الكمية
                  </span>
                )}

                {isDiscreet ? (
                  <div className="w-full h-full flex flex-col items-center justify-center text-[var(--color-ink-soft)] p-6 text-center">
                    <div className="w-20 h-20 rounded-full bg-[var(--color-surface)] border border-[var(--color-line)] flex items-center justify-center mb-4">
                      <Lock strokeWidth={1.5} size={32} />
                    </div>
                    <p className="font-ibm text-sm font-medium opacity-90">
                      لضمان الخصوصية، يتم عرض هذا المنتج في فئة العناية الخاصة.
                    </p>
                  </div>
                ) : (
                  <img
                    src={images[activeImageIndex]?.url || "/placeholder.png"}
                    alt={product.name}
                    className="object-cover w-full h-full mix-blend-multiply transition-transform duration-700 hover:scale-105"
                  />
                )}
              </div>

              {/* الصور المصغرة */}
              {!isDiscreet && images.length > 1 && (
                <div className="flex gap-3 overflow-x-auto pb-2 scrollbar-hide">
                  {images.map((img, idx) => (
                    <button
                      key={img.id ?? idx}
                      type="button"
                      onClick={() => setActiveImageIndex(idx)}
                      aria-label={`عرض الصورة ${idx + 1} من ${images.length}`}
                      aria-pressed={activeImageIndex === idx}
                      className={`w-16 h-16 shrink-0 bg-[var(--color-bg)] border flex items-center justify-center transition-all overflow-hidden ${activeImageIndex === idx ? "border-[var(--color-primary)] ring-1 ring-[var(--color-primary)] opacity-100" : "border-[var(--color-line)] opacity-70 hover:opacity-100"}`}
                      style={{ borderRadius: "var(--radius-sm)" }}
                    >
                      <img
                        src={img.url}
                        loading="lazy"
                        className="w-full h-full object-cover mix-blend-multiply"
                        alt=""
                      />
                    </button>
                  ))}
                </div>
              )}
            </div>

            {/* التفاصيل والشراء */}
            <div className="w-full md:w-1/2 p-8 flex flex-col gap-6 justify-center bg-[var(--color-bg)]">
              <div>
                <h2 className="text-2xl md:text-3xl font-cairo font-black text-[var(--color-ink)] leading-tight mb-3">
                  {isDiscreet ? "منتج عناية (فئة خاصة)" : product.name}
                </h2>

                <div className="flex flex-col mb-2">
                  {hasDiscount && (
                    <span className="text-[var(--color-ink-soft)] text-sm font-readex line-through mb-1">
                      {oldPrice} {currency}
                    </span>
                  )}
                  <div className="flex items-baseline gap-1.5">
                    <span className="font-readex font-bold text-3xl tracking-tight text-[var(--color-primary)]">
                      {product.price}
                    </span>
                    <span className="font-ibm text-sm text-[var(--color-ink-soft)] font-medium">
                      {currency}
                    </span>
                  </div>
                </div>
              </div>

              {/* التوثيق ومصدر المكون */}
              <div
                className="bg-[var(--color-surface)] p-4 border border-[var(--color-line)]"
                style={{ borderRadius: "var(--radius-md)" }}
              >
                <h4 className="font-readex font-bold text-[var(--color-ink)] text-sm mb-2 flex items-center gap-2">
                  <Info
                    size={16}
                    strokeWidth={2}
                    className="text-[var(--color-primary)]"
                  />{" "}
                  مصدر المكوّن:
                </h4>
                <p className="font-ibm text-sm text-[var(--color-ink-soft)] leading-relaxed">
                  <span className="font-medium text-[var(--color-ink)]">
                    المنشأ:
                  </span>{" "}
                  {product.origin || "مستخلص من مصادر طبيعية موثوقة."} <br />
                  <span className="font-medium text-[var(--color-ink)]">
                    الاستخلاص:
                  </span>{" "}
                  {product.extraction_method ||
                    "معالج بأحدث الطرق لضمان الفعالية والموثوقية."}
                </p>
              </div>

              <p className="text-[var(--color-ink-soft)] font-ibm text-sm leading-relaxed line-clamp-3">
                {product.description}
              </p>

              {/* التنويه الطبي */}
              {isMedicalCategory && (
                <p
                  className="text-[var(--color-energy)] font-ibm text-xs bg-[var(--color-energy)]/5 p-3 border-r-2 border-[var(--color-energy)]"
                  style={{
                    borderTopLeftRadius: "var(--radius-sm)",
                    borderBottomLeftRadius: "var(--radius-sm)",
                  }}
                >
                  هذا المنتج للعناية الشخصية، يُنصح باستشارة مختص عند الحاجة.
                </p>
              )}

              <div className="flex flex-col gap-4 mt-auto pt-6 border-t border-[var(--color-line)]">
                <div className="flex items-center gap-4">
                  {/* محدد الكمية الحديث */}
                  <div
                    className="flex items-center bg-[var(--color-surface)] border border-[var(--color-line)] h-12 overflow-hidden"
                    style={{ borderRadius: "var(--radius-md)" }}
                  >
                    <button
                      type="button"
                      onClick={() => setQuantity((q) => Math.max(1, q - 1))}
                      disabled={isOutOfStock || quantity <= 1}
                      aria-label="تقليل الكمية"
                      className="w-12 h-full flex items-center justify-center text-[var(--color-ink-soft)] hover:text-[var(--color-primary)] hover:bg-[var(--color-line)] transition-colors disabled:opacity-40 disabled:pointer-events-none"
                    >
                      <Minus size={18} strokeWidth={2} />
                    </button>
                    <span
                      className="w-12 text-center font-readex font-bold text-lg text-[var(--color-ink)]"
                      aria-live="polite"
                      aria-label={`الكمية: ${quantity}`}
                    >
                      {quantity}
                    </span>
                    <button
                      type="button"
                      onClick={() =>
                        setQuantity((q) =>
                          hasStockLimit ? Math.min(stockLimit, q + 1) : q + 1,
                        )
                      }
                      disabled={isOutOfStock || reachedStockLimit}
                      aria-label={
                        reachedStockLimit
                          ? "وصلت إلى الكمية المتاحة"
                          : "زيادة الكمية"
                      }
                      className="w-12 h-full flex items-center justify-center text-[var(--color-ink-soft)] hover:text-[var(--color-primary)] hover:bg-[var(--color-line)] transition-colors disabled:opacity-40 disabled:pointer-events-none"
                    >
                      <Plus size={18} strokeWidth={2} />
                    </button>
                  </div>

                  {/* زر الإضافة للسلة */}
                  <button
                    onClick={handleAddToCart}
                    disabled={isOutOfStock}
                    className="flex-1 bg-[var(--color-primary)] hover:brightness-95 active:scale-[0.98] text-white font-ibm font-medium h-12 px-4 transition-all flex items-center justify-center gap-2 shadow-lg shadow-[var(--color-primary)]/20 disabled:opacity-50 disabled:pointer-events-none disabled:shadow-none"
                    style={{ borderRadius: "var(--radius-md)" }}
                  >
                    <ShoppingBag size={18} strokeWidth={2} />
                    <span>
                      {isOutOfStock
                        ? "نفدت الكمية حالياً"
                        : `إضافة للسلة • ${product.price * quantity} ${currency}`}
                    </span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          {/* التبويبات السفلية */}
          <div className="p-8 bg-[var(--color-bg)]">
            <div
              className="flex gap-6 overflow-x-auto pb-3 scrollbar-hide border-b border-[var(--color-line)] mb-6"
              role="tablist"
              aria-label="معلومات المنتج"
            >
              {productTabs.map((tab, index) => {
                const isActive = activeTab === tab.id;
                return (
                  <button
                    key={tab.id}
                    type="button"
                    id={`product-tab-${product.id}-${tab.id}`}
                    role="tab"
                    tabIndex={isActive ? 0 : -1}
                    aria-selected={isActive}
                    aria-controls={`product-panel-${product.id}`}
                    onClick={() => setActiveTab(tab.id)}
                    onKeyDown={(event) => handleTabKeyDown(event, index)}
                    className={`pb-3 font-ibm text-sm font-medium whitespace-nowrap transition-all border-b-2 ${isActive ? "border-[var(--color-primary)] text-[var(--color-primary)]" : "border-transparent text-[var(--color-ink-soft)] hover:text-[var(--color-ink)] hover:border-[var(--color-line)]"}`}
                  >
                    {tab.label}
                  </button>
                );
              })}
            </div>

            <div
              id={`product-panel-${product.id}`}
              role="tabpanel"
              aria-labelledby={`product-tab-${product.id}-${activeTab}`}
              tabIndex={0}
              className="text-sm font-ibm text-[var(--color-ink-soft)] leading-relaxed min-h-[120px]"
            >
              {activeTab === "description" && (
                <p>
                  {product.description || "تفاصيل المنتج غير متوفرة حالياً."}
                </p>
              )}
              {activeTab === "ingredients" && (
                <p>
                  {product.ingredients ||
                    "مكونات طبيعية تم اختيارها بعناية فائقة."}
                </p>
              )}
              {activeTab === "usage" && (
                <p>
                  {product.usage_instructions ||
                    "يُستخدم وفقاً للتعليمات المرفقة مع العبوة."}
                </p>
              )}
              {activeTab === "reviews" && (
                <div className="space-y-7 text-right">
                  <form
                    onSubmit={handleReviewSubmit}
                    className="rounded-[var(--radius-md)] border border-[var(--color-line)] bg-[var(--color-surface)] p-4 sm:p-5"
                  >
                    <div className="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between">
                      <h3 className="font-cairo text-base font-black text-[var(--color-ink)]">
                        شاركي تجربتكِ مع المنتج
                      </h3>
                      <p className="text-xs text-[var(--color-ink-soft)]">
                        يظهر الرأي بعد مراجعته من الإدارة.
                      </p>
                    </div>
                    <div className="mt-4 grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
                      <label className="block text-xs font-semibold text-[var(--color-ink)]">
                        الاسم الظاهر
                        <input
                          value={reviewForm.customer_name}
                          onChange={(event) =>
                            setReviewForm((previous) => ({
                              ...previous,
                              customer_name: event.target.value,
                            }))
                          }
                          required
                          maxLength={100}
                          className="mt-1.5 h-10 w-full rounded-[var(--radius-sm)] border border-[var(--color-line)] bg-[var(--color-bg)] px-3 text-sm font-normal outline-none focus:border-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary-soft)]"
                        />
                      </label>
                      <fieldset className="min-w-0">
                        <legend className="text-xs font-semibold text-[var(--color-ink)]">
                          التقييم
                        </legend>
                        <div
                          className="mt-1.5 flex h-10 items-center gap-1"
                          aria-label={`التقييم المختار ${reviewForm.rating} من 5`}
                        >
                          {[1, 2, 3, 4, 5].map((star) => (
                            <button
                              key={star}
                              type="button"
                              onClick={() =>
                                setReviewForm((previous) => ({
                                  ...previous,
                                  rating: star,
                                }))
                              }
                              className="rounded p-1 focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)]"
                              aria-label={`${star} من 5`}
                              aria-pressed={reviewForm.rating === star}
                            >
                              <Star
                                size={20}
                                className={
                                  star <= reviewForm.rating
                                    ? "fill-[var(--color-energy)] text-[var(--color-energy)]"
                                    : "text-[var(--color-line)]"
                                }
                              />
                            </button>
                          ))}
                        </div>
                      </fieldset>
                    </div>
                    <label className="mt-3 block text-xs font-semibold text-[var(--color-ink)]">
                      رأيك
                      <textarea
                        value={reviewForm.comment}
                        onChange={(event) =>
                          setReviewForm((previous) => ({
                            ...previous,
                            comment: event.target.value,
                          }))
                        }
                        required
                        minLength={8}
                        maxLength={1000}
                        rows={3}
                        placeholder="ما الذي أعجبك في المنتج؟"
                        className="mt-1.5 w-full resize-y rounded-[var(--radius-sm)] border border-[var(--color-line)] bg-[var(--color-bg)] px-3 py-2 text-sm font-normal outline-none focus:border-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary-soft)]"
                      />
                    </label>
                    <div className="mt-3 flex justify-end">
                      <button
                        type="submit"
                        disabled={submitReview.isPending}
                        className="rounded-[var(--radius-sm)] bg-[var(--color-primary)] px-4 py-2 text-sm font-bold text-white transition active:scale-[.97] disabled:cursor-not-allowed disabled:opacity-60"
                      >
                        {submitReview.isPending
                          ? "جارٍ الإرسال..."
                          : "إرسال الرأي"}
                      </button>
                    </div>
                  </form>
                  {isReviewsLoading ? (
                    <div className="py-6 text-center text-[var(--color-ink-soft)]">
                      جارٍ تحميل الآراء المعتمدة...
                    </div>
                  ) : reviews.length > 0 ? (
                    <div className="space-y-5">
                      {reviews.map((review) => (
                        <div
                          key={review.id}
                          className="border-b border-[var(--color-line)] pb-4 last:border-b-0 last:pb-0"
                        >
                          <div className="flex items-center justify-between mb-1.5">
                            <span className="font-cairo font-bold text-sm text-[var(--color-ink)]">
                              {review.customer_name}
                            </span>
                            <span className="font-readex text-xs text-[var(--color-ink-soft)]">
                              {review.created_at}
                            </span>
                          </div>
                          <div className="flex items-center gap-1 mb-2">
                            {[1, 2, 3, 4, 5].map((star) => (
                              <Star
                                key={star}
                                size={14}
                                strokeWidth={1.5}
                                className={
                                  star <= review.rating
                                    ? "fill-[var(--color-energy)] text-[var(--color-energy)]"
                                    : "text-[var(--color-line)]"
                                }
                              />
                            ))}
                          </div>
                          <p className="text-sm text-[var(--color-ink-soft)] leading-relaxed">
                            {review.comment}
                          </p>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <div className="text-center py-10">
                      <p className="text-[var(--color-ink-soft)] font-ibm">
                        لم يتم تقييم هذا المنتج بعد.
                      </p>
                    </div>
                  )}
                </div>
              )}
            </div>
          </div>

          {/* منتجات قد تهمك */}
          {relatedProducts.length > 0 && (
            <div className="p-8 bg-[var(--color-surface)] border-t border-[var(--color-line)]">
              <h3 className="font-cairo font-black text-[var(--color-ink)] text-xl mb-6">
                منتجات قد تهمك
              </h3>
              <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                {relatedProducts.map((rp) => (
                  <div
                    key={rp.id}
                    role="button"
                    tabIndex={0}
                    onClick={() => onSelectProduct?.(rp)}
                    onKeyDown={(e) => {
                      if (e.key === "Enter" || e.key === " ") {
                        e.preventDefault();
                        onSelectProduct?.(rp);
                      }
                    }}
                    className="bg-[var(--color-bg)] border border-[var(--color-line)] p-3 cursor-pointer hover:shadow-[var(--shadow-soft)] hover:border-[var(--color-primary)] transition-all group flex flex-col h-full"
                    style={{ borderRadius: "var(--radius-md)" }}
                  >
                    <div
                      className="aspect-[4/5] bg-[var(--color-surface)] mb-3 overflow-hidden flex items-center justify-center relative"
                      style={{ borderRadius: "var(--radius-md)" }}
                    >
                      <img
                        src={
                          rp.images?.[0]?.url ||
                          rp.image_url ||
                          "/placeholder.png"
                        }
                        loading="lazy"
                        className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 mix-blend-multiply"
                        alt=""
                      />
                    </div>
                    <div className="mt-auto">
                      <h4 className="font-cairo font-black text-[var(--color-ink)] text-sm mb-1 line-clamp-1">
                        {rp.name}
                      </h4>
                      <span className="font-readex font-bold text-[var(--color-primary)] text-sm">
                        {rp.price}{" "}
                        <span className="text-xs text-[var(--color-ink-soft)] font-normal">
                          {currency}
                        </span>
                      </span>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};
