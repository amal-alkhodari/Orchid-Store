<?php
session_start();

require_once __DIR__ . '/db.php';

$current_lang = $_SESSION['lang'] ?? 'ar';

if (!in_array($current_lang, ['ar', 'en'], true)) {
    $current_lang = 'ar';
}

$isArabic = $current_lang === 'ar';
$dir = $isArabic ? 'rtl' : 'ltr';

$error = '';
$success = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = $isArabic
            ? 'يرجى إدخال بريد إلكتروني صحيح.'
            : 'Please enter a valid email address.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'SELECT id, full_name, email FROM users WHERE email = ? LIMIT 1'
            );
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $code = (string) random_int(100000, 999999);
                $codeHash = hash('sha256', $code);

                $delete = $pdo->prepare('DELETE FROM password_reset_codes WHERE user_id = ?');
                $delete->execute([$user['id']]);

                $insert = $pdo->prepare(
                    'INSERT INTO password_reset_codes (user_id, code_hash, expires_at, attempts)
                     VALUES (?, ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 15 MINUTE), 0)'
                );
                $insert->execute([$user['id'], $codeHash]);

                require_once __DIR__ . '/vendor/autoload.php';
                $mailConfig = require __DIR__ . '/mail_config.php';

                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = $mailConfig['host'];
                $mail->SMTPAuth = true;
                $mail->Username = $mailConfig['username'];
                $mail->Password = $mailConfig['password'];
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->CharSet = 'UTF-8';

                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];

                $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
                $mail->addAddress($user['email'], $user['full_name']);
                $mail->isHTML(true);

                $mail->Subject = $isArabic
                    ? 'رمز استعادة كلمة المرور - Orchid Boutique'
                    : 'Password Reset Code - Orchid Boutique';

                $safeName = htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8');

                $mail->Body = $isArabic
                    ? "<div style='font-family:Arial,sans-serif;direction:rtl;text-align:center;padding:25px'>
                        <h2 style='color:#581d2c'>Orchid Boutique</h2>
                        <p>مرحبًا {$safeName}</p>
                        <p>استخدمي الرمز التالي لاستعادة كلمة المرور:</p>
                        <h1 style='color:#581d2c;letter-spacing:8px'>{$code}</h1>
                        <p>تنتهي صلاحية الرمز بعد 15 دقيقة.</p>
                       </div>"
                    : "<div style='font-family:Arial,sans-serif;text-align:center;padding:25px'>
                        <h2 style='color:#581d2c'>Orchid Boutique</h2>
                        <p>Hello {$safeName}</p>
                        <p>Use this code to reset your password:</p>
                        <h1 style='color:#581d2c;letter-spacing:8px'>{$code}</h1>
                        <p>This code expires in 15 minutes.</p>
                       </div>";

                $mail->AltBody = $isArabic
                    ? "رمز استعادة كلمة المرور: $code. تنتهي صلاحيته خلال 15 دقيقة."
                    : "Your password reset code is: $code. It expires in 15 minutes.";

                $mail->send();

                $_SESSION['password_reset_email'] = $user['email'];
                header('Location: reset-password.php');
                exit;
            }

            $success = $isArabic
                ? 'إذا كان البريد مسجلًا لدينا، فستصلك رسالة تحتوي على رمز الاستعادة.'
                : 'If the email is registered, you will receive a reset code.';

                } catch (\Throwable $e) {
            error_log('Orchid forgot-password error: ' . $e->getMessage());
            $error = $e->getMessage();
        }

    }
}
?>

<!DOCTYPE html>
<html lang="<?= $isArabic ? 'ar' : 'en' ?>" dir="<?= $dir ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isArabic ? 'استعادة كلمة المرور' : 'Forgot Password' ?> | Orchid Boutique</title>
<link rel="stylesheet" href="assets/css/forgot-password.css?v=2">
    </head>
<body>

<div class="split-wrapper">

    <section class="form-side">
        <div class="top-bar">
            <a href="index.php" class="brand-title">Orchid Boutique</a>
            <a class="form-back" href="login.php">
                <?= $isArabic ? '← تسجيل الدخول' : 'Back to Login →' ?>
            </a>
        </div>

        <div class="form-header">
            <h1><?= $isArabic ? 'نسيتِ كلمة المرور؟' : 'Forgot Password?' ?></h1>
            <p>
                <?= $isArabic
                    ? 'أدخلي بريدكِ الإلكتروني المسجل لدينا لإرسال رمز استعادة كلمة المرور.'
                    : 'Enter your registered email address to receive a password reset code.' ?>
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

        <form method="POST" action="forgot-password.php">
            <div class="fields-stack">
                <div class="field-group">
                    <label for="email"><?= $isArabic ? 'البريد الإلكتروني' : 'Email Address' ?></label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="name@example.com"
                        autocomplete="email"
                        required
                    >
                </div>
            </div>

            <button type="submit" class="submit-btn">
                <?= $isArabic ? 'إرسال رمز الاستعادة' : 'Send Reset Code' ?>
            </button>
        </form>

        <div class="form-footer-note">
            <a href="login.php"><?= $isArabic ? 'العودة إلى تسجيل الدخول' : 'Back to Login' ?></a>
        </div>
    </section>

    <aside class="visual-side">
        <div class="visual-top-dot"></div>

        <div class="glass-center-card">
            <div class="glass-badge-circle">✉️</div>
            <h2><?= $isArabic ? 'استعادة الوصول السريع' : 'Quick Account Recovery' ?></h2>
            <p>
                <?= $isArabic
                    ? 'سنرسل لكِ رمز تحقق مكوناً من 6 أرقام إلى بريدكِ الإلكتروني لإعادة تعيين كلمة المرور بأمان'
                    : 'We will send a 6-digit verification code to your email to securely reset your password' ?>
            </p>
        </div>

        <div class="visual-bottom-badge">
            <strong><?= $isArabic ? '"خامات رائعة وتوصيل سريع جداً"' : '"Exquisite fabrics & fast delivery"' ?></strong>
        </div>
    </aside>

</div>

</body>
</html>
