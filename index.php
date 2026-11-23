<?php
session_start();
require_once __DIR__ . '/db.php';

$current_lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'ar';
if (!in_array($current_lang, ['ar', 'en'], true)) $current_lang = 'ar';
$_SESSION['lang'] = $current_lang;
$is_ar = $current_lang === 'ar';
$dir = $is_ar ? 'rtl' : 'ltr';
$next_lang = $is_ar ? 'en' : 'ar';


$lang = [];
$langFile = __DIR__ . '/languages/' . $current_lang . '.php';
if (is_file($langFile)) include $langFile;

$t = [
    'brand' => 'Orchid Boutique',
    'home' => $is_ar ? 'الرئيسية' : 'Home',
    'collections' => $is_ar ? 'التصنيفات' : 'Collections',
    'new' => $is_ar ? 'أحدث المنتجات' : 'New Arrivals',
    'offers' => $is_ar ? 'العروض' : 'Offers',
    'blog' => $is_ar ? 'عن أوركيد' : 'About',
    'contact' => $is_ar ? 'التواصل' : 'Contact',
    'login' => $is_ar ? 'تسجيل الدخول' : 'Login / Sign Up',
    'account' => $is_ar ? 'حسابي' : 'My Account',
    'search' => $is_ar ? 'بحث' : 'Search',
    'search_placeholder' => $is_ar ? 'ابحثي عن منتج...' : 'Search products...',
    'hero_kicker' => $is_ar ? 'أناقة خالدة، تناسبكِ' : 'TIMELESS ELEGANCE',
    'hero_1' => $is_ar ? 'إطلالتكِ' : 'Your Style',
    'hero_2' => $is_ar ? 'حكايتكِ الخاصة' : 'Our Passion',
    'hero_desc' => $is_ar
        ? 'اكتشفي تشكيلة مختارة من الفساتين والعبايات والأزياء اليومية، لتعبّري عن أسلوبكِ بثقة.'
        : 'Discover a curated collection of elegant dresses, abayas and everyday outfits. Designed for your unique style.',
    'shop_now' => $is_ar ? 'تسوّقي الآن' : 'Shop Now',
    'premium' => $is_ar ? 'جودة مميزة' : 'Premium Quality',
    'premium_desc' => $is_ar ? 'قطع مختارة بعناية' : 'Thoughtfully selected pieces',
    'category_kicker' => $is_ar ? 'تسوّقي حسب التصنيف' : 'SHOP BY CATEGORY',
    'categories_heading' => $is_ar ? 'اكتشفي مجموعاتنا' : 'Explore Our Collections',
    'category_sub' => $is_ar ? 'اختاري التصنيف الأقرب إلى ذوقكِ.' : 'Find your perfect style in a few clicks.',
    'casual' => $is_ar ? 'الأزياء اليومية' : 'Casual Wear',
    'abaya' => $is_ar ? 'العبايات' : 'Abayas',
    'dresses' => $is_ar ? 'فساتين السهرة' : 'Dresses',
    'casual_desc' => $is_ar ? 'أناقة يومية بلا تكلّف' : 'Effortless style for every day',
    'abaya_desc' => $is_ar ? 'احتشام وأناقة في كل خطوة' : 'Modest fashion, modern style',
    'dresses_desc' => $is_ar ? 'فساتين أنيقة لكل مناسبة' : 'Elegant dresses for every occasion',
    'browse' => $is_ar ? 'تسوّقي الآن' : 'Shop Now',
    'items' => $is_ar ? 'قطعة' : 'items',
    'featured_kicker' => $is_ar ? 'مختارة لكِ' : 'CURATED FOR YOU',
    'featured_heading' => $is_ar ? 'الأكثر تميزًا' : 'Featured Products',
    'featured_sub' => $is_ar ? 'قطع مميزة لإطلالتكِ القادمة.' : 'Our most-loved pieces, handpicked for you.',
    'view_all' => $is_ar ? 'عرض كل المنتجات' : 'View All Products',
    'details' => $is_ar ? 'تفاصيل المنتج' : 'View Details',
    'add_cart' => $is_ar ? 'أضيفي إلى السلة' : 'Add to Cart',
    'new_badge' => $is_ar ? 'جديد' : 'NEW',
    'sale_badge' => $is_ar ? 'تخفيض' : 'SALE',
    'discount' => $is_ar ? 'خصم' : 'OFF',
    'offers_heading' => $is_ar ? 'عروض أوركيد' : 'Special Offers',
    'offers_sub' => $is_ar ? 'اكتشفي المنتجات المخفّضة بالفعل.' : 'Discover products with active discounts.',
    'no_offers' => $is_ar ? 'لا توجد منتجات مخفّضة حاليًا.' : 'There are no discounted products right now.',
    'why_heading' => $is_ar ? 'لماذا تختارين أوركيد؟' : 'Why Choose Orchid Boutique?',
    'why_sub' => $is_ar ? 'أكثر من مجرد أزياء، إنها تجربة تسوّق تهتم بتفاصيلكِ.' : 'More than just fashion, it is an experience made for you.',
    'quality' => $is_ar ? 'جودة مميزة' : 'Premium Quality',
    'quality_desc' => $is_ar ? 'قطع أنيقة بعناية' : 'Thoughtfully selected pieces',
    'shipping' => $is_ar ? 'توصيل موثوق' : 'Fast & Reliable Shipping',
    'shipping_desc' => $is_ar ? 'تفاصيل التوصيل عند إتمام الطلب' : 'Shipping details at checkout',
    'payment' => $is_ar ? 'دفع آمن' : 'Secure Payments',
    'payment_desc' => $is_ar ? 'تسوّقي بثقة' : 'Shop with confidence',
    'returns' => $is_ar ? 'خدمة العملاء' : 'Customer Support',
    'returns_desc' => $is_ar ? 'نحن هنا لمساعدتكِ' : 'We are here to help',
    'reviews_kicker' => $is_ar ? 'آراء عميلاتنا' : 'OUR CUSTOMERS LOVE US',
    'reviews_heading' => $is_ar ? 'تجارب جميلة وأسلوبكِ الخاص' : 'Real Stories, Real Style',
    'reviews_sub' => $is_ar ? 'آراء العملاء ستظهر هنا عند توفرها.' : 'Customer reviews will appear here when available.',
    'gallery_heading' => $is_ar ? 'تابعينا على إنستغرام' : 'Follow Us on Instagram',
    'gallery_sub' => $is_ar ? 'اكتشفي إلهامًا جديدًا من أحدث القطع.' : 'Get inspired by our latest looks and styles.',
    'search_results' => $is_ar ? 'نتائج البحث' : 'Search Results',
    'no_results' => $is_ar ? 'لم نعثر على منتجات تطابق بحثكِ.' : 'No products matched your search.',
    'empty_products' => $is_ar ? 'لا توجد منتجات متاحة حاليًا.' : 'No products are available right now.',
    'quick_links' => $is_ar ? 'روابط سريعة' : 'Quick Links',
    'customer_care' => $is_ar ? 'خدمة العملاء' : 'Customer Care',
    'newsletter' => $is_ar ? 'النشرة البريدية' : 'Newsletter',
    'newsletter_desc' => $is_ar ? 'تابعي أحدث المنتجات والعروض.' : 'Subscribe for updates and special offers.',
    'email_placeholder' => $is_ar ? 'بريدك الإلكتروني' : 'Your email address',
    'subscribe' => $is_ar ? 'اشتراك' : 'Subscribe',
    'copyright' => $is_ar ? 'جميع الحقوق محفوظة.' : 'All rights reserved.',
    'admin' => $is_ar ? 'لوحة التحكم' : 'Control Panel',
];

