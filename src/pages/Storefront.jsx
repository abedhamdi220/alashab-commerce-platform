/**
 * اتجاه الواجهة: عناية هادئة — مفضلة شخصية واضحة ومنتجات حساسة معروضة بترتيب يحترم الخصوصية.
 */
import React, { lazy, Suspense, useState, useMemo, useEffect } from "react";
import {
  ArrowLeft,
  CheckCircle2,
  ClipboardList,
  Heart,
  Leaf,
  MessageCircle,
  Search,
  ShieldCheck,
  ShoppingBag,
  Sparkles,
  Star,
  Truck,
} from "lucide-react";
import { Header } from "../components/Header";
import { ProductCard } from "../components/ProductCard";
import { Footer } from "../components/Footer";
import { CategoryShowcase } from "../components/CategoryShowcase";
import { HowItWorks } from "../components/HowItWorks";
import { FAQ } from "../components/FAQ";
import { useProducts, useCategories, useBestsellers } from "../hooks/useApi";
import { useCart } from "../hooks/useCart";
import { useDebounce } from "../hooks/useDebounce";
import heroIllustration from "../assets/hero-botanical.svg";

const ProductDetailsModal = lazy(() =>
  import("../components/ProductDetailsModal").then(
    ({ ProductDetailsModal: Component }) => ({ default: Component }),
  ),
);
const CareAdvisorModal = lazy(() =>
  import("../components/CareAdvisorModal").then(
    ({ CareAdvisorModal: Component }) => ({ default: Component }),
  ),
);
const CartDrawer = lazy(() =>
  import("../components/CartDrawer").then(({ CartDrawer: Component }) => ({
    default: Component,
  })),
);
const PRODUCTS_PAGE_SIZE = 8;
const PRIVATE_CARE_PRODUCT_PATTERN =
  /العناية\s*الخاصة|الصحة\s*الخاصة|intimate|private[\s_-]?care/i;

const isPrivateCareProduct = (product = {}) => {
  const category = product.category || {};
  const isDiscreet =
    product.is_discreet === true ||
    product.is_discreet === 1 ||
    product.is_discreet === "true";
  const searchableProductData = [
    product.name,
    category.name,
    category.slug,
    category.care_type,
  ]
    .filter(Boolean)
    .join(" ");

  return isDiscreet || PRIVATE_CARE_PRODUCT_PATTERN.test(searchableProductData);
};

const TrustItem = ({ icon: Icon, children }) => (
  <div className="flex items-center gap-2 text-sm text-[var(--color-ink-soft)]">
    <span className="w-8 h-8 rounded-full bg-[var(--color-primary-soft)] text-[var(--color-primary)] grid place-items-center flex-none">
      <Icon size={16} strokeWidth={1.8} />
    </span>
    <span>{children}</span>
  </div>
);

const RatingStars = ({ rating, size = 15 }) => (
  <span
    className="inline-flex items-center gap-0.5"
    role="img"
    aria-label={`التقييم ${rating} من 5`}
  >
    {Array.from({ length: 5 }, (_, index) => (
      <Star
        key={index}
        size={size}
        className={
          index < Math.round(rating)
            ? "fill-[var(--color-energy)] text-[var(--color-energy)]"
            : "text-[var(--color-line)]"
        }
        strokeWidth={1.8}
      />
    ))}
  </span>
);

