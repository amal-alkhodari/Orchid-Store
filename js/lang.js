// قاموس النصوص الشامل لمتجر أوركيد
const translations = {
    ar: {
        announcement: "✨ شحن مجاني للطلبات الأعلى من 200 شيكل | كود الخصم: ORCHID2026",
        navHome: "الرئيسية",
        navCasual: "ملابس كاجوال",
        navAbaya: "عبايات فاخرة",
        navDresses: "فساتين سهرة",
        navOffers: "العروض حصرية",
        heroSub: "تشكيلة خريف وشتاء 2026",
        heroTitle: "أناقة متجددة تليق بجمالكِ",
        heroDesc: "اكتشفي التصاميم الحصرية من ملابس الكاجوال، العبايات، وفساتين السهرة المصممة خصيصاً لتمنحكِ حضوراً استثنائياً وراقياً.",
        btnShopNow: "تسوقي الآن",
        btnCatalog: "مشاهدة الكتالوج",
        featTitle: "أقمشة فاخرة",
        featDesc: "جودة عالية 100%",
        catHeader: "تصفحي حسب الفئة",
        catDesc: "اكتشفي مجموعاتنا المتنوعة المصممة لكل المناسبات",
        catCasual: "ملابس كاجوال",
        catAbaya: "عبايات أنيقة",
        catDresses: "فساتين سهرة",
        itemCountCasual: "120+ قطعة",
        itemCountAbaya: "85+ قطعة",
        itemCountDresses: "50+ قطعة",
        exploreBtn: "استكشفي الآن",
        prodHeader: "وصلنا حديثاً والأكثر مبيعاً",
        prodDesc: "قطع تم اختيارها خصيصاً لتلائم ذوقكِ الرفيع",
        newBadge: "جديد",
        quickAdd: "إضافة سريعة",
        prod1Title: "بلوزة خريفية ناعمة",
        prod2Title: "عباية سوداء بلمسة عصرية",
        prod3Title: "فستان سهرة مخملي أنيق",
        footerAbout: "متجر أوركيد يوفر لكِ أحدث صيحات الموضة النسائية بأعلى جودة وتصاميم حصرية تناسب جمالكِ وأناقتكِ اليومية.",
        footerLinksTitle: "روابط سريعة",
        aboutUs: "من نحن",
        returnPolicy: "سياسة الاستبدال والاسترجاع",
        shipping: "الشحن والتوصيل",
        faq: "الأسئلة الشائعة",
        contactUsTitle: "تواصل معنا",
        address: "غزة، فلسطين",
        rights: "© 2026 جميع الحقوق محفوظة لمتجر أوركيد | برمجة وتطوير أمل",
        langBtnText: "En "
    },
    en: {
        announcement: "✨ Free shipping on orders over 200 NIS | Promo Code: ORCHID2026",
        navHome: "Home",
        navCasual: "Casual Wear",
        navAbaya: "Luxury Abayas",
        navDresses: "Evening Dresses",
        navOffers: "Exclusive Offers",
        heroSub: "Fall & Winter Collection 2026",
        heroTitle: "Renewed Elegance Matching Your Beauty",
        heroDesc: "Discover exclusive designs of casual wear, abayas, and evening dresses tailored to give you an exceptional and sophisticated presence.",
        btnShopNow: "Shop Now",
        btnCatalog: "View Catalog",
        featTitle: "Luxury Fabrics",
        featDesc: "100% High Quality",
        catHeader: "Browse by Category",
        catDesc: "Discover our diverse collections designed for every occasion",
        catCasual: "Casual Wear",
        catAbaya: "Elegant Abayas",
        catDresses: "Evening Dresses",
        itemCountCasual: "120+ Items",
        itemCountAbaya: "85+ Items",
        itemCountDresses: "50+ Items",
        exploreBtn: "Explore Now",
        prodHeader: "New Arrivals & Best Sellers",
        prodDesc: "Pieces handpicked to suit your refined taste",
        newBadge: "New",
        quickAdd: "Quick Add",
        prod1Title: "Soft Autumn Blouse",
        prod2Title: "Modern Black Abaya",
        prod3Title: "Elegant Velvet Evening Dress",
        footerAbout: "Orchid Store provides you with the latest women's fashion trends with the highest quality and exclusive designs.",
        footerLinksTitle: "Quick Links",
        aboutUs: "About Us",
        returnPolicy: "Return & Exchange Policy",
        shipping: "Shipping & Delivery",
        faq: "FAQ",
        contactUsTitle: "Contact Us",
        address: "Gaza, Palestine",
        rights: "© 2026 All rights reserved to Orchid Store | Programmed & Developed by Amal",
        langBtnText: "AR"
    }
};

function changeLanguage(lang) {
    const html = document.documentElement;
    html.setAttribute('dir', lang === 'en' ? 'ltr' : 'rtl');
    html.setAttribute('lang', lang);

    // البحث عن كل عنصر يحمل data-i18n وتغيير نصه
    document.querySelectorAll('[data-i18n]').forEach(element => {
        const key = element.getAttribute('data-i18n');
        if (translations[lang] && translations[lang][key]) {
            element.textContent = translations[lang][key];
        }
    });

    // تحديث نص زر التبديل
    const btn = document.getElementById('langToggle');
    if (btn) {
        btn.textContent = translations[lang].langBtnText;
    }

    localStorage.setItem('orchid_lang', lang);
}

document.addEventListener('DOMContentLoaded', () => {
    const savedLang = localStorage.getItem('orchid_lang') || 'ar';
    changeLanguage(savedLang);

    const btn = document.getElementById('langToggle');
    if (btn) {
        btn.addEventListener('click', () => {
            const current = document.documentElement.getAttribute('lang') || 'ar';
            const next = current === 'ar' ? 'en' : 'ar';
            changeLanguage(next);
        });
    }
});