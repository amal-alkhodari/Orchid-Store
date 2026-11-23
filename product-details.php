<?php
session_start();

$current_lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'ar';

if (!in_array($current_lang, ['ar', 'en'], true)) {
    $current_lang = 'ar';
}

$_SESSION['lang'] = $current_lang;

$is_ar = ($current_lang === 'ar');
$dir = $is_ar ? 'rtl' : 'ltr';

require_once __DIR__ . '/db.php';

$lang = [];
$lang_file = __DIR__ . '/languages/' . $current_lang . '.php';

if (is_file($lang_file)) {
    include $lang_file;
}

$t = [
    'brand' => 'Orchid Boutique',
    'home' => $is_ar ? 'الرئيسية' : 'Home',
    'collections' => $is_ar ? 'التصنيفات' : 'Collections',
    'casual' => $is_ar ? 'الأزياء اليومية' : 'Casual Wear',
    'abaya' => $is_ar ? 'العبايات' : 'Abayas',
    'dresses' => $is_ar ? 'فساتين السهرة' : 'Evening Dresses',
    'offers' => $is_ar ? 'التخفيضات' : 'Offers',
    'login' => $is_ar ? 'تسجيل الدخول' : 'Log In',
    'account' => $is_ar ? 'حسابي' : 'My Account',
    'cart' => $is_ar ? 'سلة التسوق' : 'Shopping Cart',

    'breadcrumb_product' => $is_ar ? 'تفاصيل المنتج' : 'Product Details',
    'collection' => $is_ar ? 'من مجموعة أوركيد' : 'FROM THE ORCHID COLLECTION',
    'price' => $is_ar ? 'السعر' : 'Price',
    'old_price' => $is_ar ? 'السعر السابق' : 'Original price',
    'discount' => $is_ar ? 'خصم' : 'OFF',
    'stock' => $is_ar ? 'متوفر في المخزون' : 'In stock',
    'stock_left' => $is_ar ? 'قطعة متبقية' : 'items left',
    'out_stock' => $is_ar ? 'غير متوفر حاليًا' : 'Currently unavailable',

    'description' => $is_ar ? 'تفاصيل المنتج' : 'Product Details',
    'select_size' => $is_ar ? 'اختاري المقاس' : 'Select Size',
    'select_color' => $is_ar ? 'اختاري اللون' : 'Select Color',
    'quantity' => $is_ar ? 'الكمية' : 'Quantity',
    'add_cart' => $is_ar ? 'أضيفي إلى السلة' : 'Add to Cart',
    'wishlist' => $is_ar ? 'إضافة إلى المفضلة' : 'Add to Wishlist',
    'choose_size' => $is_ar ? 'يرجى اختيار المقاس أولًا.' : 'Please select a size first.',
    'added_cart' => $is_ar ? 'تم اختيار المنتج للإضافة إلى السلة.' : 'Product selected for your cart.',
    'secure_note' => $is_ar ? 'اختاري القطعة التي تناسب أسلوبكِ.' : 'Choose the piece that fits your style.',
    'empty_description' => $is_ar
        ? 'قطعة مميزة من أوركيد لتكملي إطلالتكِ.'
        : 'A distinctive Orchid piece to complete your look.',

    'related' => $is_ar ? 'قد يعجبكِ أيضًا' : 'You May Also Like',
    'related_desc' => $is_ar
        ? 'اكتشفي المزيد من القطع المميزة.'
        : 'Discover more pieces from our collection.',
    'details' => $is_ar ? 'عرض التفاصيل' : 'View Details',

    'empty_product' => $is_ar ? 'المنتج غير موجود.' : 'Product not found.',
    'back_home' => $is_ar ? 'العودة إلى الرئيسية' : 'Back to Home',
    'quick_links' => $is_ar ? 'روابط سريعة' : 'Quick Links',
    'customer' => $is_ar ? 'حسابكِ' : 'Your Account',
    'copyright' => $is_ar ? 'جميع الحقوق محفوظة.' : 'All rights reserved.',
];

$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$product_id || $product_id < 1) {
    http_response_code(404);
    exit($t['empty_product']);
}

