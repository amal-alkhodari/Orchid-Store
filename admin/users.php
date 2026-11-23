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

// معالجة حذف مستخدم عند الضغط على زر الحذف
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $delId = (int)$_POST['delete_user_id'];
    if ($delId > 0) {
        try {
            $delStmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $delStmt->execute([$delId]);
            header("Location: users.php?deleted=1");
            exit;
        } catch (PDOException $e) {}
    }
}

// البحث عن مستخدم بالاسم أو الإيميل
$search = trim($_GET['search'] ?? '');
$users = [];
try {
    if ($search !== '') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE name LIKE ? OR email LIKE ? ORDER BY id DESC");
        $like = "%{$search}%";
        $stmt->execute([$like, $like]);
    } else {
        $stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
    }
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
}
?>
<!DOCTYPE html>
<html lang="<?= h($lang) ?>" dir="<?= h($dir) ?>" class="notranslate" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isArabic ? 'إدارة المستخدمين | Orchid Admin' : 'Users Management | Orchid Admin' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="../assets/css/admin-users.css?v=2">
</head>
<body>

<div class="users-shell">
    <!-- الشريط العلوي -->
    <header class="users-topbar">
        <div class="users-title">
            <h1><?= $isArabic ? 'إدارة المستخدمين' : 'Users Management' ?></h1>
            <p><?= $isArabic ? 'متابعة حسابات الزبائن المسجلين وتفاصيلهم وصورهم الشخصية.' : 'Manage and monitor all registered user accounts.' ?></p>
        </div>

        <div class="users-nav-btns">
            <a href="dashboard.php" class="pill-nav-btn">
                <i data-lucide="layout-dashboard"></i>
                <span><?= $isArabic ? 'لوحة التحكم' : 'Dashboard' ?></span>
            </a>
            <a href="orders.php" class="pill-nav-btn">
                <i data-lucide="shopping-bag"></i>
                <span><?= $isArabic ? 'الطلبات' : 'Orders' ?></span>
            </a>
        </div>
    </header>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="users-alert-success">
            <?= $isArabic ? 'تم حذف حساب المستخدم بنجاح.' : 'User deleted successfully.' ?>
        </div>
    <?php endif; ?>

    <!-- شريط البحث والعدد -->
    <div class="users-toolbar">
        <form method="GET" class="search-box-form">
            <input type="text" name="search" value="<?= h($search) ?>" placeholder="<?= $isArabic ? 'ابحثي بالاسم أو البريد الإلكتروني...' : 'Search users by name, email...' ?>">
            <button type="submit"><?= $isArabic ? 'بحث' : 'Search' ?></button>
            <?php if ($search !== ''): ?>
                <a href="users.php" class="pill-nav-btn search-reset-btn"><?= $isArabic ? 'الكل' : 'All' ?></a>
            <?php endif; ?>
        </form>

        <span class="users-count-badge">
            <?= $isArabic ? 'إجمالي المسجلين: ' . count($users) : 'Total Users: ' . count($users) ?>
        </span>
    </div>

    <!-- جدول المستخدمين -->
    <div class="users-table-wrap">
        <table class="users-table">
            <thead>
                <tr>
                    <th><?= $isArabic ? 'المستخدم' : 'USER' ?></th>
                    <th><?= $isArabic ? 'البريد الإلكتروني' : 'EMAIL' ?></th>
                    <th><?= $isArabic ? 'الدور' : 'ROLE' ?></th>
                    <th><?= $isArabic ? 'الحالة' : 'STATUS' ?></th>
                    <th><?= $isArabic ? 'تاريخ الانضمام' : 'JOINED' ?></th>
                    <th><?= $isArabic ? 'إجراءات' : 'ACTIONS' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" class="users-empty-cell">
                            <?= $isArabic ? 'لا يوجد مستخدمون مسجلون حالياً.' : 'No registered users found.' ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <?php
                            $uName   = $u['name'] ?? $u['full_name'] ?? 'مستخدم أوركيد';
                            $uEmail  = $u['email'] ?? '-';
                            $uAvatar = $u['avatar'] ?? $u['profile_image'] ?? '';
                            $uJoined = !empty($u['created_at']) ? date('Y-m-d', strtotime($u['created_at'])) : '-';
                            $initial = mb_substr(trim($uName), 0, 1);
                        ?>
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <?php if (!empty($uAvatar)): ?>
                                        <img src="../<?= h(ltrim($uAvatar, '/')) ?>" class="user-avatar-img" alt="">
                                    <?php else: ?>
                                        <span class="user-avatar-fallback"><?= h($initial) ?></span>
                                    <?php endif; ?>
                                    <div class="user-meta">
                                        <strong><?= h($uName) ?></strong>
                                        <small>ID #<?= (int)$u['id'] ?></small>
                                    </div>
                                </div>
                            </td>
                            <td class="user-email-cell"><?= h($uEmail) ?></td>
                            <td><span class="role-pill"><?= $isArabic ? 'زبونة' : 'Customer' ?></span></td>
                            <td>
                                <span class="status-pill-active">
                                    <span class="status-dot"></span>
                                    <?= $isArabic ? 'نشط' : 'Active' ?>
                                </span>
                            </td>
                            <td class="user-date-cell"><?= h($uJoined) ?></td>
                            <td>
                                <form method="POST" class="del-user-form" onsubmit="return confirm('<?= $isArabic ? 'هل أنتِ متأكدة من حذف هذا المستخدم؟' : 'Delete this user?' ?>')">
                                    <input type="hidden" name="delete_user_id" value="<?= (int)$u['id'] ?>">
                                    <button type="submit" class="del-user-btn" title="<?= $isArabic ? 'حذف المستخدم' : 'Delete' ?>">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>lucide.createIcons();</script>
</body>
</html>