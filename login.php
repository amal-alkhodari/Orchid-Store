
<?php
session_start();

$current_lang = $_SESSION['lang'] ?? 'ar';

if (!in_array($current_lang, ['ar', 'en'], true)) {
    $current_lang = 'ar';
}

$_SESSION['lang'] = $current_lang;

require_once "languages/$current_lang.php";
require_once 'db.php';

$isArabic = $current_lang === 'ar';
$dir = $isArabic ? 'rtl' : 'ltr';

$error = '';
$email = $_COOKIE['orchid_user_email'] ?? '';
$showReactivateButton = (($_GET['account_status'] ?? '') === 'frozen');

if (!is_string($email)) {
    $email = '';
}

// التحقق من حالة الجلسة الحالية
if (isset($_SESSION['user_id'])) {
    $check = $pdo->prepare(
        'SELECT account_status FROM users WHERE id = ? LIMIT 1'
    );
    $check->execute([$_SESSION['user_id']]);
    $currentUser = $check->fetch(PDO::FETCH_ASSOC);

    if ($currentUser && $currentUser['account_status'] === 'active') {
        header('Location: index.php');
        exit;
    }

    session_unset();
    session_destroy();
    session_start();
}

// رسائل حالة الحساب
$accountStatus = $_GET['account_status'] ?? '';

