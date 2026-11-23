
<?php
session_start();

require_once __DIR__ . '/db.php';

// Language
$current_lang = $_GET['lang'] ?? ($_SESSION['lang'] ?? 'ar');

if (!in_array($current_lang, ['ar', 'en'], true)) {
    $current_lang = 'ar';
}

$_SESSION['lang'] = $current_lang;

$is_ar = $current_lang === 'ar';
$dir = $is_ar ? 'rtl' : 'ltr';

// Translations
$t = [
    'home'       => $is_ar ? 'الرئيسية' : 'Home',
    'categories' => $is_ar ? 'التصنيفات' : 'Categories',
    'abaya'      => $is_ar ? 'العبايات' : 'Abayas',
    'dresses'    => $is_ar ? 'الفساتين' : 'Dresses',
    'casual'     => $is_ar ? 'الأزياء اليومية' : 'Casual Wear',
    'all_casual' => $is_ar ? 'كل الأزياء اليومية' : 'All Casual Wear',
    'pants'      => $is_ar ? 'البناطيل' : 'Pants',
    'tops'       => $is_ar ? 'البلايز والقمصان' : 'Tops & Shirts',
    'summer'     => $is_ar ? 'الملابس الصيفية' : 'Summer Wear',
    'winter'     => $is_ar ? 'الملابس الشتوية' : 'Winter Wear',
    'new'        => $is_ar ? 'أحدث المنتجات' : 'New Arrivals',
    'offers'     => $is_ar ? 'العروض' : 'Offers',
    'about'      => $is_ar ? 'من نحن' : 'About Us',
    'contact'    => $is_ar ? 'تواصل معنا' : 'Contact Us',
    'page_title' => $is_ar ? 'عروض مميزة' : 'Special Offers',
    'page_desc'  => $is_ar
        ? 'اكتشفي قطعك المفضلة بأسعار مميزة.'
        : 'Discover your favorite pieces at special prices.',
    'empty'      => $is_ar ? 'لا توجد عروض متاحة حاليًا.' : 'No offers available at the moment.',
    'currency'   => $is_ar ? 'دولار' : 'USD',
    'sale'       => $is_ar ? 'خصم' : 'OFF',
    'view'       => $is_ar ? 'اكتشفي القطعة' : 'DISCOVER ITEM',
    'count'      => $is_ar ? 'منتج ضمن العروض' : 'ITEMS ON SALE',
];

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function imagePath($value): string {
    $value = trim((string)$value);

    if ($value === '') {
        return '';
    }

    if (
        preg_match('~^(https?:)?//~i', $value) ||
        str_starts_with($value, 'data:')
    ) {
        return $value;
    }

    $value = ltrim($value, '/');

    if (str_contains($value, '/')) {
        return $value;
    }

    return 'assets/images/' . $value;
}

function productTitle(array $product, bool $is_ar): string {
    $name = $is_ar
        ? ($product['name_ar'] ?? '')
        : ($product['name_en'] ?? '');

    if (trim($name) === '') {
        $name = $product['name_ar'] ?? $product['name_en'] ?? '';
    }

    return (string)$name;
}

function salePrice(array $product): float {
    $price = (float)($product['price'] ?? 0);
    $discount = (float)($product['discount_percent'] ?? 0);

    return $price - ($price * $discount / 100);
}

function detailsUrl($id, $lang): string {
    return 'product-details.php?id=' . (int)$id . '&lang=' . urlencode($lang);
}

