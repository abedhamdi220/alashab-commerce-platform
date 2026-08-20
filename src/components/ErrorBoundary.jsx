import React from 'react';
import { AlertTriangle, RefreshCw } from 'lucide-react';

// [جديد]: ما كان في أي Error Boundary بكامل التطبيق — يعني أي خطأ غير متوقع بأي كومبوننت
// (حتى بمودال بسيط) كان بيكسر الشجرة كلها ويرجع شاشة بيضاء فاضية بدون أي تفسير للزبونة.
// Error Boundaries لازم تكون class component (React لسا ما وفر hook مكافئ رسمي).
export class ErrorBoundary extends React.Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false };
    }

    static getDerivedStateFromError() {
        return { hasError: true };
    }

    componentDidCatch(error, info) {
        // TODO: وصّليها لخدمة تتبع أخطاء فعلية (Sentry أو ما شابه) بدل console فقط قبل الإطلاق
        console.error('حدث خطأ غير متوقع بالواجهة:', error, info);
    }

    handleReload = () => {
        window.location.reload();
    };

    render() {
        if (this.state.hasError) {
            return (
                <div className="min-h-screen flex items-center justify-center bg-[var(--color-bg)] px-4" dir="rtl">
                    <div className="max-w-md w-full text-center space-y-5 bg-[var(--color-surface)] border border-[var(--color-line)] p-8" style={{ borderRadius: 'var(--radius-lg)' }}>
                        <div className="w-14 h-14 mx-auto rounded-full bg-[var(--color-bg)] border border-[var(--color-line)] flex items-center justify-center">
                            <AlertTriangle className="w-6 h-6 text-[var(--color-energy)]" strokeWidth={1.5} />
                        </div>
                        <div className="space-y-2">
                            <h2 className="font-cairo font-black text-xl text-[var(--color-ink)]">حدث خطأ غير متوقع</h2>
                            <p className="font-ibm text-sm text-[var(--color-ink-soft)] leading-relaxed">
                                نعتذر عن الإزعاج. جربي تحديث الصفحة، ولو استمرت المشكلة تواصلي معنا مباشرة عبر واتساب من الفوتر.
                            </p>
                        </div>
                        <button
                            onClick={this.handleReload}
                            className="inline-flex items-center gap-2 bg-[var(--color-primary)] hover:brightness-95 text-white px-6 py-3 font-ibm font-medium transition-all"
                            style={{ borderRadius: 'var(--radius-lg)' }}
                        >
                            <RefreshCw className="w-4 h-4" strokeWidth={2} />
                            إعادة تحميل الصفحة
                        </button>
                    </div>
                </div>
            );
        }

        return this.props.children;
    }
}
