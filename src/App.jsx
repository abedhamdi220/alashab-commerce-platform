import React from 'react';
import { BrowserRouter as Router, Routes, Route, useLocation } from 'react-router-dom';
import { AnimatePresence, motion } from 'framer-motion';

import { Storefront } from './pages/Storefront';
import { ErrorBoundary } from './components/ErrorBoundary';

const CustomerRoutes = () => {
    const location = useLocation();

    return (
        <AnimatePresence mode="wait">
            <Routes location={location} key={location.pathname}>
                <Route
                    path="/*"
                    element={
                        <motion.div
                            initial={{ opacity: 0, scale: 0.96, y: 8 }}
                            animate={{ opacity: 1, scale: 1, y: 0 }}
                            exit={{ opacity: 0, scale: 0.96, y: -8 }}
                            transition={{
                                duration: 0.3, /* تم تقليل المدة لـ 300ms لزيادة إحساس النشاط */
                                ease: 'easeOut'
                            }}
                            /* استبدال parchment بالخلفية الأساسية النظيفة bg */
                            className="relative min-h-screen bg-[var(--color-bg)] font-ibm z-0"
                            dir="rtl"
                        >
                            <ErrorBoundary>
                                <Storefront />
                            </ErrorBoundary>
                        </motion.div>
                    }
                />
            </Routes>
        </AnimatePresence>
    );
};

export default function App() {
    return (
        <Router>
            <CustomerRoutes />
        </Router>
    );
}