if ($accountStatus === 'frozen') {
    $error = $isArabic
        ? 'حسابك مجمّد مؤقتاً. أدخلي بياناتك واختاري إعادة تفعيل الحساب.'
        : 'Your account is frozen. Enter your details and choose to reactivate it.';
} elseif ($accountStatus === 'deleted') {
    $error = $isArabic
        ? 'هذا الحساب محذوف ولا يمكن تسجيل الدخول إليه.'
        : 'This account has been deleted and cannot be accessed.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $reactivate = isset($_POST['reactivate_account']);

    if ($email === '' || $password === '') {
        $error = $isArabic
            ? 'يرجى إدخال البريد الإلكتروني وكلمة المرور.'
            : 'Please enter your email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = $isArabic
            ? 'يرجى إدخال بريد إلكتروني صحيح.'
            : 'Please enter a valid email address.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id, full_name, email, password_hash,
                    phone, profile_image, account_status
             FROM users
             WHERE email = ?
             LIMIT 1'
        );

        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $error = $isArabic
                ? 'البريد الإلكتروني أو كلمة المرور غير صحيحة.'
                : 'Incorrect email or password.';
        } elseif ($user['account_status'] === 'deleted') {
            $error = $isArabic
                ? 'هذا الحساب محذوف ولا يمكن استعادته.'
                : 'This account has been deleted and cannot be restored.';
        } elseif ($user['account_status'] === 'frozen' && !$reactivate) {

    $showReactivateButton = true;

    $error = $isArabic
        ? 'حسابك مجمّد. اضغطي على زر إعادة تفعيل الحساب للمتابعة.'
        : 'Your account is frozen. Choose Reactivate Account to continue.';
        } else {
            if ($user['account_status'] === 'frozen' && $reactivate) {
                $activate = $pdo->prepare(
                    "UPDATE users SET account_status = 'active' WHERE id = ?"
                );
                $activate->execute([$user['id']]);
            }

            session_regenerate_id(true);

            setcookie(
                'orchid_user_email',
                $email,
                [
                    'expires' => time() + (30 * 24 * 60 * 60),
                    'path' => '/',
                    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]
            );

            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_phone'] = $user['phone'] ?? '';
            $_SESSION['user_image'] = $user['profile_image'] ?? '';

            header('Location: index.php');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $isArabic ? 'ar' : 'en' ?>" dir="<?= $dir ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isArabic ? 'تسجيل الدخول' : 'Log In' ?> | Orchid Boutique</title>
    <!-- استدعاء ملفات التنسيق وأيقونات FontAwesome للتصميم الحديث -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="stylesheet" href="https://cloudflare.com">
</head>
<body>

<div class="login-page">
    <div class="container">
        
        <!-- زر الإغلاق العلوي -->
        <a href="index.php" class="close-btn"><i class="fa-solid fa-xmark"></i></a>

        <!-- القسم الأول: نموذج تسجيل الدخول -->
        <main class="form-section">
            <div class="login-brand">Orchid Boutique</div>
            
            <div class="form-wrapper">
                <h1 class="login-title">
                    <?= $isArabic ? 'تسجيل الدخول' : 'Log In' ?>
                </h1>
                <p class="login-subtitle">
                    <?= $isArabic ? 'أهلًا بعودتكِ إلى Orchid Boutique' : 'Welcome back to Orchid Boutique' ?>
                </p>

                <?php if ($error !== ''): ?>
                    <div class="login-error" role="alert">
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form class="login-form" method="POST" action="login.php">
                    
                    <div class="input-group">
                        <label for="email"><?= $isArabic ? 'البريد الإلكتروني' : 'Email Address' ?></label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                            autocomplete="email"
                            maxlength="150"
                            required
                        >
                    </div>

                    <div class="input-group">
                        <label for="password"><?= $isArabic ? 'كلمة المرور' : 'Password' ?></label>
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

                    <!-- روابط التحكم الفوقية: تذكرني ونسيت كلمة المرور -->
                    <div class="login-meta">
                        <div class="remember-me">
                            <label for="remember_me">
                                <input type="checkbox" id="remember_me" name="remember_me" value="1">
                                <span><?= $isArabic ? 'تذكّرني' : 'Remember me' ?></span>
                            </label>
                        </div>
                        <div class="forgot-password-link">
                            <a href="forgot-password.php">
                                <?= $isArabic ? 'نسيتِ كلمة المرور؟' : 'Forgot your password?' ?>
                            </a>
                        </div>
                    </div>
<?php if ($showReactivateButton): ?>
    <button
        type="submit"
        name="reactivate_account"
        value="1"
        class="login-button"
    >
        <?= $isArabic ? 'إعادة تفعيل الحساب' : 'Reactivate Account' ?>
    </button>

<?php else: ?>

    <button type="submit" class="login-button">
        <?= $isArabic ? 'دخول' : 'Log In' ?>
    </button>

<?php endif; ?>

                <!-- أزرار الدخول السريع عبر مواقع التواصل السوشيال ميديا كأيقونات مظهرية تناسب الهيكل -->
                <div class="social-login">
                    <button type="button" class="social-btn"><i class="fa-brands fa-apple"></i> Apple</button>
                    <button type="button" class="social-btn"><i class="fa-brands fa-google"></i> Google</button>
                </div>
            </div>

            <div class="login-footer">
                <span>
                    <?= $isArabic ? 'ليس لديكِ حساب؟' : "Don't have an account?" ?>
                    <a href="register.php"><?= $isArabic ? 'إنشاء حساب' : 'Create Account' ?></a>
                </span>
            </div>
        </main>
          <!-- القسم الثاني: صورة البوتيك والبطاقات العائمة الجمالية للملابس -->
        <div class="image-section" style="position: relative; overflow: hidden; flex: 1; background-color: #f3ede2; margin: 16px; border-radius: 20px; min-width: 350px;">
            
            <!-- تم تعديل المسار هنا بحذف assets ليقرأ من المجلد الحقيقي مباشرة -->
            <img src="<?php echo $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']); ?>/images/products/clothing.jpg" alt="Boutique" style="width: 100%; height: 100%; object-fit: cover; position: absolute; top: 0; left: 0; z-index: 1;">

            <!-- بطاقة كود الخصم العائمة العلوية -->
            <div class="floating-card top-card" style="z-index: 2;">
                <div class="discount-badge"><i class="fa-solid fa-tags"></i></div>
                <div>
                    <h4><?= $isArabic ? 'خصم الموسم الجديد' : 'New Season Sale' ?></h4>
                    <p><?= $isArabic ? 'استخدمي كود:' : 'Use Code:' ?> <b>ORCHID15</b></p>
                </div>
            </div>

            <!-- بطاقة التقييم العائمة السفلية -->
            <div class="floating-card bottom-card" style="z-index: 2;">
                <p class="review-text">"<?= $isArabic ? 'خامات رائعة وتوصيل سريع جداً!' : 'Amazing quality & fast delivery!' ?>"</p>
                <div class="stars">
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star"></i>
                </div>
            </div>

        </div>

<script src="js/login.js"></script>
</body>
</html>  