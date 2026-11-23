<?php
session_start();

require_once __DIR__ . '/../db.php';

// حماية الصفحة
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$lang = $_SESSION['lang'] ?? 'ar';

if (!in_array($lang, ['ar', 'en'], true)) {
    $lang = 'ar';
}

$isArabic = $lang === 'ar';
$dir = $isArabic ? 'rtl' : 'ltr';

$errorMessage = '';
$products = [];

// استقبال الفلتر القادم من القائمة المنسدلة في لوحة التحكم
$filter = $_GET['filter'] ?? '';
$allowedFilters = ['today', 'week', 'month', 'low_stock', 'active'];
if (!in_array($filter, $allowedFilters, true)) {
    $filter = '';
}

$whereClause = '';
if ($filter === 'today') {
    $whereClause = 'WHERE DATE(created_at) = CURDATE()';
} elseif ($filter === 'week') {
    $whereClause = 'WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)';
} elseif ($filter === 'month') {
    $whereClause = 'WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())';
} elseif ($filter === 'low_stock') {
    $whereClause = 'WHERE stock <= 5';
} elseif ($filter === 'active') {
    $whereClause = 'WHERE is_active = 1';
}

// جلب المنتجات من قاعدة البيانات حسب الفلتر
try {
    $stmt = $pdo->query("
        SELECT
            id,
            name_ar,
            name_en,
            price,
            discount_percent,
            image,
            stock,
            is_active
        FROM products
        {$whereClause}
        ORDER BY id DESC
    ");

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $errorMessage = $isArabic
        ? 'حدث خطأ أثناء تحميل المنتجات.'
        : 'An error occurred while loading products.';
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$totalProducts = count($products);
$activeProducts = 0;
$lowStockProducts = 0;

foreach ($products as $product) {
    if ((int) $product['is_active'] === 1) {
        $activeProducts++;
    }

    if ((int) $product['stock'] <= 5) {
        $lowStockProducts++;
    }
}
?>


<!DOCTYPE html>
<html lang="<?= $isArabic ? 'ar' : 'en' ?>" dir="<?= $dir ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= $isArabic ? 'إدارة المنتجات | Orchid Admin' : 'Products Management | Orchid Admin' ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;600;700;800&display=swap" rel="stylesheet">

   <link rel="stylesheet" href="../assets/css/admin-products.css?v=99">

    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body>

<div class="products-admin">

    <!-- 1. الشريط العلوي الزجاجي بالقوائم المنسدلة للإحصائيات والفلاتر -->
    <header class="products-glass-navbar">
        <!-- الشعار -->
        <a href="dashboard.php" class="nav-brand">
            <span class="brand-icon">
                <i data-lucide="flower-2"></i>
            </span>
            <div class="brand-text">
                <strong>Orchid Admin</strong>
                <small><?= $isArabic ? 'إدارة المنتجات' : 'Manage Products' ?></small>
            </div>
        </a>

        <!-- كبسولة الفلاتر والإحصائيات المنسدلة (بدل المربعات الثلاثة) -->
        <nav class="nav-pill-group">

            <!-- 1. إجمالي المنتجات -->
            <div class="nav-dropdown">
                <a href="products.php" class="pill-link <?= $filter === '' || in_array($filter, ['today', 'week', 'month']) ? 'active' : '' ?>">
                    <span><?= $isArabic ? 'إجمالي المنتجات' : 'All Products' ?></span>
                    <span class="pill-count"><?= number_format($totalProducts) ?></span>
                    <i data-lucide="chevron-down" class="chevron-icon"></i>
                </a>
                <div class="glass-dropdown-menu">
                    <a href="products.php" class="dropdown-item">
                        <i data-lucide="layers"></i>
                        <div>
                            <strong><?= $isArabic ? 'عرض جميع المنتجات' : 'Show All Products' ?></strong>
                            <small><?= $isArabic ? 'قاعدة البيانات الكاملة (' . $totalProducts . ')' : 'Full catalog (' . $totalProducts . ')' ?></small>
                        </div>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="products.php?filter=today" class="dropdown-item">
                        <i data-lucide="calendar"></i>
                        <span><?= $isArabic ? 'المضافة اليوم' : 'Added Today' ?></span>
                    </a>
                    <a href="products.php?filter=week" class="dropdown-item">
                        <i data-lucide="calendar-days"></i>
                        <span><?= $isArabic ? 'المضافة هذا الأسبوع' : 'Added This Week' ?></span>
                    </a>
                    <a href="products.php?filter=month" class="dropdown-item">
                        <i data-lucide="calendar-range"></i>
                        <span><?= $isArabic ? 'المضافة هذا الشهر' : 'Added This Month' ?></span>
                    </a>
                </div>
            </div>

            <!-- 2. المنتجات الظاهرة والمخفية -->
            <div class="nav-dropdown">
                <a href="products.php?filter=active" class="pill-link <?= in_array($filter, ['active', 'hidden']) ? 'active' : '' ?>">
                    <span><?= $isArabic ? 'المنتجات الظاهرة' : 'Visible' ?></span>
                    <span class="pill-count green"><?= number_format($activeProducts) ?></span>
                    <i data-lucide="chevron-down" class="chevron-icon"></i>
                </a>
                <div class="glass-dropdown-menu">
                    <a href="products.php?filter=active" class="dropdown-item">
                        <i data-lucide="eye" style="color:#2DA44E;"></i>
                        <div>
                            <strong><?= $isArabic ? 'المنتجات الظاهرة بالمتجر' : 'Visible in Store' ?></strong>
                            <small><?= $isArabic ? 'متاحة للزبائن الآن (' . $activeProducts . ')' : 'Active items (' . $activeProducts . ')' ?></small>
                        </div>
                    </a>
                    <a href="products.php?filter=hidden" class="dropdown-item">
                        <i data-lucide="eye-off" style="color:#846E75;"></i>
                        <div>
                            <strong><?= $isArabic ? 'المنتجات المخفية' : 'Hidden Products' ?></strong>
                            <small><?= $isArabic ? 'غير معروضة للزبائن' : 'Draft / Hidden' ?></small>
                        </div>
                    </a>
                </div>
            </div>

            <!-- 3. مخزون منخفض ونفاد المخزون -->
            <div class="nav-dropdown">
                <a href="products.php?filter=low_stock" class="pill-link <?= in_array($filter, ['low_stock', 'out_stock']) ? 'active' : '' ?>">
                    <span><?= $isArabic ? 'مخزون منخفض' : 'Low Stock' ?></span>
                    <span class="pill-count orange"><?= number_format($lowStockProducts) ?></span>
                    <i data-lucide="chevron-down" class="chevron-icon"></i>
                </a>
                <div class="glass-dropdown-menu">
                    <a href="products.php?filter=low_stock" class="dropdown-item">
                        <i data-lucide="alert-triangle" style="color:#C28228;"></i>
                        <div>
                            <strong><?= $isArabic ? 'قاربت على النفاد (≤ 5)' : 'Low Stock (≤ 5)' ?></strong>
                            <small><?= $isArabic ? 'تحتاج إعادة تعبئة (' . $lowStockProducts . ')' : 'Needs restock' ?></small>
                        </div>
                    </a>
                    <a href="products.php?filter=out_stock" class="dropdown-item">
                        <i data-lucide="x-circle" style="color:#C93B4B;"></i>
                        <div>
                            <strong><?= $isArabic ? 'نفد المخزون (0)' : 'Out of Stock (0)' ?></strong>
                            <small><?= $isArabic ? 'الكمية منتهية تماماً' : 'Zero quantity' ?></small>
                        </div>
                    </a>
                </div>
            </div>

        </nav>

        <!-- أزرار التحكم السريعة على اليسار -->
        <div class="nav-actions-right">
            <a href="add_product.php" class="add-pill-btn">
                <i data-lucide="plus"></i>
                <span><?= $isArabic ? 'إضافة منتج' : 'Add Product' ?></span>
            </a>

            <a href="dashboard.php" class="back-link">
                <i data-lucide="arrow-right"></i>
                <span><?= $isArabic ? 'لوحة التحكم' : 'Dashboard' ?></span>
            </a>
        </div>
    </header>

    <!-- عنوان الصفحة المختصر -->
    <div class="page-heading">
        <div class="heading-text">
            <h1><?= $isArabic ? 'إدارة المنتجات' : 'Manage Products' ?></h1>
            <p><?= $isArabic ? 'استعرض منتجات متجرك وتابع الأسعار والمخزون وحالة العرض.' : 'Browse store products, track prices, stock, and visibility.' ?></p>
        </div>

        <?php if ($filter !== ''): ?>
            <a href="products.php" class="clear-filter-pill">
                <i data-lucide="x"></i>
                <span><?= $isArabic ? 'إلغاء الفلتر وعرض الكل' : 'Clear Filter' ?></span>
            </a>
        <?php endif; ?>
    </div>

    <!-- Products Content -->
    <section class="products-panel">

        <div class="panel-heading">

            <div>
                <h2><?= $isArabic ? 'جميع المنتجات' : 'All Products' ?></h2>
                <p>
                    <?= $isArabic
                        ? 'قائمة المنتجات المسجلة في قاعدة البيانات.'
                        : 'Products registered in your database.' ?>
                </p>
            </div>

            <div class="products-total">
                <i data-lucide="layers"></i>
                <?= $totalProducts ?>
                <?= $isArabic ? 'منتج' : 'Products' ?>
            </div>

        </div>


        <!-- Search -->
        <div class="products-toolbar">

            <div class="products-search">
                <i data-lucide="search"></i>

                <input
                    type="search"
                    id="productSearch"
                    placeholder="<?= $isArabic ? 'ابحث عن منتج...' : 'Search products...' ?>"
                    autocomplete="off"
                >
            </div>

            <span class="search-hint">
                <?= $isArabic ? 'ابحث باستخدام اسم المنتج' : 'Search by product name' ?>
            </span>

        </div>


        <?php if ($errorMessage !== ''): ?>

            <div class="products-message error-message">
                <i data-lucide="alert-triangle"></i>
                <?= e($errorMessage) ?>
            </div>

        <?php elseif (empty($products)): ?>

            <div class="products-empty">
                <div class="empty-icon">
                    <i data-lucide="package-open"></i>
                </div>

                <h3><?= $isArabic ? 'لا توجد منتجات حتى الآن' : 'No Products Yet' ?></h3>

                <p>
                    <?= $isArabic
                        ? 'ستظهر المنتجات هنا عند إضافتها إلى المتجر.'
                        : 'Products will appear here once they are added to the store.' ?>
                </p>
            </div>

        <?php else: ?>

            <div class="products-table-wrapper">

                <table class="products-table">

                    <thead>
                        <tr>
                            <th><?= $isArabic ? 'المنتج' : 'Product' ?></th>
                            <th><?= $isArabic ? 'السعر' : 'Price' ?></th>
                            <th><?= $isArabic ? 'الخصم' : 'Discount' ?></th>
                            <th><?= $isArabic ? 'المخزون' : 'Stock' ?></th>
                            <th><?= $isArabic ? 'الحالة' : 'Status' ?></th>
                            <th><?= $isArabic ? 'الإجراءات' : 'Actions' ?></th>
                        </tr>
                    </thead>

                    <tbody id="productsTableBody">

                        <?php foreach ($products as $product): ?>

                            <?php
                            $productName = $isArabic
                                ? ($product['name_ar'] ?: $product['name_en'])
                                : ($product['name_en'] ?: $product['name_ar']);

                            $discount = (float) $product['discount_percent'];
                            $price = (float) $product['price'];
                            $salePrice = $price * (1 - $discount / 100);
                            $stock = (int) $product['stock'];

                            $imagePath = trim((string) $product['image']);
                            $imagePath = '../' . ltrim($imagePath, '/');
                            ?>

                            <tr class="product-row"
                                data-name="<?= e($productName) ?>">

                                <!-- Product -->
                                <td>
                                    <div class="product-info">

                                        <?php if ($imagePath !== '../'): ?>

                                            <img
                                                class="product-admin-image"
                                                src="<?= e($imagePath) ?>"
                                                alt="<?= e($productName) ?>"
                                                loading="lazy"
                                            >

                                        <?php else: ?>

                                            <div class="product-image-placeholder">
                                                <i data-lucide="image"></i>
                                            </div>

                                        <?php endif; ?>

                                        <div class="product-name">
                                            <strong><?= e($productName) ?></strong>
                                            <small>
                                                #<?= (int) $product['id'] ?>
                                            </small>
                                        </div>

                                    </div>
                                </td>


                                <!-- Price -->
                                <td>
                                    <div class="price-info">

                                        <?php if ($discount > 0): ?>

                                            <del><?= number_format($price, 2) ?></del>

                                            <strong class="sale-price">
                                                <?= number_format($salePrice, 2) ?>
                                            </strong>

                                        <?php else: ?>

                                            <strong>
                                                <?= number_format($price, 2) ?>
                                            </strong>

                                        <?php endif; ?>

                                    </div>
                                </td>


                                <!-- Discount -->
                                <td>
                                    <?php if ($discount > 0): ?>

                                        <span class="discount-badge">
                                            <?= number_format($discount, 0) ?>%
                                        </span>

                                    <?php else: ?>

                                        <span class="no-discount">—</span>

                                    <?php endif; ?>
                                </td>


                                <!-- Stock -->
                                <td>

                                    <?php if ($stock <= 5): ?>

                                        <span class="stock-badge low-stock">
                                            <i data-lucide="alert-circle"></i>
                                            <?= $stock ?>
                                        </span>

                                    <?php else: ?>

                                        <span class="stock-badge">
                                            <?= $stock ?>
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- Status -->
                                <td>

                                    <?php if ((int) $product['is_active'] === 1): ?>

                                        <span class="product-status active">
                                            <span class="status-dot"></span>
                                            <?= $isArabic ? 'ظاهر' : 'Active' ?>
                                        </span>

                                    <?php else: ?>

                                        <span class="product-status inactive">
                                            <span class="status-dot"></span>
                                            <?= $isArabic ? 'مخفي' : 'Hidden' ?>
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- Actions -->
                                <td>
                                    <a
                                        class="edit-product-btn"
                                        href="edit_product.php?id=<?= (int) $product['id'] ?>"
                                        title="<?= $isArabic ? 'تعديل المنتج' : 'Edit Product' ?>"
                                    >
                                        <i data-lucide="pencil"></i>
                                        <span><?= $isArabic ? 'تعديل' : 'Edit' ?></span>
                                    </a>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

                <div class="no-search-results" id="noSearchResults" hidden>
                    <i data-lucide="search-x"></i>
                    <p>
                        <?= $isArabic
                            ? 'لم يتم العثور على منتجات مطابقة.'
                            : 'No matching products found.' ?>
                    </p>
                </div>

            </div>

        <?php endif; ?>

    </section>


    <footer class="products-footer">
        <span>Orchid Boutique</span>
        <span><?= $isArabic ? 'إدارة المنتجات' : 'Products Management' ?></span>
    </footer>

</main>


<script>
    // تفعيل أيقونات Lucide
    if (window.lucide) {
        lucide.createIcons();
    }

    // البحث عن المنتجات
    const searchInput = document.getElementById('productSearch');
    const productRows = document.querySelectorAll('.product-row');
    const noSearchResults = document.getElementById('noSearchResults');

    if (searchInput) {
        searchInput.addEventListener('input', function () {

            const searchValue = this.value.trim().toLowerCase();
            let visibleCount = 0;

            productRows.forEach(function (row) {

                const productName = row.dataset.name.toLowerCase();
                const matches = productName.includes(searchValue);

                row.style.display = matches ? '' : 'none';

                if (matches) {
                    visibleCount++;
                }
            });

            if (noSearchResults) {
                noSearchResults.hidden = visibleCount !== 0;
            }
        });
    }
</script>

</body>
</html>