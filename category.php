<?php
session_start();
require_once __DIR__ . '/db.php';

$current_lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'ar';
if (!in_array($current_lang, ['ar', 'en'], true)) {
    $current_lang = 'ar';
}
$_SESSION['lang'] = $current_lang;
$is_ar = $current_lang === 'ar';
$dir = $is_ar ? 'rtl' : 'ltr';
$next_lang = $is_ar ? 'en' : 'ar';

$lang = [];
$lang_file = __DIR__ . '/languages/' . $current_lang . '.php';
if (is_file($lang_file)) {
    include $lang_file;
}

$t = [
    'brand' => 'Orchid Boutique',
    'home' => $is_ar ? 'الرئيسية' : 'Home',
    'casual' => $is_ar ? 'الأزياء اليومية' : 'Casual Wear',
    'abaya' => $is_ar ? 'العبايات' : 'Abayas',
    'dresses' => $is_ar ? 'فساتين السهرة' : 'Evening Dresses',
    'all' => $is_ar ? 'جميع المنتجات' : 'All Products',
    'offers' => $is_ar ? 'التخفيضات' : 'Offers',
    'login' => $is_ar ? 'تسجيل الدخول' : 'Log In',
    'account' => $is_ar ? 'حسابي' : 'My Account',
    'search' => $is_ar ? 'بحث' : 'Search',
    'search_hint' => $is_ar ? 'ابحثي داخل هذا التصنيف...' : 'Search this collection...',
    'eyebrow' => $is_ar ? 'مجموعة أوركيد' : 'THE ORCHID COLLECTION',
    'intro' => $is_ar ? 'اختاري القطع التي تشبه أسلوبكِ.' : 'Find the pieces that feel like you.',
    'subheading' => $is_ar ? 'تسوّقي حسب النوع' : 'Shop by Style',
    'subheading_desc' => $is_ar ? 'اختاري نوع الأزياء التي تبحثين عنها.' : 'Choose the kind of pieces you are looking for.',
    'pants' => $is_ar ? 'البناطيل' : 'Pants',
    'tops' => $is_ar ? 'القمصان والبلايز' : 'Shirts & Blouses',
    'summer' => $is_ar ? 'الملابس الصيفية' : 'Summer Wear',
    'winter' => $is_ar ? 'الملابس الشتوية' : 'Winter Wear',
    'all_casual' => $is_ar ? 'كل الأزياء اليومية' : 'All Casual Wear',
    'browse' => $is_ar ? 'اكتشفي' : 'Explore',
    'product_count' => $is_ar ? 'منتج' : 'products',
    'sort' => $is_ar ? 'ترتيب حسب' : 'Sort by',
    'latest' => $is_ar ? 'الأحدث' : 'Newest',
    'price_low' => $is_ar ? 'السعر: من الأقل للأعلى' : 'Price: Low to High',
    'price_high' => $is_ar ? 'السعر: من الأعلى للأقل' : 'Price: High to Low',
    'collection_title' => $is_ar ? 'اكتشفي المجموعة' : 'Explore the Collection',
    'collection_desc' => $is_ar ? 'منتجات مختارة لتكملي إطلالتكِ اليومية.' : 'Selected pieces to complete your everyday look.',
    'details' => $is_ar ? 'تفاصيل المنتج' : 'View Details',
    'add_cart' => $is_ar ? 'أضيفي إلى السلة' : 'Add to Cart',
    'discount' => $is_ar ? 'خصم' : 'OFF',
    'no_products' => $is_ar ? 'لا توجد منتجات تطابق اختياراتكِ حاليًا.' : 'No products match your selection right now.',
    'clear_filters' => $is_ar ? 'عرض كل المنتجات' : 'Clear Filters',
    'footer_desc' => $is_ar ? 'أزياء أنيقة وتفاصيل تعبّر عنكِ.' : 'Elegant fashion, with details that express you.',
    'links' => $is_ar ? 'روابط سريعة' : 'Quick Links',
    'customer' => $is_ar ? 'حسابكِ' : 'Your Account',
    'cart' => $is_ar ? 'سلة التسوق' : 'Shopping Cart',
    'copyright' => $is_ar ? 'جميع الحقوق محفوظة.' : 'All rights reserved.',
];

