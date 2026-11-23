<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$current_lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'ar';
$current_lang = $current_lang === 'en' ? 'en' : 'ar';
$isArabic = $current_lang === 'ar';

function h($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$statusLabels = [
    'pending'    => ['ar' => 'قيد المراجعة', 'en' => 'Pending'],
    'confirmed'  => ['ar' => 'تم التأكيد', 'en' => 'Confirmed'],
    'processing' => ['ar' => 'قيد التجهيز', 'en' => 'Processing'],
    'shipped'    => ['ar' => 'تم الشحن', 'en' => 'Shipped'],
    'delivered'  => ['ar' => 'تم التسليم', 'en' => 'Delivered'],
    'cancelled'  => ['ar' => 'ملغي', 'en' => 'Cancelled'],
];

$paymentLabels = [
    'cod' => ['ar' => 'الدفع عند الاستلام', 'en' => 'Cash on delivery'],
    'paypal' => ['ar' => 'PayPal', 'en' => 'PayPal'],
    'bank_transfer' => ['ar' => 'تحويل بنكي', 'en' => 'Bank transfer'],
];

$accountStatusLabels = [
    'active' => [
        'ar' => 'حساب نشط',
        'en' => 'Active Account',
        'color' => '#286B50',
        'background' => '#E8F5EE'
    ],
    'frozen' => [
        'ar' => 'حساب مجمّد',
        'en' => 'Frozen Account',
        'color' => '#A66B16',
        'background' => '#FFF4DF'
    ],
    'deleted' => [
        'ar' => 'حساب محذوف',
        'en' => 'Deleted Account',
        'color' => '#A12F3C',
        'background' => '#FCEBED'
    ]
];
// معالجة قبول الطلب السريع أو تحديث الحالة والملاحظة
$flashMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)($_POST['order_id'] ?? 0);

    // 1. قبول الطلب السريع
    if (isset($_POST['quick_accept']) && $orderId > 0) {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'confirmed' WHERE id = ?");
        $stmt->execute([$orderId]);
        $flashMessage = $isArabic ? "تم قبول الطلب رقم #$orderId بنجاح ✅" : "Order #$orderId accepted ✅";
    }

    // 2. تحديث الحالة وحفظ ملاحظة الأدمن
    if (isset($_POST['update_order']) && $orderId > 0) {
        $newStatus = $_POST['status'] ?? 'pending';
        $adminNote = trim((string)($_POST['admin_note'] ?? ''));

        if (array_key_exists($newStatus, $statusLabels)) {
            $stmt = $pdo->prepare("UPDATE orders SET status = ?, admin_note = ? WHERE id = ?");
            $stmt->execute([$newStatus, $adminNote, $orderId]);
            $flashMessage = $isArabic ? "تم حفظ التحديث والملاحظة للطلب #$orderId بنجاح 🌸" : "Order #$orderId updated successfully 🌸";
        }
    }
}

// استقبال البحث + فلاتر القائمة المنسدلة (الحالة والفترة الزمنية)
$search = trim((string)($_GET['search'] ?? ''));
$statusFilter = trim((string)($_GET['status'] ?? ''));
$dateFilter = trim((string)($_GET['filter'] ?? ''));

$conditions = [];
$params = [];

// 1. فلتر الحالة (من القائمة المنسدلة)
if ($statusFilter !== '' && array_key_exists($statusFilter, $statusLabels)) {
    $conditions[] = "o.status = ?";
    $params[] = $statusFilter;
}

// 2. فلتر الوقت (اليوم / الأسبوع / الشهر)
if ($dateFilter === 'today') {
   $conditions[] = "DATE(o.created_at) = CURDATE()";
} elseif ($dateFilter === 'week') {
    $conditions[] = "YEARWEEK(o.created_at, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($dateFilter === 'month') {
   $conditions[] = "MONTH(o.created_at) = MONTH(CURDATE()) AND YEAR(o.created_at) = YEAR(CURDATE())";
}

// 3. فلتر البحث برقم الطلب أو الاسم أو الهاتف
if ($search !== '') {
    $cleanId = ltrim($search, '#');
    $conditions[] = "(o.id = ? OR o.full_name LIKE ? OR o.phone LIKE ?)";
    $likeTerm = "%$search%";
    $params[] = (int)$cleanId;
    $params[] = $likeTerm;
    $params[] = $likeTerm;
}

