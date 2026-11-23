<?php
session_start();
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$current_lang = $_SESSION['lang'] ?? 'ar';

if (!in_array($current_lang, ['ar', 'en'], true)) {
    $current_lang = 'ar';
}

$_SESSION['lang'] = $current_lang;

require_once "languages/$current_lang.php";
require_once 'db.php';
require_once 'send_otp_email.php';

$isArabic = $current_lang === 'ar';
$dir = $isArabic ? 'rtl' : 'ltr';
$next_lang = $isArabic ? 'en' : 'ar';
$lang_btn_text = $isArabic ? 'English' : 'العربية';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php?lang=' . $current_lang);
    exit;
}

function normalize_mobile_digits(string $value): string
{
    $arabicDigits = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩','۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    $englishDigits = ['0','1','2','3','4','5','6','7','8','9','0','1','2','3','4','5','6','7','8','9'];
    return str_replace($arabicDigits, $englishDigits, trim($value));
}

function is_valid_real_email_domain(string $email): bool
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $parts = explode('@', $email);
    $localPart = trim($parts[0] ?? '');
    $domain = strtolower(trim($parts[1] ?? ''));

    if ($localPart === '' || $domain === '' || !str_contains($domain, '.')) {
        return false;
    }

    $fakeLocals = ['test', 'fake', 'asd', 'qwe', 'aaa', 'bbb', '123', 'admin', 'noemail', 'none'];
    if (in_array(strtolower($localPart), $fakeLocals, true)) {
        return false;
    }

    $blockedDomains = [
        'example.com', 'example.org', 'example.net',
        'test.com', 'localhost', 'mailinator.com',
        'tempmail.com', '10minutemail.com', 'guerrillamail.com',
        'throwaway.email', 'yopmail.com', 'fakeinbox.com',
        'fake.com', 'abc.com', 'xyz.com'
    ];

    if (in_array($domain, $blockedDomains, true)) {
        return false;
    }

    if (function_exists('checkdnsrr')) {
        return checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
    }

    return true;
}

function is_valid_phone_number(string $phone): bool
{
    $cleaned = preg_replace('/[\s\-\(\)]+/', '', trim($phone));
    if (!is_string($cleaned) || $cleaned === '') {
        return false;
    }

    if (!preg_match('/^\+?[0-9]{9,15}$/', $cleaned)) {
        return false;
    }

    $digitsOnly = ltrim($cleaned, '+');

    if (preg_match('/^(\d)\1+$/', $digitsOnly)) {
        return false;
    }

    $sequences = ['0123456789', '1234567890', '9876543210', '12345678', '01234567', '11223344', '12121212'];
    foreach ($sequences as $seq) {
        if (str_contains($digitsOnly, $seq)) {
            return false;
        }
    }

    return true;
}

$error = '';
$info = '';
$fullName = '';
$email = '';
$phone = '';
$step = 'register';