$categories = [
    'casual' => ['title' => $t['casual'], 'icon' => 'fa-shirt'],
    'abaya' => ['title' => $t['abaya'], 'icon' => 'fa-person-dress'],
    'dresses' => ['title' => $t['dresses'], 'icon' => 'fa-person-dress'],
];

$subcategories = [
    'pants' => ['title' => $t['pants'], 'image' => 'images/pants.jpg', 'icon' => 'fa-socks'],
    'tops' => ['title' => $t['tops'], 'image' => 'images/tops.jpg', 'icon' => 'fa-shirt'],
    'summer' => ['title' => $t['summer'], 'image' => 'images/summer.jpg', 'icon' => 'fa-sun'],
    'winter' => ['title' => $t['winter'], 'image' => 'images/winter.jpg', 'icon' => 'fa-snowflake'],
];

$cat = (string)($_GET['cat'] ?? 'all');
if (!isset($categories[$cat]) && $cat !== 'all') {
    $cat = 'all';
}

$sub = (string)($_GET['sub'] ?? '');
if (!isset($subcategories[$sub]) || $cat !== 'casual') {
    $sub = '';
}

$search = trim((string)($_GET['q'] ?? ''));
$sort = (string)($_GET['sort'] ?? 'latest');
if (!in_array($sort, ['latest', 'price_low', 'price_high'], true)) {
    $sort = 'latest';
}

if ($sub !== '') {
    $title = $subcategories[$sub]['title'];
} elseif (isset($categories[$cat])) {
    $title = $categories[$cat]['title'];
} else {
    $title = $t['all'];
}

$stmt = $pdo->query(
    "SELECT p.*, c.slug AS category_slug
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.is_active = 1
     ORDER BY p.id DESC"
);
$allProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$products = array_values(array_filter($allProducts, function ($product) use ($cat, $sub, $search) {
    if ($cat !== 'all' && ($product['category_slug'] ?? '') !== $cat) {
        return false;
    }

    if ($sub !== '' && (string)($product['subcategory'] ?? '') !== $sub) {
        return false;
    }

    if ($search !== '') {
        $nameAr = (string)($product['name_ar'] ?? '');
        $nameEn = (string)($product['name_en'] ?? '');

        if (
            mb_stripos($nameAr, $search) === false &&
            mb_stripos($nameEn, $search) === false
        ) {
            return false;
        }
    }

    return true;
}));

usort($products, function ($a, $b) use ($sort) {
    if ($sort === 'price_low') {
        return categorySalePrice($a) <=> categorySalePrice($b);
    }

    if ($sort === 'price_high') {
        return categorySalePrice($b) <=> categorySalePrice($a);
    }

    return (int)($b['id'] ?? 0) <=> (int)($a['id'] ?? 0);
});


$subcategoryCounts = [];

foreach ($subcategories as $key => $data) {
    $subcategoryCounts[$key] = count(array_filter(
        $allProducts,
        function ($product) use ($key) {
            return ($product['category_slug'] ?? '') === 'casual'
                && (string)($product['subcategory'] ?? '') === $key;
        }
    ));
}

