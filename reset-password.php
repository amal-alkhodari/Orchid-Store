
<?php
session_start();

require_once __DIR__ . '/db.php';

// تحديد اللغة
$current_lang = $_SESSION['lang'] ?? 'ar';

if (!in_array($current_lang, ['ar', 'en'], true)) {
    $current_lang = 'ar';
}

$isArabic = $current_lang === 'ar';
$dir = $isArabic ? 'rtl' : 'ltr';

// تهيئة المتغيرات
$error = '';
$success = '';
$completed = false;
$resetEmail = $_SESSION['password_reset_email'] ?? '';

if (!is_string($resetEmail) || $resetEmail === '') {
    $resetEmail = '';
    $error = $isArabic
        ? 'جلسة استعادة كلمة المرور غير موجودة. يرجى طلب رمز جديد.'
        : 'Password reset session not found. Please request a new code.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $resetEmail !== '') {

    $code = trim($_POST['code'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!preg_match('/^[0-9]{6}$/', $code)) {
        $error = $isArabic
            ? 'يرجى إدخال رمز تحقق صحيح مكون من 6 أرقام.'
            : 'Please enter a valid 6-digit verification code.';

    } elseif (strlen($password) < 8) {
        $error = $isArabic
            ? 'كلمة المرور يجب ألا تقل عن 8 أحرف.'
            : 'Password must be at least 8 characters.';

    } elseif ($password !== $confirmPassword) {
        $error = $isArabic
            ? 'كلمتا المرور غير متطابقتين.'
            : 'Passwords do not match.';

    } else {

        try {
            // جلب المستخدم ورمز الاستعادة
            $stmt = $pdo->prepare(
                'SELECT u.id, r.code_hash, r.expires_at, r.attempts
                 FROM users u
                 INNER JOIN password_reset_codes r
                 ON u.id = r.user_id
                 WHERE u.email = ?
                 LIMIT 1'
            );

            $stmt->execute([$resetEmail]);
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reset) {
                $error = $isArabic
                    ? 'رمز الاستعادة غير موجود. اطلبي رمزًا جديدًا.'
                    : 'Reset code not found. Please request a new code.';

            } elseif ((int)$reset['attempts'] >= 5) {
                $error = $isArabic
                    ? 'تم تجاوز عدد المحاولات المسموح. اطلبي رمزًا جديدًا.'
                    : 'Maximum attempts exceeded. Please request a new code.';

            } elseif (strtotime($reset['expires_at'] . ' UTC') <= time()) {
                $error = $isArabic
                    ? 'انتهت صلاحية الرمز. اطلبي رمزًا جديدًا.'
                    : 'The code has expired. Please request a new one.';

            } elseif (!hash_equals(
                $reset['code_hash'],
                hash('sha256', $code)
            )) {

                // زيادة عدد المحاولات
                $updateAttempts = $pdo->prepare(
                    'UPDATE password_reset_codes
                     SET attempts = attempts + 1
                     WHERE user_id = ?'
                );

                $updateAttempts->execute([$reset['id']]);

                $error = $isArabic
                    ? 'رمز التحقق غير صحيح.'
                    : 'Incorrect verification code.';

            } else {

                // تشفير كلمة المرور الجديدة
                $newPasswordHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $pdo->beginTransaction();

                try {
                    // تحديث كلمة مرور المستخدم
                    $updatePassword = $pdo->prepare(
                        'UPDATE users
                         SET password_hash = ?
                         WHERE id = ?'
                    );

                    $updatePassword->execute([
                        $newPasswordHash,
                        $reset['id']
                    ]);

                    // حذف رمز الاستعادة بعد النجاح
                    $deleteCode = $pdo->prepare(
                        'DELETE FROM password_reset_codes
                         WHERE user_id = ?'
                    );

                    $deleteCode->execute([$reset['id']]);

                    $pdo->commit();

                    unset($_SESSION['password_reset_email']);

                    $resetEmail = '';
                    $completed = true;

                    $success = $isArabic
                        ? 'تم تغيير كلمة المرور بنجاح! يمكنك الآن تسجيل الدخول.'
                        : 'Your password has been changed successfully! You can now log in.';

                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    throw $e;
                }
            }

        } catch (Throwable $e) {
            error_log('Orchid reset-password error: ' . $e->getMessage());

            $error = $isArabic
                ? 'حدث خطأ أثناء تغيير كلمة المرور. يرجى المحاولة مرة أخرى.'
                : 'An error occurred while resetting your password. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $isArabic ? 'ar' : 'en' ?>" dir="<?= $dir ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= $isArabic ? 'إعادة تعيين كلمة المرور' : 'Reset Password' ?>
        | Orchid Boutique
    </title>
    <link rel="stylesheet" href="assets/css/reset-password.css">
</head>
<body>

<div class="split-wrapper">

    <!-- الجانب الأيمن: نموذج إعادة تعيين كلمة المرور -->
    <section class="form-side">
        <div class="top-bar">
            <a href="index.php" class="brand-title">Orchid Boutique</a>
            <a class="form-back" href="login.php">
                <?= $isArabic ? '← تسجيل الدخول' : 'Back to Login →' ?>
            </a>
        </div>

        <div class="form-header">
            <h1>
                <?= $isArabic ? 'إعادة تعيين كلمة المرور' : 'Reset Your Password' ?>
            </h1>
            <p>
                <?= $isArabic
                    ? 'أدخلي رمز التحقق الذي وصلكِ بالبريد الإلكتروني، ثم اختاري كلمة مرور جديدة.'
                    : 'Enter the code sent to your email, then choose a new password.' ?>
            </p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="message error" role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="message success" role="status">
                <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (!$completed && is_string($resetEmail) && $resetEmail !== ''): ?>
            <form method="POST" action="reset-password.php">
                <div class="fields-stack">
                    <div class="field-group">
                        <label for="code">
                            <?= $isArabic ? 'رمز التحقق (6 أرقام)' : 'Verification Code (6 digits)' ?>
                        </label>
                        <input
                            type="text"
                            id="code"
                            name="code"
                            inputmode="numeric"
                            pattern="[0-9]{6}"
                            maxlength="6"
                            placeholder="****"
                            autocomplete="one-time-code"
                            required
                        >
                    </div>

<div class="field-group">
    <label for="password">
        <?= $isArabic ? 'كلمة المرور الجديدة' : 'New Password' ?>
    </label>

    <div class="password-wrapper">
        <input
            type="password"
            id="password"
            name="password"
            minlength="8"
            placeholder="••••••••"
            autocomplete="new-password"
            required
        >

        <button
            type="button"
            class="toggle-password"
            onclick="togglePassword('password', this)"
            aria-label="<?= $isArabic ? 'إظهار كلمة المرور' : 'Show password' ?>"
        >
            <svg viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 3l18 18"/>
                <path d="M10.6 10.6a2 2 0 002.8 2.8"/>
                <path d="M9.9 5.2A10.8 10.8 0 0121 12a11 11 0 01-3.2 4.3"/>
                <path d="M6.2 6.2A11 11 0 003 12s3.3 7 9 7a9.8 9.8 0 004-.8"/>
            </svg>
        </button>
    </div>
</div>

<div class="field-group">
    <label for="confirm_password">
        <?= $isArabic ? 'تأكيد كلمة المرور' : 'Confirm Password' ?>
    </label>

    <div class="password-wrapper">
        <input
            type="password"
            id="confirm_password"
            name="confirm_password"
            minlength="8"
            placeholder="••••••••"
            autocomplete="new-password"
            required
        >

        <button
            type="button"
            class="toggle-password"
            onclick="togglePassword('confirm_password', this)"
            aria-label="<?= $isArabic ? 'إظهار كلمة المرور' : 'Show password' ?>"
        >
            <svg viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 3l18 18"/>
                <path d="M10.6 10.6a2 2 0 002.8 2.8"/>
                <path d="M9.9 5.2A10.8 10.8 0 0121 12a11 11 0 01-3.2 4.3"/>
                <path d="M6.2 6.2A11 11 0 003 12s3.3 7 9 7a9.8 9.8 0 004-.8"/>
            </svg>
        </button>
    </div>
</div>

<button type="submit" class="submit-btn">
    <?= $isArabic ? 'تغيير كلمة المرور' : 'Change Password' ?>
</button>

</form>
<?php elseif (!$completed): ?>
    <a class="submit-btn link-btn" href="forgot-password.php">
        <?= $isArabic ? 'اطلبي رمز استعادة جديدًا' : 'Request a New Reset Code' ?>
    </a>
<?php endif; ?>

<div class="form-footer-note">
    <a href="login.php">
        <?= $isArabic ? 'العودة إلى تسجيل الدخول' : 'Back to Login' ?>
    </a>
</div>
</section>

<!-- الجانب الأيسر: البطاقة الزجاجية مع الصورة -->
<aside class="visual-side">
    <div class="visual-top-dot"></div>

    <div class="glass-center-card">
        <div class="glass-badge-circle">🔒</div>
        <h2><?= $isArabic ? 'حماية وتأمين الحساب' : 'Account Security' ?></h2>
        <p>
            <?= $isArabic
                ? 'اختاري كلمة مرور قوية مكونة من 8 أحرف على الأقل لحماية بياناتكِ وطلباتكِ'
                : 'Choose a strong password of at least 8 characters to protect your account' ?>
        </p>
    </div>

    <div class="visual-bottom-badge">
        <strong>
            <?= $isArabic ? '"تسوق آمن وتجربة فريدة"' : '"Safe shopping & unique experience"' ?>
        </strong>
    </div>
</aside>

</div>

<script>
function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);

    if (input.type === 'password') {
        input.type = 'text';

        button.innerHTML = `
            <svg viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round">
                <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>`;

        button.setAttribute(
            'aria-label',
            '<?= $isArabic ? "إخفاء كلمة المرور" : "Hide password" ?>'
        );

    } else {
        input.type = 'password';

        button.innerHTML = `
            <svg viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 3l18 18"/>
                <path d="M10.6 10.6a2 2 0 002.8 2.8"/>
                <path d="M9.9 5.2A10.8 10.8 0 0121 12a11 11 0 01-3.2 4.3"/>
                <path d="M6.2 6.2A11 11 0 003 12s3.3 7 9 7a9.8 9.8 0 004-.8"/>
            </svg>`;

        button.setAttribute(
            'aria-label',
            '<?= $isArabic ? "إظهار كلمة المرور" : "Show password" ?>'
        );
    }
}
</script>

</body>
</html>
