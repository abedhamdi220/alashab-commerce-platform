import { useQuery } from '@tanstack/react-query';
import apiClient from '../services/api';

const DEFAULT_SETTINGS = {
    currency: 'ل.س',
    whatsapp_number: '',
    free_shipping_threshold: 15000,
};

const getSettings = async () => {
    const { data } = await apiClient.get('/settings');
    return { ...DEFAULT_SETTINGS, ...(data?.data || {}) };
};

// إعدادات المتجر بيانات مشتركة وثابتة نسبياً داخل الجلسة؛ React Query يمنع
// إنشاء طلب جديد لكل بطاقة منتج أو نافذة ويعيد استخدام النتيجة المخزنة مؤقتاً.
export const useSettings = () => {
    const query = useQuery({
        queryKey: ['settings'],
        queryFn: getSettings,
        staleTime: 15 * 60 * 1000,
        gcTime: 60 * 60 * 1000,
        retry: 1,
    });
    const settings = query.data || DEFAULT_SETTINGS;

    return {
        currency: settings.currency,
        whatsappNumber: settings.whatsapp_number,
        freeShippingThreshold: Number(settings.free_shipping_threshold) || 0,
        isLoading: query.isLoading,
        isError: query.isError,
    };
};