function ch($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function categoryImagePath($image): string {
    $image = trim((string)($image ?? ''));

    if ($image === '') {
        return '';
    }

    if (
        preg_match('~^(https?:)?//~i', $image) ||
        str_starts_with($image, 'data:')
    ) {
        return $image;
    }

    $image = ltrim($image, '/');

    if (str_contains($image, '/')) {
        return $image;
    }

    return 'assets/images/' . $image;
}

function categoryProductName(array $product, bool $is_ar): string {
    $preferred = $is_ar
        ? trim((string)($product['name_ar'] ?? ''))
        : trim((string)($product['name_en'] ?? ''));

    $fallback = $is_ar
        ? trim((string)($product['name_en'] ?? ''))
        : trim((string)($product['name_ar'] ?? ''));

    return $preferred !== ''
        ? $preferred
        : ($fallback !== '' ? $fallback : 'Product');
}

function categorySalePrice(array $product): float {
    $price = (float)($product['price'] ?? 0);
    $discount = (float)($product['discount_percent'] ?? 0);

    return ($discount > 0 && $discount <= 100)
        ? $price * (1 - $discount / 100)
        : $price;
}

function categoryProductUrl($id, $lang): string {
    return 'product-details.php?id='
        . (int)$id
        . '&lang='
        . rawurlencode((string)$lang);
}

function categoryUrl($slug, $lang, $sub = ''): string {
    $url = 'category.php?cat=' . rawurlencode((string)$slug);

    if ($sub !== '') {
        $url .= '&sub=' . rawurlencode((string)$sub);
    }

    return $url . '&lang=' . rawurlencode((string)$lang);
}

?>
<!DOCTYPE html>
<html lang="<?= ch($current_lang) ?>" dir="<?= ch($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= ch($title) ?> | <?= ch($t['brand']) ?></title>
    <meta name="description" content="<?= ch($t['intro']) ?>">

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

    
    <link rel="stylesheet" href="assets/css/orchid-home.css?v=2">

    
    <link rel="stylesheet" href="assets/css/category.css?v=2">
</head>

<body>

</header>

<main class="category-page">

    
    <section class="subpage-intro">
        <div class="container">

            <nav class="subpage-breadcrumb" aria-label="Breadcrumb">
                <a href="index.php?lang=<?= ch($current_lang) ?>">
                    <?= ch($t['home']) ?>
                </a>

                <i class="fa-solid fa-chevron-left"></i>

                <span><?= ch($title) ?></span>
            </nav>

            <div class="subpage-title-wrap">
                <div class="subpage-title-copy">

                    <span class="subpage-eyebrow">
                        ORCHID COLLECTION
                    </span>

                    <h1><?= ch($title) ?></h1>

                    <p><?= ch($t['intro']) ?></p>
                </div>

                <span class="subpage-flower" aria-hidden="true">❀</span>
            </div>
        </div>
    </section>

    <?php if ($cat === 'casual' && $sub === ''): ?>

        
        <section class="subcategories-section">
            <div class="container">

                <div class="subcategories-heading">

                    <div>
                        <span class="subpage-eyebrow">
                            <?= $is_ar ? 'اختاري ما يناسبكِ' : 'FIND YOUR FIT' ?>
                        </span>

                        <h2><?= ch($t['subheading']) ?></h2>
                    </div>

                    <a
                        class="subcategories-all active"
                        href="<?= ch(categoryUrl('casual', $current_lang)) ?>"
                    >
                        <?= ch($t['all_casual']) ?>
                    </a>

                </div>

                <div class="subcategory-pills">

                    <?php foreach ($subcategories as $key => $subcategory): ?>

                        <a
                            href="<?= ch(categoryUrl('casual', $current_lang, $key)) ?>"
                            class="subcategory-pill"
                        >
                            <i class="fa-solid <?= ch($subcategory['icon']) ?>"></i>

                            <span><?= ch($subcategory['title']) ?></span>

                            <small><?= (int)$subcategoryCounts[$key] ?></small>
                        </a>

                    <?php endforeach; ?>

                </div>
            </div>
        </section>

    <?php elseif ($cat === 'casual' && $sub !== ''): ?>

        
        <section class="subcategories-section compact-subcategories">
            <div class="container">

                <div class="subcategory-pills">

                    <a
                        href="<?= ch(categoryUrl('casual', $current_lang)) ?>"
                        class="subcategory-pill"
                    >
                        <i class="fa-solid fa-border-all"></i>
                        <span><?= ch($t['all_casual']) ?></span>
                    </a>

                    <?php foreach ($subcategories as $key => $subcategory): ?>

                        <a
                            href="<?= ch(categoryUrl('casual', $current_lang, $key)) ?>"
                            class="subcategory-pill <?= $key === $sub ? 'active' : '' ?>"
                        >
                            <i class="fa-solid <?= ch($subcategory['icon']) ?>"></i>
                            <span><?= ch($subcategory['title']) ?></span>
                        </a>

                    <?php endforeach; ?>

                </div>
            </div>
        </section>

    <?php endif; ?>

    
    <section class="category-listing-section">
        <div class="container">

            <div class="listing-heading">

                <div>
                    <span class="subpage-eyebrow">
                        <?= $is_ar ? 'تسوّقي بطريقتكِ' : 'SHOP YOUR WAY' ?>
                    </span>

                    <h2><?= ch($title) ?></h2>

                    <p>
                        <?= count($products) ?>
                        <?= ch($t['product_count']) ?>
                    </p>
                </div>

                
                <form
                    class="category-search"
                    method="get"
                    action="category.php"
                >
                    <input type="hidden" name="cat" value="<?= ch($cat) ?>">

                    <?php if ($sub !== ''): ?>
                        <input type="hidden" name="sub" value="<?= ch($sub) ?>">
                    <?php endif; ?>

                    <input type="hidden" name="lang" value="<?= ch($current_lang) ?>">

                    <label class="sr-only" for="category-q">
                        <?= ch($t['search']) ?>
                    </label>

                    <input
                        id="category-q"
                        type="search"
                        name="q"
                        value="<?= ch($search) ?>"
                        placeholder="<?= ch($t['search_hint']) ?>"
                    >

                    <button type="submit" aria-label="<?= ch($t['search']) ?>">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </form>

            </div>

            
            <div class="listing-toolbar">

                <span><?= ch($t['collection_title']) ?></span>

                <form
                    method="get"
                    action="category.php"
                    class="sort-form"
                >
                    <input type="hidden" name="cat" value="<?= ch($cat) ?>">

                    <?php if ($sub !== ''): ?>
                        <input type="hidden" name="sub" value="<?= ch($sub) ?>">
                    <?php endif; ?>

                    <?php if ($search !== ''): ?>
                        <input type="hidden" name="q" value="<?= ch($search) ?>">
                    <?php endif; ?>

                    <input type="hidden" name="lang" value="<?= ch($current_lang) ?>">

                    <label for="category-sort"><?= ch($t['sort']) ?></label>

                    <select
                        id="category-sort"
                        name="sort"
                        onchange="this.form.submit()"
                    >
                        <option
                            value="latest"
                            <?= $sort === 'latest' ? 'selected' : '' ?>
                        >
                            <?= ch($t['latest']) ?>
                        </option>

                        <option
                            value="price_low"
                            <?= $sort === 'price_low' ? 'selected' : '' ?>
                        >
                            <?= ch($t['price_low']) ?>
                        </option>

                        <option
                            value="price_high"
                            <?= $sort === 'price_high' ? 'selected' : '' ?>
                        >
                            <?= ch($t['price_high']) ?>
                        </option>
                    </select>
                </form>

            </div>

            <?php if ($products): ?>

                <div class="category-products-grid">

                    <?php foreach ($products as $product): ?>

                        <?php
                        $name = categoryProductName($product, $is_ar);
                        $image = categoryImagePath($product['image'] ?? '');
                        $price = (float)($product['price'] ?? 0);
                        $discount = (float)($product['discount_percent'] ?? 0);
                        $hasDiscount = $discount > 0 && $discount <= 100;
                        $finalPrice = categorySalePrice($product);
                        ?>

                        <article class="category-product-card">

                            <div class="category-product-image">

                                <?php if ($hasDiscount): ?>
                                    <span class="category-sale-badge">
                                        <?= (int)$discount ?>%
                                        <?= ch($t['discount']) ?>
                                    </span>
                                <?php endif; ?>

                                <a
                                    class="category-product-image-link"
                                    href="<?= ch(categoryProductUrl($product['id'], $current_lang)) ?>"
                                >

                                    <?php if ($image !== ''): ?>

                                        <img
                                            src="<?= ch($image) ?>"
                                            alt="<?= ch($name) ?>"
                                            loading="lazy"
                                            onerror="this.style.display='none';this.nextElementSibling.style.display='grid'"
                                        >

                                        <span
                                            class="category-image-fallback"
                                            style="display:none"
                                        >
                                            <i class="fa-solid fa-shirt"></i>
                                        </span>

                                    <?php else: ?>

                                        <span class="category-image-fallback">
                                            <i class="fa-solid fa-shirt"></i>
                                        </span>

                                    <?php endif; ?>

                                </a>

                                <a
                                    class="category-product-heart"
                                    href="wishlist.php?lang=<?= ch($current_lang) ?>"
                                    aria-label="Wishlist"
                                >
                                    <i class="fa-regular fa-heart"></i>
                                </a>

                            </div>

                            <div class="category-product-info">

                                <h3>
                                    <a href="<?= ch(categoryProductUrl($product['id'], $current_lang)) ?>">
                                        <?= ch($name) ?>
                                    </a>
                                </h3>

                                <div class="category-price-row">

                                    <span class="category-current-price">
                                        $<?= number_format($finalPrice, 2) ?>
                                    </span>

                                    <?php if ($hasDiscount): ?>
                                        <span class="category-old-price">
                                            $<?= number_format($price, 2) ?>
                                        </span>
                                    <?php endif; ?>

                                </div>

                                <a
                                    class="category-details-link"
                                    href="<?= ch(categoryProductUrl($product['id'], $current_lang)) ?>"
                                >
                                    <?= ch($t['details']) ?>

                                    <i class="fa-solid <?= $is_ar ? 'fa-arrow-left' : 'fa-arrow-right' ?>"></i>
                                </a>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="category-empty">
                    <i class="fa-regular fa-face-frown"></i>

                    <p><?= ch($t['no_products']) ?></p>

                    <a href="<?= ch(categoryUrl($cat, $current_lang)) ?>">
                        <?= ch($t['clear_filters']) ?>
                    </a>
                </div>

            <?php endif; ?>

        </div>
    </section>

</main>


<footer class="subpage-footer">
    <div class="container subpage-footer-main">

        <div class="subpage-footer-brand-block">

            <a
                class="subpage-footer-brand"
                href="index.php?lang=<?= ch($current_lang) ?>"
            >
                <i class="fa-brands fa-pagelines"></i>
                Orchid
                <small>B O U T I Q U E</small>
            </a>

            <p><?= ch($t['footer_desc']) ?></p>
        </div>

        <div class="subpage-footer-col">
            <h3><?= ch($t['links']) ?></h3>

            <a href="index.php?lang=<?= ch($current_lang) ?>">
                <?= ch($t['home']) ?>
            </a>

            <a href="<?= ch(categoryUrl('casual', $current_lang)) ?>">
                <?= ch($t['casual']) ?>
            </a>

            <a href="<?= ch(categoryUrl('abaya', $current_lang)) ?>">
                <?= ch($t['abaya']) ?>
            </a>

            <a href="<?= ch(categoryUrl('dresses', $current_lang)) ?>">
                <?= ch($t['dresses']) ?>
            </a>
        </div>

        <div class="subpage-footer-col">
            <h3><?= ch($t['customer']) ?></h3>

            <?php if (isset($_SESSION['user_id'])): ?>

                <a href="profile.php"><?= ch($t['account']) ?></a>

            <?php else: ?>

                <a href="login.php?lang=<?= ch($current_lang) ?>">
                    <?= ch($t['login']) ?>
                </a>

            <?php endif; ?>

            <a href="cart.php?lang=<?= ch($current_lang) ?>">
                <?= ch($t['cart']) ?>
            </a>

            <a href="admin/login.php">
                <?= $is_ar ? 'لوحة التحكم' : 'Admin Panel' ?>
            </a>
        </div>

    </div>

    <div class="container subpage-footer-bottom">

        <span>
            © <?= date('Y') ?> Orchid Boutique.
            <?= ch($t['copyright']) ?>
        </span>

        <a href="category.php?cat=<?= ch($cat) ?><?= $sub !== '' ? '&sub=' . ch($sub) : '' ?>&lang=<?= ch($next_lang) ?>">
            <?= $is_ar ? 'English' : 'العربية' ?>
        </a>

    </div>
</footer>

<?php if (is_file(__DIR__ . '/js/cart.js')): ?>
    <script src="js/cart.js" defer></script>
<?php endif; ?>
<script src="js/wishlist.js"></script>

</body>
</html>