export const Storefront = () => {
  const [selectedCategory, setSelectedCategory] = useState(null);
  const [searchQuery, setSearchQuery] = useState("");
  const [activeProduct, setActiveProduct] = useState(null);
  const [isAdvisorOpen, setIsAdvisorOpen] = useState(false);
  const [visibleCount, setVisibleCount] = useState(PRODUCTS_PAGE_SIZE);

  const debouncedSearch = useDebounce(searchQuery, 300, true);
  const { data: categories = [] } = useCategories();
  const {
    data: allProducts = [],
    isLoading,
    isError: isProductsError,
    refetch: refetchProducts,
  } = useProducts();
  const { data: bestsellers = [] } = useBestsellers();
  const {
    cart,
    addToCart,
    updateQuantity,
    removeFromCart,
    clearCart,
    isCartOpen,
    setIsCartOpen,
    lastAddedItemId,
    wishlist,
    toggleWishlist,
  } = useCart();
  const [shouldRenderCart, setShouldRenderCart] = useState(false);

  useEffect(() => {
    if (isCartOpen) {
      setShouldRenderCart(true);
      return undefined;
    }

    const exitTimer = window.setTimeout(() => setShouldRenderCart(false), 260);
    return () => window.clearTimeout(exitTimer);
  }, [isCartOpen]);

  const wishlistIds = useMemo(() => new Set(wishlist), [wishlist]);
  const favoriteProducts = useMemo(
    () => allProducts.filter((product) => wishlistIds.has(Number(product.id))),
    [allProducts, wishlistIds],
  );

  const handleCategorySelect = (categoryId) => {
    setSelectedCategory(categoryId);
    document
      .getElementById("products-catalog")
      ?.scrollIntoView({ behavior: "smooth", block: "start" });
  };

  const trimmedSearch = debouncedSearch.trim().toLowerCase();
  const categoryProducts = useMemo(
    () =>
      selectedCategory === null
        ? allProducts
        : allProducts.filter(
            (product) =>
              String(product.category?.id ?? product.category_id) ===
              String(selectedCategory),
          ),
    [allProducts, selectedCategory],
  );
  const filteredProducts = useMemo(
    () =>
      trimmedSearch
        ? allProducts.filter((product) =>
            String(product.name || "")
              .toLowerCase()
              .includes(trimmedSearch),
          )
        : categoryProducts,
    [allProducts, categoryProducts, trimmedSearch],
  );

  const prioritizedProducts = useMemo(() => {
    const privateCareProducts = filteredProducts.filter(isPrivateCareProduct);
    const otherProducts = filteredProducts.filter(
      (product) => !isPrivateCareProduct(product),
    );
    return [...privateCareProducts, ...otherProducts];
  }, [filteredProducts]);

  useEffect(() => {
    setVisibleCount(PRODUCTS_PAGE_SIZE);
  }, [selectedCategory, trimmedSearch]);

  const visibleProducts = prioritizedProducts.slice(0, visibleCount);
  const hasMoreProducts = visibleCount < prioritizedProducts.length;

  const testimonials = useMemo(
    () =>
      allProducts
        .flatMap((product) =>
          (product.reviews || []).map((review) => ({
            ...review,
            product_name: product.name,
            product,
          })),
        )
        .filter((review) => review.comment && review.comment.trim().length > 0)
        .sort((first, second) => second.rating - first.rating)
        .slice(0, 3),
    [allProducts],
  );
  const reviewSummary = useMemo(() => {
    const approvedReviews = allProducts.flatMap(
      (product) => product.reviews || [],
    );
    const count = approvedReviews.length;
    const average = count
      ? approvedReviews.reduce(
          (total, review) => total + Number(review.rating || 0),
          0,
        ) / count
      : 0;
    return { count, average };
  }, [allProducts]);

  return (
    <div className="wellness-shell min-h-screen text-[var(--color-ink)] font-ibm">
      <Header
        cartCount={cart.reduce((total, item) => total + item.quantity, 0)}
        onCartClick={() => setIsCartOpen(true)}
        searchQuery={searchQuery}
        onSearchChange={setSearchQuery}
        lastAddedItemId={lastAddedItemId}
        categories={categories}
        onCategorySelect={handleCategorySelect}
      />

      <main>
        <section className="wellness-container pt-5 sm:pt-7">
          <div className="relative overflow-hidden rounded-[var(--radius-xl)] bg-[var(--color-surface)] border border-[var(--color-line)] shadow-[var(--shadow-sm)]">
            <div className="absolute inset-y-0 left-0 w-1/2 bg-gradient-to-l from-transparent to-[rgba(255,255,255,.45)] pointer-events-none" />
            <div className="relative grid lg:grid-cols-2 min-h-[500px]">
              <div className="order-2 lg:order-1 p-7 sm:p-10 lg:p-14 flex flex-col justify-center items-start text-right">
                <span className="wellness-kicker inline-flex items-center gap-2 mb-4">
                  <Leaf size={15} strokeWidth={1.8} /> عناية يومية بوعي
                </span>
                <h1 className="font-cairo font-black text-4xl sm:text-5xl xl:text-6xl leading-[1.22] text-[var(--color-primary)] max-w-xl">
                  اختيارات صحية
                  <br />
                  أقرب لروتينك
                </h1>
                <p className="mt-5 max-w-lg text-[var(--color-ink-soft)] leading-8 text-base sm:text-lg">
                  منتجات عناية وتغذية مختارة بعناية، بمعلومات أوضح وتجربة تسوق
                  مريحة تحترم خصوصيتك.
                </p>
                <div className="mt-7 flex flex-wrap items-center gap-3">
                  <button
                    type="button"
                    onClick={() =>
                      document
                        .getElementById("products-catalog")
                        ?.scrollIntoView({ behavior: "smooth", block: "start" })
                    }
                    className="inline-flex items-center gap-2 bg-[var(--color-primary)] hover:bg-[var(--color-primary-dark)] text-white px-7 py-3.5 rounded-[var(--radius-md)] font-semibold text-sm transition-all active:scale-95"
                  >
                    اكتشفي المنتجات <ArrowLeft size={17} strokeWidth={1.9} />
                  </button>
                  <button
                    type="button"
                    onClick={() => setIsAdvisorOpen(true)}
                    className="inline-flex items-center gap-2 text-[var(--color-primary)] px-4 py-3 font-semibold text-sm hover:underline underline-offset-4"
                  >
                    <MessageCircle size={18} strokeWidth={1.7} /> كيف نختار لكِ؟
                  </button>
                </div>
                <div className="mt-6 inline-flex items-center gap-2 rounded-full bg-[rgba(255,255,255,.7)] px-4 py-2 text-xs text-[var(--color-ink-soft)] border border-[rgba(223,228,216,.8)]">
                  <ShieldCheck
                    size={15}
                    className="text-[var(--color-trust)]"
                    strokeWidth={1.8}
                  />{" "}
                  مكونات موضحة بوضوح
                </div>
              </div>

              <div className="order-1 lg:order-2 relative min-h-[285px] lg:min-h-full overflow-hidden bg-[var(--color-surface-deep)]">
                <img
                  src={heroIllustration}
                  alt="رسم توضيحي لمنتج عناية نباتي"
                  className="absolute inset-0 w-full h-full object-cover object-center"
                />
                <div className="absolute inset-0 bg-gradient-to-r from-[rgba(242,244,234,.65)] via-transparent to-transparent lg:block hidden" />
                <div className="absolute left-6 bottom-6 rounded-[var(--radius-md)] bg-[rgba(255,255,255,.88)] backdrop-blur px-4 py-3 border border-white/70 shadow-[var(--shadow-sm)]">
                  <p className="text-xs text-[var(--color-ink-soft)]">
                    اختيار واعٍ يبدأ من التفاصيل
                  </p>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section className="wellness-container py-7 grid grid-cols-1 sm:grid-cols-3 gap-4 border-b border-[var(--color-line)]">
          <TrustItem icon={ClipboardList}>
            تفاصيل منتج واضحة قبل الشراء
          </TrustItem>
          <TrustItem icon={ShieldCheck}>منتجات مفحوصة وموثوقة</TrustItem>
          <TrustItem icon={Truck}>خصوصية تامة في التغليف والشحن</TrustItem>
        </section>

        <CategoryShowcase
          categories={categories}
          onSelectCategory={handleCategorySelect}
        />

        {favoriteProducts.length > 0 && (
          <section
            className="wellness-container py-6 sm:py-10"
            aria-labelledby="favorites-title"
          >
            <div className="flex items-end justify-between gap-4 mb-6 sm:mb-8">
              <div>
                <span className="wellness-kicker inline-flex items-center gap-2">
                  <Heart size={15} strokeWidth={1.8} /> قائمتك الخاصة
                </span>
                <h2
                  id="favorites-title"
                  className="font-cairo font-black text-2xl sm:text-3xl mt-1"
                >
                  المنتجات التي احتفظتِ بها
                </h2>
              </div>
              <span className="text-xs sm:text-sm text-[var(--color-ink-soft)]">
                محفوظة بأمان في زيارتك الحالية
              </span>
            </div>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
              {favoriteProducts.slice(0, 4).map((product) => (
                <ProductCard
                  key={product.id}
                  product={product}
                  onOpenProduct={() => setActiveProduct(product)}
                  onAddToCart={addToCart}
                  onToggleWishlist={toggleWishlist}
                  isWishlisted
                />
              ))}
            </div>
          </section>
        )}

        {bestsellers.length > 0 && (
          <section className="wellness-container py-6 sm:py-10">
            <div className="flex items-end justify-between gap-4 mb-6 sm:mb-8">
              <div>
                <span className="wellness-kicker">مختارات المجتمع</span>
                <h2 className="font-cairo font-black text-2xl sm:text-3xl mt-1">
                  الأكثر اختياراً هذا الأسبوع
                </h2>
              </div>
              <button
                type="button"
                onClick={() => handleCategorySelect(null)}
                className="hidden sm:inline-flex items-center gap-1 text-sm font-semibold text-[var(--color-primary)] hover:underline underline-offset-4"
              >
                عرض الكل <ArrowLeft size={16} />
              </button>
            </div>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
              {bestsellers.slice(0, 4).map((product) => (
                <ProductCard
                  key={product.id}
                  product={product}
                  onOpenProduct={() => setActiveProduct(product)}
                  onAddToCart={addToCart}
                  onToggleWishlist={toggleWishlist}
                  isWishlisted={wishlistIds.has(product.id)}
                />
              ))}
            </div>
          </section>
        )}

        <section
          id="products-catalog"
          className="wellness-container py-12 sm:py-16 scroll-mt-28"
        >
          <div className="text-center max-w-xl mx-auto mb-8 sm:mb-10">
            <span className="wellness-kicker">تسوّقي بطريقتك</span>
            <h2 className="font-cairo font-black text-3xl sm:text-4xl mt-2">
              اختاري ما يناسب احتياجك
            </h2>
            <p className="text-sm sm:text-base text-[var(--color-ink-soft)] mt-3 leading-7">
              فلّتي حسب الفئة، أو استخدمي البحث للوصول إلى المنتج المناسب بسرعة.
            </p>
          </div>

          <div
            className="flex items-center gap-2 overflow-x-auto pb-4 mb-8 scrollbar-hide"
            role="group"
            aria-label="فلترة المنتجات"
          >
            <button
              type="button"
              onClick={() => setSelectedCategory(null)}
              aria-pressed={selectedCategory === null}
              className={`px-5 py-2.5 rounded-full font-semibold text-sm transition-all border whitespace-nowrap ${selectedCategory === null ? "bg-[var(--color-primary)] text-white border-[var(--color-primary)] shadow-[var(--shadow-sm)]" : "bg-[var(--color-bg)] text-[var(--color-ink-soft)] border-[var(--color-line)] hover:border-[var(--color-primary)] hover:text-[var(--color-primary)]"}`}
            >
              الكل
            </button>
            {categories.map((category) => (
              <button
                key={category.id}
                type="button"
                onClick={() => setSelectedCategory(category.id)}
                aria-pressed={selectedCategory === category.id}
                className={`px-5 py-2.5 rounded-full font-semibold text-sm transition-all border whitespace-nowrap ${selectedCategory === category.id ? "text-white shadow-[var(--shadow-sm)]" : "bg-[var(--color-bg)] text-[var(--color-ink-soft)] border-[var(--color-line)] hover:border-[var(--color-primary)] hover:text-[var(--color-primary)]"}`}
                style={
                  selectedCategory === category.id && category.accent_color
                    ? {
                        backgroundColor: category.accent_color,
                        borderColor: category.accent_color,
                      }
                    : undefined
                }
              >
                {category.name}
              </button>
            ))}
          </div>

          {isLoading ? (
            <div
              className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5"
              aria-label="جاري تحميل المنتجات"
            >
              {[...Array(8)].map((_, index) => (
                <div
                  key={index}
                  className="bg-[var(--color-surface)] h-80 rounded-[var(--radius-lg)] animate-pulse border border-[var(--color-line)]"
                />
              ))}
            </div>
          ) : isProductsError ? (
            <div
              className="text-center py-16 bg-[var(--color-surface)] border border-[var(--color-line)] rounded-[var(--radius-lg)]"
              role="alert"
            >
              <Search
                className="w-11 h-11 text-[var(--color-energy)] opacity-80 mx-auto mb-4"
                strokeWidth={1.5}
              />
              <p className="font-cairo text-xl font-bold">
                تعذر تحميل المنتجات
              </p>
              <p className="text-sm text-[var(--color-ink-soft)] mt-2">
                تحققي من اتصال الإنترنت ثم أعيدي المحاولة.
              </p>
              <button
                type="button"
                onClick={() => refetchProducts()}
                className="mt-5 bg-[var(--color-primary)] text-white px-5 py-2.5 rounded-[var(--radius-md)] text-sm font-semibold hover:bg-[var(--color-primary-dark)] transition-colors"
              >
                إعادة المحاولة
              </button>
            </div>
          ) : filteredProducts.length === 0 ? (
            <div className="text-center py-16 bg-[var(--color-surface)] border border-[var(--color-line)] rounded-[var(--radius-lg)]">
              <Search
                className="w-11 h-11 text-[var(--color-ink-soft)] opacity-50 mx-auto mb-4"
                strokeWidth={1.5}
              />
              <p className="font-cairo text-xl font-bold">
                لم نجد ما تبحثين عنه
              </p>
              <p className="text-sm text-[var(--color-ink-soft)] mt-2">
                جربي كلمات أبسط أو اختاري فئة مختلفة.
              </p>
            </div>
          ) : (
            <>
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                {visibleProducts.map((product) => (
                  <ProductCard
                    key={product.id}
                    product={product}
                    onOpenProduct={() => setActiveProduct(product)}
                    onAddToCart={addToCart}
                    onToggleWishlist={toggleWishlist}
                    isWishlisted={wishlistIds.has(product.id)}
                  />
                ))}
              </div>
              {hasMoreProducts && (
                <div className="flex justify-center mt-10">
                  <button
                    type="button"
                    onClick={() =>
                      setVisibleCount((count) => count + PRODUCTS_PAGE_SIZE)
                    }
                    className="bg-[var(--color-bg)] border border-[var(--color-line)] hover:border-[var(--color-primary)] hover:text-[var(--color-primary)] text-[var(--color-ink)] font-semibold text-sm px-8 py-3 rounded-[var(--radius-md)] transition-all"
                  >
                    تحميل المزيد ({filteredProducts.length - visibleCount}{" "}
                    متبقي)
                  </button>
                </div>
              )}
            </>
          )}
        </section>

        {testimonials.length > 0 && (
          <section
            id="customer-reviews"
            className="bg-[var(--color-surface)] border-y border-[var(--color-line)] py-14 sm:py-18 scroll-mt-28"
          >
            <div className="wellness-container">
              <div className="flex flex-col md:flex-row md:items-end md:justify-between gap-5 mb-9">
                <div>
                  <span className="wellness-kicker">تجارب موثقة</span>
                  <h2 className="font-cairo font-black text-3xl mt-2">
                    آراء عملائنا
                  </h2>
                  <p className="mt-3 text-sm leading-7 text-[var(--color-ink-soft)]">
                    نشارك هنا الآراء التي تمت مراجعتها والموافقة عليها.
                  </p>
                </div>
                <div className="inline-flex items-center gap-3 self-start rounded-[var(--radius-lg)] border border-[var(--color-line)] bg-[var(--color-bg)] px-4 py-3">
                  <span className="font-readex text-2xl font-bold text-[var(--color-ink)]">
                    {reviewSummary.average.toFixed(1)}
                  </span>
                  <div>
                    <RatingStars rating={reviewSummary.average} />
                    <p className="mt-1 text-xs font-ibm text-[var(--color-ink-soft)]">
                      من {reviewSummary.count} رأي معتمد
                    </p>
                  </div>
                </div>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
                {testimonials.map((review) => (
                  <article
                    key={review.id}
                    className="bg-[var(--color-bg)] p-6 sm:p-7 border border-[var(--color-line)] rounded-[var(--radius-lg)] flex flex-col justify-between shadow-[0_8px_20px_rgba(28,61,49,.04)]"
                  >
                    <div>
                      <div className="flex items-center justify-between gap-3 mb-5">
                        <RatingStars rating={Number(review.rating || 0)} />
                        <Sparkles
                          size={19}
                          className="text-[var(--color-energy)]"
                          strokeWidth={1.5}
                          aria-hidden="true"
                        />
                      </div>
                      <p className="text-sm text-[var(--color-ink)] leading-7 mb-6">
                        “{review.comment}”
                      </p>
                    </div>
                    <div className="pt-4 border-t border-[var(--color-line)] flex items-end justify-between gap-4">
                      <div>
                        <p className="font-cairo font-bold text-sm">
                          {review.customer_name}
                        </p>
                        <button
                          type="button"
                          onClick={() => setActiveProduct(review.product)}
                          className="mt-1 text-xs text-[var(--color-primary)] hover:underline underline-offset-4"
                        >
                          {review.product_name}
                        </button>
                      </div>
                      <span className="text-xs text-[var(--color-ink-soft)] whitespace-nowrap">
                        {review.created_at}
                      </span>
                    </div>
                  </article>
                ))}
              </div>
            </div>
          </section>
        )}

        <HowItWorks />
        <FAQ />
      </main>

      <Footer />
      {(activeProduct || isAdvisorOpen || isCartOpen || shouldRenderCart) && (
        <Suspense
          fallback={
            <div className="sr-only" role="status">
              جاري فتح المحتوى
            </div>
          }
        >
          {activeProduct && (
            <ProductDetailsModal
              product={activeProduct}
              isOpen
              onClose={() => setActiveProduct(null)}
              onAddToCart={addToCart}
              onSelectProduct={setActiveProduct}
              products={allProducts}
            />
          )}
          {isAdvisorOpen && (
            <CareAdvisorModal
              isOpen
              onClose={() => setIsAdvisorOpen(false)}
              products={allProducts}
              onSelectProduct={(product) => {
                setIsAdvisorOpen(false);
                setActiveProduct(product);
              }}
            />
          )}
          {(isCartOpen || shouldRenderCart) && (
            <CartDrawer
              isOpen={isCartOpen}
              onClose={() => setIsCartOpen(false)}
              cart={cart}
              onUpdateQuantity={updateQuantity}
              onRemoveFromCart={removeFromCart}
              onAddToCart={addToCart}
              onCheckoutSuccess={clearCart}
              products={allProducts}
            />
          )}
        </Suspense>
      )}
    </div>
  );
};
