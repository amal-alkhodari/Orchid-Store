
<?php
session_start();

require_once __DIR__ . '/../db.php';

$isArabic = ($_SESSION['lang'] ?? 'ar') === 'ar';

$message = '';
$success = false;
$showForm = false;

$token = $_POST['token'] ?? $_GET['token'] ?? '';
$csrfToken = $_SESSION['admin_reset_csrf'] ?? '';

if (
    !is_string($token) ||
    !preg_match('/^[a-f0-9]{64}$/i', $token)
) {
    $token = '';
}

if (empty($_SESSION['admin_reset_csrf'])) {
    $_SESSION['admin_reset_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedCsrf = $_POST['csrf_token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (
        !is_string($postedCsrf) ||
        !is_string($csrfToken) ||
        $csrfToken === '' ||
        !hash_equals($csrfToken, $postedCsrf)
    ) {
        $message = $isArabic
            ? 'انتهت صلاحية الطلب. حدّثي الصفحة وحاولي مرة أخرى.'
            : 'The request expired. Refresh the page and try again.';
    } elseif ($token === '') {
        $message = $isArabic
            ? 'رابط الاستعادة غير صالح.'
            : 'The reset link is invalid.';
    } elseif (!is_string($password) || strlen($password) < 10) {
        $message = $isArabic
            ? 'يجب أن تتكون كلمة المرور من 10 أحرف على الأقل.'
            : 'The password must contain at least 10 characters.';
    } elseif (
        !is_string($confirmPassword) ||
        $password !== $confirmPassword
    ) {
        $message = $isArabic
            ? 'كلمتا المرور غير متطابقتين.'
            : 'The passwords do not match.';
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "SELECT id, account_id
                 FROM password_resets
                 WHERE account_type = 'admin'
                   AND token_hash = :token_hash
                   AND expires_at > UTC_TIMESTAMP()
                   AND used_at IS NULL
                 LIMIT 1
                 FOR UPDATE"
            );

            $stmt->execute([
                'token_hash' => hash('sha256', $token)
            ]);

            $reset = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reset) {
                $pdo->rollBack();

                $message = $isArabic
                    ? 'رابط الاستعادة غير صالح أو منتهي الصلاحية أو مستخدم سابقًا.'
                    : 'The reset link is invalid, expired, or already used.';
            } else {
                $newHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $updateAdmin = $pdo->prepare(
                    'UPDATE admin_users
                     SET password_hash = :password_hash
                     WHERE id = :id'
                );

                $updateAdmin->execute([
                    'password_hash' => $newHash,
                    'id' => $reset['account_id']
                ]);

                if ($updateAdmin->rowCount() !== 1) {
                    throw new RuntimeException(
                        'The administrator account could not be updated.'
                    );
                }

                // Invalidate all reset links for this administrator.
                $invalidateResets = $pdo->prepare(
                    "UPDATE password_resets
                     SET used_at = UTC_TIMESTAMP()
                     WHERE account_type = 'admin'
                       AND account_id = :account_id
                       AND used_at IS NULL"
                );

                $invalidateResets->execute([
                    'account_id' => $reset['account_id']
                ]);

                // Revoke remembered logins for this administrator.
                $deleteRememberTokens = $pdo->prepare(
                    "DELETE FROM remember_tokens
                     WHERE account_type = 'admin'
                       AND account_id = :account_id"
                );

                $deleteRememberTokens->execute([
                    'account_id' => $reset['account_id']
                ]);

                $pdo->commit();

                $success = true;

                $message = $isArabic
                    ? 'تم تغيير كلمة المرور بنجاح. يمكنكِ تسجيل الدخول بكلمة المرور الجديدة.'
                    : 'Your password was changed successfully. You can now log in with your new password.';
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Admin password reset error: ' . $e->getMessage());

            $message = $isArabic
                ? 'حدث خطأ أثناء تغيير كلمة المرور. حاولي مرة أخرى.'
                : 'An error occurred while resetting the password. Please try again.';
        }
    }
}

