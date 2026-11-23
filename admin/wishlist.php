<?php
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['lang']) && in_array($_GET['lang'], ['ar', 'en'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = $_SESSION['lang'] ?? 'ar';
$isArabic = $lang === 'ar';
$dir = $isArabic ? 'rtl' : 'ltr';

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// جلب سجل الإعجابات مع بيانات المستخدم والمنتج
$likesList = [];
$dbErrorMsg = '';
try {
    $stmt = $pdo->query("
        SELECT 
            w.id AS wish_id,
            w.user_id AS wish_user_id,
            w.product_id AS wish_product_id,
            w.created_at AS liked_at,
            u.*,
            p.name_ar,
            p.name_en,
            p.price,
            p.image AS product_image
        FROM wishlist w
        LEFT JOIN products p ON p.id = w.product_id
        LEFT JOIN users u ON u.id = w.user_id
        ORDER BY w.id DESC
    ");
    $likesList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $dbErrorMsg = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="<?= h($lang) ?>" dir="<?= h($dir) ?>" class="notranslate" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isArabic ? 'إعجابات المستخدمين | Orchid Admin' : 'Customer Likes | Orchid Admin' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="../assets/css/admin-wishlist.css?v=2">
</head>
<body>

<div class="likes-shell">
    <!-- الشريط العلوي -->
    <header class="likes-topbar">
        <div class="likes-title">
            <h1><?= $isArabic ? 'إعجابات والمفضلة لدى المستخدمين' : 'Customer Wishlist & Likes' ?></h1>
            <p><?= $isArabic ? 'تعرفي على المنتجات التي نالت إعجاب الزبائن ومن قام بالإعجاب بها.' : 'See which products customers liked and who saved them.' ?></p>
        </div>

        <div class="likes-nav-btns">
            <a href="dashboard.php" class="pill-nav-btn">
                <i data-lucide="layout-dashboard"></i>
                <span><?= $isArabic ? 'لوحة التحكم' : 'Dashboard' ?></span>
            </a>
            <a href="users.php" class="pill-nav-btn">
                <i data-lucide="users"></i>
                <span><?= $isArabic ? 'المستخدمون' : 'Users' ?></span>
            </a>
        </div>
    </header>

    <?php if ($dbErrorMsg !== ''): ?>
        <div class="likes-error-alert">
            <?= h($dbErrorMsg) ?>
        </div>
    <?php endif; ?>

    <!-- شريط الإحصاء السريع -->
    <div class="likes-summary-bar">
        <span class="likes-count-pill">
            <i data-lucide="heart"></i>
            <?= $isArabic ? 'إجمالي الإعجابات المسجلة: ' . count($likesList) : 'Total Likes Recorded: ' . count($likesList) ?>
        </span>
    </div>

    <!-- جدول الإعجابات -->
    <div class="likes-table-wrap">
        <table class="likes-table">
            <thead>
                <tr>
                    <th><?= $isArabic ? 'المستخدم (المُعجب)' : 'USER' ?></th>
                    <th><?= $isArabic ? 'المنتج المُعجب به' : 'LIKED PRODUCT' ?></th>
                    <th><?= $isArabic ? 'السعر' : 'PRICE' ?></th>
                    <th><?= $isArabic ? 'وقت الإعجاب' : 'DATE' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($likesList)): ?>
                    <tr>
                        <td colspan="4" class="likes-empty-cell">
                            <?= $isArabic ? 'لا توجد إعجابات مسجلة في قاعدة البيانات بعد.' : 'No likes recorded yet.' ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($likesList as $row): ?>
                        <?php
                            $isGuest     = empty($row['wish_user_id']);
                            $rawUserName = $row['full_name'] ?? $row['name'] ?? $row['username'] ?? ('مستخدم #' . $row['wish_user_id']);
                            $uName       = $isGuest ? ($isArabic ? 'زائر (غير مسجل)' : 'Guest Visitor') : $rawUserName;
                            $uEmail      = $isGuest ? '-' : ($row['email'] ?? '-');
                            $uAvatar     = $row['avatar'] ?? $row['profile_image'] ?? $row['image'] ?? '';
                            $initial     = mb_substr(trim($uName), 0, 1);

                            $pName = $isArabic
                                ? ($row['name_ar'] ?? $row['name_en'] ?? ('منتج #' . $row['wish_product_id']))
                                : ($row['name_en'] ?? $row['name_ar'] ?? ('Product #' . $row['wish_product_id']));
                            $pImg  = !empty($row['product_image']) ? '../' . ltrim($row['product_image'], '/') : '';
                            $date  = !empty($row['liked_at']) ? date('Y-m-d H:i', strtotime($row['liked_at'])) : '-';
                        ?>
                        <tr>
                            <!-- بيانات المستخدم -->
                            <td>
                                <div class="entity-cell">
                                    <?php if (!empty($uAvatar)): ?>
                                        <img src="../<?= h(ltrim($uAvatar, '/')) ?>" class="user-circle-img" alt="">
                                    <?php else: ?>
                                        <span class="user-circle-fallback <?= $isGuest ? 'guest' : '' ?>">
                                            <?= h($initial) ?>
                                        </span>
                                    <?php endif; ?>
                                    <div class="entity-meta">
                                        <strong><?= h($uName) ?></strong>
                                        <small><?= h($uEmail) ?></small>
                                    </div>
                                </div>
                            </td>

                            <!-- بيانات المنتج -->
                            <td>
                                <div class="entity-cell">
                                    <?php if ($pImg !== ''): ?>
                                        <img src="<?= h($pImg) ?>" class="product-square-img" alt="">
                                    <?php endif; ?>
                                    <div class="entity-meta">
                                        <strong><?= h($pName) ?></strong>
                                        <small>#<?= (int)$row['wish_product_id'] ?></small>
                                    </div>
                                </div>
                            </td>

                            <!-- السعر -->
                            <td class="price-cell">₪<?= number_format((float)($row['price'] ?? 0), 2) ?></td>

                            <!-- التاريخ -->
                            <td class="date-cell"><?= h($date) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    if (window.lucide) {
        lucide.createIcons();
    }
</script>
</body>
</html>