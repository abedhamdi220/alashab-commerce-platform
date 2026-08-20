/** عناية هادئة: السلة محلية، والمفضلة مصدرها الخادم مع تحديث متفائل قابل للتراجع. */
import { useState, useEffect, useCallback } from "react";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { normalizeProductMedia } from "../utils/mediaUrl";
import apiClient from "../services/api";
import { useToast } from "../context/ToastContext";

const getRequestedQuantity = (product) => {
  const quantity = Number.parseInt(product?.quantity, 10);
  return Number.isInteger(quantity) && quantity > 0 ? quantity : 1;
};

const getStockLimit = (product) => {
  if (
    product?.stock_quantity === null ||
    product?.stock_quantity === undefined
  ) {
    return null;
  }

  const stock = Number.parseInt(product.stock_quantity, 10);
  return Number.isInteger(stock) && stock >= 0 ? stock : null;
};

export const useCart = () => {
  const queryClient = useQueryClient();
  const { showError, showToast } = useToast();
  const [cart, setCart] = useState(() => {
    try {
      const savedCart = localStorage.getItem("alashab_cart");
      if (!savedCart) return [];

      const parsedCart = JSON.parse(savedCart);
      return Array.isArray(parsedCart)
        ? parsedCart.map(normalizeProductMedia)
        : [];
    } catch (error) {
      console.error("Cart data is corrupted, resetting cart.", error);
      localStorage.removeItem("alashab_cart");
      return [];
    }
  });

  const favoritesQuery = useQuery({
    queryKey: ["favorites"],
    queryFn: async () => {
      const { data } = await apiClient.get("/favorites");
      return (data.data || []).map((favorite) => Number(favorite.product_id));
    },
    staleTime: 30 * 1000,
    retry: 1,
  });
  const wishlist = favoritesQuery.data || [];

  useEffect(() => {
    if (favoritesQuery.isError) {
      showError("تعذر تحميل المفضلة من الخادم. يمكنك إعادة المحاولة لاحقاً.");
    }
  }, [favoritesQuery.isError, showError]);

  const [isCartOpen, setIsCartOpen] = useState(false);
  const [lastAddedItemId, setLastAddedItemId] = useState(null);

  useEffect(() => {
    localStorage.setItem("alashab_cart", JSON.stringify(cart));
  }, [cart]);

  const addToCart = useCallback((product) => {
    product = normalizeProductMedia(product);
    const requestedQuantity = getRequestedQuantity(product);
    const stockLimit = getStockLimit(product);

    if (!product?.id || product.in_stock === false || stockLimit === 0) {
      return;
    }

    setCart((previousCart) => {
      const existing = previousCart.find((item) => item.id === product.id);

      if (existing) {
        return previousCart.map((item) => {
          if (item.id !== product.id) return item;

          const latestStockLimit =
            getStockLimit(product) ?? getStockLimit(item);
          const nextQuantity = item.quantity + requestedQuantity;

          return {
            ...item,
            ...product,
            quantity:
              latestStockLimit === null
                ? nextQuantity
                : Math.min(nextQuantity, latestStockLimit),
          };
        });
      }

      return [
        ...previousCart,
        {
          ...product,
          quantity:
            stockLimit === null
              ? requestedQuantity
              : Math.min(requestedQuantity, stockLimit),
        },
      ];
    });

    setLastAddedItemId(product.id);
    setIsCartOpen(true);
  }, []);

  const updateQuantity = useCallback((id, quantity) => {
    const requestedQuantity = Number.parseInt(quantity, 10);
    if (!Number.isInteger(requestedQuantity) || requestedQuantity < 1) return;

    setCart((previousCart) =>
      previousCart.map((item) => {
        if (item.id !== id) return item;

        const stockLimit = getStockLimit(item);
        return {
          ...item,
          quantity:
            stockLimit === null
              ? requestedQuantity
              : Math.min(requestedQuantity, stockLimit),
        };
      }),
    );
  }, []);

  const removeFromCart = useCallback((id) => {
    setCart((previousCart) => previousCart.filter((item) => item.id !== id));
  }, []);

  const clearCart = useCallback(() => {
    setCart([]);
    setLastAddedItemId(null);
  }, []);

  const toggleWishlist = useCallback(
    async (product) => {
      const productId = Number(product?.id);
      if (!Number.isInteger(productId) || productId < 1) return;

      const previousWishlist = wishlist;
      const isFavorite = previousWishlist.includes(productId);
      const nextWishlist = isFavorite
        ? previousWishlist.filter((id) => id !== productId)
        : [...previousWishlist, productId];

      queryClient.setQueryData(["favorites"], nextWishlist);

      try {
        const { data } = isFavorite
          ? await apiClient.delete(`/products/${productId}/favorite`)
          : await apiClient.post(`/products/${productId}/favorite`);
        showToast(
          data.message ||
            (isFavorite
              ? "تمت إزالة المنتج من المفضلة."
              : "تمت إضافة المنتج إلى المفضلة."),
        );
      } catch (error) {
        queryClient.setQueryData(["favorites"], previousWishlist);

        const isProductUnavailable = error?.response?.status === 404;
        if (isProductUnavailable) {
          // المنتج قد يكون حُذف أو نُقل أو تغيّر متجر الرابط منذ آخر جلب للقائمة.
          // نحدّث البيانات المصدرية بدلاً من الإبقاء على بطاقة غير صالحة للتفاعل.
          void queryClient.invalidateQueries({ queryKey: ["products"] });
          void queryClient.invalidateQueries({ queryKey: ["favorites"] });
        }

        showError(
          isProductUnavailable
            ? "هذا المنتج لم يعد متاحاً في المتجر. تم تحديث قائمة المنتجات."
            : error?.response?.data?.message ||
              "تعذر حفظ تغيير المفضلة. يرجى المحاولة مرة أخرى.",
        );
      }
    },
    [queryClient, showError, showToast, wishlist],
  );

  const getFreeShippingProgress = useCallback((subtotal, threshold) => {
    if (!threshold || threshold <= 0) {
      return { progress: 100, remaining: 0, threshold: 0 };
    }

    return {
      progress: Math.min((subtotal / threshold) * 100, 100),
      remaining: Math.max(threshold - subtotal, 0),
      threshold,
    };
  }, []);

  return {
    cart,
    isCartOpen,
    setIsCartOpen,
    addToCart,
    updateQuantity,
    removeFromCart,
    clearCart,
    lastAddedItemId,
    wishlist,
    toggleWishlist,
    getFreeShippingProgress,
  };
};
