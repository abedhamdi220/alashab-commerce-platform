const stripTrailingSlash = (value) => String(value || '').replace(/\/+$/, '');

const runtimeConfig = typeof window === 'undefined'
    ? {}
    : (window.__APP_CONFIG__ || window.CONFIG || {});

const resolveMediaBaseUrl = () => {
    const configuredMediaBase = runtimeConfig.MEDIA_BASE_URL
        || runtimeConfig.ASSETS_BASE_URL
        || import.meta.env.VITE_MEDIA_BASE_URL;
    const configuredBackend = runtimeConfig.BACKEND_ORIGIN
        || import.meta.env.VITE_BACKEND_ORIGIN;
    const configuredApiBase = runtimeConfig.API_BASE_URL
        || import.meta.env.VITE_API_BASE_URL;

    if (/^https?:\/\//i.test(configuredMediaBase || '')) {
        return stripTrailingSlash(configuredMediaBase);
    }

    if (/^https?:\/\//i.test(configuredBackend || '')) {
        return stripTrailingSlash(configuredBackend);
    }

    if (/^https?:\/\//i.test(configuredApiBase || '')) {
        return new URL(configuredApiBase).origin;
    }

    // في التطوير المحلي يتوافق مع خادم Laravel الافتراضي؛ وفي الاستضافة ذات الأصل الواحد
    // يبقى مسار الواجهة الحالي خياراً متوافقاً مع الإصدارات السابقة.
    return import.meta.env.DEV ? 'http://127.0.0.1:8000' : window.location.origin;
};

export const resolveMediaUrl = (value) => {
    if (typeof value !== 'string' || value.trim() === '') return value;

    const source = value.trim();
    if (/^(https?:|data:|blob:)/i.test(source)) return source;

    const mediaBaseUrl = resolveMediaBaseUrl();
    const normalizedPath = source.replace(/^\/+/, '');
    return `${mediaBaseUrl}/${normalizedPath}`;
};

export const normalizeProductMedia = (product) => {
    if (!product || typeof product !== 'object') return product;

    return {
        ...product,
        image: resolveMediaUrl(product.image),
        image_url: resolveMediaUrl(product.image_url),
        images: Array.isArray(product.images)
            ? product.images.map((image) => ({
                ...image,
                url: resolveMediaUrl(image?.url),
            }))
            : product.images,
    };
};