$stmt = $pdo->query("
    SELECT
        p.id,
        p.category_id,
        p.subcategory,
        p.name_ar,
        p.name_en,
        p.price,
        p.discount_percent,
        p.image,
        c.slug AS category_slug
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.is_active = 1
    ORDER BY p.id DESC
");

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$counts = ['casual' => 0, 'abaya' => 0, 'dresses' => 0];

foreach ($products as $p) {
    $slug = $p['category_slug'] ?? '';
    if (isset($counts[$slug])) {
        $counts[$slug]++;
    }
}

$search = trim((string)($_GET['q'] ?? ''));
$shownProducts = $products;

if ($search !== '') {
    $shownProducts = array_values(array_filter($products, function ($p) use ($search) {
        return mb_stripos((string)($p['name_ar'] ?? ''), $search) !== false
            || mb_stripos((string)($p['name_en'] ?? ''), $search) !== false;
    }));
}

// 1. أول اشي بنجيب المنتجات اللي عليها خصم لقسم (عروض أوركيد)
$discounted = array_values(array_filter($shownProducts, function ($p) {
    $d = (float)($p['discount_percent'] ?? 0);
    return $d > 0 && $d <= 100;
}));
$discounted = array_slice($discounted, 0, 4);

// 2. بنحفظ أرقام المنتجات المخفضة عشان ما تتكرر فوق
$discountedIds = array_column($discounted, 'id');

// 3. بنجيب المنتجات العادية (اللي مش مخفضة) لقسم (الأكثر تميزاً)
$nonDiscounted = array_values(array_filter($shownProducts, function ($p) use ($discountedIds) {
    return !in_array($p['id'], $discountedIds, true);
}));
$featured = array_slice($nonDiscounted, 0, 4);


function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function imagePath($value): string {
    $value = trim((string)$value);

    if ($value === '') return '';

    if (preg_match('~^(https?:)?//~i', $value) || str_starts_with($value, 'data:')) {
        return $value;
    }

    $value = ltrim($value, '/');

    if (str_contains($value, '/')) {
        return $value;
    }

    return 'assets/images/' . $value;
}

function productTitle(array $p, bool $is_ar): string {
    $preferred = $is_ar
        ? trim((string)($p['name_ar'] ?? ''))
        : trim((string)($p['name_en'] ?? ''));

    $fallback = $is_ar
        ? trim((string)($p['name_en'] ?? ''))
        : trim((string)($p['name_ar'] ?? ''));

    return $preferred !== ''
        ? $preferred
        : ($fallback !== '' ? $fallback : 'Product');
}

function salePrice(array $p): float {
    $price = (float)($p['price'] ?? 0);
    $discount = (float)($p['discount_percent'] ?? 0);

    return ($discount > 0 && $discount <= 100)
        ? $price * (1 - $discount / 100)
        : $price;
}

function detailsUrl($id, $lang): string {
    return 'product-details.php?id='
        . rawurlencode((string)$id)
        . '&lang='
        . rawurlencode((string)$lang);
}

function categoryImage(array $products, string $slug): string {
    foreach ($products as $p) {
        if (($p['category_slug'] ?? '') === $slug && !empty($p['image'])) {
            return imagePath($p['image']);
        }
    }

    return '';
}

$heroImage = '';

foreach ($products as $p) {
    if (!empty($p['image'])) {
        $heroImage = imagePath($p['image']);
        break;
    }
}

$categoryData = [
    [
        'slug' => 'dresses',
        'title' => $t['dresses'],
        'description' => $t['dresses_desc'],
        'icon' => 'fa-person-dress'
    ],
    [
        'slug' => 'abaya',
        'title' => $t['abaya'],
        'description' => $t['abaya_desc'],
        'icon' => 'fa-person-dress'
    ],
    [
        'slug' => 'casual',
        'title' => $t['casual'],
        'description' => $t['casual_desc'],
        'icon' => 'fa-shirt'
    ],
];
// ضعي مسارات صوركِ الخاصة لكل تصنيف بالترتيب
$customCategoryImages = [
    'images/cat-dresses.jpg', // صورة فساتين السهرة
    'images/cat-abayas.jpg',  // صورة العبايات
    'images/cat-casual.jpg',  // صورة الأزياء اليومية
];
?>
<!DOCTYPE html>
<html lang="<?= h($current_lang) ?>" dir="<?= h($dir) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?= h($t['brand']) ?> | <?= $is_ar ? 'متجر الأزياء' : 'Fashion Boutique' ?></title>

<meta name="description" content="<?= h($t['hero_desc']) ?>">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Montserrat:wght@400;500;600;700&family=Tajawal:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<link rel="stylesheet" href="assets/css/orchid-home.css?v=6">


</head>

<body dir="<?= h($dir) ?>">

<div class="topbar">
    <div class="container topbar-inner">

        <span>
            <i class="fa-solid fa-truck-fast"></i>
            <?= $is_ar ? 'أزياء أنيقة، بتفاصيل تعبّر عنكِ' : 'Elegant fashion, made for your style' ?>
        </span>

        <div class="topbar-right">

            <a href="index.php?lang=<?= h($next_lang) ?>">
                <?= $is_ar ? 'EN | العربية' : 'EN | AR' ?>
            </a>

            <a href="#instagram" aria-label="Instagram">
                <i class="fa-brands fa-instagram"></i>
            </a>

            <a href="#instagram" aria-label="Facebook">
                <i class="fa-brands fa-facebook-f"></i>
            </a>

        </div>
    </div>
</div>

<header class="site-header">
    <div class="container header-inner">

        <a class="brand" href="index.php?lang=<?= h($current_lang) ?>">
            <span class="brand-flower">
                <i class="fa-brands fa-pagelines"></i>
            </span>

            <span>
                Orchid
                <small>B O U T I Q U E</small>
            </span>
        </a>
<nav class="main-nav" aria-label="Main navigation">

    <a href="index.php?lang=<?= h($current_lang) ?>">
        <?= h($t['home']) ?>
    </a>

    <div class="nav-dropdown">
        <a href="javascript:void(0)">
            <?= $is_ar ? 'التصنيفات' : 'Categories' ?>
            <i class="fa-solid fa-chevron-down nav-chevron"></i>
        </a>

        <div class="nav-dropdown-menu">
            <a href="category.php?cat=abaya&lang=<?= h($current_lang) ?>">
                <?= $is_ar ? 'العبايات' : 'Abayas' ?>
            </a>

            <a href="category.php?cat=dresses&lang=<?= h($current_lang) ?>">
                <?= $is_ar ? 'الفساتين' : 'Dresses' ?>
            </a>

            <div class="nav-subdropdown">
                <a href="category.php?cat=casual&lang=<?= h($current_lang) ?>">
                    <?= $is_ar ? 'الأزياء اليومية' : 'Casual Wear' ?>
                </a>

                <div class="nav-subdropdown-menu">
                    <a href="category.php?cat=casual&lang=<?= h($current_lang) ?>">
                        <?= $is_ar ? 'كل الأزياء اليومية' : 'All Casual Wear' ?>
                    </a>
                    <a href="category.php?cat=casual&sub=pants&lang=<?= h($current_lang) ?>">
                        <?= $is_ar ? 'البناطيل' : 'Pants' ?>
                    </a>
                    <a href="category.php?cat=casual&sub=tops&lang=<?= h($current_lang) ?>">
                        <?= $is_ar ? 'البلايز والقمصان' : 'Tops & Shirts' ?>
                    </a>
                    <a href="category.php?cat=casual&sub=summer&lang=<?= h($current_lang) ?>">
                        <?= $is_ar ? 'الملابس الصيفية' : 'Summer Wear' ?>
                    </a>
                    <a href="category.php?cat=casual&sub=winter&lang=<?= h($current_lang) ?>">
                        <?= $is_ar ? 'الملابس الشتوية' : 'Winter Wear' ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <a href="new-arrivals.php?lang=<?= h($current_lang) ?>">
        <?= h($t['new']) ?>
    </a>

    <a href="offers.php?lang=<?= h($current_lang) ?>">
        <?= h($t['offers']) ?>
    </a>

    <a href="about.php?lang=<?= h($current_lang) ?>">
        <?= h($t['blog']) ?>
    </a>

    <a href="contact.php?lang=<?= h($current_lang) ?>">
        <?= h($t['contact']) ?>
    </a>

</nav>
        <div class="header-actions">

            <a href="#search" aria-label="Search">
                <i class="fa-solid fa-magnifying-glass"></i>
            </a>

            <a href="wishlist.php?lang=<?= h($current_lang) ?>" aria-label="Wishlist">
                <i class="fa-regular fa-heart"></i>
            </a>

            <a class="cart-icon-link" href="cart.php?lang=<?= h($current_lang) ?>" aria-label="Cart">
                <i class="fa-solid fa-bag-shopping"></i>
                <span class="badge cart-badge">0</span>
            </a>
<?php if (isset($_SESSION['user_id'])): ?>

    
    <div class="account-dropdown">

        <button
            type="button"
            class="account-link account-profile-trigger"
            id="accountBtn"
            aria-haspopup="true"
            aria-controls="accountMenu"
            aria-expanded="false"
        >

         
<span class="account-trigger-avatar">

    <?php if (!empty($_SESSION['user_image'])): ?>

        <img
            src="<?= h($_SESSION['user_image']) ?>"
            class="account-trigger-image"
            alt="<?= $is_ar ? 'صورتي الشخصية' : 'My Profile Picture' ?>"
            onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';"
        >

        <i
            class="fa-regular fa-user account-trigger-fallback"
            style="display:none;"
        ></i>

    <?php else: ?>

        <i class="fa-regular fa-user account-trigger-fallback"></i>

    <?php endif; ?>

</span>


<i class="fa-solid fa-chevron-down account-chevron"></i>

        </button>

        
        <div class="account-dropdown-menu" id="accountMenu" hidden>

            <div class="account-dropdown-user">

                <strong>
                    <?= h($_SESSION['user_name'] ?? '') ?>
                </strong>

                <small>
                    <?= h($_SESSION['user_email'] ?? '') ?>
                </small>

            </div>

            <a href="profile.php?lang=<?= h($current_lang) ?>">
                <i class="fa-regular fa-user"></i>
                <?= $is_ar ? 'الصفحة الشخصية' : 'My Profile' ?>
            </a>

            <a href="orders.php?lang=<?= h($current_lang) ?>">
                <i class="fa-solid fa-bag-shopping"></i>
                <?= $is_ar ? 'طلباتي' : 'My Orders' ?>
            </a>

            <a href="addresses.php?lang=<?= h($current_lang) ?>">
                <i class="fa-solid fa-location-dot"></i>
                <?= $is_ar ? 'معلومات الاتصال والعنوان' : 'Contact & Address' ?>
            </a>

            <a href="logout.php" class="account-dropdown-logout">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <?= $is_ar ? 'تسجيل الخروج' : 'Log Out' ?>
            </a>

        </div>

    </div>

<?php else: ?>

    <a class="account-link" href="login.php?lang=<?= h($current_lang) ?>">
        <?= h($t['login']) ?>
    </a>

<?php endif; ?>
        </div>
    </div>
</header>

<div class="container search-panel" id="search">
    <form class="search-form" method="get" action="index.php">

        <input type="hidden" name="lang" value="<?= h($current_lang) ?>">

        <input
            type="search"
            name="q"
            value="<?= h($search) ?>"
            placeholder="<?= h($t['search_placeholder']) ?>"
            aria-label="<?= h($t['search_placeholder']) ?>"
        >

        <button type="submit">
            <i class="fa-solid fa-magnifying-glass"></i>
            <?= h($t['search']) ?>
        </button>

    </form>
</div>

<main>
<section class="orchid-hero-section">
    <!-- الجانب الأيمن: الشعار والعبارة الترحيبية في المساحة الفارغة -->
    <div class="hero-brand-side">
        <span class="hero-est">ORCHID BOUTIQUE • 2026</span>
        <h1 class="hero-slogan">أناقةٌ تُحاكي أنوثتكِ</h1>
        <p class="hero-sub">تشكيلة حصرية من الأزياء الراقية المصممة لتمنحكِ حضوراً استثنائياً في كل مناسبة.</p>
        <a href="#products" class="hero-cta-btn">اكتشفي التشكيلة</a>
    </div>

    <!-- المنتصف: القوس المعماري بصور متحركة -->
    <div class="hero-arch-frame">
        <div class="arch-slides" id="orchidArchSlides">
            <img src="images/hero1.jpg" class="arch-slide active" alt="Orchid Look 1">
            <img src="images/hero2.jpg" class="arch-slide" alt="Orchid Look 2">
            <img src="images/hero3.jpg" class="arch-slide" alt="Orchid Look 3">
            <img src="images/hero4.jpg" class="arch-slide" alt="Orchid Look 4">
        </div>

        <!-- نقاط التنقل السفلية داخل القوس -->
        <div class="arch-dots" id="orchidArchDots">
            <span class="dot active" onclick="goToArchSlide(0)"></span>
            <span class="dot" onclick="goToArchSlide(1)"></span>
            <span class="dot" onclick="goToArchSlide(2)"></span>
            <span class="dot" onclick="goToArchSlide(3)"></span>
        </div>
    </div>

    <!-- الجانب الأيسر: ختم جمالي خفيف لموازنة التصميم -->
    <div class="hero-side-emblem">
        <div class="emblem-ring">
            <span>ORCHID</span>
            <small>LUXURY</small>
        </div>
    </div>
</section>

<?php if ($search === ''): ?>

<section class="section category-section" id="categories">
    <div class="container">

        <div class="center-heading">
            <span class="section-kicker"><?= h($t['category_kicker']) ?></span>
            <h2><?= h($t['categories_heading']) ?></h2>
            <p><?= h($t['category_sub']) ?></p>
        </div>

        <div class="category-grid">

            <?php foreach ($categoryData as $index => $cat): ?>
                <?php
                    $slug   = $cat['slug'];
                    // يستخدم صورتكِ المخصصة، وإذا لم توجد يرجع لصورة التصنيف التلقائية
                    $catImg = $customCategoryImages[$index] ?? categoryImage($products, $slug);
                ?>

                <a
                    class="category-card"
                    href="category.php?cat=<?= h($slug) ?>&lang=<?= h($current_lang) ?>"
                >

                    <div class="category-photo">

                        <?php if ($catImg): ?>

                            <img
                                src="<?= h($catImg) ?>"
                                alt="<?= h($cat['title']) ?>"
                                loading="lazy"
                                onerror="this.style.display='none';this.nextElementSibling.style.display='grid'"
                            >

                            <span class="category-fallback" style="display:none">
                                <i class="fa-solid <?= h($cat['icon']) ?>"></i>
                            </span>

                        <?php else: ?>

                            <span class="category-fallback">
                                <i class="fa-solid <?= h($cat['icon']) ?>"></i>
                            </span>

                        <?php endif; ?>

                        <div class="category-shade"></div>

                        <div class="category-content">
                            <h3><?= h($cat['title']) ?></h3>
                            <p><?= h($cat['description']) ?></p>

                            <span class="category-arrow">
                                <i class="fa-solid <?= $is_ar ? 'fa-arrow-left' : 'fa-arrow-right' ?>"></i>
                            </span>
                        </div>

                    </div>

                    <span class="category-count">
                        <?= (int)($counts[$slug] ?? 0) ?>
                        <?= h($t['items']) ?>
                    </span>

                </a>

            <?php endforeach; ?>

        </div>
    </div>
</section>
<?php endif; ?>

<section class="section product-section" id="products">
    <div class="container">

        <div class="section-head">

            <div>
                <span class="section-kicker"><?= h($t['featured_kicker']) ?></span>

                <h2>
                    <?= $search !== ''
                        ? h($t['search_results'])
                        : h($t['featured_heading']) ?>
                </h2>

                <p>
                    <?= $search !== ''
                        ? h($search)
                        : h($t['featured_sub']) ?>
                </p>
            </div>

            <?php if ($search === ''): ?>

                <a
                    class="view-all"
                    href="category.php?cat=casual&lang=<?= h($current_lang) ?>"
                >
                    <?= h($t['view_all']) ?>
                    <i class="fa-solid <?= $is_ar ? 'fa-arrow-left' : 'fa-arrow-right' ?>"></i>
                </a>

            <?php endif; ?>

        </div>

        <?php if ($featured): ?>

            <div class="product-grid">

                <?php foreach ($featured as $index => $p): ?>

                    <?php
                    $name = productTitle($p, $is_ar);
                    $img = imagePath($p['image'] ?? '');
                    $price = (float)($p['price'] ?? 0);
                    $discount = (float)($p['discount_percent'] ?? 0);
                    $hasDiscount = $discount > 0 && $discount <= 100;
                    $sale = salePrice($p);
                    ?>

                    <article class="product-card">

                        <div class="product-photo">

                            <?php if ($index === 0): ?>

                                <span class="product-badge badge-new">
                                    <?= h($t['new_badge']) ?>
                                </span>

                            <?php elseif ($hasDiscount): ?>

                                <span class="product-badge">
                                    <?= (int)$discount ?>% <?= h($t['discount']) ?>
                                </span>

                            <?php endif; ?>

                            <a
                                class="product-image-link"
                                href="<?= h(detailsUrl($p['id'], $current_lang)) ?>"
                            >

                                <?php if ($img): ?>

                                    <img
                                        src="<?= h($img) ?>"
                                        alt="<?= h($name) ?>"
                                        loading="lazy"
                                        onerror="this.style.display='none';this.nextElementSibling.style.display='grid'"
                                    >

                                    <span class="product-fallback" style="display:none">
                                        <i class="fa-solid fa-shirt"></i>
                                    </span>

                                <?php else: ?>

                                    <span class="product-fallback">
                                        <i class="fa-solid fa-shirt"></i>
                                    </span>

                                <?php endif; ?>

                            </a>

                            <a
                                class="product-heart"
                                href="wishlist.php?lang=<?= h($current_lang) ?>"
                                aria-label="Wishlist"
                            >
                                <i class="fa-regular fa-heart"></i>
                            </a>

                        </div>

                        <div class="product-info">

                            <a
                                class="product-name"
                                href="<?= h(detailsUrl($p['id'], $current_lang)) ?>"
                            >
                                <?= h($name) ?>
                            </a>

                            <div class="product-category">
                                <?= h(
                                    $is_ar
                                        ? ($p['subcategory'] ?? $p['category_slug'] ?? '')
                                        : ($p['category_slug'] ?? '')
                                ) ?>
                            </div>

                            <div class="price-row">

                                <span class="current-price">
                                    <?= number_format($sale, 2) ?> ₪
                                </span>

                                <?php if ($hasDiscount): ?>

                                    <span class="old-price">
                                        <?= number_format($price, 2) ?> ₪
                                    </span>

                                <?php endif; ?>

                            </div>

                            <?php
                            $ratingValues = ['4.8', '4.8', '4.9', '4.7'];
                            $ratingCounts = [24, 18, 32, 15];
                            ?>

                            <div
                                class="product-rating"
                                aria-label="<?= $is_ar ? 'تقييم توضيحي' : 'Illustrative rating' ?>"
                            >
                                <span class="stars" aria-hidden="true">★★★★★</span>
                                <span class="rating-value"><?= $ratingValues[$index] ?? '4.8' ?></span>
                                <span class="rating-note">(<?= $ratingCounts[$index] ?? 12 ?>)</span>
                            </div>

                            <button
                                class="add-cart-btn quick-add-btn"
                                type="button"
                                data-id="<?= h($p['id']) ?>"
                                data-product-id="<?= h($p['id']) ?>"
                                data-name="<?= h($name) ?>"
                                data-product-name="<?= h($name) ?>"
                                data-price="<?= h(number_format($sale, 2, '.', '')) ?>"
                                data-product-price="<?= h(number_format($sale, 2, '.', '')) ?>"
                                data-image="<?= h($img) ?>"
                                data-product-image="<?= h($img) ?>"
                            >
                                <i class="fa-solid fa-cart-shopping"></i>
                                <?= h($t['add_cart']) ?>
                            </button>

                        </div>
                    </article>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="empty-state">
                <i class="fa-regular fa-face-frown"></i>
                <p>
                    <?= $search !== ''
                        ? h($t['no_results'])
                        : h($t['empty_products']) ?>
                </p>
            </div>

        <?php endif; ?>

    </div>
</section>

<?php if ($search === ''): ?>

<section class="section offers-section" id="offers">
    <div class="container">

        <div class="section-head">
            <div>
                <span class="section-kicker">
                    <?= $is_ar ? 'اختيارات خاصة' : 'SPECIAL PICKS' ?>
                </span>

                <h2><?= h($t['offers_heading']) ?></h2>
                <p><?= h($t['offers_sub']) ?></p>
            </div>
        </div>

        <?php if ($discounted): ?>

            <div class="product-grid">

                <?php foreach ($discounted as $p): ?>

                    <?php
                    $name = productTitle($p, $is_ar);
                    $img = imagePath($p['image'] ?? '');
                    $price = (float)($p['price'] ?? 0);
                    $discount = (float)($p['discount_percent'] ?? 0);
                    ?>

                    <article class="product-card">

                        <div class="product-photo">

                            <span class="product-badge">
                                <?= (int)$discount ?>% <?= h($t['discount']) ?>
                            </span>

                            <a
                                class="product-image-link"
                                href="<?= h(detailsUrl($p['id'], $current_lang)) ?>"
                            >

                                <?php if ($img): ?>

                                    <img
                                        src="<?= h($img) ?>"
                                        alt="<?= h($name) ?>"
                                        loading="lazy"
                                    >

                                <?php else: ?>

                                    <span class="product-fallback">
                                        <i class="fa-solid fa-shirt"></i>
                                    </span>

                                <?php endif; ?>

                            </a>

                            <a
                                class="product-heart"
                                href="wishlist.php?lang=<?= h($current_lang) ?>"
                                aria-label="Wishlist"
                            >
                                <i class="fa-regular fa-heart"></i>
                            </a>

                        </div>

                        <div class="product-info">

                            <a
                                class="product-name"
                                href="<?= h(detailsUrl($p['id'], $current_lang)) ?>"
                            >
                                <?= h($name) ?>
                            </a>

                            <div class="price-row">

                                <span class="current-price">
                                    <?= number_format(salePrice($p), 2) ?> ₪
                                </span>

                                <span class="old-price">
                                    <?= number_format($price, 2) ?> ₪
                                </span>

                            </div>

                            <div class="product-rating">
                                <span class="stars" aria-hidden="true">★★★★★</span>
                                <span class="rating-value">4.8</span>
                                <span class="rating-note">
                                    (<?= $is_ar ? 'عينة' : 'demo' ?>)
                                </span>
                            </div>

                            <button
                                class="add-cart-btn quick-add-btn"
                                type="button"
                                data-id="<?= h($p['id']) ?>"
                                data-product-id="<?= h($p['id']) ?>"
                                data-name="<?= h($name) ?>"
                                data-product-name="<?= h($name) ?>"
                                data-price="<?= h(number_format(salePrice($p), 2, '.', '')) ?>"
                                data-product-price="<?= h(number_format(salePrice($p), 2, '.', '')) ?>"
                                data-image="<?= h($img) ?>"
                                data-product-image="<?= h($img) ?>"
                            >
                                <i class="fa-solid fa-cart-shopping"></i>
                                <?= h($t['add_cart']) ?>
                            </button>

                        </div>
                    </article>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="empty-state">
                <i class="fa-solid fa-tag"></i>
                <p><?= h($t['no_offers']) ?></p>
            </div>

        <?php endif; ?>

    </div>
</section>

<section class="benefits-band" id="about">
    <div class="container benefits-layout">

        <div class="benefits-intro">
            <h2><?= h($t['why_heading']) ?></h2>
            <p><?= h($t['why_sub']) ?></p>
        </div>

        <div class="benefit">
            <span class="benefit-icon">
                <i class="fa-regular fa-gem"></i>
            </span>
            <strong><?= h($t['quality']) ?></strong>
            <small><?= h($t['quality_desc']) ?></small>
        </div>

        <div class="benefit">
            <span class="benefit-icon">
                <i class="fa-solid fa-truck-fast"></i>
            </span>
            <strong><?= h($t['shipping']) ?></strong>
            <small><?= h($t['shipping_desc']) ?></small>
        </div>

        <div class="benefit">
            <span class="benefit-icon">
                <i class="fa-solid fa-shield-halved"></i>
            </span>
            <strong><?= h($t['payment']) ?></strong>
            <small><?= h($t['payment_desc']) ?></small>
        </div>

        <div class="benefit">
            <span class="benefit-icon">
                <i class="fa-regular fa-circle-check"></i>
            </span>
            <strong><?= h($t['returns']) ?></strong>
            <small><?= h($t['returns_desc']) ?></small>
        </div>

    </div>
</section>


<section class="section testimonials-section">
    <div class="container">

        <div class="section-head">
            <div>
                <span class="section-kicker"><?= h($t['reviews_kicker']) ?></span>
                <h2><?= h($t['reviews_heading']) ?></h2>
                <p><?= h($t['reviews_sub']) ?></p>
            </div>
        </div>

        <div class="testimonials-grid">

            <article class="testimonial-card">
                <div class="avatar avatar-one">S</div>
                <div class="testimonial-body">
                    <div class="testimonial-stars" aria-label="Demo rating">★★★★★</div>
                    <p>
                        <?= $is_ar
                            ? 'جودة القطع والتفاصيل رائعة، والتجربة كانت مميزة جدًا.'
                            : 'The quality is amazing and the details are so unique. A lovely shopping experience.' ?>
                    </p>
                    <strong><?= $is_ar ? 'سارة م.' : 'Sarah M.' ?></strong>
                </div>
            </article>

            <article class="testimonial-card">
                <div class="avatar avatar-two">L</div>
                <div class="testimonial-body">
                    <div class="testimonial-stars" aria-label="Demo rating">★★★★★</div>
                    <p>
                        <?= $is_ar
                            ? 'وصل طلبي بشكل جميل، وكانت القطع أجمل من الصور.'
                            : 'Beautiful pieces and a lovely shopping experience. Highly recommend!' ?>
                    </p>
                    <strong><?= $is_ar ? 'لينا ك.' : 'Lina K.' ?></strong>
                </div>
            </article>

            <article class="testimonial-card">
                <div class="avatar avatar-three">N</div>
                <div class="testimonial-body">
                    <div class="testimonial-stars" aria-label="Demo rating">★★★★★</div>
                    <p>
                        <?= $is_ar
                            ? 'اختيارات أنيقة وخدمة لطيفة. سأعود للتسوّق من أوركيد.'
                            : 'Orchid Boutique made my special day feel even more special. Thank you!' ?>
                    </p>
                    <strong><?= $is_ar ? 'نور أ.' : 'Nour A.' ?></strong>
                </div>
            </article>

        </div>
    </div>
</section>

<section class="section instagram-section" id="instagram">
    <div class="container instagram-layout">

        <div class="instagram-intro">
            <h2><?= h($t['gallery_heading']) ?></h2>
            <p><?= h($t['gallery_sub']) ?></p>
        </div>

        <div class="instagram-grid">

            <?php
            $gallery = array_slice(
                array_values(array_filter($products, fn($p) => !empty($p['image']))),
                0,
                6
            );
            ?>

            <?php foreach ($gallery as $g): ?>

                <a
                    class="instagram-item"
                    href="<?= h(detailsUrl($g['id'], $current_lang)) ?>"
                >
                    <img
                        src="<?= h(imagePath($g['image'])) ?>"
                        alt="<?= h(productTitle($g, $is_ar)) ?>"
                        loading="lazy"
                    >
                </a>

            <?php endforeach; ?>

            <?php if (!$gallery): ?>

                <div class="instagram-item instagram-placeholder">
                    <i class="fa-brands fa-instagram"></i>
                </div>

            <?php endif; ?>

        </div>

        <a class="instagram-handle" href="#instagram">
            <i class="fa-brands fa-instagram"></i>
            @orchid.boutique
        </a>

    </div>
</section>

<?php endif; ?>

</main>

<footer class="footer" id="footer">
    <div class="container">

        <div class="footer-grid">

            <div class="footer-about">

                <a class="footer-brand" href="index.php?lang=<?= h($current_lang) ?>">
                    <i class="fa-brands fa-pagelines"></i>
                    Orchid
                    <small>B O U T I Q U E</small>
                </a>

                <p>
                    <?= $is_ar
                        ? 'وجهتكِ للأزياء الأنيقة والقطع التي تعكس شخصيتكِ.'
                        : 'Your destination for luxury fashion, dresses, abayas and more.' ?>
                </p>

                <div class="social-links">
                    <a href="#instagram" aria-label="Instagram">
                        <i class="fa-brands fa-instagram"></i>
                    </a>
                    <a href="#instagram" aria-label="Facebook">
                        <i class="fa-brands fa-facebook-f"></i>
                    </a>
                    <a href="#instagram" aria-label="Pinterest">
                        <i class="fa-brands fa-pinterest-p"></i>
                    </a>
                    <a href="#instagram" aria-label="TikTok">
                        <i class="fa-brands fa-tiktok"></i>
                    </a>
                </div>

            </div>

            <div class="footer-col">
                <h3><?= h($t['quick_links']) ?></h3>
                <ul>
                    <li><a href="index.php?lang=<?= h($current_lang) ?>"><?= h($t['home']) ?></a></li>
                    <li><a href="#categories"><?= h($t['collections']) ?></a></li>
                    <li><a href="#products"><?= h($t['new']) ?></a></li>
                    <li><a href="#offers"><?= h($t['offers']) ?></a></li>
                    <li><a href="#about"><?= h($t['blog']) ?></a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h3><?= h($t['customer_care']) ?></h3>
                <ul>
                    <li><a href="profile.php"><?= h($t['account']) ?></a></li>
                    <li><a href="cart.php?lang=<?= h($current_lang) ?>"><?= $is_ar ? 'سلة التسوق' : 'Shopping Cart' ?></a></li>
                    <li><a href="login.php?lang=<?= h($current_lang) ?>"><?= h($t['login']) ?></a></li>
                    <li><a href="admin/login.php"><?= h($t['admin']) ?></a></li>
                </ul>
            </div>

            <div class="footer-col newsletter-col">
                <h3><?= h($t['newsletter']) ?></h3>
                <p><?= h($t['newsletter_desc']) ?></p>

                <form class="newsletter-form" action="#footer" method="get">

                    <input
                        type="email"
                        placeholder="<?= h($t['email_placeholder']) ?>"
                        aria-label="<?= h($t['email_placeholder']) ?>"
                        required
                    >

                    <button type="submit" aria-label="<?= h($t['subscribe']) ?>">
                        <i class="fa-solid <?= $is_ar ? 'fa-arrow-left' : 'fa-arrow-right' ?>"></i>
                    </button>

                </form>
            </div>

        </div>

        <div class="footer-bottom">

            <span>
                © <?= date('Y') ?> Orchid Boutique.
                <?= h($t['copyright']) ?>
            </span>

            <a href="index.php?lang=<?= h($next_lang) ?>">
                <?= $is_ar ? 'English' : 'العربية' ?>
            </a>

            <span>
                <?= h($t['admin']) ?>:
                <a href="admin/login.php">Admin</a>
            </span>

        </div>

    </div>
</footer>

<div id="cartOverlay" class="cart-overlay"></div>


<aside id="cartDrawer" class="cart-drawer">
    <div class="cart-drawer-header">
        <h3>
            <span class="cart-drawer-title" data-ar="سلة التسوق" data-en="Shopping Cart">سلة التسوق</span> 
            (<span class="cart-drawer-count">0</span>)
        </h3>
        <button id="closeCart" class="close-cart-btn" type="button">&times;</button>
    </div>

    
    <div class="cart-items-container"></div>

    <div class="cart-drawer-footer">
        <div class="cart-total-row">
            <span class="cart-total-label" data-ar="المجموع:" data-en="Total:">المجموع:</span>
            <span class="total-price">₪0.00</span>
        </div>
        <a id="fullCartLink" href="cart.php" class="view-cart-btn" data-ar="عرض السلة الكاملة" data-en="View Full Cart">عرض السلة الكاملة</a>
        <a id="checkoutLink" href="checkout.php" class="checkout-btn" data-ar="إتمام الطلب" data-en="Checkout">إتمام الطلب</a>
    </div>
</aside>



<?php if (is_file(__DIR__ . '/js/cart.js')): ?>
    <script src="js/cart.js" defer></script>
<?php endif; ?>

<?php if (is_file(__DIR__ . '/js/account.js')): ?>
    <script src="js/account.js" defer></script>
<?php endif; ?>
  <?php if (is_file(__DIR__ . '/js/account.js')): ?>
    <script src="js/account.js" defer></script>
<?php endif; ?>

<script>
let currentArchIdx = 0;
const archSlides = document.querySelectorAll('#orchidArchSlides .arch-slide');
const archDots = document.querySelectorAll('#orchidArchDots .dot');

function goToArchSlide(index) {
    if (!archSlides.length) return;
    archSlides[currentArchIdx].classList.remove('active');
    archDots[currentArchIdx].classList.remove('active');
    currentArchIdx = (index + archSlides.length) % archSlides.length;
    archSlides[currentArchIdx].classList.add('active');
    archDots[currentArchIdx].classList.add('active');
}

setInterval(() => {
    goToArchSlide(currentArchIdx + 1);
}, 4000);
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const accountBtn = document.getElementById('accountBtn');
    const accountMenu = document.getElementById('accountMenu');

    if (accountBtn && accountMenu) {
        // رفع طبقة القائمة لتظهر فوق القوس المتحرك وأي عنصر آخر
        accountBtn.closest('.account-dropdown').style.position = 'relative';
        accountBtn.closest('.account-dropdown').style.zIndex = '99999';
        accountMenu.style.zIndex = '100000';

        accountBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();

            const isHidden = accountMenu.hasAttribute('hidden') || accountMenu.style.display === 'none';

            if (isHidden) {
                accountMenu.removeAttribute('hidden');
                accountMenu.style.display = 'block';
                accountBtn.setAttribute('aria-expanded', 'true');
            } else {
                accountMenu.setAttribute('hidden', '');
                accountMenu.style.display = 'none';
                accountBtn.setAttribute('aria-expanded', 'false');
            }
        });

        // إغلاق القائمة عند الضغط في أي مكان خارجها
        document.addEventListener('click', (e) => {
            if (!accountBtn.contains(e.target) && !accountMenu.contains(e.target)) {
                accountMenu.setAttribute('hidden', '');
                accountMenu.style.display = 'none';
                accountBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }
});
</script>

<script src="js/wishlist.js"></script>

</body>
</html>


</body>
</html>