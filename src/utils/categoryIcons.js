import { Leaf, Sparkles, Feather, Droplet, Flame, Star } from 'lucide-react';

// مطابقة أيقونة حسب اسم التصنيف — منطق واحد مشترك بين Header.jsx وCategoryShowcase.jsx
// بدل ما يكون نفس الـ mapping مكرر بملفين (لو ضفنا فئة جديدة لازم نعدلها بمكان واحد بس).
// Star كأيقونة احتياطية لأي تصنيف جديد يضيفه التاجر مستقبلاً من لوحة التحكم
export const getCategoryIcon = (name = '') => {
    if (name.includes('بشرة')) return Leaf;
    if (name.includes('تجميل')) return Sparkles;
    if (name.includes('تنحيف')) return Feather;
    if (name.includes('تسمين')) return Droplet;
    if (name.includes('جنس') || name.includes('حميم')) return Flame;
    return Star;
};
