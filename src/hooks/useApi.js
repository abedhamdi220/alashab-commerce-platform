import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useEffect } from "react";
import apiClient from "../services/api";
import { normalizeProductMedia } from "../utils/mediaUrl";
// نفترض وجود السياق الجديد الذي تم إنشاؤه في main.jsx
import { useToast } from "../context/ToastContext";

export const useCategories = () => {
  const { showError } = useToast();
  const query = useQuery({
    queryKey: ["categories"], //[cite: 10]
    queryFn: async () => {
      const { data } = await apiClient.get("/categories"); //[cite: 10]
      return data.data || []; //[cite: 10]
    },
    // [تصحيح]: بلا staleTime كانت هاي البيانات (وبقية الـ queries تحت) تُعتبر "قديمة" فوراً
    // فتُعاد جلبها بأي remount أو أي رجوع focus للنافذة — التصنيفات نادراً ما تتغير خلال الجلسة
    staleTime: 5 * 60 * 1000, // 5 دقائق
  });

  useEffect(() => {
    if (query.isError) showError("تعذر تحميل التصنيفات. يرجى المحاولة لاحقاً.");
  }, [query.isError, showError]);

  return query;
};

export const useProducts = (categoryId = null) => {
  const { showError } = useToast();
  const query = useQuery({
    queryKey: ["products", categoryId], //[cite: 10]
    queryFn: async () => {
      const url = categoryId
        ? `/products?category_id=${categoryId}`
        : "/products"; //[cite: 10]
      const { data } = await apiClient.get(url); //[cite: 10]
      return (data.data || []).map(normalizeProductMedia); //[cite: 10]
    },
    staleTime: 2 * 60 * 1000, // دقيقتين — يخفف طلبات متكررة عند تبديل التصنيفات ذهاباً وإياباً
  });

  useEffect(() => {
    if (query.isError)
      showError("تعذر تحميل المنتجات. يرجى التحقق من اتصالك بالإنترنت.");
  }, [query.isError, showError]);

  return query;
};

// --- الـ Hooks الجديدة لدعم الهوية البصرية ---

export const useBestsellers = () => {
  const { showError } = useToast();
  const query = useQuery({
    queryKey: ["products", "bestsellers"],
    queryFn: async () => {
      // جلب المنتجات المفعّلة يدوياً كـ "الأكثر مبيعاً" (is_bestseller = true)
      // الباك اند لا يقرأ باراميتر sort أصلاً، فيه endpoint مخصص بدل ذلك
      const { data } = await apiClient.get("/products/bestsellers");
      return (data.data || []).map(normalizeProductMedia);
    },
    staleTime: 5 * 60 * 1000,
  });

  useEffect(() => {
    if (query.isError) showError("تعذر تحميل قائمة المنتجات الأكثر مبيعاً.");
  }, [query.isError, showError]);

  return query;
};

export const useNewArrivals = () => {
  const { showError } = useToast();
  const query = useQuery({
    queryKey: ["products", "new_arrivals"],
    queryFn: async () => {
      // جلب المنتجات المضافة خلال آخر 14 يوماً — endpoint مخصص، الباك اند لا يقرأ sort أصلاً
      const { data } = await apiClient.get("/products/newest");
      return (data.data || []).map(normalizeProductMedia);
    },
    staleTime: 5 * 60 * 1000,
  });

  useEffect(() => {
    if (query.isError) showError("تعذر تحميل أحدث الإضافات.");
  }, [query.isError, showError]);

  return query;
};

export const useProductReviews = (productId) => {
  const { showError } = useToast();
  const query = useQuery({
    queryKey: ["reviews", productId],
    queryFn: async () => {
      const { data } = await apiClient.get(`/products/${productId}/reviews`);
      return data.data || [];
    },
    enabled: !!productId, // يمنع تشغيل الاستعلام إذا لم يكن productId متوفراً
  });

  useEffect(() => {
    if (query.isError) showError("تعذر تحميل تقييمات المنتج.");
  }, [query.isError, showError]);

  return query;
};

export const useSubmitProductReview = (productId) => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (payload) => {
      const { data } = await apiClient.post(
        `/products/${productId}/reviews`,
        payload,
      );
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["reviews", productId] });
      queryClient.invalidateQueries({ queryKey: ["products"] });
    },
  });
};
