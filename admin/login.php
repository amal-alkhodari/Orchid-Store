<?php

session_start();

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../remember_me.php';

// Restore the admin session if a valid remember token exists.
if (
    !isset($_SESSION['admin_id']) &&
    restoreRememberedLogin($pdo, 'admin')
) {
    header('Location: dashboard.php');
    exit;
}


// Use the language already saved in the session.
$lang = $_SESSION['lang'] ?? 'ar';

if (!in_array($lang, ['ar', 'en'], true)) {
    $lang = 'ar';
}

$isArabic = $lang === 'ar';

// If the administrator is already logged in, go to the dashboard.
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Text in Arabic and English.
$text = [
    'ar' => [
        'title' => 'تسجيل دخول الأدمن',
        'brand' => 'Orchid Boutique',
        'heading' => 'تسجيل دخول المسؤول',
        'description' => 'أهلًا بكِ في لوحة إدارة المتجر',
        'username' => 'اسم المستخدم',
        'password' => 'كلمة المرور',
        'submit' => 'تسجيل الدخول',
        'invalid' => 'اسم المستخدم أو كلمة المرور غير صحيحة.',
        'csrf_error' => 'انتهت صلاحية الطلب. حدّثي الصفحة وحاولي مرة أخرى.',
    ],
    'en' => [
        'title' => 'Admin Login',
        'brand' => 'Orchid Boutique',
        'heading' => 'Administrator Login',
        'description' => 'Welcome to your store dashboard',
        'username' => 'Username',
        'password' => 'Password',
        'submit' => 'Log In',
        'invalid' => 'The username or password is incorrect.',
        'csrf_error' => 'Your request expired. Refresh the page and try again.',
    ],
];

$t = $text[$lang];

$username = $_COOKIE['orchid_admin_username'] ?? '';

if (!is_string($username)) {
    $username = '';
}

$message = '';
// Create a CSRF token for the login form.
if (empty($_SESSION['admin_login_csrf'])) {
    $_SESSION['admin_login_csrf'] = bin2hex(random_bytes(32));
}

