/**
 * اتجاه الواجهة: عناية هادئة — شريط ثابت الأبعاد، وفئة العناية الخاصة واضحة دون إثارة بصرية.
 */
import React, { useEffect, useMemo, useState } from "react";
import { Search, ShieldCheck, ShoppingBag, Truck } from "lucide-react";
import { getCategoryIcon } from "../utils/categoryIcons";
import logo from "../assets/alashab-logo.svg";

const PRIVATE_CARE_CATEGORY_PATTERN =
  /العناية\s*الخاصة|الصحة\s*الخاصة|intimate|private[\s_-]?care/i;

const isPrivateCareCategory = (category = {}) => {
  const searchableCategoryData = [
    category.name,
    category.slug,
    category.care_type,
    category.code,
  ]
    .filter(Boolean)
    .join(" ");

  return PRIVATE_CARE_CATEGORY_PATTERN.test(searchableCategoryData);
};

export const Header = ({
  cartCount,
  onCartClick,
  searchQuery,
  onSearchChange,
  categories = [],
  onCategorySelect,
  lastAddedItemId,
}) => {
  const [isSearchFocused, setIsSearchFocused] = useState(false);
  const [isBouncing, setIsBouncing] = useState(false);

  useEffect(() => {
    if (lastAddedItemId == null) return undefined;

    setIsBouncing(true);
    const timer = setTimeout(() => setIsBouncing(false), 500);
    return () => clearTimeout(timer);
  }, [lastAddedItemId]);

  const orderedCategories = useMemo(() => {
    const privateCareCategories = categories.filter(isPrivateCareCategory);
    const otherCategories = categories.filter(
      (category) => !isPrivateCareCategory(category),
    );
    return [...privateCareCategories, ...otherCategories];
  }, [categories]);

  const renderSearchInput = (inputId) => (
    <div
      className={`relative w-full transition-all duration-300 ${
        isSearchFocused ? "shadow-[var(--shadow-sm)]" : ""
      }`}
    >
      <label htmlFor={inputId} className="sr-only">
        البحث عن منتج
      </label>
      <input
        id={inputId}
        type="search"
        inputMode="search"
        autoComplete="off"
        placeholder="ابحثي عن منتج أو احتياج..."
        value={searchQuery}
        onChange={(event) => onSearchChange(event.target.value)}
        onFocus={() => setIsSearchFocused(true)}
        onBlur={() => setIsSearchFocused(false)}
        className="w-full h-11 bg-[var(--color-bg)] border border-[var(--color-line)] rounded-[var(--radius-full)] py-2 px-4 pr-10 text-[var(--color-ink)] font-ibm text-sm focus:outline-none focus:border-[var(--color-primary)] focus:ring-4 focus:ring-[var(--color-primary-soft)] transition-all placeholder:text-[var(--color-ink-soft)]"
      />
      <Search
        aria-hidden="true"
        className="absolute right-3.5 top-3 text-[var(--color-ink-soft)]"
        size={18}
        strokeWidth={1.7}
      />
    </div>
  );

  return (
    <header className="sticky top-0 z-50 w-full border-b border-[var(--color-line)] bg-[color:rgba(252,252,248,.96)] backdrop-blur-xl transition-[background-color,box-shadow] duration-200">
      <div className="hidden h-9 border-b border-[var(--color-line)] bg-[var(--color-surface)] lg:block">
        <div className="wellness-container flex h-8 items-center justify-between text-[11px] font-ibm text-[var(--color-ink-soft)]">
          <span className="flex items-center gap-1.5">
            <Truck aria-hidden="true" size={13} strokeWidth={1.8} /> شحن موثوق
            لكافة المحافظات السورية
          </span>
          <span className="flex items-center gap-1.5">
            <ShieldCheck aria-hidden="true" size={13} strokeWidth={1.8} /> دفع
            آمن وخصوصية تامة
          </span>
        </div>
      </div>

      <div className="wellness-container">
        <div className="flex min-h-16 items-center justify-between gap-3 sm:min-h-[4.25rem]">
          <a
            href="/"
            className="flex shrink-0 items-center rounded-[var(--radius-md)] transition-transform hover:scale-[1.02] focus:outline-none focus-visible:ring-4 focus-visible:ring-[var(--color-primary-soft)]"
            aria-label="العشاب - العودة إلى الصفحة الرئيسية"
          >
            <img
              src={logo}
              alt="العشاب - عناية يومية بوعي"
              className="h-9 w-auto sm:h-10"
              width="150"
              height="40"
            />
          </a>

          <div className="hidden min-w-0 flex-1 lg:flex lg:max-w-2xl">
            {renderSearchInput("store-search-desktop")}
          </div>

          <button
            type="button"
            onClick={onCartClick}
            className={`relative grid h-10 w-10 shrink-0 place-items-center rounded-full border border-[var(--color-line)] bg-[var(--color-bg)] text-[var(--color-ink)] transition-colors hover:border-[var(--color-primary)] hover:text-[var(--color-primary)] focus:outline-none focus-visible:ring-4 focus-visible:ring-[var(--color-primary-soft)] ${isBouncing ? "animate-bounce" : ""}`}
            aria-label={`سلة المشتريات، ${cartCount} منتجات`}
          >
            <ShoppingBag aria-hidden="true" size={20} strokeWidth={1.8} />
            {cartCount > 0 && (
              <span
                className="absolute -top-1 -right-1 flex h-5 min-w-5 items-center justify-center rounded-full border-2 border-[var(--color-bg)] bg-[var(--color-energy)] px-1 text-[10px] font-readex text-white"
                aria-hidden="true"
              >
                {cartCount}
              </span>
            )}
          </button>
        </div>

        <div className="pb-2.5 lg:hidden">
          {renderSearchInput("store-search-mobile")}
        </div>

        <nav
          className="flex h-10 items-center gap-1 overflow-x-auto border-t border-[var(--color-line)] scrollbar-hide"
          aria-label="فئات المتجر"
        >
          <button
            type="button"
            onClick={() => onCategorySelect?.(null)}
            className="shrink-0 rounded-full bg-[var(--color-primary-soft)] px-3 py-1.5 font-ibm text-xs font-bold text-[var(--color-primary)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)]"
          >
            كل المنتجات
          </button>
          {orderedCategories.map((cat) => {
            const Icon = getCategoryIcon(cat.name || "");
            const accent = cat.accent_color || "var(--color-primary)";
            const isPrivateCare = isPrivateCareCategory(cat);
            return (
              <button
                key={cat.id}
                type="button"
                onClick={() => onCategorySelect?.(cat.id)}
                className={`flex shrink-0 items-center gap-1 rounded-full px-2.5 py-1.5 font-ibm text-xs transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)] ${isPrivateCare ? "border border-[var(--color-primary)] bg-[var(--color-primary-soft)] font-bold text-[var(--color-primary)] hover:bg-[var(--color-primary)] hover:text-white" : "text-[var(--color-ink)] hover:bg-[var(--color-surface)] hover:text-[var(--color-primary)]"}`}
              >
                <Icon
                  aria-hidden="true"
                  size={14}
                  strokeWidth={1.7}
                  style={{ color: accent }}
                />
                <span>{cat.name}</span>
              </button>
            );
          })}
        </nav>
      </div>
    </header>
  );
};
