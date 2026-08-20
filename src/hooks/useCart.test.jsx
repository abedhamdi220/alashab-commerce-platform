import { act, renderHook } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { useCart } from './useCart';

const product = {
    id: 42,
    name: 'منتج تجريبي',
    price: 25000,
    in_stock: true,
    stock_quantity: 3,
    image_url: '/uploads/product.png',
};

describe('useCart', () => {
    let consoleErrorSpy;

    beforeEach(() => {
        localStorage.clear();
        consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
    });

    afterEach(() => {
        consoleErrorSpy.mockRestore();
        localStorage.clear();
    });

    it('يدمج المنتج نفسه ويحترم حد المخزون', () => {
        const { result } = renderHook(() => useCart());

        act(() => result.current.addToCart({ ...product, quantity: 2 }));
        act(() => result.current.addToCart({ ...product, quantity: 2 }));

        expect(result.current.cart).toHaveLength(1);
        expect(result.current.cart[0]).toMatchObject({ id: 42, quantity: 3 });
        expect(result.current.isCartOpen).toBe(true);
    });

    it('لا يضيف منتجاً نافداً ويزيل السلة عند طلب البدء من جديد', () => {
        const { result } = renderHook(() => useCart());

        act(() => result.current.addToCart({ ...product, in_stock: false }));
        expect(result.current.cart).toEqual([]);

        act(() => result.current.addToCart(product));
        expect(result.current.cart).toHaveLength(1);

        act(() => result.current.clearCart());
        expect(result.current.cart).toEqual([]);
        expect(result.current.lastAddedItemId).toBeNull();
    });

    it('يتعافى بأمان من بيانات سلة تالفة في التخزين المحلي', () => {
        localStorage.setItem('alashab_cart', '{not-valid-json');

        const { result } = renderHook(() => useCart());

        expect(result.current.cart).toEqual([]);
        // بعد التعافي، يؤكد تأثير الحفظ اللاحق أن السلة الصالحة أصبحت فارغة.
        expect(localStorage.getItem('alashab_cart')).toBe('[]');
        expect(consoleErrorSpy).toHaveBeenCalled();
    });
});
