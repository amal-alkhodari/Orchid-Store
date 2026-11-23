<?php
session_start();

$current_lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'ar';

if (!in_array($current_lang, ['ar', 'en'], true)) {
    $current_lang = 'ar';
}

$_SESSION['lang'] = $current_lang;

$is_ar = $current_lang === 'ar';
$dir = $is_ar ? 'rtl' : 'ltr';

$lang = [];
$lang_file = __DIR__ . '/languages/' . $current_lang . '.php';

if (is_file($lang_file)) {
    include $lang_file;
}

function ch($value): string
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

$t = [
    'brand' => 'Orchid Boutique',
    'home' => $lang['nav_home'] ?? ($is_ar ? 'الرئيسية' : 'Home'),
    'casual' => $is_ar ? 'الأزياء اليومية' : 'Casual Wear',
    'abaya' => $is_ar ? 'العبايات' : 'Abayas',
    'dresses' => $is_ar ? 'فساتين السهرة' : 'Evening Dresses',

    'cart_title' => $lang['cart_title'] ?? ($is_ar ? 'سلة التسوق' : 'Shopping Cart'),
    'cart_subtitle' => $is_ar
        ? 'راجعي اختياراتكِ قبل إتمام طلبكِ.'
        : 'Review your selections before completing your order.',

    'continue' => $is_ar ? 'مواصلة التسوق' : 'Continue Shopping',
    'checkout' => $lang['checkout'] ?? ($is_ar ? 'إتمام الطلب' : 'Checkout'),
    'total' => $lang['total'] ?? ($is_ar ? 'المجموع الكلي' : 'Total'),
    'empty_title' => $is_ar ? 'سلتكِ بانتظار اختياراتكِ' : 'Your Bag Is Waiting',
    'empty_desc' => $is_ar
        ? 'لم تضيفي أي منتجات إلى سلة التسوق حتى الآن.'
        : 'You have not added any products to your shopping bag yet.',
    'explore' => $is_ar ? 'اكتشفي المنتجات' : 'Explore Products',

    'secure' => $is_ar ? 'تسوّق مريح' : 'Easy Shopping',
    'secure_desc' => $is_ar ? 'راجعي تفاصيل طلبكِ قبل المتابعة.' : 'Review your order before continuing.',
    'support' => $is_ar ? 'مساعدة عند الحاجة' : 'Customer Support',
    'support_desc' => $is_ar ? 'يمكنكِ الرجوع للمنتجات ومراجعة اختياراتكِ.' : 'Return to products and review your choices.',

    'quick_links' => $is_ar ? 'روابط سريعة' : 'Quick Links',
    'customer' => $is_ar ? 'حسابكِ' : 'Your Account',
    'account' => $is_ar ? 'حسابي' : 'My Account',
    'login' => $is_ar ? 'تسجيل الدخول' : 'Log In',
    'copyright' => $is_ar ? 'جميع الحقوق محفوظة.' : 'All rights reserved.',
];

$next_lang = $is_ar ? 'en' : 'ar';
$store_name = $lang['store_name'] ?? 'Orchid';
?>
<!DOCTYPE html>
<html lang="<?= ch($current_lang) ?>" dir="<?= ch($dir) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= ch($t['cart_title']) ?> | <?= ch($t['brand']) ?></title>

    <meta
        name="description"
        content="<?= ch($t['cart_subtitle']) ?>"
    >

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Montserrat:wght@400;500;600;700&family=Tajawal:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    >
    <!-- التصميم الجديد الخاص بصفحة السلة -->
<link rel="stylesheet" href="assets/css/cart.css?v=2">
    </head>

<body class="orchid-cart-page">

<!-- شريط علوي مختصر -->
<div class="cart-topbar">
    <div class="cart-container cart-topbar-inner">

        <span>
            <i class="fa-regular fa-heart"></i>
            <?= $is_ar ? 'اختياراتكِ الأنيقة من أوركيد' : 'Your elegant choices from Orchid' ?>
        </span>

        
    </div>
</div>

