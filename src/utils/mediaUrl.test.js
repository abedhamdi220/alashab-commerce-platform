import { afterEach, describe, expect, it, vi } from 'vitest';

const setRuntimeConfig = (config) => {
    Object.defineProperty(window, '__APP_CONFIG__', {
        value: config,
        writable: true,
        configurable: true,
    });
};

const loadMediaHelpers = async () => {
    vi.resetModules();
    return import('./mediaUrl');
};

describe('resolveMediaUrl', () => {
    afterEach(() => {
        delete window.__APP_CONFIG__;
        delete window.CONFIG;
        vi.unstubAllEnvs();
    });

    it('يفضل رابط الوسائط الصريح عندما يكون CDN منفصلاً', async () => {
        setRuntimeConfig({ MEDIA_BASE_URL: 'https://cdn.example.com/storage/' });
        const { resolveMediaUrl } = await loadMediaHelpers();

        expect(resolveMediaUrl('/uploads/item.jpg')).toBe('https://cdn.example.com/storage/uploads/item.jpg');
    });

    it('يستخدم أصل API عندما لا يكون رابط الوسائط مستقلاً', async () => {
        setRuntimeConfig({ API_BASE_URL: 'https://api.example.com/api/stores/demo' });
        const { resolveMediaUrl } = await loadMediaHelpers();

        expect(resolveMediaUrl('uploads/item.jpg')).toBe('https://api.example.com/uploads/item.jpg');
    });

    it('لا يغير روابط الصور المطلقة أو data URLs', async () => {
        const { resolveMediaUrl } = await loadMediaHelpers();

        expect(resolveMediaUrl('https://images.example.com/item.jpg')).toBe('https://images.example.com/item.jpg');
        expect(resolveMediaUrl('data:image/png;base64,abc')).toBe('data:image/png;base64,abc');
    });
});