// Check whether the reset link is valid before displaying the form.
if (!$success && $token !== '') {
    try {
        $check = $pdo->prepare(
            "SELECT id
             FROM password_resets
             WHERE account_type = 'admin'
               AND token_hash = :token_hash
               AND expires_at > NOW()
               AND used_at IS NULL
             LIMIT 1"
        );

        $check->execute([
            'token_hash' => hash('sha256', $token)
        ]);

        $showForm = (bool) $check->fetch(PDO::FETCH_ASSOC);

        if (!$showForm && $message === '') {
            $message = $isArabic
                ? 'رابط الاستعادة غير صالح أو منتهي الصلاحية أو مستخدم سابقًا.'
                : 'The reset link is invalid, expired, or already used.';
        }
    } catch (PDOException $e) {
        error_log('Admin reset link check error: ' . $e->getMessage());

        $message = $isArabic
            ? 'تعذر التحقق من رابط الاستعادة.'
            : 'Could not verify the reset link.';
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $isArabic ? 'ar' : 'en' ?>"
      dir="<?= $isArabic ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>
        <?= $isArabic ? 'إعادة تعيين كلمة المرور' : 'Reset Password' ?>
        | Orchid Boutique
    </title>

<link rel="stylesheet" href="../assets/css/reset-password.css?v=2">
    <style>
        .password-wrapper {
            position: relative;
            width: 100%;
        }

        .password-wrapper input {
            width: 100%;
            box-sizing: border-box;
            padding-inline-end: 48px;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            inset-inline-end: 12px;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            cursor: pointer;
            color: #652e70;
            font-size: 13px;
            padding: 6px;
        }
    </style>
</head>

<body>

<main class="split-wrapper">

    <section class="form-side">

        <div class="top-bar">
            <a href="../index.php" class="brand-title">
                Orchid Boutique
            </a>

            <a href="login.php" class="form-back">
                <?= $isArabic ? 'العودة ←' : '← Back' ?>
            </a>
        </div>

        <div class="form-header">
            <h1>
                <?= $isArabic
                    ? 'إنشاء كلمة مرور جديدة'
                    : 'Create New Password' ?>
            </h1>

            <p>
                <?= $isArabic
                    ? 'أدخلي كلمة المرور الجديدة لتتمكني من الدخول إلى لوحة التحكم.'
                    : 'Enter your new password to access the admin dashboard.' ?>
            </p>
        </div>

        <?php if ($message !== ''): ?>
            <div class="message <?= $success ? 'success' : 'error' ?>"
                 role="status">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>

            <div class="form-footer-note">
                <a href="login.php">
                    <?= $isArabic
                        ? 'العودة إلى تسجيل الدخول'
                        : 'Back to login' ?>
                </a>
            </div>

        <?php elseif ($showForm): ?>

            <form method="post"
                  action="reset_admin_password.php"
                  autocomplete="off">

                <input type="hidden"
                       name="token"
                       value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

                <input type="hidden"
                       name="csrf_token"
                       value="<?= htmlspecialchars(
                           $_SESSION['admin_reset_csrf'],
                           ENT_QUOTES,
                           'UTF-8'
                       ) ?>">

                <div class="fields-stack">

                    <div class="field-group">
                        <label for="password">
                            <?= $isArabic
                                ? 'كلمة المرور الجديدة'
                                : 'New Password' ?>
                        </label>

                        <div class="password-wrapper">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                minlength="10"
                                autocomplete="new-password"
                                required
                            >

                            <button type="button"
                                    class="toggle-password"
                                    onclick="togglePassword('password', this)">
                                <?= $isArabic ? 'إظهار' : 'Show' ?>
                            </button>
                        </div>
                    </div>

                    <div class="field-group">
                        <label for="confirm_password">
                            <?= $isArabic
                                ? 'تأكيد كلمة المرور الجديدة'
                                : 'Confirm New Password' ?>
                        </label>

                        <div class="password-wrapper">
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                minlength="10"
                                autocomplete="new-password"
                                required
                            >

                            <button type="button"
                                    class="toggle-password"
                                    onclick="togglePassword('confirm_password', this)">
                                <?= $isArabic ? 'إظهار' : 'Show' ?>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">
                        <?= $isArabic
                            ? 'تغيير كلمة المرور'
                            : 'Reset Password' ?>
                    </button>

                </div>
            </form>

        <?php endif; ?>

        <div class="form-footer-note">
            <?= $isArabic
                ? '© Orchid Boutique - جميع الحقوق محفوظة'
                : '© Orchid Boutique - All rights reserved' ?>
        </div>

    </section>

    <section class="visual-side">

        <div class="visual-top-dot"></div>

        <div class="glass-center-card">
            <div class="glass-badge-circle">
                <span>✦</span>
            </div>

            <h2>Orchid Boutique</h2>

            <p>
                <?= $isArabic
                    ? 'تجربة أنيقة تبدأ من التفاصيل.'
                    : 'An elegant experience begins with details.' ?>
            </p>
        </div>

        <div class="visual-bottom-badge">
            <?= $isArabic
                ? 'أناقة • ثقة • تميز'
                : 'Elegance • Trust • Style' ?>
        </div>

    </section>

</main>

<script>
function togglePassword(id, button) {
    const input = document.getElementById(id);

    if (input.type === 'password') {
        input.type = 'text';
        button.textContent = '<?= $isArabic ? "إخفاء" : "Hide" ?>';
    } else {
        input.type = 'password';
        button.textContent = '<?= $isArabic ? "إظهار" : "Show" ?>';
    }
}
</script>

</body>
</html>
