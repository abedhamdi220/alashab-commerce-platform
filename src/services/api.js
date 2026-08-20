/** اتصال التطوير: لا تُرسل طلبات API إلى Vite؛ وجّهها إلى Laravel المحلي ما لم يحدد الإعداد عنواناً صريحاً. */
import axios from "axios";

const stripTrailingSlash = (value) => String(value || "").replace(/\/+$/, "");

const runtimeConfig =
  typeof window === "undefined"
    ? {}
    : window.__APP_CONFIG__ || window.CONFIG || {};

const env = import.meta.env;
const isViteDevelopmentServer =
  typeof window !== "undefined" && window.location.port === "5173";
const localLaravelApiBaseUrl =
  typeof window !== "undefined"
    ? `${window.location.protocol}//${window.location.hostname}:8000/api`
    : null;
const declaredBaseUrl = runtimeConfig.API_BASE_URL || env.VITE_API_BASE_URL;
const isRelativeApiUrl = declaredBaseUrl && /^\//.test(declaredBaseUrl);
const configuredBaseUrl =
  isViteDevelopmentServer && (!declaredBaseUrl || isRelativeApiUrl)
    ? localLaravelApiBaseUrl
    : declaredBaseUrl;
const configuredStoreSlug =
  runtimeConfig.STORE_SLUG ||
  runtimeConfig.MERCHANT_SLUG ||
  env.VITE_STORE_SLUG ||
  (typeof window === "undefined"
    ? null
    : new URLSearchParams(window.location.search).get("store"));

const apiRoot = stripTrailingSlash(configuredBaseUrl || "/api");
const hasStoreInBaseUrl = /\/stores\/[^/]+(?:\/|$)/.test(apiRoot);
const hasStoreContext = hasStoreInBaseUrl || Boolean(configuredStoreSlug);
const apiBaseUrl = hasStoreInBaseUrl
  ? apiRoot
  : configuredStoreSlug
    ? `${apiRoot}/stores/${encodeURIComponent(configuredStoreSlug)}`
    : null;

const configurationError = [
  "تعذر تحديد المتجر العام.",
  "اضبط VITE_STORE_SLUG مع VITE_API_BASE_URL،",
  "أو اضبط VITE_API_BASE_URL كاملاً بالشكل /api/stores/{store-slug}.",
].join(" ");

if (!hasStoreContext) {
  console.error(configurationError);
}

const apiClient = axios.create({
  baseURL: apiBaseUrl || undefined,
  timeout: 15000,
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
});

apiClient.interceptors.request.use((config) => {
  if (!hasStoreContext) {
    return Promise.reject(new Error(configurationError));
  }

  if (typeof window === "undefined") {
    return config;
  }

  let visitorId = localStorage.getItem("alashab_visitor_id");
  if (!visitorId) {
    visitorId =
      typeof window.crypto?.randomUUID === "function"
        ? window.crypto.randomUUID()
        : `visitor_${Math.random().toString(36).slice(2, 15)}`;
    localStorage.setItem("alashab_visitor_id", visitorId);
  }

  config.headers["X-Visitor-ID"] = visitorId;
  return config;
});

apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    const errorText = String(error?.message || "").toLowerCase();
    const isConfigurationFailure = error?.message === configurationError;
    const isTimeout =
      error?.code === "ECONNABORTED" || errorText.includes("timeout");
    const isNetworkFailure = !error?.response;
    const isNotFound = error?.response?.status === 404;
    const isHandledProductUnavailable =
      error?.response?.data?.code === "PRODUCT_UNAVAILABLE";
    const isServerFailure = error?.response?.status >= 500;

    if (
      typeof window !== "undefined" &&
      !isHandledProductUnavailable &&
      (isConfigurationFailure ||
        isTimeout ||
        isNetworkFailure ||
        isNotFound ||
        isServerFailure)
    ) {
      const message = isConfigurationFailure
        ? configurationError
        : isTimeout
          ? "انتهت مهلة الاتصال بالخادم. يرجى المحاولة لاحقاً."
          : isNetworkFailure
            ? "تعذر الاتصال بالخادم. يرجى التحقق من الاتصال ثم المحاولة."
            : isNotFound
              ? "تعذر الوصول إلى خدمة المتجر. يرجى التحقق من إعداد اتصال API ثم المحاولة."
              : "نواجه مشكلة مؤقتة في الخادم. يرجى المحاولة لاحقاً.";

      window.dispatchEvent(
        new CustomEvent("api-error", { detail: { message } }),
      );
    }

    return Promise.reject(error);
  },
);

export { apiBaseUrl, configurationError };
export default apiClient;