// Process the login form.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    $savedToken = $_SESSION['admin_login_csrf'] ?? '';

    if (
        !is_string($postedToken) ||
        !is_string($savedToken) ||
        $savedToken === '' ||
        !hash_equals($savedToken, $postedToken)
    ) {
        $message = $t['csrf_error'];
        $_SESSION['admin_login_csrf'] = bin2hex(random_bytes(32));
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare(
            'SELECT id, username, password_hash
             FROM admin_users
             WHERE username = :username
             LIMIT 1'
        );

        $stmt->execute(['username' => $username]);
        $admin = $stmt->fetch();

        if (
            $admin &&
            is_string($password) &&
            password_verify($password, $admin['password_hash'])
        ) {
            // Protect the session after successful login.
            session_regenerate_id(true);

            $_SESSION['admin_id'] = (int) $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            
setcookie(
    'orchid_admin_username',
    $admin['username'],
    [
        'expires' => time() + (30 * 24 * 60 * 60),
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]
);


if (($_POST['remember_me'] ?? '') === '1') {
    issueRememberToken($pdo, 'admin', (int) $admin['id'], 7);
} else {
    forgetRememberToken($pdo, 'admin');
}


            unset($_SESSION['admin_login_csrf']);

            header('Location: dashboard.php');
            exit;
        }

        $message = $t['invalid'];
        $_SESSION['admin_login_csrf'] = bin2hex(random_bytes(32));
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $isArabic ? 'ar' : 'en' ?>" dir="<?= $isArabic ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8') ?> | Orchid Boutique</title>
    <!-- استدعاء ملفات التنسيق الموحدة للبراند وأيقونات FontAwesome -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin-login.css">
    <link rel="stylesheet" href="https://cloudflare.com">
</head>

<body>
<div class="login-page">
    <div class="container">
        
        <!-- زر العودة العلوي -->
        <a href="../index.php" class="close-btn"><i class="fa-solid fa-xmark"></i></a>

        <!-- الشق الأول: نموذج تسجيل دخول الإدارة -->
        <main class="form-section">
            <div class="login-brand"><?= htmlspecialchars($t['brand'], ENT_QUOTES, 'UTF-8') ?></div>
            
            <div class="form-wrapper">
                <h1 class="login-title">
                    <?= htmlspecialchars($t['heading'], ENT_QUOTES, 'UTF-8') ?>
                </h1>
                <p class="login-subtitle">
                    <?= htmlspecialchars($t['description'], ENT_QUOTES, 'UTF-8') ?>
                </p>

                <?php if ($message !== ''): ?>
                    <div class="login-error" role="alert">
                        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form class="login-form" method="post" action="login.php">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION['admin_login_csrf'], ENT_QUOTES, 'UTF-8') ?>"
                    >
                    
                    <!-- حقل اسم المستخدم -->
                    <div class="input-group">
                        <label for="username"><?= htmlspecialchars($t['username'], ENT_QUOTES, 'UTF-8') ?></label>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>"
                            maxlength="50"
                            autocomplete="username"
                            required
                        >
                    </div>

                    <!-- حقل كلمة المرور -->
                    <div class="input-group">
                        <label for="password"><?= htmlspecialchars($t['password'], ENT_QUOTES, 'UTF-8') ?></label>
                        <div class="password-field">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                autocomplete="current-password"
                                required
                            >
                            <button
                                type="button"
                                id="togglePassword"
                                class="password-toggle"
                                aria-label="<?= $isArabic ? 'إظهار كلمة المرور' : 'Show password' ?>"
                                title="<?= $isArabic ? 'إظهار كلمة المرور' : 'Show password' ?>"
                            >
                                <i class="fa-regular fa-eye-slash"></i>
                            </button>
                        </div>
                    </div>

                    <!-- الروابط الفوقية والتحكم -->
                    <div class="login-meta">
                        <div class="remember-me">
                            <label for="remember_me">
                                <input type="checkbox" id="remember_me" name="remember_me" value="1">
                                <span><?= $isArabic ? 'تذكّرني' : 'Remember me' ?></span>
                            </label>
                        </div>
                        <div class="forgot-password-link">
                            <a href="forgot-password.php">
                                <?= $isArabic ? 'هل نسيتِ كلمة المرور؟' : 'Forgot your password?' ?>
                            </a>
                        </div>
                    </div>

                    <!-- زر الدخول العنابي الفخم الموحد -->
                    <button type="submit" class="login-button">
                        <?= htmlspecialchars($t['submit'], ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </form>
            </div>

            <!-- التذييل السفلي لإدارة النظام -->
            <div class="login-footer">
                <span>&copy; <?= date('Y') ?> Orchid Boutique - Control Panel</span>
            </div>
        </main>

        <!-- الشق الثاني: صورة البوتيك والبطاقات التفاعلية الجمالية لإدارة المتجر -->
        <div class="image-section" style="background: url('../images/products/clothing.jpg') center/cover no-repeat;">
            
            <!-- بطاقة إحصائيات عائمة علوية لتعبر عن لوحة التحكم الإدارية بشكل منسق -->
            <div class="floating-card top-card">
                <div class="discount-badge" style="background-color: #581d2c;"><i class="fa-solid fa-chart-line"></i></div>
                <div>
                    <h4><?= $isArabic ? 'إدارة المتجر الذكية' : 'Smart Admin Panel' ?></h4>
                    <p><?= $isArabic ? 'متابعة الطلبات المباشرة' : 'Live Order Tracking' ?></p>
                </div>
            </div>

            <!-- بطاقة حماية النظام العائمة السفلية -->
            <div class="floating-card bottom-card">
                <p class="review-text" style="font-weight: 600;">
                    <i class="fa-solid fa-shield-halved" style="color: #581d2c; margin-inline-end: 4px;"></i>
                    <?= $isArabic ? 'الوصول آمن ومحمي' : 'Secure Admin Access' ?>
                </p>
                <div class="stars" style="color: #6b7280; font-size: 10px;">
                    <span>SSL Encrypted Connection</span>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
// كود جافا سكريبت تفاعلي لتشغيل زر كشف وإخفاء كلمة المرور بسلاسة للأدمن
document.getElementById('togglePassword').addEventListener('click', function () {
    const passwordInput = document.getElementById('password');
    const icon = this.querySelector('i');
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    } else {
        passwordInput.type = 'password';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    }
});
</script>
</body>
</html>
