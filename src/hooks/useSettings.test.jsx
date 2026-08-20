import React from 'react';
import { renderHook, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { afterEach, describe, expect, it, vi } from 'vitest';

const apiGet = vi.hoisted(() => vi.fn());

vi.mock('../services/api', () => ({
    default: {
        get: apiGet,
    },
}));

import { useSettings } from './useSettings';

const createWrapper = () => {
    const queryClient = new QueryClient({
        defaultOptions: {
            queries: { retry: false },
        },
    });

    return ({ children }) => (
        <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
    );
};

describe('useSettings', () => {
    afterEach(() => {
        apiGet.mockReset();
    });

    it('يشارك طلب الإعدادات بين جميع المستهلكين', async () => {
        apiGet.mockResolvedValue({
            data: {
                data: {
                    currency: 'د.أ',
                    whatsapp_number: '963900000000',
                    free_shipping_threshold: 25000,
                },
            },
        });

        const { result } = renderHook(() => ({
            first: useSettings(),
            second: useSettings(),
        }), { wrapper: createWrapper() });

        await waitFor(() => {
            expect(result.current.first.currency).toBe('د.أ');
        });

        expect(result.current.second.freeShippingThreshold).toBe(25000);
        expect(apiGet).toHaveBeenCalledTimes(1);
        expect(apiGet).toHaveBeenCalledWith('/settings');
    });

    it('يبقي قيم العرض الآمنة عند فشل تحميل الإعدادات', async () => {
        apiGet.mockRejectedValue(new Error('Network unavailable'));

        const { result } = renderHook(() => useSettings(), { wrapper: createWrapper() });

        await waitFor(() => {
            expect(result.current.isError).toBe(true);
        }, { timeout: 2500 });

        expect(result.current.currency).toBe('ل.س');
        expect(result.current.freeShippingThreshold).toBe(15000);
    });
});