try {
    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.category_id,
            p.subcategory,
            p.name_ar,
            p.name_en,
            p.description_ar,
            p.description_en,
            p.price,
            p.discount_percent,
            p.image,
            p.stock,
            p.is_active,
            c.slug AS category_slug
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE p.id = :id
          AND p.is_active = 1
        LIMIT 1
    ");

    $stmt->execute(['id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit($is_ar
        ? 'حدث خطأ أثناء تحميل بيانات المنتج.'
        : 'An error occurred while loading the product.');
}

if (!$product) {
    http_response_code(404);
    exit($t['empty_product']);
}

$product_name = $is_ar
    ? (trim((string)($product['name_ar'] ?? '')) ?: ($product['name_en'] ?? 'Product'))
    : (trim((string)($product['name_en'] ?? '')) ?: ($product['name_ar'] ?? 'Product'));

$product_description = $is_ar
    ? (trim((string)($product['description_ar'] ?? '')) ?: ($product['description_en'] ?? ''))
    : (trim((string)($product['description_en'] ?? '')) ?: ($product['description_ar'] ?? ''));

if (trim((string)$product_description) === '') {
    $product_description = $t['empty_description'];
}

function pd_image_path($image): string
{
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

$product_image = pd_image_path($product['image'] ?? '');

$category_names = [
    'casual' => ['ar' => $t['casual'], 'en' => $t['casual']],
    'abaya' => ['ar' => $t['abaya'], 'en' => $t['abaya']],
    'dresses' => ['ar' => $t['dresses'], 'en' => $t['dresses']],
];

$category_slug = $product['category_slug'] ?? '';
$category = $category_names[$category_slug][$current_lang]
    ?? ($is_ar ? 'أزياء نسائية' : 'Women’s Fashion');

$category_url = isset($category_names[$category_slug])
    ? 'category.php?cat=' . rawurlencode($category_slug) . '&lang=' . rawurlencode($current_lang)
    : 'index.php?lang=' . rawurlencode($current_lang);

$product_price = (float)($product['price'] ?? 0);
$product_discount = (float)($product['discount_percent'] ?? 0);

$has_discount = $product_discount > 0 && $product_discount <= 100;

$final_price = $has_discount
    ? $product_price * (1 - $product_discount / 100)
    : $product_price;

$stock = max(0, (int)($product['stock'] ?? 0));

$size_options = ['S', 'M', 'L', 'XL'];
$product_colors = [];

try {
    $colors_stmt = $pdo->prepare("
        SELECT
            id,
            color_name_ar AS name_ar,
            color_name_en AS name_en,
            color_code AS hex,
            image
        FROM product_colors
        WHERE product_id = :product_id
        ORDER BY id ASC
    ");

    $colors_stmt->execute([
        'product_id' => $product_id
    ]);

    $raw_colors = $colors_stmt->fetchAll(PDO::FETCH_ASSOC);

    $color_images_map = [];
    try {
        $multi_stmt = $pdo->prepare("
            SELECT color_id, image
            FROM product_color_images
            WHERE product_id = :product_id
            ORDER BY sort_order ASC, id ASC
        ");
        $multi_stmt->execute(['product_id' => $product_id]);
        foreach ($multi_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $cid = (int)$row['color_id'];
            $path = pd_image_path($row['image'] ?? '');
            if ($path !== '') {
                $color_images_map[$cid][] = $path;
            }
        }
    } catch (PDOException $e) {
        // إذا لم يكن الجدول موجودًا بعد، نعتمد على حقل image في product_colors
    }

    foreach ($raw_colors as $c) {
        $cid = (int)$c['id'];
        $imgs = $color_images_map[$cid] ?? [];
        $mainColorImg = !empty($c['image']) ? pd_image_path($c['image']) : '';

        if ($mainColorImg !== '' && !in_array($mainColorImg, $imgs, true)) {
            array_unshift($imgs, $mainColorImg);
        }

        if (empty($imgs) && $product_image !== '') {
            $imgs[] = $product_image;
        }

        $c['gallery'] = array_values(array_unique($imgs));
        $c['image_resolved'] = $c['gallery'][0] ?? $product_image;
        $product_colors[] = $c;
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    $product_colors = [];
}

$initial_gallery = !empty($product_colors[0]['gallery'])
    ? $product_colors[0]['gallery']
    : ($product_image !== '' ? [$product_image] : []);

$initial_main_image = $initial_gallery[0] ?? '';

$related_products = [];

if ($category_slug !== '') {
    try {
        $related_stmt = $pdo->prepare("
            SELECT
                p.id,
                p.name_ar,
                p.name_en,
                p.price,
                p.discount_percent,
                p.image,
                c.slug AS category_slug
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE p.is_active = 1
              AND c.slug = :slug
              AND p.id != :id
            ORDER BY p.id DESC
            LIMIT 4
        ");

        $related_stmt->execute([
            'slug' => $category_slug,
            'id' => $product_id,
        ]);

        $related_products = $related_stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log($e->getMessage());
        $related_products = [];
    }
}

function pd_h($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function pd_name(array $item, bool $is_ar): string
{
    $preferred = $is_ar
        ? trim((string)($item['name_ar'] ?? ''))
        : trim((string)($item['name_en'] ?? ''));

    $fallback = $is_ar
        ? trim((string)($item['name_en'] ?? ''))
        : trim((string)($item['name_ar'] ?? ''));

    return $preferred !== ''
        ? $preferred
        : ($fallback !== '' ? $fallback : 'Product');
}

function pd_price(array $item): float
{
    $price = (float)($item['price'] ?? 0);
    $discount = (float)($item['discount_percent'] ?? 0);

    return ($discount > 0 && $discount <= 100)
        ? $price * (1 - $discount / 100)
        : $price;
}

function pd_url($id, $lang): string
{
    return 'product-details.php?id=' . (int)$id
        . '&lang=' . rawurlencode((string)$lang);
}
?>
<!DOCTYPE html>
<html lang="<?= pd_h($current_lang) ?>" dir="<?= pd_h($dir) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= pd_h($product_name) ?> | Orchid Boutique</title>
    <meta name="description" content="<?= pd_h($product_description) ?>">

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

    <link rel="stylesheet" href="assets/css/orchid-home.css">
    <link rel="stylesheet" href="assets/css/cart.css">
    <link rel="stylesheet" href="assets/css/product-details.css?v=3">
</head>

<body>

<main class="pd-page">

    <div class="pd-container">
        <nav class="pd-breadcrumb" aria-label="Breadcrumb">
            <a href="index.php?lang=<?= pd_h($current_lang) ?>">
                <?= pd_h($t['home']) ?>
            </a>

            <i class="fa-solid fa-chevron-left"></i>

            <a href="<?= pd_h($category_url) ?>">
                <?= pd_h($category) ?>
            </a>

            <i class="fa-solid fa-chevron-left"></i>

            <span><?= pd_h($product_name) ?></span>
        </nav>
    </div>

    <section class="pd-main-section">
        <div class="pd-container">

            <div class="pd-product-grid">

                <div class="pd-gallery">

                    <div class="pd-main-image" id="pdGalleryBox">

                        <?php if ($has_discount): ?>
                            <span class="pd-sale-label">
                                <?= (int)$product_discount ?>% <?= pd_h($t['discount']) ?>
                            </span>
                        <?php endif; ?>

                        <?php if ($initial_main_image !== ''): ?>

                            <img
                                id="productDetailImage"
                                src="<?= pd_h($initial_main_image) ?>"
                                alt="<?= pd_h($product_name) ?>"
                                class="product-detail-image"
                                onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='grid';"
                            >

                            <div class="pd-image-fallback" style="display:none;">
                                <i class="fa-solid fa-shirt"></i>
                            </div>

                        <?php else: ?>

                            <div class="pd-image-fallback">
                                <i class="fa-solid fa-shirt"></i>
                            </div>

                        <?php endif; ?>

                        <button
                            type="button"
                            class="pd-slide-btn pd-slide-prev"
                            id="pdSlidePrev"
                            aria-label="<?= $is_ar ? 'الصورة السابقة' : 'Previous image' ?>"
                            style="<?= count($initial_gallery) > 1 ? '' : 'display:none;' ?>"
                        >
                            <i class="fa-solid <?= $is_ar ? 'fa-chevron-right' : 'fa-chevron-left' ?>"></i>
                        </button>

                        <button
                            type="button"
                            class="pd-slide-btn pd-slide-next"
                            id="pdSlideNext"
                            aria-label="<?= $is_ar ? 'الصورة التالية' : 'Next image' ?>"
                            style="<?= count($initial_gallery) > 1 ? '' : 'display:none;' ?>"
                        >
                            <i class="fa-solid <?= $is_ar ? 'fa-chevron-left' : 'fa-chevron-right' ?>"></i>
                        </button>

                        <div class="pd-slide-dots" id="pdSlideDots"></div>

                        <div class="pd-image-caption">
                            <i class="fa-brands fa-pagelines"></i>
                            Orchid Boutique
                        </div>

                    </div>

                    <div class="pd-gallery-note">
                        <i class="fa-regular fa-gem"></i>
                        <span><?= pd_h($t['secure_note']) ?></span>
                    </div>

                </div>

                <div class="pd-info">

                    <span class="pd-collection">
                        <?= pd_h($t['collection']) ?>
                    </span>

                    <span class="pd-category">
                        <?= pd_h($category) ?>
                    </span>

                    <h1 class="pd-title">
                        <?= pd_h($product_name) ?>
                    </h1>

                    <div class="pd-price-block">

                        <span class="pd-current-price">
                            $<?= number_format($final_price, 2) ?>
                        </span>

                        <?php if ($has_discount): ?>

                            <span class="pd-old-price">
                                $<?= number_format($product_price, 2) ?>
                            </span>

                            <span class="pd-discount">
                                <?= (int)$product_discount ?>% <?= pd_h($t['discount']) ?>
                            </span>

                        <?php endif; ?>

                    </div>

                    <div class="pd-stock <?= $stock > 0 ? 'in-stock' : 'out-of-stock' ?>">
                        <span class="pd-stock-dot"></span>

                        <?php if ($stock > 0): ?>

                            <?= pd_h($t['stock']) ?>

                            <?php if ($stock <= 5): ?>
                                — <?= $stock ?> <?= pd_h($t['stock_left']) ?>
                            <?php endif; ?>

                        <?php else: ?>

                            <?= pd_h($t['out_stock']) ?>

                        <?php endif; ?>
                    </div>

                    <div class="pd-divider"></div>

                    <div class="pd-description">
                        <h2><?= pd_h($t['description']) ?></h2>
                        <p><?= nl2br(pd_h($product_description)) ?></p>
                    </div>

                    <div class="option-group pd-option-group">

                        <label class="option-label" id="pdSizeLabel">
                            <?= pd_h($t['select_size']) ?>
                        </label>

                        <div class="size-options" role="group" aria-labelledby="pdSizeLabel">

                            <?php foreach ($size_options as $index => $size): ?>

                                <button
                                    type="button"
                                    class="size-btn <?= $index === 0 ? 'active' : '' ?>"
                                    data-size="<?= pd_h($size) ?>"
                                    aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>"
                                >
                                    <?= pd_h($size) ?>
                                </button>

                            <?php endforeach; ?>

                        </div>

                    </div>

                    <?php if (!empty($product_colors)): ?>

                        <div class="option-group pd-option-group">

                            <label class="option-label" id="pdColorLabel">
                                <?= pd_h($t['select_color']) ?>
                            </label>

                            <div class="color-options" role="group" aria-labelledby="pdColorLabel">

                                <?php foreach ($product_colors as $index => $color): ?>

                                    <button
                                        type="button"
                                        class="color-btn <?= $index === 0 ? 'active' : '' ?>"
                                        data-color-ar="<?= pd_h($color['name_ar']) ?>"
                                        data-color-en="<?= pd_h($color['name_en']) ?>"
                                        data-color="<?= pd_h($is_ar ? $color['name_ar'] : $color['name_en']) ?>"
                                        data-image="<?= pd_h($color['image_resolved']) ?>"
                                        data-images="<?= pd_h(json_encode($color['gallery'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>"
                                        title="<?= pd_h($is_ar ? $color['name_ar'] : $color['name_en']) ?>"
                                        aria-label="<?= pd_h($is_ar ? $color['name_ar'] : $color['name_en']) ?>"
                                        aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>"
                                    >
                                        <span
                                            class="color-swatch"
                                            style="--color-swatch: <?= pd_h($color['hex']) ?>;"
                                        ></span>

                                        <span class="color-name">
                                            <?= pd_h($is_ar ? $color['name_ar'] : $color['name_en']) ?>
                                        </span>
                                    </button>

                                <?php endforeach; ?>

                            </div>

                        </div>

                    <?php endif; ?>

                    <div class="pd-quantity-group">

                        <label class="option-label" for="pdQtyNum">
                            <?= pd_h($t['quantity']) ?>
                        </label>

                        <div class="quantity-selector pd-quantity-selector">

                            <button
                                type="button"
                                id="pdMinus"
                                class="pd-qty-btn"
                                aria-label="<?= $is_ar ? 'تقليل الكمية' : 'Decrease quantity' ?>"
                            >
                                −
                            </button>

                            <span
                                id="pdQtyNum"
                                class="pd-qty-num"
                                role="status"
                                aria-live="polite"
                                data-stock="<?= $stock ?>"
                            >1</span>

                            <button
                                type="button"
                                id="pdPlus"
                                class="pd-qty-btn"
                                aria-label="<?= $is_ar ? 'زيادة الكمية' : 'Increase quantity' ?>"
                            >
                                +
                            </button>

                        </div>

                        <span class="pd-stock-limit" id="pdStockLimit" data-stock="<?= $stock ?>">
                            <?php if ($stock > 0 && $stock <= 5): ?>
                                <?= $is_ar ? 'الكمية محدودة' : 'Limited quantity' ?>
                            <?php endif; ?>
                        </span>

                    </div>

                    <div class="pd-actions">

                        <button
                            type="button"
                            class="add-to-cart-pd-btn"
                            data-id="<?= (int)$product['id'] ?>"
                            data-name-ar="<?= pd_h($product['name_ar'] ?? '') ?>"
                            data-name-en="<?= pd_h($product['name_en'] ?? '') ?>"
                            data-name="<?= pd_h($product_name) ?>"
                            data-price="<?= pd_h(number_format($final_price, 2, '.', '')) ?>"
                            data-original-price="<?= pd_h(number_format($product_price, 2, '.', '')) ?>"
                            data-discount="<?= pd_h($has_discount ? $product_discount : 0) ?>"
                            data-img="<?= pd_h($initial_main_image) ?>"
                            data-stock="<?= $stock ?>"
                            <?= $stock < 1 ? 'disabled' : '' ?>
                        >
                            <i class="fa-solid fa-bag-shopping"></i>
                            <span>
                                <?= pd_h($stock > 0 ? $t['add_cart'] : $t['out_stock']) ?>
                            </span>
                        </button>

                        <button
                            type="button"
                            class="btn-wishlist-pd"
                            aria-label="<?= pd_h($t['wishlist']) ?>"
                            title="<?= pd_h($t['wishlist']) ?>"
                        >
                            <i class="fa-regular fa-heart"></i>
                        </button>

                    </div>

                    <p class="pd-selection-message" id="pdSelectionMessage" aria-live="polite"></p>

                    <div class="pd-service-notes">

                        <div>
                            <i class="fa-solid fa-box-open"></i>
                            <span><?= $is_ar ? 'تفاصيل الطلب واضحة' : 'Clear order details' ?></span>
                        </div>

                        <div>
                            <i class="fa-solid fa-heart"></i>
                            <span><?= $is_ar ? 'اختيارات من أوركيد' : 'Selected by Orchid' ?></span>
                        </div>

                    </div>

                </div>

            </div>

        </div>
    </section>

    <?php if (!empty($related_products)): ?>

        <section class="pd-related-section">
            <div class="pd-container">

                <div class="pd-related-heading">
                    <span class="pd-collection">
                        <?= $is_ar ? 'اكتشفي المزيد' : 'EXPLORE MORE' ?>
                    </span>

                    <h2><?= pd_h($t['related']) ?></h2>

                    <p><?= pd_h($t['related_desc']) ?></p>
                </div>

                <div class="pd-related-grid">

                    <?php foreach ($related_products as $related): ?>

                        <?php
                        $related_name = pd_name($related, $is_ar);
                        $related_image = pd_image_path($related['image'] ?? '');
                        $related_price = (float)($related['price'] ?? 0);
                        $related_discount = (float)($related['discount_percent'] ?? 0);
                        $related_has_discount = $related_discount > 0 && $related_discount <= 100;
                        ?>

                        <article class="pd-related-card">

                            <a
                                class="pd-related-image"
                                href="<?= pd_h(pd_url($related['id'], $current_lang)) ?>"
                            >

                                <?php if ($related_has_discount): ?>
                                    <span class="pd-related-badge">
                                        <?= (int)$related_discount ?>%
                                    </span>
                                <?php endif; ?>

                                <?php if ($related_image !== ''): ?>

                                    <img
                                        src="<?= pd_h($related_image) ?>"
                                        alt="<?= pd_h($related_name) ?>"
                                        loading="lazy"
                                        onerror="this.style.display='none';this.nextElementSibling.style.display='grid'"
                                    >

                                    <span class="pd-related-fallback" style="display:none">
                                        <i class="fa-solid fa-shirt"></i>
                                    </span>

                                <?php else: ?>

                                    <span class="pd-related-fallback">
                                        <i class="fa-solid fa-shirt"></i>
                                    </span>

                                <?php endif; ?>

                            </a>

                            <div class="pd-related-info">

                                <h3>
                                    <a href="<?= pd_h(pd_url($related['id'], $current_lang)) ?>">
                                        <?= pd_h($related_name) ?>
                                    </a>
                                </h3>

                                <div class="pd-related-price">
                                    <span>
                                        $<?= number_format(pd_price($related), 2) ?>
                                    </span>

                                    <?php if ($related_has_discount): ?>
                                        <del>$<?= number_format($related_price, 2) ?></del>
                                    <?php endif; ?>
                                </div>

                                <a
                                    class="pd-related-link"
                                    href="<?= pd_h(pd_url($related['id'], $current_lang)) ?>"
                                >
                                    <?= pd_h($t['details']) ?>
                                    <i class="fa-solid <?= $is_ar ? 'fa-arrow-left' : 'fa-arrow-right' ?>"></i>
                                </a>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            </div>
        </section>

    <?php endif; ?>

</main>

<div id="cartOverlay" class="cart-overlay"></div>

<aside id="cartDrawer" class="cart-drawer">
    <div class="cart-drawer-header">
        <h3>سلة التسوق (<span class="cart-drawer-count">0</span>)</h3>
        <button id="closeCart" class="close-cart-btn" type="button">&times;</button>
    </div>

    <div class="cart-items-container"></div>

    <div class="cart-drawer-footer">
        <div class="cart-total-row">
            <span>المجموع:</span>
            <span class="total-price">$0.00</span>
        </div>
        <a id="fullCartLink" href="cart.php" class="view-cart-btn">عرض السلة الكاملة</a>
        <a href="checkout.php" class="checkout-btn">إتمام الطلب</a>
    </div>
</aside>

<script>
    window.ORCHID_LANG = <?= json_encode($current_lang) ?>;
    window.ORCHID_PRODUCT_STOCK = <?= (int)$stock ?>;
    window.ORCHID_INITIAL_GALLERY = <?= json_encode($initial_gallery, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
</script>

<script src="js/cart.js"></script>
<script src="js/product-details.js?v=2"></script>
<script src="js/wishlist.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const galleryBox = document.getElementById('pdGalleryBox');
    const mainImg = document.getElementById('productDetailImage');
    const prevBtn = document.getElementById('pdSlidePrev');
    const nextBtn = document.getElementById('pdSlideNext');
    const dotsWrap = document.getElementById('pdSlideDots');
    const addCartBtn = document.querySelector('.add-to-cart-pd-btn');

    if (!mainImg) return;

    let images = Array.isArray(window.ORCHID_INITIAL_GALLERY)
        ? window.ORCHID_INITIAL_GALLERY.slice()
        : [];
    let currentIdx = 0;
    let autoTimer = null;
    const intervalMs = 3500;

    function buildDots() {
        if (!dotsWrap) return;
        dotsWrap.innerHTML = '';
        if (images.length <= 1) return;

        images.forEach((_, idx) => {
            const dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'pd-slide-dot' + (idx === currentIdx ? ' active' : '');
            dot.setAttribute('aria-label', 'Image ' + (idx + 1));
            dot.addEventListener('click', function () {
                goToSlide(idx);
                restartAuto();
            });
            dotsWrap.appendChild(dot);
        });
    }

    function updateControlsVisibility() {
        const hasMultiple = images.length > 1;
        if (prevBtn) prevBtn.style.display = hasMultiple ? '' : 'none';
        if (nextBtn) nextBtn.style.display = hasMultiple ? '' : 'none';
        if (dotsWrap) dotsWrap.style.display = hasMultiple ? '' : 'none';
    }

    function goToSlide(idx) {
        if (!images.length) return;
        currentIdx = ((idx % images.length) + images.length) % images.length;
        mainImg.style.display = '';
        if (mainImg.nextElementSibling && mainImg.nextElementSibling.classList.contains('pd-image-fallback')) {
            mainImg.nextElementSibling.style.display = 'none';
        }
        mainImg.src = images[currentIdx];

        if (addCartBtn) {
            addCartBtn.dataset.img = images[0];
        }

        if (dotsWrap) {
            dotsWrap.querySelectorAll('.pd-slide-dot').forEach((d, i) => {
                d.classList.toggle('active', i === currentIdx);
            });
        }
    }

    function stopAuto() {
        if (autoTimer) {
            clearInterval(autoTimer);
            autoTimer = null;
        }
    }

    function startAuto() {
        stopAuto();
        if (images.length > 1) {
            autoTimer = setInterval(function () {
                goToSlide(currentIdx + 1);
            }, intervalMs);
        }
    }

    function restartAuto() {
        startAuto();
    }

    function setGallery(newImages) {
        images = Array.isArray(newImages) && newImages.length ? newImages.slice() : [];
        currentIdx = 0;
        updateControlsVisibility();
        buildDots();
        if (images.length) {
            goToSlide(0);
        }
        startAuto();
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            goToSlide(currentIdx - 1);
            restartAuto();
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            goToSlide(currentIdx + 1);
            restartAuto();
        });
    }

    document.querySelectorAll('.color-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            let parsed = [];
            if (this.dataset.images) {
                try {
                    parsed = JSON.parse(this.dataset.images);
                } catch (e) {
                    parsed = [];
                }
            }
            if (!Array.isArray(parsed) || !parsed.length) {
                if (this.dataset.image) {
                    parsed = [this.dataset.image];
                }
            }
            if (parsed.length) {
                setGallery(parsed);
            }
        });
    });

    if (galleryBox) {
        galleryBox.addEventListener('mouseenter', stopAuto);
        galleryBox.addEventListener('mouseleave', startAuto);

        let startX = 0;
        galleryBox.addEventListener('touchstart', function (e) {
            startX = e.changedTouches[0].screenX;
            stopAuto();
        }, { passive: true });

        galleryBox.addEventListener('touchend', function (e) {
            const endX = e.changedTouches[0].screenX;
            const diff = endX - startX;
            if (Math.abs(diff) > 35 && images.length > 1) {
                goToSlide(diff > 0 ? currentIdx - 1 : currentIdx + 1);
            }
            startAuto();
        }, { passive: true });
    }

    updateControlsVisibility();
    buildDots();
    startAuto();
});
</script>

</body>
</html>