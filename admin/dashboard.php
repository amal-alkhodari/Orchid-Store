<?php
session_start();
require_once __DIR__ . '/../db.php';

// حماية لوحة التحكم
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// تفعيل تبديل اللغة من الشريط العلوي
if (isset($_GET['lang']) && in_array($_GET['lang'], ['ar', 'en'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = $_SESSION['lang'] ?? 'ar';
$lang = in_array($lang, ['ar', 'en'], true) ? $lang : 'ar';

$isArabic = $lang === 'ar';
$dir = $isArabic ? 'rtl' : 'ltr';

// تنسيق النصوص
function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function money($value) {
    return '₪' . number_format((float)$value, 2);
}

$t = [
    'ar' => [
        'title' => 'لوحة التحكم',
        'welcome' => 'مرحبًا بكِ في لوحة التحكم',
        'subtitle' => 'إليكِ ملخص أداء متجرك اليوم',
        'dashboard' => 'الرئيسية',
        'products' => 'المنتجات',
        'add_product' => 'إضافة منتج',
        'orders' => 'الطلبات',
        'messages' => 'الرسائل',
        'visit' => 'زيارة المتجر',
        'logout' => 'تسجيل الخروج',
        'store_admin' => 'مدير المتجر',
        'stats' => 'ملخص المتجر',
        'total_products' => 'إجمالي المنتجات',
        'active_products' => 'المنتجات المعروضة',
        'total_orders' => 'إجمالي الطلبات',
        'pending_orders' => 'طلبات تحتاج متابعة',
        'sales' => 'مبيعات الطلبات المسلمة',
        'delivered' => 'طلبات مسلمة',
        'low_stock' => 'مخزون منخفض',
        'out_stock' => 'نفد المخزون',
        'recent_orders' => 'أحدث الطلبات',
        'view_all' => 'عرض الكل',
        'order_number' => 'رقم الطلب',
        'date' => 'التاريخ',
        'amount' => 'المبلغ',
        'status' => 'الحالة',
        'weekly_sales' => 'المبيعات خلال آخر 7 أيام',
        'no_orders' => 'لا توجد طلبات حتى الآن',
        'no_products' => 'لا توجد منتجات',
        'quick_actions' => 'إجراءات سريعة',
        'manage_products' => 'إدارة المنتجات',
        'manage_orders' => 'متابعة الطلبات',
        'create_product' => 'إضافة منتج جديد',
        'manage_messages' => 'عرض رسائل العملاء',
        'stock_alert' => 'تنبيهات المخزون',
        'low_stock_desc' => 'منتجات تحتاج إلى إعادة توفير',
        'out_stock_desc' => 'منتجات غير متوفرة حاليًا',
        'no_alerts' => 'المخزون بحالة جيدة',
        'cancelled' => 'ملغي',
        'delivered_status' => 'تم التسليم',
        'pending' => 'قيد المتابعة',
        'footer' => 'Orchid Boutique',
        'error' => 'تعذر تحميل بعض البيانات. تحققي من اتصال قاعدة البيانات.',
        'currency' => 'شيكل',
        'demo_data' => 'بيانات تجريبية للعرض',
        'compared_week' => 'مقارنة بالأسبوع السابق',
        'up' => 'ارتفاع',
        'down' => 'انخفاض',
        'stable' => 'ثابت',
        'statuses' => [
            'pending'    => 'قيد المراجعة',
            'confirmed'  => 'تم التأكيد',
            'processing' => 'قيد التجهيز',
            'shipped'    => 'تم الشحن',
            'delivered'  => 'تم التسليم',
            'cancelled'  => 'ملغي',
        ],
    ],
    'en' => [
        'title' => 'Dashboard',
        'welcome' => 'Welcome to your Dashboard',
        'subtitle' => 'Here is your store performance overview',
        'dashboard' => 'Dashboard',
        'products' => 'Products',
        'add_product' => 'Add Product',
        'orders' => 'Orders',
        'messages' => 'Messages',
        'visit' => 'Visit Store',
        'logout' => 'Log out',
        'store_admin' => 'Store Admin',
        'stats' => 'Store Overview',
        'total_products' => 'Total Products',
        'active_products' => 'Active Products',
        'total_orders' => 'Total Orders',
        'pending_orders' => 'Orders to Follow Up',
        'sales' => 'Delivered Sales',
        'delivered' => 'Delivered Orders',
        'low_stock' => 'Low Stock',
        'out_stock' => 'Out of Stock',
        'recent_orders' => 'Recent Orders',
        'view_all' => 'View All',
        'order_number' => 'Order',
        'date' => 'Date',
        'amount' => 'Amount',
        'status' => 'Status',
        'weekly_sales' => 'Sales Over the Last 7 Days',
        'no_orders' => 'No orders yet',
        'no_products' => 'No products',
        'quick_actions' => 'Quick Actions',
        'manage_products' => 'Manage Products',
        'manage_orders' => 'Manage Orders',
        'create_product' => 'Add New Product',
        'manage_messages' => 'View Customer Messages',
        'stock_alert' => 'Stock Alerts',
        'low_stock_desc' => 'Products that need restocking',
        'out_stock_desc' => 'Products currently unavailable',
        'no_alerts' => 'Stock is looking good',
        'cancelled' => 'Cancelled',
        'delivered_status' => 'Delivered',
        'pending' => 'In Progress',
        'footer' => 'Orchid Boutique',
        'error' => 'Some data could not be loaded. Check your database connection.',
        'currency' => 'NIS',
        'demo_data' => 'Demo data for display',
        'compared_week' => 'Compared with previous week',
        'up' => 'Increase',
        'down' => 'Decrease',
        'stable' => 'Stable',
        'statuses' => [
            'pending'    => 'Pending',
            'confirmed'  => 'Confirmed',
            'processing' => 'Processing',
            'shipped'    => 'Shipped',
            'delivered'  => 'Delivered',
            'cancelled'  => 'Cancelled',
        ],
    ],
];

$tx = $t[$lang];

$totalProducts = 0;
$activeProducts = 0;
$lowStock = 0;
$outStock = 0;

$totalOrders = 0;
$pendingOrders = 0;
$deliveredOrders = 0;
$totalSales = 0;

$recentOrders = [];
$dashboardError = false;

try {
    $productStats = $pdo->query("
        SELECT
            COUNT(*) AS total_products,
            COALESCE(SUM(is_active = 1), 0) AS active_products,
            COALESCE(SUM(stock > 0 AND stock <= 5), 0) AS low_stock,
            COALESCE(SUM(stock <= 0), 0) AS out_stock
        FROM products
    ")->fetch(PDO::FETCH_ASSOC);

    $totalProducts = (int)$productStats['total_products'];
    $activeProducts = (int)$productStats['active_products'];
    $lowStock = (int)$productStats['low_stock'];
    $outStock = (int)$productStats['out_stock'];

    $orderStats = $pdo->query("
        SELECT
            COUNT(*) AS total_orders,
            COALESCE(SUM(status NOT IN ('delivered', 'cancelled')), 0) AS pending_orders,
            COALESCE(SUM(status = 'delivered'), 0) AS delivered_orders,
            COALESCE(SUM(
                CASE
                    WHEN status = 'delivered' THEN total_amount
                    ELSE 0
                END
            ), 0) AS total_sales
        FROM orders
    ")->fetch(PDO::FETCH_ASSOC);

    $totalOrders = (int)$orderStats['total_orders'];
    $pendingOrders = (int)$orderStats['pending_orders'];
    $deliveredOrders = (int)$orderStats['delivered_orders'];
    $totalSales = (float)$orderStats['total_sales'];

    $recentStmt = $pdo->query("
        SELECT id, created_at, status, total_amount
        FROM orders
        ORDER BY created_at DESC
        LIMIT 6
    ");
    $recentOrders = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Dashboard error: ' . $e->getMessage());
    $dashboardError = true;
}

$lowStockItems = [];
try {
    $lowStmt = $pdo->query("SELECT id, name_ar, name_en, stock, image FROM products WHERE stock <= 5 ORDER BY stock ASC LIMIT 3");
    $lowStockItems = $lowStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $lowStockItems = [];
}

$topProducts = [];
try {
    $topStmt = $pdo->query("
        SELECT p.id, p.name_ar, p.name_en, SUM(oi.quantity) AS total_sold
        FROM order_items oi
        JOIN products p ON p.id = oi.product_id
        GROUP BY p.id, p.name_ar, p.name_en
        ORDER BY total_sold DESC
        LIMIT 3
    ");
    $topProducts = $topStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $topProducts = [];
}

$monthlyGoal = 5000;
$goalPercent = $monthlyGoal > 0 ? min(100, round(($totalSales / $monthlyGoal) * 100, 1)) : 0;

$recentCustomers = [];
try {
    $custStmt = $pdo->query("
        SELECT full_name, phone, city, MAX(created_at) AS last_order
        FROM orders
        WHERE full_name IS NOT NULL AND full_name != ''
        GROUP BY full_name, phone, city
        ORDER BY last_order DESC
        LIMIT 4
    ");
    $recentCustomers = $custStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recentCustomers = [];
}

$discountedCount = 0;
$maxDiscount = 0;
try {
    $discStmt = $pdo->query("
        SELECT 
            COUNT(*) AS cnt, 
            COALESCE(MAX(discount_percent), 0) AS max_disc 
        FROM products 
        WHERE discount_percent > 0 AND is_active = 1
    ");
    $discRow = $discStmt->fetch(PDO::FETCH_ASSOC);
    $discountedCount = (int)($discRow['cnt'] ?? 0);
    $maxDiscount = (float)($discRow['max_disc'] ?? 0);
} catch (PDOException $e) {}

$statusCounts = ['pending' => 0, 'confirmed' => 0, 'processing' => 0, 'shipped' => 0, 'delivered' => 0];
try {
    $stStmt = $pdo->query("SELECT status, COUNT(*) as c FROM orders GROUP BY status");
    while ($row = $stStmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($statusCounts[$row['status']])) {
            $statusCounts[$row['status']] = (int)$row['c'];
        }
    }
} catch (PDOException $e) {}

$safeTotalOrders = max(1, $totalOrders);
$pctPending    = round(($statusCounts['pending'] / $safeTotalOrders) * 100);
$pctProcessing = round((($statusCounts['confirmed'] + $statusCounts['processing']) / $safeTotalOrders) * 100);
$pctShipped    = round(($statusCounts['shipped'] / $safeTotalOrders) * 100);
$pctDelivered  = round(($statusCounts['delivered'] / $safeTotalOrders) * 100);

$adminId = (int)$_SESSION['admin_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['admin_avatar']) && $_FILES['admin_avatar']['error'] === UPLOAD_ERR_OK) {
    $fileTmp  = $_FILES['admin_avatar']['tmp_name'];
    $fileSize = $_FILES['admin_avatar']['size'];
    $ext      = strtolower(pathinfo($_FILES['admin_avatar']['name'], PATHINFO_EXTENSION));
    $allowed  = ['jpg', 'jpeg', 'png', 'webp'];

    if (in_array($ext, $allowed, true) && $fileSize <= 3 * 1024 * 1024) {
        $uploadDir = __DIR__ . '/../assets/images/admins/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $newFileName = 'admin_' . $adminId . '_' . time() . '.' . $ext;
        $destPath    = $uploadDir . $newFileName;
        $dbPath      = 'assets/images/admins/' . $newFileName;

        if (move_uploaded_file($fileTmp, $destPath)) {
            $upd = $pdo->prepare("UPDATE admin_users SET avatar = ? WHERE id = ?");
            $upd->execute([$dbPath, $adminId]);
            header("Location: dashboard.php?avatar_updated=1");
            exit;
        }
    }
}

$adminData = ['name' => 'Orchid Admin', 'email' => 'admin@orchid.com', 'avatar' => ''];
try {
    $admStmt = $pdo->prepare("SELECT * FROM admin_users WHERE id = ? LIMIT 1");
    $admStmt->execute([$adminId]);
    if ($row = $admStmt->fetch(PDO::FETCH_ASSOC)) {
        $adminData['name']   = $row['name'] ?? $row['username'] ?? 'Orchid Admin';
        $adminData['email']  = $row['email'] ?? 'admin@orchid.com';
        $adminData['avatar'] = $row['avatar'] ?? '';
    }
} catch (PDOException $e) {}

$adminAvatarUrl = !empty($adminData['avatar']) ? '../' . ltrim($adminData['avatar'], '/') : '';
?>
<!DOCTYPE html>
<html lang="<?= $isArabic ? 'ar' : 'en' ?>" dir="<?= $dir ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= h($tx['title']) ?> | Orchid Boutique</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- تم رفع الإصدار إلى v=12 لتجنب مشكلة الكاش -->
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css?v=12">

    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body>

<div class="dashboard-glass-shell">

    <!-- الشريط العلوي -->
    <header class="glass-navbar">
        <!-- الشعار -->
        <a href="dashboard.php" class="nav-brand">
            <span class="brand-circle">
                <i data-lucide="flower-2"></i>
            </span>
            <span class="brand-title">ORCHID</span>
        </a>

        <!-- كبسولة التنقل الوسطى -->
        <nav class="nav-pill-group">
            <a href="dashboard.php" class="pill-link active">
                <?= $isArabic ? 'لوحة التحكم' : 'Dashboard' ?>
            </a>

            <!-- المنتجات -->
            <div class="nav-dropdown">
                <a href="products.php" class="pill-link dropdown-trigger">
                    <span><?= $isArabic ? 'المنتجات' : 'Products' ?></span>
                    <span class="pill-count"><?= number_format($totalProducts) ?></span>
                    <i data-lucide="chevron-down" class="chevron-icon"></i>
                </a>
                <div class="glass-dropdown-menu">
                    <a href="products.php" class="dropdown-item">
                        <i data-lucide="layers"></i>
                        <div>
                            <strong><?= $isArabic ? 'إجمالي المنتجات' : 'All Products' ?></strong>
                            <small><?= $isArabic ? 'المعروضة حالياً: ' . $activeProducts : 'Active: ' . $activeProducts ?></small>
                        </div>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="products.php?filter=today" class="dropdown-item">
                        <i data-lucide="calendar"></i>
                        <span><?= $isArabic ? 'منتجات اليوم' : 'Today\'s Products' ?></span>
                    </a>
                    <a href="products.php?filter=week" class="dropdown-item">
                        <i data-lucide="calendar-days"></i>
                        <span><?= $isArabic ? 'منتجات الأسبوع' : 'This Week' ?></span>
                    </a>
                </div>
            </div>

            <!-- الطلبات -->
            <div class="nav-dropdown">
                <a href="orders.php" class="pill-link dropdown-trigger">
                    <span><?= $isArabic ? 'الطلبات' : 'Orders' ?></span>
                    <span class="pill-count"><?= number_format($totalOrders) ?></span>
                    <i data-lucide="chevron-down" class="chevron-icon"></i>
                </a>
                <div class="glass-dropdown-menu">
                    <a href="orders.php" class="dropdown-item">
                        <i data-lucide="shopping-bag"></i>
                        <div>
                            <strong><?= $isArabic ? 'جميع الطلبات' : 'All Orders' ?></strong>
                            <small><?= $isArabic ? 'السجل الكامل (' . $totalOrders . ')' : 'Full log (' . $totalOrders . ')' ?></small>
                        </div>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="orders.php?filter=today" class="dropdown-item">
                        <i data-lucide="clock"></i>
                        <span><?= $isArabic ? 'طلبات اليوم' : 'Today\'s Orders' ?></span>
                    </a>
                </div>
            </div>

            <!-- المتابعة -->
            <div class="nav-dropdown">
                <a href="orders.php?status=pending" class="pill-link dropdown-trigger">
                    <span><?= $isArabic ? 'المتابعة' : 'Follow-up' ?></span>
                    <?php if ($pendingOrders > 0): ?>
                        <span class="pill-count alert"><?= number_format($pendingOrders) ?></span>
                    <?php endif; ?>
                    <i data-lucide="chevron-down" class="chevron-icon"></i>
                </a>
                <div class="glass-dropdown-menu">
                    <a href="orders.php?status=pending" class="dropdown-item">
                        <i data-lucide="alert-circle" style="color:#C28228;"></i>
                        <div>
                            <strong><?= $isArabic ? 'قيد المراجعة (جديدة)' : 'Pending Orders' ?></strong>
                            <small><?= $isArabic ? 'بانتظار التأكيد (' . $pendingOrders . ')' : 'Awaiting action' ?></small>
                        </div>
                    </a>
                </div>
            </div>

            <!-- المبيعات -->
            <div class="nav-dropdown">
                <a href="orders.php?status=delivered" class="pill-link dropdown-trigger">
                    <span><?= $isArabic ? 'المبيعات' : 'Sales' ?></span>
                    <span class="pill-count"><?= h(money($totalSales)) ?></span>
                    <i data-lucide="chevron-down" class="chevron-icon"></i>
                </a>
                <div class="glass-dropdown-menu">
                    <a href="orders.php?status=delivered" class="dropdown-item">
                        <i data-lucide="check-circle-2" style="color:#2DA44E;"></i>
                        <div>
                            <strong><?= $isArabic ? 'مبيعات الطلبات المسلمة' : 'Delivered Sales' ?></strong>
                            <small><?= $isArabic ? 'طلبات مسلمة: ' . $deliveredOrders : 'Delivered: ' . $deliveredOrders ?></small>
                        </div>
                    </a>
                </div>
            </div>

            <!-- أحدث الطلبات -->
            <div class="nav-dropdown">
                <a href="orders.php" class="pill-link dropdown-trigger">
                    <span><?= $isArabic ? 'أحدث الطلبات' : 'Recent' ?></span>
                    <i data-lucide="chevron-down" class="chevron-icon"></i>
                </a>
                <div class="glass-dropdown-menu recent-orders-dropdown">
                    <div class="dropdown-header-title">
                        <span><?= h($tx['recent_orders']) ?></span>
                        <a href="orders.php"><?= h($tx['view_all']) ?></a>
                    </div>
                    <?php if (empty($recentOrders)): ?>
                        <div class="dropdown-empty"><?= h($tx['no_orders']) ?></div>
                    <?php else: ?>
                        <?php foreach (array_slice($recentOrders, 0, 5) as $order): ?>
                            <?php
                                $st = $order['status'] ?? 'pending';
                                $stLabel = $tx['statuses'][$st] ?? $st;
                            ?>
                            <a href="orders.php?search=<?= (int)$order['id'] ?>" class="dropdown-order-row">
                                <div>
                                    <strong>#<?= (int)$order['id'] ?> - <?= h(money($order['total_amount'] ?? 0)) ?></strong>
                                    <small><?= h(date('Y-m-d', strtotime($order['created_at'] ?? 'now'))) ?></small>
                                </div>
                                <span class="mini-status <?= h($st) ?>"><?= h($stLabel) ?></span>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </nav>

        <!-- الأدوات الجانبية + زر المنيو + قائمة الأدمن -->
        <div class="nav-tools">
            <button type="button" class="tool-circle mobile-menu-btn" id="mobileMenuToggle" aria-label="Menu" aria-expanded="false">
                <i data-lucide="menu"></i>
            </button>
            <a href="../index.php" target="_blank" class="tool-circle" title="<?= h($tx['visit']) ?>">
                <i data-lucide="external-link"></i>
            </a>
            <div class="nav-dropdown admin-profile-dropdown">
                <button
                    type="button"
                    class="admin-pill-avatar"
                    id="adminAvatarToggle"
                    title="<?= h($adminData['name']) ?>"
                    aria-expanded="false"
                    aria-controls="adminDropdownMenu"
                >
                    <?php if (!empty($adminAvatarUrl)): ?>
                        <img src="<?= h($adminAvatarUrl) ?>" alt="Admin">
                    <?php else: ?>
                        <i data-lucide="user-round"></i>
                    <?php endif; ?>
                </button>

                <div class="glass-dropdown-menu admin-menu-box" id="adminDropdownMenu">
                    <div class="admin-menu-header">
                        <div class="menu-avatar-large">
                            <?php if (!empty($adminAvatarUrl)): ?>
                                <img src="<?= h($adminAvatarUrl) ?>" alt="Admin">
                            <?php else: ?>
                                <i data-lucide="user-round"></i>
                            <?php endif; ?>
                        </div>
                        <div class="menu-admin-details">
                            <strong><?= h($adminData['name']) ?></strong>
                            <small><?= $isArabic ? 'مديرة المتجر • Orchid' : 'Store Manager • Orchid' ?></small>
                        </div>
                    </div>

                    <div class="dropdown-divider"></div>

                    <form method="POST" enctype="multipart/form-data" id="avatarUploadForm" style="margin:0;">
                        <label for="adminAvatarInput" class="dropdown-item" style="cursor:pointer;">
                            <i data-lucide="camera"></i>
                            <span><?= $isArabic ? 'تغيير الصورة الشخصية' : 'Change Profile Photo' ?></span>
                        </label>
                        <input
                            type="file"
                            id="adminAvatarInput"
                            name="admin_avatar"
                            accept="image/jpeg,image/png,image/webp"
                            style="display:none;"
                            onchange="document.getElementById('avatarUploadForm').submit();"
                        >
                    </form>

                    <a href="?lang=<?= $isArabic ? 'en' : 'ar' ?>" class="dropdown-item">
                        <i data-lucide="globe"></i>
                        <span><?= $isArabic ? 'Switch to English' : 'التبديل إلى العربية' ?></span>
                    </a>
                    
                    <a href="users.php" class="dropdown-item">
                        <i data-lucide="users"></i>
                        <span><?= $isArabic ? 'إدارة المستخدمين' : 'Users Management' ?></span>
                    </a>

                    <a href="wishlist.php" class="dropdown-item">
                        <i data-lucide="heart"></i>
                        <span><?= $isArabic ? 'إعجابات المستخدمين' : 'Customer Likes' ?></span>
                    </a>
                       
                   <a href="messages.php" class="dropdown-item">
                        <i data-lucide="message-square"></i>
                       <span><?= $isArabic ? 'صندوق الشكاوي والرسائل' : 'Complaints & Messages' ?></span>
                   </a>
                    
                    <div class="dropdown-divider"></div>

                    <a href="logout.php" class="dropdown-item logout-dropdown-item"
                       onclick="return confirm('<?= $isArabic ? 'هل تريدين تسجيل الخروج؟' : 'Log out?' ?>')">
                        <i data-lucide="log-out"></i>
                        <span><?= h($tx['logout']) ?></span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- بطاقة الترحيب والأزرار السريعة -->
    <section class="control-hub-card">
        <div class="hub-welcome">
            <span class="hub-date"><?= h(date('Y-m-d')) ?></span>
            <h1><?= h($tx['welcome']) ?></h1>
            <p><?= h($tx['subtitle']) ?></p>
        </div>

        <div class="hub-actions-row">
            <a href="products.php" class="glass-action-pill">
                <i data-lucide="package"></i>
                <span><?= h($tx['manage_products']) ?></span>
            </a>
            <a href="orders.php" class="glass-action-pill">
                <i data-lucide="shopping-bag"></i>
                <span><?= h($tx['manage_orders']) ?></span>
                <?php if ($pendingOrders > 0): ?>
                    <span class="pill-count alert"><?= number_format($pendingOrders) ?></span>
                <?php endif; ?>
            </a>
            <a href="add_product.php" class="burgundy-action-pill">
                <i data-lucide="plus"></i>
                <span><?= h($tx['create_product']) ?></span>
            </a>
        </div>
    </section>

    <!-- الشبكة التحليلية السفلية -->
    <section class="analytics-bento-grid">
        <div class="bento-glass-card">
            <div class="bento-header">
                <div>
                    <h3><?= $isArabic ? 'نظرة عامة على المبيعات' : 'Sales Overview' ?></h3>
                    <small><?= $isArabic ? 'أداء المتجر خلال آخر 7 أيام' : 'Store performance over the last 7 days' ?></small>
                </div>
                <span class="bento-badge">
                    <i data-lucide="trending-up"></i>
                    <?= h(money($totalSales)) ?>
                </span>
            </div>

            <div class="mini-bar-chart">
                <div class="bar-col"><div class="bar-track"><div class="bar-fill" style="height: 35%;"></div></div><span><?= $isArabic ? 'السبت' : 'Sat' ?></span></div>
                <div class="bar-col"><div class="bar-track"><div class="bar-fill" style="height: 55%;"></div></div><span><?= $isArabic ? 'الأحد' : 'Sun' ?></span></div>
                <div class="bar-col"><div class="bar-track"><div class="bar-fill" style="height: 45%;"></div></div><span><?= $isArabic ? 'الإثنين' : 'Mon' ?></span></div>
                <div class="bar-col"><div class="bar-track"><div class="bar-fill active" style="height: 85%;"></div></div><span><?= $isArabic ? 'الثلاثاء' : 'Tue' ?></span></div>
                <div class="bar-col"><div class="bar-track"><div class="bar-fill" style="height: 65%;"></div></div><span><?= $isArabic ? 'الأربعاء' : 'Wed' ?></span></div>
                <div class="bar-col"><div class="bar-track"><div class="bar-fill" style="height: 50%;"></div></div><span><?= $isArabic ? 'الخميس' : 'Thu' ?></span></div>
                <div class="bar-col"><div class="bar-track"><div class="bar-fill" style="height: 75%;"></div></div><span><?= $isArabic ? 'الجمعة' : 'Fri' ?></span></div>
            </div>
        </div>

        <div class="bento-glass-card">
            <div class="bento-header">
                <div>
                    <h3><?= $isArabic ? 'حالة المتجر والمخزون' : 'Store & Stock Health' ?></h3>
                    <small><?= $isArabic ? 'ملخص سريع لحالة القطع والطلبات' : 'Quick summary of items & orders' ?></small>
                </div>
                <a href="products.php?filter=low_stock" class="bento-link">
                    <?= $isArabic ? 'مراجعة المخزون' : 'Check Stock' ?>
                </a>
            </div>

            <div class="health-list">
                <div class="health-row">
                    <div class="health-info">
                        <span class="dot-indicator green"></span>
                        <span><?= $isArabic ? 'المنتجات النشطة والمعروضة' : 'Active Products' ?></span>
                    </div>
                    <strong><?= number_format($activeProducts) ?> / <?= number_format($totalProducts) ?></strong>
                </div>
                <div class="health-row">
                    <div class="health-info">
                        <span class="dot-indicator orange"></span>
                        <span><?= $isArabic ? 'طلبات بانتظار التأكيد' : 'Pending Orders' ?></span>
                    </div>
                    <strong><?= number_format($pendingOrders) ?></strong>
                </div>
                <div class="health-row">
                    <div class="health-info">
                        <span class="dot-indicator burgundy"></span>
                        <span><?= $isArabic ? 'الطلبات التي تم تسليمها' : 'Delivered Orders' ?></span>
                    </div>
                    <strong><?= number_format($deliveredOrders) ?></strong>
                </div>
            </div>
        </div>
    </section>

    <!-- الصف الثالث -->
    <section class="triple-widgets-grid">
        <div class="bento-glass-card">
            <div class="bento-header">
                <div>
                    <h3><?= $isArabic ? 'القطع الأكثر مبيعاً' : 'Top Selling Pieces' ?></h3>
                    <small><?= $isArabic ? 'الأعلى طلباً في المتجر' : 'Most ordered items' ?></small>
                </div>
                <span class="widget-icon-box"><i data-lucide="award"></i></span>
            </div>

            <div class="widget-list">
                <?php if (empty($topProducts)): ?>
                    <p class="widget-empty"><?= $isArabic ? 'لا توجد بيانات مبيعات كافية بعد.' : 'No sales data yet.' ?></p>
                <?php else: ?>
                    <?php foreach ($topProducts as $idx => $item): ?>
                        <div class="widget-item-row">
                            <div class="item-rank-name">
                                <span class="rank-badge">#<?= $idx + 1 ?></span>
                                <strong><?= h($isArabic ? $item['name_ar'] : $item['name_en']) ?></strong>
                            </div>
                            <span class="sold-tag"><?= (int)$item['total_sold'] ?> <?= $isArabic ? 'مبيعة' : 'sold' ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="bento-glass-card">
            <div class="bento-header">
                <div>
                    <h3><?= $isArabic ? 'تنبيهات المخزون' : 'Low Stock Alerts' ?></h3>
                    <small><?= $isArabic ? 'قطع أوشكت على النفاد (≤ 5)' : 'Items running low (≤ 5)' ?></small>
                </div>
                <span class="widget-icon-box warning"><i data-lucide="alert-triangle"></i></span>
            </div>

            <div class="widget-list">
                <?php if (empty($lowStockItems)): ?>
                    <p class="widget-empty"><?= $isArabic ? 'جميع القطع متوفرة بكميات جيدة ✨' : 'All items well stocked ✨' ?></p>
                <?php else: ?>
                    <?php foreach ($lowStockItems as $low): ?>
                        <div class="widget-item-row">
                            <div class="item-rank-name">
                                <strong><?= h($isArabic ? $low['name_ar'] : $low['name_en']) ?></strong>
                                <small class="stock-left"><?= $isArabic ? 'متبقي: ' . (int)$low['stock'] : 'Left: ' . (int)$low['stock'] ?></small>
                            </div>
                            <a href="edit_product.php?id=<?= (int)$low['id'] ?>" class="restock-btn">
                                <?= $isArabic ? 'تعديل' : 'Restock' ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="bento-glass-card goal-card">
            <div class="bento-header">
                <div>
                    <h3><?= $isArabic ? 'هدف المبيعات' : 'Sales Target' ?></h3>
                    <small><?= $isArabic ? 'الهدف: ' . h(money($monthlyGoal)) : 'Goal: ' . h(money($monthlyGoal)) ?></small>
                </div>
                <span class="widget-icon-box"><i data-lucide="target"></i></span>
            </div>

            <div class="gauge-box">
                <svg viewBox="0 0 200 110" class="half-gauge-svg">
                    <path d="M 20 100 A 80 80 0 0 1 180 100" fill="none" stroke="rgba(74, 21, 37, 0.1)" stroke-width="15" stroke-linecap="round" />
                    <path d="M 20 100 A 80 80 0 0 1 180 100" fill="none" stroke="#4A1525" stroke-width="15" stroke-linecap="round"
                          stroke-dasharray="251.2"
                          stroke-dashoffset="<?= 251.2 - (251.2 * ($goalPercent / 100)) ?>" />
                </svg>
                <div class="gauge-center-text">
                    <strong><?= $goalPercent ?>%</strong>
                    <span><?= h(money($totalSales)) ?></span>
                </div>
            </div>
        </div>
    </section>

    <!-- الصف الرابع -->
    <section class="bottom-dual-grid">
        <div class="bento-glass-card">
            <div class="bento-header">
                <div>
                    <h3><?= $isArabic ? 'أحدث الزبائن' : 'Recent Customers' ?></h3>
                    <small><?= $isArabic ? 'تواصل سريع مع زبائن المتجر' : 'Quick contact with store buyers' ?></small>
                </div>
                <span class="widget-icon-box"><i data-lucide="users"></i></span>
            </div>

            <div class="widget-list">
                <?php if (empty($recentCustomers)): ?>
                    <p class="widget-empty"><?= $isArabic ? 'لا يوجد زبائن مسجلون بعد.' : 'No customers yet.' ?></p>
                <?php else: ?>
                    <?php foreach ($recentCustomers as $cust): ?>
                        <?php
                            $rawPhone = preg_replace('/[^0-9]/', '', (string)$cust['phone']);
                            $waPhone = str_starts_with($rawPhone, '05') ? '970' . substr($rawPhone, 1) : $rawPhone;
                            $waUrl = "https://wa.me/" . $waPhone;
                        ?>
                        <div class="widget-item-row">
                            <div class="customer-meta">
                                <span class="cust-avatar"><?= mb_substr(trim($cust['full_name']), 0, 1) ?></span>
                                <div>
                                    <strong><?= h($cust['full_name']) ?></strong>
                                    <small><?= h($cust['city'] ?: ($isArabic ? 'غير محدد' : 'N/A')) ?> • <?= h($cust['phone']) ?></small>
                                </div>
                            </div>
                            <a href="<?= h($waUrl) ?>" target="_blank" class="wa-mini-btn" title="WhatsApp">
                                <i data-lucide="message-circle"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="bento-glass-card">
            <div class="bento-header">
                <div>
                    <h3><?= $isArabic ? 'مفكرة الأدمن السريعة' : 'Admin Quick Notes' ?></h3>
                    <small><?= $isArabic ? 'اكتبي مهامك اليومية (تُحفظ تلقائياً)' : 'Write daily tasks (auto-saved)' ?></small>
                </div>
                <span class="saved-status" id="noteSavedBadge"><?= $isArabic ? 'محفوظ ✓' : 'Saved ✓' ?></span>
            </div>

            <textarea
                id="adminQuickNote"
                class="glass-note-area"
                placeholder="<?= $isArabic ? 'اكتبي ملاحظاتك هنا... مثلاً: متابعة شركة التوصيل، تصوير الكولكشن الجديد...' : 'Type your notes here...' ?>"
            ></textarea>
        </div>
    </section>

    <!-- الصف الخامس -->
    <section class="bottom-dual-grid">
        <div class="bento-glass-card">
            <div class="bento-header">
                <div>
                    <h3><?= $isArabic ? 'توزيع حالات الطلبات' : 'Order Status Breakdown' ?></h3>
                    <small><?= $isArabic ? 'نسب إنجاز وتوصيل الطلبات' : 'Order fulfillment progress' ?></small>
                </div>
                <span class="widget-icon-box"><i data-lucide="pie-chart"></i></span>
            </div>

            <div class="segmented-progress-bar">
                <div class="seg-fill pending" style="width: <?= $pctPending ?>%;"></div>
                <div class="seg-fill processing" style="width: <?= $pctProcessing ?>%;"></div>
                <div class="seg-fill shipped" style="width: <?= $pctShipped ?>%;"></div>
                <div class="seg-fill delivered" style="width: <?= $pctDelivered ?>%;"></div>
            </div>

            <div class="status-legend-grid">
                <div class="legend-item"><span class="dot-indicator orange"></span><span><?= $isArabic ? 'قيد المراجعة' : 'Pending' ?>: <strong><?= $statusCounts['pending'] ?></strong></span></div>
                <div class="legend-item"><span class="dot-indicator purple"></span><span><?= $isArabic ? 'قيد التجهيز' : 'Processing' ?>: <strong><?= $statusCounts['confirmed'] + $statusCounts['processing'] ?></strong></span></div>
                <div class="legend-item"><span class="dot-indicator teal"></span><span><?= $isArabic ? 'تم الشحن' : 'Shipped' ?>: <strong><?= $statusCounts['shipped'] ?></strong></span></div>
                <div class="legend-item"><span class="dot-indicator green"></span><span><?= $isArabic ? 'تم التسليم' : 'Delivered' ?>: <strong><?= $statusCounts['delivered'] ?></strong></span></div>
            </div>
        </div>

        <div class="bento-glass-card">
            <div class="bento-header">
                <div>
                    <h3><?= $isArabic ? 'العروض والخصومات النشطة' : 'Active Promotions' ?></h3>
                    <small><?= $isArabic ? 'القطع المشمولة بالتخفيضات حالياً' : 'Items currently on sale' ?></small>
                </div>
                <a href="products.php" class="bento-link"><?= $isArabic ? 'تعديل الأسعار' : 'Manage Prices' ?></a>
            </div>

            <div class="promo-stats-row">
                <div class="promo-box">
                    <span class="promo-icon"><i data-lucide="tag"></i></span>
                    <div>
                        <strong><?= number_format($discountedCount) ?></strong>
                        <small><?= $isArabic ? 'منتج عليه خصم' : 'Discounted Items' ?></small>
                    </div>
                </div>
                <div class="promo-box">
                    <span class="promo-icon"><i data-lucide="percent"></i></span>
                    <div>
                        <strong><?= rtrim(rtrim(number_format($maxDiscount, 1), '0'), '.') ?>%</strong>
                        <small><?= $isArabic ? 'أعلى نسبة خصم' : 'Highest Discount' ?></small>
                    </div>
                </div>
            </div>
        </div>
    </section>

</div>

<script>
    if (window.lucide) {
        lucide.createIcons();
    }

    // حفظ ملاحظات الأدمن
    const noteBox = document.getElementById('adminQuickNote');
    const savedBadge = document.getElementById('noteSavedBadge');

    if (noteBox) {
        noteBox.value = localStorage.getItem('orchid_admin_note') || '';
        noteBox.addEventListener('input', () => {
            localStorage.setItem('orchid_admin_note', noteBox.value);
            if (savedBadge) savedBadge.style.opacity = '1';
            clearTimeout(window.noteTimer);
            window.noteTimer = setTimeout(() => {
                if (savedBadge) savedBadge.style.opacity = '0.6';
            }, 1200);
        });
    }

    // إدارة القوائم المنسدلة ومنيو الجوال
    document.addEventListener('DOMContentLoaded', function () {
        const allDropdowns = document.querySelectorAll('.nav-dropdown');
        const mobileMenuBtn = document.getElementById('mobileMenuToggle');
        const navPillGroup = document.querySelector('.nav-pill-group');

        if (mobileMenuBtn && navPillGroup) {
            mobileMenuBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                const isOpen = navPillGroup.classList.toggle('menu-open');
                mobileMenuBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });
        }

        allDropdowns.forEach(dropdown => {
            const trigger = dropdown.querySelector('.dropdown-trigger, #adminAvatarToggle');
            if (!trigger) return;

            trigger.addEventListener('click', function (e) {
                if (window.innerWidth <= 900 || trigger.id === 'adminAvatarToggle') {
                    e.preventDefault();
                    e.stopPropagation();

                    const isOpen = dropdown.classList.contains('is-open');
                    allDropdowns.forEach(d => d.classList.remove('is-open'));

                    if (!isOpen) {
                        dropdown.classList.add('is-open');
                    }
                }
            });
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('.nav-dropdown')) {
                allDropdowns.forEach(d => d.classList.remove('is-open'));
            }
            if (navPillGroup && mobileMenuBtn && !e.target.closest('.nav-pill-group') && !e.target.closest('#mobileMenuToggle')) {
                navPillGroup.classList.remove('menu-open');
                mobileMenuBtn.setAttribute('aria-expanded', 'false');
            }
        });
    });
</script>

</body>
</html>