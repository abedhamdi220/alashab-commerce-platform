import React, { memo, useEffect, useState } from 'react';
import { Heart, Lock, Plus, ShoppingBag } from 'lucide-react';
import { useSettings } from '../hooks/useSettings';

const ProductCardComponent = ({ product, onOpenProduct, onAddToCart, onToggleWishlist, isWishlisted }) => {
    const { currency } = useSettings();
    const isDiscreet = product.is_discreet;
    const isOutOfStock = product.in_stock === false;
    const accentColor = product.category?.accent_color || 'var(--color-primary)';
    const imageUrl = product.images?.find((image) => image?.url)?.url || product.image_url || null;
    const [imageFailed, setImageFailed] = useState(false);

    useEffect(() => {
        setImageFailed(false);
    }, [product.id, imageUrl]);
    const hasDiscount = product.discount_percentage > 0 || product.old_price;
    const oldPrice = product.old_price || (product.discount_percentage ? Math.round(product.price / (1 - product.discount_percentage / 100)) : null);
    const productName = isDiscreet ? 'منتج عناية بخصوصية' : product.name;

    const handleOpenProduct = () => {
        onOpenProduct?.();
    };

    const handleAddToCart = (event) => {
        event.preventDefault();
        event.stopPropagation();
        if (!isOutOfStock) onAddToCart?.(product);
    };

    const handleWishlist = (event) => {
        event.preventDefault();
        event.stopPropagation();
        onToggleWishlist?.(product);
    };

    return (
        <article className="relative h-full overflow-hidden bg-[var(--color-bg)] border border-[var(--color-line)] rounded-[var(--radius-lg)] hover:shadow-[var(--shadow-soft)] transition-all duration-300 group">
            <div className="relative aspect-[1.08/1] overflow-hidden bg-[var(--color-surface)]">
                <span className="absolute top-0 inset-x-0 h-1 z-20" style={{ backgroundColor: accentColor }} />
                <div className="absolute top-3 right-3 z-10 flex gap-2">
                    <button
                        type="button"
                        onClick={handleWishlist}
                        className="w-9 h-9 rounded-full grid place-items-center bg-[rgba(255,255,255,.88)] backdrop-blur border border-[rgba(223,228,216,.9)] text-[var(--color-ink-soft)] hover:text-[var(--accent-beauty)] transition-colors"
                        aria-label={isWishlisted ? 'إزالة من المفضلة' : 'إضافة للمفضلة'}
                    >
                        <Heart className={isWishlisted ? 'fill-[var(--accent-beauty)] text-[var(--accent-beauty)]' : ''} size={17} strokeWidth={1.8} />
                    </button>
                </div>
                <div className="absolute top-3 left-3 z-10 flex flex-col gap-1.5 items-start pointer-events-none">
                    {product.is_bestseller && <span className="bg-[var(--color-energy)] text-white text-[10px] font-bold px-2.5 py-1 rounded-full">الأكثر طلباً</span>}
                    {hasDiscount && <span className="bg-[var(--color-primary)] text-white text-[10px] font-bold px-2.5 py-1 rounded-full">خصم {product.discount_percentage ? `${product.discount_percentage}%` : 'خاص'}</span>}
                </div>

                <button
                    type="button"
                    onClick={handleOpenProduct}
                    className="absolute inset-0 w-full h-full z-0 text-right focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-4px] focus-visible:outline-[var(--color-primary)]"
                    aria-label={`عرض تفاصيل ${productName}`}
                >
                    {imageUrl && !imageFailed ? (
                        <img
                            src={imageUrl}
                            alt={productName}
                            loading="lazy"
                            decoding="async"
                            onError={() => setImageFailed(true)}
                            className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                        />
                    ) : (
                        <span className="h-full flex flex-col items-center justify-center text-center p-5 text-[var(--color-ink-soft)]">
                            <span className="w-14 h-14 rounded-full bg-[var(--color-bg)] border border-[var(--color-line)] grid place-items-center mb-3"><ShoppingBag size={24} strokeWidth={1.5} /></span>
                            <span className="text-sm font-semibold">صورة المنتج غير متاحة حالياً</span>
                        </span>
                    )}
                    {isDiscreet && (
                        <span className="pointer-events-none absolute bottom-3 right-3 inline-flex items-center gap-1.5 rounded-full border border-white/70 bg-[rgba(22,59,45,.84)] px-2.5 py-1 text-[10px] font-bold text-white shadow-sm backdrop-blur-sm">
                            <Lock size={12} strokeWidth={2} aria-hidden="true" /> عرض بخصوصية
                        </span>
                    )}
                </button>
            </div>

            <div className="p-4 sm:p-5 flex flex-col min-h-[172px]">
                {product.category?.name && <span className="text-[11px] font-semibold text-[var(--color-trust)] mb-1.5">{product.category.name}</span>}
                <h3 className="font-cairo font-bold text-base sm:text-lg text-[var(--color-ink)] leading-7 line-clamp-2">
                    <button
                        type="button"
                        onClick={handleOpenProduct}
                        className="text-right hover:text-[var(--color-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-primary)]"
                    >
                        {productName}
                    </button>
                </h3>
                <div className="mt-auto pt-4 flex items-end justify-between gap-3">
                    <div>
                        {hasDiscount && <span className="block text-xs text-[var(--color-ink-soft)] line-through mb-0.5">{oldPrice} {currency}</span>}
                        <div className="flex items-baseline gap-1"><span className="font-readex font-bold text-lg text-[var(--color-primary)]">{product.price}</span><span className="text-[11px] text-[var(--color-ink-soft)]">{currency}</span></div>
                    </div>
                    <button
                        type="button"
                        onClick={handleAddToCart}
                        disabled={isOutOfStock}
                        className="inline-flex items-center justify-center w-10 h-10 rounded-[var(--radius-sm)] bg-[var(--color-primary-soft)] text-[var(--color-primary)] hover:bg-[var(--color-primary)] hover:text-white transition-all active:scale-95 disabled:opacity-45 disabled:pointer-events-none"
                        aria-label={isOutOfStock ? 'نفدت الكمية' : 'أضيفي للسلة'}
                    >
                        {isOutOfStock ? <ShoppingBag size={18} strokeWidth={1.8} /> : <Plus size={21} strokeWidth={2} />}
                    </button>
                </div>
            </div>
        </article>
    );
};

export const ProductCard = memo(ProductCardComponent);