// Fetch active discounted products only
$stmt = $pdo->query("
    SELECT
        p.id,
        p.name_ar,
        p.name_en,
        p.price,
        p.discount_percent,
        p.image,
        p.created_at
    FROM products p
    WHERE p.is_active = 1
      AND p.discount_percent > 0
    ORDER BY p.created_at DESC, p.id DESC
");

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="<?= h($current_lang) ?>" dir="<?= h($dir) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= h($t['offers']) ?> | Orchid Boutique</title>

    <link rel="stylesheet" href="assets/css/orchid-home.css?v=2">
    <link rel="stylesheet" href="assets/css/offers.css?v=1">
    <link rel="stylesheet" href="assets/css/account.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<header class="site-header">
    <nav class="main-nav" aria-label="Main navigation">

        <a href="index.php?lang=<?= h($current_lang) ?>">
            <?= h($t['home']) ?>
        </a>

        <div class="nav-dropdown">
            <a href="javascript:void(0)">
                <?= h($t['categories']) ?>
                <i class="fa-solid fa-chevron-down nav-chevron"></i>
            </a>

            <div class="nav-dropdown-menu">

                <a href="category.php?cat=abaya&lang=<?= h($current_lang) ?>">
                    <?= h($t['abaya']) ?>
                </a>

                <a href="category.php?cat=dresses&lang=<?= h($current_lang) ?>">
                    <?= h($t['dresses']) ?>
                </a>

                <div class="nav-subdropdown">

                    <a href="category.php?cat=casual&lang=<?= h($current_lang) ?>">
                        <?= h($t['casual']) ?>
                    </a>

                    <div class="nav-subdropdown-menu">

                        <a href="category.php?cat=casual&lang=<?= h($current_lang) ?>">
                            <?= h($t['all_casual']) ?>
                        </a>

                        <a href="category.php?cat=casual&sub=pants&lang=<?= h($current_lang) ?>">
                            <?= h($t['pants']) ?>
                        </a>

                        <a href="category.php?cat=casual&sub=tops&lang=<?= h($current_lang) ?>">
                            <?= h($t['tops']) ?>
                        </a>

                        <a href="category.php?cat=casual&sub=summer&lang=<?= h($current_lang) ?>">
                            <?= h($t['summer']) ?>
                        </a>

                        <a href="category.php?cat=casual&sub=winter&lang=<?= h($current_lang) ?>">
                            <?= h($t['winter']) ?>
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
            <?= h($t['about']) ?>
        </a>

        <a href="contact.php?lang=<?= h($current_lang) ?>">
            <?= h($t['contact']) ?>
        </a>

    </nav>
</header>

<main class="offers-page">

    <section class="offers-intro">
        <div class="offers-intro-content">

            <span class="offers-eyebrow">
                ORCHID BOUTIQUE · SPECIAL SELECTION
            </span>

            <h1><?= h($t['page_title']) ?></h1>

            <p><?= h($t['page_desc']) ?></p>

            <a href="#offer-products" class="offers-intro-link">
                <?= $is_ar ? 'اكتشفي العروض' : 'EXPLORE OFFERS' ?>
                <i class="fa-solid fa-arrow-down"></i>
            </a>

        </div>
    </section>

    <?php if (empty($products)): ?>

        <div class="offers-empty">
            <i class="fa-solid fa-tag"></i>
            <p><?= h($t['empty']) ?></p>
        </div>

    <?php else: ?>

        <section class="offers-products-section" id="offer-products">

            <div class="offers-toolbar">
                <div>
                    <span class="offers-section-label">
                        <?= $is_ar ? 'اختياراتنا المخفضة' : 'SPECIAL PRICES' ?>
                    </span>

                    <h2>
                        <?= $is_ar ? 'تسوقي العروض' : 'Shop The Offers' ?>
                    </h2>
                </div>

                <span class="offers-count">
                    <?= count($products) ?> <?= h($t['count']) ?>
                </span>
            </div>

            <div class="offers-grid">

                <?php foreach ($products as $product): ?>

                    <?php
                        $title = productTitle($product, $is_ar);
                        $price = (float)$product['price'];
                        $discount = (float)$product['discount_percent'];
                        $finalPrice = salePrice($product);
                        $image = imagePath($product['image'] ?? '');
                        $url = detailsUrl($product['id'], $current_lang);
                    ?>

                    <article class="offer-card">

                        <a href="<?= h($url) ?>" class="offer-image">

                            <img
                                src="<?= h($image) ?>"
                                alt="<?= h($title) ?>"
                                loading="lazy"
                            >

                            <span class="offer-discount">
                                <?= h($discount) ?>% <?= h($t['sale']) ?>
                            </span>

                            <span class="offer-view">
                                <?= h($t['view']) ?>
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            </span>

                        </a>

                        <div class="offer-info">

                            <h3>
                                <a href="<?= h($url) ?>">
                                    <?= h($title) ?>
                                </a>
                            </h3>

                            <div class="offer-price">

                                <span class="offer-current-price">
                                    <?= number_format($finalPrice, 2) ?>
                                    <?= h($t['currency']) ?>
                                </span>

                                <span class="offer-old-price">
                                    <?= number_format($price, 2) ?>
                                    <?= h($t['currency']) ?>
                                </span>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        </section>

    <?php endif; ?>

</main>

<script src="js/cart.js"></script>
<script src="js/account.js"></script>
<script src="js/wishlist.js"></script>

</body>
</html>