<!-- ترويسة الصفحات الداخلية -->
<header class="cart-header">
    <div class="cart-container cart-header-inner">

        <a
            class="cart-brand"
            href="index.php?lang=<?= ch($current_lang) ?>"
        >
            <i class="fa-brands fa-pagelines"></i>

            <span>
                Orchid
                <small>B O U T I Q U E</small>
            </span>
        </a>

        <nav class="cart-nav">
            <a href="index.php?lang=<?= ch($current_lang) ?>">
                <?= ch($t['home']) ?>
            </a>

            <a href="category.php?cat=casual&lang=<?= ch($current_lang) ?>">
                <?= ch($t['casual']) ?>
            </a>

            <a href="category.php?cat=abaya&lang=<?= ch($current_lang) ?>">
                <?= ch($t['abaya']) ?>
            </a>

            <a href="category.php?cat=dresses&lang=<?= ch($current_lang) ?>">
                <?= ch($t['dresses']) ?>
            </a>
        </nav>

        <div class="cart-header-actions">

            <a href="wishlist.php?lang=<?= ch($current_lang) ?>" aria-label="Wishlist">
                <i class="fa-regular fa-heart"></i>
            </a>

            <?php if (isset($_SESSION['user_id'])): ?>

                <a class="cart-account-btn" href="profile.php">
                    <?= ch($_SESSION['user_name'] ?? $t['account']) ?>
                </a>

            <?php else: ?>

                <a
                    class="cart-account-btn"
                    href="login.php?lang=<?= ch($current_lang) ?>"
                >
                    <?= ch($t['login']) ?>
                </a>

            <?php endif; ?>

        </div>
    </div>
</header>

<main class="cart-main">

    <div class="cart-container">

        <!-- مقدمة صفحة السلة -->
        <section class="cart-page-intro">

            <nav class="cart-breadcrumb" aria-label="Breadcrumb">
                <a href="index.php?lang=<?= ch($current_lang) ?>">
                    <?= ch($t['home']) ?>
                </a>

                <i class="fa-solid fa-chevron-left"></i>

                <span><?= ch($t['cart_title']) ?></span>
            </nav>

            <div class="cart-title-row">

                <div>
                    <span class="cart-eyebrow">
                        ORCHID BOUTIQUE
                    </span>

                    <h1><?= ch($t['cart_title']) ?></h1>

                    <p><?= ch($t['cart_subtitle']) ?></p>
                </div>

                <div class="cart-title-icon">
                    <i class="fa-solid fa-bag-shopping"></i>
                </div>

            </div>

        </section>

        <!-- المحتوى الذي يعرضه cart.js -->
        <section class="cart-content-section">

            <div id="cartPageContent" aria-live="polite">
                <div class="cart-page-loading">
                    <i class="fa-solid fa-spinner fa-spin"></i>

                    <span>
                        <?= $is_ar ? 'جارٍ تحميل سلة التسوق...' : 'Loading your shopping cart...' ?>
                    </span>
                </div>
            </div>

        </section>

        <!-- روابط متابعة التسوق -->
        <div class="cart-continue-row">

            <a
                class="cart-continue-btn"
                href="category.php?cat=casual&lang=<?= ch($current_lang) ?>"
            >
                <i class="fa-solid <?= $is_ar ? 'fa-arrow-right' : 'fa-arrow-left' ?>"></i>
                <?= ch($t['continue']) ?>
            </a>

        </div>

        <!-- معلومات مساعدة -->
        <section class="cart-benefits">

            <article class="cart-benefit">
                <span class="cart-benefit-icon">
                    <i class="fa-regular fa-rectangle-list"></i>
                </span>

                <div>
                    <h3><?= ch($t['secure']) ?></h3>
                    <p><?= ch($t['secure_desc']) ?></p>
                </div>
            </article>

            <article class="cart-benefit">
                <span class="cart-benefit-icon">
                    <i class="fa-regular fa-heart"></i>
                </span>

                <div>
                    <h3><?= ch($t['support']) ?></h3>
                    <p><?= ch($t['support_desc']) ?></p>
                </div>
            </article>

        </section>

    </div>
</main>

<script>
    window.ORCHID_LANG = <?= json_encode($current_lang) ?>;
</script><script src="js/cart.js"></script>
<script src="js/product-details.js"></script>


</body>
</html>