
<?php
session_start();

require_once __DIR__ . '/../db.php';

$lang = $_SESSION['lang'] ?? 'ar';

if (!in_array($lang, ['ar', 'en'], true)) {
    $lang = 'ar';
}

$isArabic = $lang === 'ar';
$dir = $isArabic ? 'rtl' : 'ltr';

$text = [
    'ar' => [
        'title' => 'نسيت كلمة المرور',
        'brand' => 'Orchid Boutique',
        'heading' => 'نسيتِ كلمة المرور؟',
        'description' => 'أدخلي بريد الاستعادة المسجّل لدى الأدمن لإرسال رابط تغيير كلمة المرور.',
        'email' => 'بريد الاستعادة',
        'submit' => 'إرسال رابط الاستعادة',
        'back' => 'العودة إلى تسجيل الدخول',
        'success' => 'إذا كان البريد مسجّلًا، فستصلك رسالة تحتوي على رابط استعادة كلمة المرور. افحصي أيضًا مجلد الرسائل غير المرغوب فيها.',
        'invalid' => 'أدخلي بريدًا إلكترونيًا صحيحًا.',
        'csrf' => 'انتهت صلاحية الطلب. حدّثي الصفحة وحاولي مرة أخرى.',
        'error' => 'تعذّر إرسال رابط الاستعادة الآن. تحققي من إعدادات البريد وقاعدة البيانات.',
    ],
    'en' => [
        'title' => 'Forgot Password',
        'brand' => 'Orchid Boutique',
        'heading' => 'Forgot your password?',
        'description' => 'Enter the recovery email registered to your administrator account.',
        'email' => 'Recovery Email',
        'submit' => 'Send Reset Link',
        'back' => 'Back to Login',
        'success' => 'If this email is registered, you will receive a password reset link. Please check your spam folder too.',
        'invalid' => 'Please enter a valid email address.',
        'csrf' => 'Your request expired. Refresh the page and try again.',
        'error' => 'Could not send the reset link. Please check the mail and database settings.',
    ],
];

$t = $text[$lang];
$message = '';
$messageType = '';
$email = '';

