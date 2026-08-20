import React from 'react';
import { fireEvent, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';

vi.mock('../hooks/useSettings', () => ({
    useSettings: () => ({ currency: 'ل.س' }),
}));

import { ProductCard } from './ProductCard';

const product = {
    id: 7,
    name: 'زيت عناية تجريبي',
    price: 12000,
    image_url: '/uploads/oil.jpg',
    in_stock: true,
    category: { name: 'العناية بالبشرة' },
};

describe('ProductCard', () => {
    it('يفتح التفاصيل من زر صالح للوصول ولا يخلط ذلك بإجراء إضافة السلة', async () => {
        const user = userEvent.setup();
        const onOpenProduct = vi.fn();
        const onAddToCart = vi.fn();

        render(
            <ProductCard
                product={product}
                isWishlisted={false}
                onOpenProduct={onOpenProduct}
                onAddToCart={onAddToCart}
                onToggleWishlist={vi.fn()}
            />,
        );

        await user.click(screen.getByRole('button', { name: 'عرض تفاصيل زيت عناية تجريبي' }));
        expect(onOpenProduct).toHaveBeenCalledTimes(1);
        expect(onAddToCart).not.toHaveBeenCalled();

        await user.click(screen.getByRole('button', { name: 'أضيفي للسلة' }));
        expect(onAddToCart).toHaveBeenCalledWith(product);
        expect(onOpenProduct).toHaveBeenCalledTimes(1);
    });

    it('يعرض صورة المنتج ذي الفئة الخاصة مع شارة الخصوصية بدلاً من إخفائها', () => {
        render(
            <ProductCard
                product={{ ...product, is_discreet: true }}
                isWishlisted={false}
                onOpenProduct={vi.fn()}
                onAddToCart={vi.fn()}
                onToggleWishlist={vi.fn()}
            />,
        );

        const image = screen.getByRole('img', { name: 'منتج عناية بخصوصية' });
        expect(image).toHaveAttribute('src', '/uploads/oil.jpg');
        expect(screen.getByText('عرض بخصوصية')).toBeInTheDocument();

        fireEvent.error(image);
        expect(screen.getByText('صورة المنتج غير متاحة حالياً')).toBeInTheDocument();
    });
});