$whereSql = !empty($conditions) ? ('WHERE ' . implode(' AND ', $conditions)) : '';

try {
    $sql = "SELECT 
            o.*,
            u.account_status
        FROM orders o
        LEFT JOIN users u ON u.id = o.user_id
        {$whereSql}
        ORDER BY o.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $itemsStmt = $pdo->prepare(
        "SELECT oi.*, p.image, p.name_ar, p.name_en
         FROM order_items oi
         LEFT JOIN products p ON p.id = oi.product_id
         WHERE oi.order_id = ?
         ORDER BY oi.id ASC"
    );

    foreach ($orders as &$order) {
        $itemsStmt->execute([$order['id']]);
        $order['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($order);

} catch (PDOException $e) {
    error_log('Orchid admin orders error: ' . $e->getMessage());
    exit($isArabic
        ? 'تعذر تحميل الطلبات. يرجى المحاولة لاحقًا.'
        : 'Could not load orders. Please try again later.');
}

$totalRevenue = array_sum(array_map(
    fn($order) => (float)$order['total_amount'],
    $orders
));
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>" dir="<?= $isArabic ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $isArabic ? 'إدارة الطلبات | Orchid Boutique' : 'Manage Orders | Orchid Boutique' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-orders.css?v=5">
</head>
    
<body>
<main class="admin-orders-page">
    <!-- 1. الشريط العلوي الزجاجي بالقوائم المنسدلة (بدل الصندوقين الكبيرين) -->
    <header class="orders-glass-navbar">
        <!-- الشعار وعنوان الصفحة -->
        <div class="orders-brand-box">
            <span class="brand-badge">ORCHID BOUTIQUE</span>
            <h1><?= $isArabic ? 'إدارة الطلبات' : 'Manage Orders' ?></h1>
        </div>

        <!-- كبسولة القوائم المنسدلة للإحصائيات والفلاتر -->
        <nav class="nav-pill-group">

            <!-- 1. قائمة إجمالي الطلبات والفلاتر الزمنية -->
            <div class="nav-dropdown">
                <a href="orders.php" class="pill-link <?= ($statusFilter === '' && $dateFilter === '') ? 'active' : '' ?>">
                    <span><?= $isArabic ? 'عدد الطلبات' : 'Total Orders' ?></span>
                    <span class="pill-count"><?= count($orders) ?></span>
                    <i data-lucide="chevron-down" class="chevron-icon"></i>
                </a>
                <div class="glass-dropdown-menu">
                    <a href="orders.php" class="dropdown-item">
                        <i data-lucide="shopping-bag"></i>
                        <div>
                            <strong><?= $isArabic ? 'عرض جميع الطلبات' : 'Show All Orders' ?></strong>
                            <small><?= $isArabic ? 'السجل الكامل (' . count($orders) . ')' : 'Full log (' . count($orders) . ')' ?></small>
                        </div>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="orders.php?filter=today" class="dropdown-item">
                        <i data-lucide="clock"></i>
                        <span><?= $isArabic ? 'طلبات اليوم' : 'Today\'s Orders' ?></span>
                    </a>
                    <a href="orders.php?filter=week" class="dropdown-item">
                        <i data-lucide="calendar-days"></i>
                        <span><?= $isArabic ? 'طلبات الأسبوع' : 'This Week\'s Orders' ?></span>
                    </a>
                    <a href="orders.php?filter=month" class="dropdown-item">
                        <i data-lucide="calendar-range"></i>
                        <span><?= $isArabic ? 'طلبات الشهر' : 'This Month\'s Orders' ?></span>
                    </a>
                </div>
            </div>

            <!-- 2. قائمة حالات الطلبات (فلترة سريعة حسب الحالة) -->
            <div class="nav-dropdown">
                <a href="orders.php?status=pending" class="pill-link <?= $statusFilter !== '' ? 'active' : '' ?>">
                    <span><?= $isArabic ? 'حالة الطلبات' : 'Order Status' ?></span>
                    <i data-lucide="chevron-down" class="chevron-icon"></i>
                </a>
                <div class="glass-dropdown-menu">
                    <a href="orders.php?status=pending" class="dropdown-item">
                        <i data-lucide="alert-circle" style="color:#C28228;"></i>
                        <span><?= $isArabic ? 'قيد المراجعة' : 'Pending' ?></span>
                    </a>
                    <a href="orders.php?status=confirmed" class="dropdown-item">
                        <i data-lucide="check-circle" style="color:#2458A6;"></i>
                        <span><?= $isArabic ? 'تم التأكيد' : 'Confirmed' ?></span>
                    </a>
                    <a href="orders.php?status=processing" class="dropdown-item">
                        <i data-lucide="package-check" style="color:#6841A5;"></i>
                        <span><?= $isArabic ? 'قيد التجهيز' : 'Processing' ?></span>
                    </a>
                    <a href="orders.php?status=shipped" class="dropdown-item">
                        <i data-lucide="truck" style="color:#087D85;"></i>
                        <span><?= $isArabic ? 'تم الشحن' : 'Shipped' ?></span>
                    </a>
                    <a href="orders.php?status=delivered" class="dropdown-item">
                        <i data-lucide="check-circle-2" style="color:#2DA44E;"></i>
                        <span><?= $isArabic ? 'تم التسليم' : 'Delivered' ?></span>
                    </a>
                </div>
            </div>

            <!-- 3. قائمة إجمالي قيمة الطلبات والمالية -->
            <div class="nav-dropdown">
                <a href="orders.php?status=delivered" class="pill-link">
                    <span><?= $isArabic ? 'إجمالي القيمة' : 'Total Value' ?></span>
                    <span class="pill-count burgundy">$<?= number_format($totalRevenue, 2) ?></span>
                    <i data-lucide="chevron-down" class="chevron-icon"></i>
                </a>
                <div class="glass-dropdown-menu">
                    <a href="orders.php?status=delivered" class="dropdown-item">
                        <i data-lucide="wallet" style="color:#2DA44E;"></i>
                        <div>
                            <strong><?= $isArabic ? 'الطلبات المسلمة والمحصّلة' : 'Delivered Revenue' ?></strong>
                            <small><?= $isArabic ? 'المعروض حالياً: $' . number_format($totalRevenue, 2) : 'Current: $' . number_format($totalRevenue, 2) ?></small>
                        </div>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="orders.php?status=delivered&filter=today" class="dropdown-item">
                        <i data-lucide="trending-up"></i>
                        <span><?= $isArabic ? 'مبيعات اليوم' : 'Today\'s Sales' ?></span>
                    </a>
                    <a href="orders.php?status=delivered&filter=week" class="dropdown-item">
                        <i data-lucide="bar-chart-2"></i>
                        <span><?= $isArabic ? 'مبيعات الأسبوع' : 'Weekly Sales' ?></span>
                    </a>
                    <a href="orders.php?status=delivered&filter=month" class="dropdown-item">
                        <i data-lucide="pie-chart"></i>
                        <span><?= $isArabic ? 'مبيعات الشهر' : 'Monthly Sales' ?></span>
                    </a>
                </div>
            </div>

        </nav>

        <!-- أزرار التنقل السريعة -->
        <div class="orders-nav-links">
            <a href="dashboard.php" class="nav-pill-btn">
                <?= $isArabic ? 'لوحة التحكم' : 'Dashboard' ?>
            </a>
            <a href="products.php" class="nav-pill-btn">
                <?= $isArabic ? 'إدارة المنتجات' : 'Manage Products' ?>
            </a>
        </div>
    </header>

    <?php if (!empty($statusFilter) || !empty($dateFilter)): ?>
        <div style="margin-bottom: 16px; display: flex; justify-content: flex-end;">
            <a href="orders.php?lang=<?= h($current_lang) ?>" class="clear-orders-filter">
                <i data-lucide="x"></i>
                <span><?= $isArabic ? 'إلغاء الفلتر وعرض جميع الطلبات' : 'Clear Filter & Show All' ?></span>
            </a>
        </div>
    <?php endif; ?>

       
    <form method="GET" style="display:flex; gap:10px; margin-bottom:24px; background:#FFFFFF; padding:16px 20px; border:1px solid #E9E2E3; border-radius:24px; box-shadow:0 10px 30px rgba(66,20,32,0.03); flex-wrap:wrap; align-items:center;">
        <input type="hidden" name="lang" value="<?= h($current_lang) ?>">
        <input type="hidden" name="status" value="<?= h($statusFilter) ?>">
       <input type="hidden" name="filter" value="<?= h($dateFilter) ?>"> 


        <button type="submit" style="height:46px; padding:0 26px; background:#581D2C; color:#FFFFFF; border:none; border-radius:30px; font-family:inherit; font-size:13.5px; font-weight:800; cursor:pointer;">
            <?= $isArabic ? 'بحث' : 'Search' ?>
        </button>

        <input
            type="text"
            name="search"
            value="<?= h($search ?? '') ?>"
            placeholder="<?= $isArabic ? 'ابحثي برقم الطلب (مثل: 8) أو اسم الزبون أو رقم الهاتف...' : 'Search by Order ID, Customer Name, or Phone...' ?>"
            style="flex:1; min-width:240px; height:46px; padding:0 18px; border:1px solid #DCD2D4; border-radius:30px; font-family:inherit; font-size:13.5px; color:#241D1F; background:#FAF8F8; outline:none; text-align:right;"
        >

        <?php if (!empty($search)): ?>
            <a href="orders.php?lang=<?= h($current_lang) ?>" style="display:inline-flex; align-items:center; justify-content:center; height:46px; padding:0 20px; background:#F8F3F3; color:#581D2C; border:1px solid #EAE0E2; border-radius:30px; font-size:13px; font-weight:800; text-decoration:none;">
                <?= $isArabic ? 'عرض الكل' : 'Reset' ?>
            </a>
        <?php endif; ?>
    </form>

    <?php if (!$orders): ?>
        <section class="orders-admin-empty" style="background: #FFFFFF; border: 1px solid #E9E2E3; border-radius: 24px; padding: 40px; text-align: center;">
            <div style="font-size: 28px; margin-bottom: 10px; color: #581D2C;"></div>
            <h2 style="color: #581D2C; font-size: 18px;"><?= $isArabic ? 'لا توجد طلبات حتى الآن' : 'No orders yet' ?></h2>
        </section>
    <?php else: ?>

        <?php foreach ($orders as $order): ?>
            <?php
                $status = $order['status'] ?? 'pending';
                $statusText = $statusLabels[$status][$current_lang] ?? $status;

                $rawPhone = preg_replace('/[^0-9]/', '', (string)$order['phone']);
                if (str_starts_with($rawPhone, '05')) {
                    $waPhone = '970' . substr($rawPhone, 1);
                } else {
                    $waPhone = $rawPhone;
                }

                $waText = "Orchid Boutique: Hello " . $order['full_name'] . ", regarding your order #" . $order['id'] . ", status: " . $statusText;
                if (!empty($order['admin_note'])) {
                    $waText .= ". Note: " . $order['admin_note'];
                }
                $waLink = "https://wa.me/" . $waPhone . "?text=" . urlencode($waText);
            ?>
            
            <article style="background: #FFFFFF; border: 1px solid #E9E2E3; border-radius: 24px; padding: 24px; margin-bottom: 24px; box-shadow: 0 10px 30px rgba(66,20,32,0.03);">

                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #F2ECEE; padding-bottom: 16px; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
                    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                        <?php if ($status === 'pending'): ?>
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                                <button type="submit" name="quick_accept" style="padding:10px 18px; background:#286B50; color:#fff; border:none; border-radius:30px; font-family:inherit; font-size:12px; font-weight:800; cursor:pointer; box-shadow: 0 4px 12px rgba(40,107,80,0.15);">
                                    <?= $isArabic ? '✓ قبول الطلب' : '✓ Accept Order' ?>
                                </button>
                            </form>
                        <?php endif; ?>

                        <span style="background: #FAF5F6; color: #581D2C; border: 1px solid #EAD8DC; border-radius: 20px; padding: 6px 16px; font-size: 12.5px; font-weight: 700;">
                            <?= h($statusText) ?>
                        </span>
                    </div>

                    <div style="text-align: right;">
                        <?php
    $accountStatus = $order['account_status'] ?? 'deleted';

    $accountLabel = $accountStatusLabels[$accountStatus]
        ?? $accountStatusLabels['deleted'];
?>

<h2 style="font-size: 18px; color: #241D1F; margin: 0 0 8px 0; font-weight: 800;">
    <span style="color: #581D2C;">
        <?= h($order['full_name']) ?>
    </span>
    — <?= $isArabic ? 'الطلب رقم #' : 'Order #' ?><?= (int)$order['id'] ?>
</h2>

<span style="
    display: inline-block;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 800;
    color: <?= h($accountLabel['color']) ?>;
    background: <?= h($accountLabel['background']) ?>;
    border: 1px solid <?= h($accountLabel['color']) ?>30;
">
    <?= h($accountLabel[$current_lang]) ?>
</span>
                        <p style="font-size: 12px; color: #81777A; margin: 0;"><?= h($order['created_at']) ?></p>
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <h3 style="font-size: 14px; color: #581D2C; margin-bottom: 12px; font-weight: 700; text-align: right;">
                        <?= $isArabic ? '📍 بيانات المشتري' : '📍 Buyer Information' ?>
                    </h3>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; background: #FAF8F8; padding: 16px; border-radius: 16px; border: 1px solid #EFEAEC; text-align: right;">
                        <div>
                            <span style="display: block; font-size: 11px; color: #81777A; margin-bottom: 2px;"><?= $isArabic ? 'الاسم' : 'Full Name' ?></span>
                            <strong style="font-size: 13px; color: #241D1F;"><?= h($order['full_name']) ?></strong>
                        </div>

                        <div>
                            <span style="display: block; font-size: 11px; color: #81777A; margin-bottom: 2px;"><?= $isArabic ? 'رقم الهاتف' : 'Phone' ?></span>
                            <strong style="font-size: 13px; color: #241D1F; display: block; margin-bottom: 6px;"><?= h($order['phone']) ?></strong>
                            <a href="<?= $waLink ?>" target="_blank" style="display:inline-flex; align-items:center; gap:6px; padding:6px 14px; background:#25D366; color:#fff; border-radius:20px; font-size:11.5px; font-weight:700; text-decoration:none; box-shadow: 0 3px 10px rgba(37,211,102,0.2);">
                                <?= $isArabic ? 'مراسلة عبر واتساب' : 'Chat on WhatsApp' ?>
                            </a>
                        </div>

                        <div>
                            <span style="display: block; font-size: 11px; color: #81777A; margin-bottom: 2px;"><?= $isArabic ? 'رقم المستخدم' : 'User ID' ?></span>
                            <strong style="font-size: 13px; color: #241D1F;"><?= (int)$order['user_id'] ?></strong>
                        </div>

                        <div>
                            <span style="display: block; font-size: 11px; color: #81777A; margin-bottom: 2px;"><?= $isArabic ? 'العنوان' : 'Address' ?></span>
                            <strong style="font-size: 13px; color: #241D1F;"><?= nl2br(h($order['address'])) ?></strong>
                        </div>

                        <div>
                            <span style="display: block; font-size: 11px; color: #81777A; margin-bottom: 2px;"><?= $isArabic ? 'طريقة الدفع' : 'Payment Method' ?></span>
                            <strong style="font-size: 13px; color: #241D1F;">
                                <?= h($paymentLabels[$order['payment_method']][$current_lang] ?? $order['payment_method']) ?>
                            </strong>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <h3 style="font-size: 14px; color: #581D2C; margin-bottom: 12px; font-weight: 700; text-align: right;">
                        <?= $isArabic ? ' المنتجات المطلوبة' : ' Ordered Products' ?>
                    </h3>

                    <?php if (empty($order['items'])): ?>
                        <p style="font-size: 13px; color: #81777A; text-align: right;"><?= $isArabic ? 'لا توجد تفاصيل منتجات.' : 'No product details.' ?></p>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <?php foreach ($order['items'] as $item): ?>
                                <?php
                                    $productName = $isArabic ? ($item['name_ar'] ?: $item['product_name']) : ($item['name_en'] ?: $item['product_name']);
                                    $imagePath = trim((string)($item['image'] ?? ''));
                                    $quantity = (int)$item['quantity'];
                                    $price = (float)$item['price'];
                                ?>
                                <div style="display: flex; align-items: center; justify-content: space-between; background: #FAF8F8; padding: 12px 16px; border-radius: 16px; border: 1px solid #EFEAEC; gap: 12px; flex-wrap: wrap;">
                                    <strong style="font-size: 14px; color: #581D2C;">$<?= number_format($price * $quantity, 2) ?></strong>
                                    
                                    <div style="display: flex; align-items: center; gap: 12px; flex-direction: row-reverse;">
                                        <div style="text-align: right;">
                                            <strong style="display: block; font-size: 13.5px; color: #241D1F; margin-bottom: 3px;"><?= h($productName) ?></strong>
                                            <span style="font-size: 12px; color: #81777A; margin-inline-start: 10px;"><?= $isArabic ? 'الكمية:' : 'Qty:' ?> <?= $quantity ?></span>
                                            <?php if (!empty($item['size'])): ?>
                                                <span style="font-size: 12px; color: #81777A; margin-inline-start: 10px;"><?= $isArabic ? 'المقاس:' : 'Size:' ?> <?= h($item['size']) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($item['color'])): ?>
                                                <span style="font-size: 12px; color: #81777A;"><?= $isArabic ? 'اللون:' : 'Color:' ?> <?= h($item['color']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($imagePath !== ''): ?>
                                            <img src="../<?= h(ltrim($imagePath, '/')) ?>" alt="<?= h($productName) ?>" onerror="this.style.display='none'" style="width: 48px; height: 48px; object-fit: cover; border-radius: 12px; border: 1px solid #E5DFE1;">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <form method="POST" style="background: #FAF7F5; border: 1px solid #E9E2E3; border-radius: 18px; padding: 18px; display: flex; flex-wrap: wrap; gap: 12px; align-items: end; margin-bottom: 16px;">
                    <button type="submit" name="update_order" style="height:44px; padding:0 26px; background:#581D2C; color:#fff; border:none; border-radius:30px; font-family:inherit; font-size:13px; font-weight:800; cursor:pointer; box-shadow: 0 4px 12px rgba(88,29,44,0.15);">
                        <?= $isArabic ? 'حفظ التحديث' : 'Save Update' ?>
                    </button>

                    <div style="flex: 1; min-width: 240px; text-align: right;">
                        <label style="display:block; margin-bottom:6px; color:#81777A; font-size:12px; font-weight:700;">
                            <?= $isArabic ? 'ملاحظة للمستخدم (مثلاً: المنتج متوفر)' : 'Note to Customer' ?>
                        </label>
                        <input type="text" name="admin_note" value="<?= h($order['admin_note'] ?? '') ?>"
                               placeholder="<?= $isArabic ? 'اكتبي ملاحظتك هنا حول توفر القطع...' : 'Write note here...' ?>"
                               style="width:100%; height:44px; padding:0 18px; border:1px solid #DCD2D4; border-radius:30px; font-family:inherit; font-size:13px; background:#FFFFFF; outline:none; text-align:right;">
                    </div>

                    <div style="min-width: 180px; text-align: right;">
                        <label style="display:block; margin-bottom:6px; color:#81777A; font-size:12px; font-weight:700;">
                            <?= $isArabic ? 'حالة الطلب' : 'Order Status' ?>
                        </label>
                        <select name="status" style="width:100%; height:44px; padding:0 14px; border:1px solid #DCD2D4; border-radius:30px; font-family:inherit; font-size:13px; background:#FFFFFF; outline:none; direction: rtl;">
                            <?php foreach ($statusLabels as $key => $labels): ?>
                                <option value="<?= h($key) ?>" <?= $status === $key ? 'selected' : '' ?>>
                                    <?= h($labels[$current_lang]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                </form>

                <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 10px; border-top: 1px dashed #EAE0E2;">
                    <strong style="font-size: 16px; color: #581D2C; font-weight: 800;">$<?= number_format((float)$order['total_amount'], 2) ?></strong>
                    <span style="font-size: 13px; color: #81777A; font-weight: 700;"><?= $isArabic ? 'إجمالي الطلب' : 'Order Total' ?></span>
                </div>

            </article>
        <?php endforeach; ?>

    <?php endif; ?>
</main>
<script src="https://unpkg.com/lucide@latest"></script>
<script>
    if (window.lucide) {
        lucide.createIcons();
    }
</script>
</body>
</html>