if (empty($_SESSION['admin_forgot_csrf'])) {
    $_SESSION['admin_forgot_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $postedCsrf = $_POST['csrf_token'] ?? '';
    $savedCsrf = $_SESSION['admin_forgot_csrf'] ?? '';

    if (
        !is_string($postedCsrf) ||
        !is_string($savedCsrf) ||
        $savedCsrf === '' ||
        !hash_equals($savedCsrf, $postedCsrf)
    ) {
        $message = $t['csrf'];
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = $t['invalid'];
        $messageType = 'error';
    } else {
        try {
            // Find an administrator with this registered recovery email.
            $stmt = $pdo->prepare(
                'SELECT id, username, recovery_email
                 FROM admin_users
                 WHERE recovery_email = ?
                 LIMIT 1'
            );

            $stmt->execute([$email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin) {
                // Generate a cryptographically secure reset token.
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $adminId = (int) $admin['id'];

                // Invalidate previous reset links for this administrator.
                $invalidate = $pdo->prepare(
                    "UPDATE password_resets
                     SET used_at = NOW()
                     WHERE account_type = 'admin'
                       AND account_id = ?
                       AND used_at IS NULL"
                );

                $invalidate->execute([$adminId]);

                // Save only the hash; the raw token goes to the email.
                $insert = $pdo->prepare(
                    "INSERT INTO password_resets
                        (account_type, account_id, token_hash, expires_at, used_at)
                     VALUES
                        ('admin', ?, ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 1 HOUR), NULL)"
                );

                $insert->execute([$adminId, $tokenHash]);

                try {
                    require_once __DIR__ . '/../vendor/autoload.php';

                    $mailConfig = require __DIR__ . '/../mail_config.php';

                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

                    $mail->isSMTP();
                    $mail->Host = $mailConfig['host'];
                    $mail->SMTPAuth = true;
                    $mail->Username = $mailConfig['username'];
                    $mail->Password = $mailConfig['password'];
                    $mail->SMTPSecure =
                        \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = (int) $mailConfig['port'];
                    $mail->CharSet = 'UTF-8';

                    $mail->setFrom(
                        $mailConfig['from_email'],
                        $mailConfig['from_name']
                    );

                    $mail->addAddress($admin['recovery_email']);
                    $mail->isHTML(true);

                    // Local XAMPP reset URL.
                    $resetUrl =
                      'https://orchid-store.likesyou.org/admin/reset_admin_password.php?token='
                          . urlencode($token);

                    $safeUrl = htmlspecialchars(
                        $resetUrl,
                        ENT_QUOTES,
                        'UTF-8'
                    );

                    $mail->Subject = $isArabic
                        ? 'رابط استعادة كلمة المرور - Orchid Boutique'
                        : 'Password Reset - Orchid Boutique';

                    $mail->Body = $isArabic
                        ? '<div style="font-family:Arial,sans-serif;direction:rtl;text-align:center;padding:25px">
                               <h2 style="color:#652e70">Orchid Boutique</h2>
                               <p>وصلنا طلب لإعادة تعيين كلمة مرور حساب الأدمن.</p>
                               <p>اضغطي على الزر أدناه لتعيين كلمة مرور جديدة:</p>
                               <p style="margin:30px 0">
                                   <a href="' . $safeUrl . '"
                                      style="display:inline-block;padding:14px 24px;background:#652e70;color:#fff;text-decoration:none;border-radius:9px">
                                      إعادة تعيين كلمة المرور
                                   </a>
                               </p>
                               <p>ينتهي الرابط بعد ساعة واحدة، ويمكن استخدامه مرة واحدة فقط.</p>
                               <p>إذا لم تطلبي هذا الرابط، فتجاهلي الرسالة.</p>
                           </div>'
                        : '<div style="font-family:Arial,sans-serif;text-align:center;padding:25px">
                               <h2 style="color:#652e70">Orchid Boutique</h2>
                               <p>We received a request to reset your administrator password.</p>
                               <p>Click below to choose a new password:</p>
                               <p style="margin:30px 0">
                                   <a href="' . $safeUrl . '"
                                      style="display:inline-block;padding:14px 24px;background:#652e70;color:#fff;text-decoration:none;border-radius:9px">
                                      Reset Password
                                   </a>
                               </p>
                               <p>This link expires in one hour and can only be used once.</p>
                               <p>If you did not request it, ignore this email.</p>
                           </div>';

                    $mail->AltBody = $isArabic
                        ? "رابط استعادة كلمة المرور: $resetUrl\nينتهي الرابط بعد ساعة واحدة."
                        : "Password reset link: $resetUrl\nThis link expires in one hour.";

                    $mail->send();

                } catch (Throwable $mailError) {
                    // Invalidate the link if the email could not be sent.
                    $delete = $pdo->prepare(
                        "UPDATE password_resets
                         SET used_at = UTC_TIMESTAMP()
                         WHERE account_type = 'admin'
                           AND account_id = ?
                           AND token_hash = ?
                           AND used_at IS NULL"
                    );

                    $delete->execute([$adminId, $tokenHash]);

                    throw $mailError;
                }
            }

            // Show the same message whether the email exists or not.
            $message = $t['success'];
            $messageType = 'success';

        } catch (Throwable $e) {
            error_log(
                'Admin forgot-password error: ' . $e->getMessage()
            );

            $message = $t['error'];
            $messageType = 'error';
        }
    }

    $_SESSION['admin_forgot_csrf'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="<?= $isArabic ? 'ar' : 'en' ?>" dir="<?= $dir ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8') ?> | Orchid Boutique</title>

<link rel="stylesheet" href="/assets/css/forgot-password.css?v=2">
    </head>
<body>

<div class="split-wrapper">

    <section class="form-side">

        <div class="top-bar">
            <a href="../index.php" class="brand-title">Orchid Boutique</a>

            <a class="form-back" href="login.php">
                <?= $isArabic ? '← تسجيل الدخول' : 'Back to Login →' ?>
            </a>
        </div>

        <div class="form-header">
            <h1><?= htmlspecialchars($t['heading'], ENT_QUOTES, 'UTF-8') ?></h1>

            <p>
                <?= htmlspecialchars($t['description'], ENT_QUOTES, 'UTF-8') ?>
            </p>
        </div>

        <?php if ($message !== ''): ?>
            <div class="message <?= $messageType === 'error' ? 'error' : 'success' ?>"
                 role="status">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="forgot-password.php">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['admin_forgot_csrf'], ENT_QUOTES, 'UTF-8') ?>"
            >

            <div class="fields-stack">
                <div class="field-group">

                    <label for="email">
                        <?= htmlspecialchars($t['email'], ENT_QUOTES, 'UTF-8') ?>
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="name@example.com"
                        maxlength="190"
                        autocomplete="email"
                        required
                    >

                </div>
            </div>

            <button type="submit" class="submit-btn">
                <?= htmlspecialchars($t['submit'], ENT_QUOTES, 'UTF-8') ?>
            </button>

        </form>

        <div class="form-footer-note">
            <a href="login.php">
                <?= htmlspecialchars($t['back'], ENT_QUOTES, 'UTF-8') ?>
            </a>
        </div>

    </section>

    <aside class="visual-side">

        <div class="visual-top-dot"></div>

        <div class="glass-center-card">

            <div class="glass-badge-circle">✉️</div>

            <h2>
                <?= $isArabic ? 'استعادة حساب الأدمن' : 'Admin Account Recovery' ?>
            </h2>

            <p>
                <?= $isArabic
                    ? 'سنرسل إليك رابطًا آمنًا إلى بريد الاستعادة المسجل لتعيين كلمة مرور جديدة لحساب الأدمن.'
                    : 'We will send a secure reset link to your registered recovery email so you can create a new administrator password.' ?>
            </p>

        </div>

        <div class="visual-bottom-badge">
            <strong>
                <?= $isArabic
                    ? '"حماية حسابك تبدأ من هنا"'
                    : '"Your account security starts here"' ?>
            </strong>
        </div>

    </aside>

</div>

</body>
</html>
