import React, { createContext, useContext, useState, useCallback, useEffect } from 'react';
// src/context/ToastContext.jsx -> src/components/Toast.jsx
import { Toast } from '../components/Toast';

const ToastContext = createContext(null);

export const ToastProvider = ({ children }) => {
    const [toastConfig, setToastConfig] = useState({
        isOpen: false,
        message: '',
        type: 'success',
        duration: 3000,
    });

    // دالة إظهار التنبيه[cite: 11]
    const showToast = useCallback((message, type = 'success', options = {}) => {
        const { duration = 3000 } = typeof options === 'number'
            ? { duration: options }
            : options;

        setToastConfig({
            isOpen: true,
            message,
            type,
            duration,
        });
    }, []);

    // تمت إضافة دالة showError للتوافق مع استخدامات useApi.js
    const showError = useCallback((message, options = {}) => {
        showToast(message, 'error', options);
    }, [showToast]);

    // دالة إغلاق التنبيه[cite: 11]
    const hideToast = useCallback(() => {
        setToastConfig((prev) => ({ ...prev, isOpen: false }));
    }, []);

    useEffect(() => {
        const handleApiError = (event) => {
            showError(event.detail?.message || 'حدث خطأ في الاتصال بالخادم.');
        };

        window.addEventListener('api-error', handleApiError);
        return () => window.removeEventListener('api-error', handleApiError);
    }, [showError]);

    return (
        <ToastContext.Provider value={{ showToast, showError, hideToast }}>
            {children}
            <Toast
                isOpen={toastConfig.isOpen}
                message={toastConfig.message}
                type={toastConfig.type}
                duration={toastConfig.duration}
                onClose={hideToast}
            />
        </ToastContext.Provider>
    );
};

// Hook لاستخدام التنبيهات بسهولة داخل أي مكون[cite: 11]
export const useToast = () => {
    const context = useContext(ToastContext);
    if (!context) {
        throw new Error('useToast must be used within a ToastProvider');
    }
    return context;
};