$pending = $_SESSION['pending_registration'] ?? null;
if (is_array($pending) && isset($pending['expires_at']) && time() <= (int) $pending['expires_at']) {
    $step = 'verify_otp';
    $fullName = (string) ($pending['full_name'] ?? '');
    $email = (string) ($pending['email'] ?? '');
    $phone = (string) ($pending['phone'] ?? '');
} elseif (isset($_SESSION['pending_registration'])) {
    unset($_SESSION['pending_registration']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'send_otp';

    if ($action === 'cancel_otp') {
        if (is_array($pending)) {
            $fullName = (string) ($pending['full_name'] ?? '');
            $email = (string) ($pending['email'] ?? '');
            $phone = (string) ($pending['phone'] ?? '');
        }
        unset($_SESSION['pending_registration']);
        $pending = null;
        $step = 'register';
    } elseif ($action === 'resend_otp') {
        if (!is_array($pending) || time() > (int) ($pending['expires_at'] ?? 0)) {
            unset($_SESSION['pending_registration']);
            $pending = null;
            $step = 'register';
            $error = $isArabic
                ? 'انتهت صلاحية جلسة التحقق. يرجى إدخال بياناتك مرة أخرى.'
                : 'Verification session expired. Please enter your details again.';
        } else {
            $otpCode = (string) random_int(100000, 999999);
            $pending['otp_hash'] = password_hash($otpCode, PASSWORD_DEFAULT);
            $pending['expires_at'] = time() + 600;
            $pending['attempts'] = 0;

            if (send_verification_otp_email((string) $pending['email'], (string) $pending['full_name'], $otpCode, $isArabic)) {
                $_SESSION['pending_registration'] = $pending;
                $step = 'verify_otp';
                $info = $isArabic
                    ? 'تم إرسال رمز تحقق جديد إلى بريدك الإلكتروني.'
                    : 'A new verification code has been sent to your email.';
            } else {
                $step = 'verify_otp';
                $error = $isArabic
                    ? 'تعذر إرسال رمز التحقق حالياً. يرجى المحاولة لاحقاً.'
                    : 'Unable to send the verification code right now. Please try again later.';
            }
        }
    } elseif ($action === 'verify_otp') {
        $step = 'verify_otp';
        $enteredOtp = normalize_mobile_digits($_POST['otp_code'] ?? '');

        if (!is_array($pending) || time() > (int) ($pending['expires_at'] ?? 0)) {
            unset($_SESSION['pending_registration']);
            $pending = null;
            $step = 'register';
            $error = $isArabic
                ? 'انتهت صلاحية رمز التحقق. يرجى إعادة تعبئة نموذج التسجيل.'
                : 'The verification code has expired. Please fill out the registration form again.';
        } elseif (!preg_match('/^[0-9]{6}$/', $enteredOtp)) {
            $error = $isArabic
                ? 'يرجى إدخال رمز التحقق المكوّن من 6 أرقام.'
                : 'Please enter the 6-digit verification code.';
        } else {
            $attempts = (int) ($pending['attempts'] ?? 0);
            if ($attempts >= 5) {
                unset($_SESSION['pending_registration']);
                $pending = null;
                $step = 'register';
                $error = $isArabic
                    ? 'تم تجاوز الحد المسموح لمحاولات إدخال الرمز. يرجى البدء من جديد.'
                    : 'Maximum verification attempts exceeded. Please start over.';
            } elseif (!password_verify($enteredOtp, (string) ($pending['otp_hash'] ?? ''))) {
                $pending['attempts'] = $attempts + 1;
                $_SESSION['pending_registration'] = $pending;
                $error = $isArabic
                    ? 'رمز التحقق غير صحيح. يرجى التأكد من الرمز المرسل إلى بريدك.'
                    : 'Incorrect verification code. Please check the code sent to your email.';
            } else {
                try {
                    $verifiedEmail = (string) $pending['email'];
                    $verifiedName = (string) $pending['full_name'];
                    $verifiedPhone = (string) $pending['phone'];
                    $verifiedPassHash = (string) $pending['password_hash'];

                    $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                    $check->execute([$verifiedEmail]);

                    if ($check->fetch()) {
                        unset($_SESSION['pending_registration']);
                        $step = 'register';
                        $error = $lang['register_error_exists'];
                    } else {
                        $stmt = $pdo->prepare(
                            'INSERT INTO users (full_name, email, phone, password_hash) VALUES (?, ?, ?, ?)'
                        );
                        $stmt->execute([
                            $verifiedName,
                            $verifiedEmail,
                            $verifiedPhone,
                            $verifiedPassHash
                        ]);

                        unset($_SESSION['pending_registration']);
                        session_regenerate_id(true);

                        $_SESSION['user_id'] = (int) $pdo->lastInsertId();
                        $_SESSION['user_name'] = $verifiedName;
                        $_SESSION['user_email'] = $verifiedEmail;
                        $_SESSION['user_phone'] = $verifiedPhone;

                        header('Location: index.php?lang=' . $current_lang);
                        exit;
                    }
                } catch (PDOException $e) {
                    $error = $lang['register_error_general'];
                }
            }
        }
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = strtolower(preg_replace('/\s+/', '', $_POST['email'] ?? ''));
        $phone = normalize_mobile_digits($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (
            $fullName === '' ||
            $email === '' ||
            $phone === '' ||
            $password === '' ||
            $confirmPassword === ''
        ) {
            $error = $lang['register_error_required'];
        } elseif (!is_valid_real_email_domain($email)) {
            $error = $isArabic
                ? 'البريد الإلكتروني غير صحيح أو وهمي. يرجى إدخال بريد إلكتروني حقيقي.'
                : 'Invalid or fake email address. Please enter a real email.';
        } elseif (!is_valid_phone_number($phone)) {
            $error = $isArabic
                ? 'رقم الهاتف غير صحيح أو وهمي. يرجى إدخال رقم هاتف حقيقي.'
                : 'Invalid or fake phone number. Please enter a real phone number.';
        } elseif (strlen($password) < 8) {
            $error = $lang['register_error_password'];
        } elseif ($password !== $confirmPassword) {
            $error = $lang['register_error_match'];
        } else {
            try {
                $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                $check->execute([$email]);

                if ($check->fetch()) {
                    $error = $lang['register_error_exists'];
                } else {
                    $otpCode = (string) random_int(100000, 999999);

                    if (send_verification_otp_email($email, $fullName, $otpCode, $isArabic)) {
                        $_SESSION['pending_registration'] = [
                            'full_name' => $fullName,
                            'email' => $email,
                            'phone' => $phone,
                            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                            'otp_hash' => password_hash($otpCode, PASSWORD_DEFAULT),
                            'expires_at' => time() + 600,
                            'attempts' => 0,
                        ];
                        $pending = $_SESSION['pending_registration'];
                        $step = 'verify_otp';
                        $info = $isArabic
                            ? "تم إرسال رمز تحقق مكوّن من 6 أرقام إلى {$email}. أدخله لتأكيد الحساب."
                            : "A 6-digit verification code has been sent to {$email}. Enter it to verify your account.";
                    } else {
                        $error = $isArabic
                            ? 'تعذر إرسال رمز التحقق إلى هذا البريد. تأكد من صحة البريد وإعدادات الإرسال.'
                            : 'Failed to send verification code to this email. Please verify the email and mail settings.';
                    }
                }
            } catch (PDOException $e) {
                $error = $lang['register_error_general'];
            }
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
        <?= htmlspecialchars($lang['register_title'], ENT_QUOTES, 'UTF-8') ?> | Orchid Boutique
    </title>

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/register.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
<div class="register-page">
    <div class="container">

        <a href="index.php" class="close-btn"><i class="fa-solid fa-xmark"></i></a>

        <main class="form-section">
            <div class="login-brand">Orchid Boutique</div>

            <div class="form-wrapper">
                <h1 class="login-title">
                    <?= htmlspecialchars($lang['register_title'], ENT_QUOTES, 'UTF-8') ?>
                </h1>

                <p class="login-subtitle">
                    <?= htmlspecialchars($lang['register_subtitle'], ENT_QUOTES, 'UTF-8') ?>
                </p>

                <?php if ($info !== ''): ?>
                    <div class="alert alert-success" role="status">
                        <?= htmlspecialchars($info, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-error" role="alert">
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <?php if ($step === 'verify_otp'): ?>
                    <form class="login-form" method="POST" action="register.php?lang=<?= $current_lang ?>">
                        <input type="hidden" name="action" value="verify_otp">

                        <div class="input-group">
                            <label for="otp_code">
                                <?= $isArabic ? 'أدخل رمز التحقق' : 'Enter Verification Code' ?>
                            </label>
                            <input
                                type="text"
                                id="otp_code"
                                name="otp_code"
                                inputmode="numeric"
                                pattern="[0-9]{6}"
                                maxlength="6"
                                autocomplete="one-time-code"
                                placeholder="000000"
                                required
                            >
                        </div>

                        <button type="submit" class="login-button">
                            <?= $isArabic ? 'تأكيد الحساب' : 'Confirm Account' ?>
                        </button>
                    </form>
                        
                         <div class="email-spam-notice">
    <i class="fa-solid fa-envelope-open-text"></i>

    <p>
        <?= $isArabic
            ? 'تنبيه: إذا لم يصلك رمز التحقق في صندوق الوارد، يرجى التحقق من مجلد الرسائل غير المرغوب فيها (Spam / Junk).'
            : 'Important: If you do not receive the verification code in your Inbox, please check your Spam / Junk folder.' ?>
    </p>
</div>
                    <form class="login-form" method="POST" action="register.php?lang=<?= $current_lang ?>" style="margin-top: 10px;">
                        <input type="hidden" name="action" value="resend_otp">
                        <button type="submit" class="login-button" style="background: transparent; color: inherit; border: 1px solid currentColor;">
                            <?= $isArabic ? 'إرسال رمز جديد' : 'Send New Code' ?>
                        </button>
                    </form>

                    <form class="login-form" method="POST" action="register.php?lang=<?= $current_lang ?>" style="margin-top: 8px;">
                        <input type="hidden" name="action" value="cancel_otp">
                        <button type="submit" class="login-button" style="background: transparent; color: inherit; border: 1px solid currentColor;">
                            <?= $isArabic ? 'تغيير البريد الإلكتروني' : 'Change Email' ?>
                        </button>
                    </form>
                <?php else: ?>
                    <form class="login-form" method="POST" action="register.php?lang=<?= $current_lang ?>">
                        <input type="hidden" name="action" value="send_otp">

                        <div class="input-group">
                            <label for="full_name">
                                <?= htmlspecialchars($lang['register_full_name'], ENT_QUOTES, 'UTF-8') ?>
                            </label>
                            <input
                                type="text"
                                id="full_name"
                                name="full_name"
                                value="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>"
                                autocomplete="name"
                                maxlength="100"
                                required
                            >
                        </div>

                        <div class="input-group">
                            <label for="email">
                                <?= htmlspecialchars($lang['register_email'], ENT_QUOTES, 'UTF-8') ?>
                            </label>
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
                            <label for="phone">
                                <?= htmlspecialchars($lang['register_phone'], ENT_QUOTES, 'UTF-8') ?>
                            </label>
                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>"
                                autocomplete="tel"
                                maxlength="25"
                                placeholder="<?= htmlspecialchars($lang['register_phone_placeholder'], ENT_QUOTES, 'UTF-8') ?>"
                                required
                            >
                        </div>

                        <div class="input-group">
                            <label for="password">
                                <?= htmlspecialchars($lang['register_password'], ENT_QUOTES, 'UTF-8') ?>
                            </label>
                            <div class="password-field">
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    autocomplete="new-password"
                                    minlength="8"
                                    required
                                >
                                <button type="button" class="password-toggle" onclick="toggleField('password', this)">
                                    <i class="fa-regular fa-eye-slash"></i>
                                </button>
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="confirm_password">
                                <?= htmlspecialchars($lang['register_confirm_password'], ENT_QUOTES, 'UTF-8') ?>
                            </label>
                            <div class="password-field">
                                <input
                                    type="password"
                                    id="confirm_password"
                                    name="confirm_password"
                                    autocomplete="new-password"
                                    minlength="8"
                                    required
                                >
                                <button type="button" class="password-toggle" onclick="toggleField('confirm_password', this)">
                                    <i class="fa-regular fa-eye-slash"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="login-button">
                            <?= htmlspecialchars($lang['register_button'], ENT_QUOTES, 'UTF-8') ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="login-footer">
                <?= htmlspecialchars($lang['register_have_account'], ENT_QUOTES, 'UTF-8') ?>
                <a href="login.php">
                    <?= htmlspecialchars($lang['register_login'], ENT_QUOTES, 'UTF-8') ?>
                </a>
            </div>
        </main>
    </div>
</div>

<script>
function toggleField(id, btn) {
    const input = document.getElementById(id);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    }
}
</script>
</body>
</html>