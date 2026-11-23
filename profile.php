<?php
require_once __DIR__ . '/auth.php';

// تحديد اللغة
$current_lang = $_SESSION['lang'] ?? 'ar';

if (!in_array($current_lang, ['ar', 'en'], true)) {
    $current_lang = 'ar';
}

$_SESSION['lang'] = $current_lang;

$isArabic = ($current_lang === 'ar');
$dir = $isArabic ? 'rtl' : 'ltr';

// الاتصال بقاعدة البيانات
require_once __DIR__ . '/db.php';

$message = '';
$messageType = '';

// إنشاء رمز حماية CSRF
if (empty($_SESSION['account_csrf'])) {
    $_SESSION['account_csrf'] = bin2hex(random_bytes(32));
}

$accountCsrf = $_SESSION['account_csrf'];

// معالجة تجميد الحساب أو حذفه نهائياً
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['account_action'])
) {
    $action = $_POST['account_action'];
    $password = $_POST['account_password'] ?? '';
    $csrf = $_POST['account_csrf'] ?? '';

    if (
        !is_string($csrf) ||
        !hash_equals($accountCsrf, $csrf)
    ) {
        $message = $isArabic
            ? 'انتهت صلاحية الطلب. حدّثي الصفحة وحاولي مجدداً.'
            : 'Invalid request. Refresh the page and try again.';
        $messageType = 'error';

    } elseif (!in_array($action, ['freeze', 'delete'], true)) {
        $message = $isArabic ? 'طلب غير صالح.' : 'Invalid request.';
        $messageType = 'error';

    } elseif (!is_string($password) || $password === '') {
        $message = $isArabic
            ? 'يرجى إدخال كلمة المرور لتأكيد العملية.'
            : 'Enter your password to confirm this action.';
        $messageType = 'error';

    } else {
        $checkPassword = $pdo->prepare(
            'SELECT password_hash, profile_image
             FROM users
             WHERE id = ?
             LIMIT 1'
        );

        $checkPassword->execute([$_SESSION['user_id']]);
        $account = $checkPassword->fetch(PDO::FETCH_ASSOC);

        if (
            !$account ||
            !password_verify($password, $account['password_hash'])
        ) {
            $message = $isArabic
                ? 'كلمة المرور غير صحيحة.'
                : 'Incorrect password.';
            $messageType = 'error';

        } elseif ($action === 'freeze') {
            $freeze = $pdo->prepare(
                "UPDATE users
                 SET account_status = 'frozen'
                 WHERE id = ?"
            );

            $freeze->execute([$_SESSION['user_id']]);

            session_unset();
            session_destroy();

            header('Location: login.php?account_status=frozen');
            exit;

        } else {
            try {
                $userId = (int) $_SESSION['user_id'];

                $pdo->beginTransaction();

                // إخفاء بيانات العميل في الطلبات مع الاحتفاظ بها
                $hideOrders = $pdo->prepare(
                    "UPDATE orders
                     SET full_name = 'حساب محذوف',
                         phone = '',
                         address = ''
                     WHERE user_id = ?"
                );
                $hideOrders->execute([$userId]);

                // حذف قائمة الأمنيات الخاصة بالحساب
                $deleteWishlist = $pdo->prepare(
                    'DELETE FROM wishlist WHERE user_id = ?'
                );
                $deleteWishlist->execute([$userId]);

                // إنشاء بريد بديل لمنع استخدام البريد القديم
                $deletedEmail = 'deleted_' . $userId . '_' .
                    bin2hex(random_bytes(8)) . '@example.invalid';

                // تغيير كلمة المرور لمنع الدخول مجدداً
                $newPasswordHash = password_hash(
                    bin2hex(random_bytes(32)),
                    PASSWORD_DEFAULT
                );

                // إخفاء بيانات الحساب مع الاحتفاظ بسجل المستخدم
                $deleteAccount = $pdo->prepare(
                    "UPDATE users
                     SET full_name = 'حساب محذوف',
                         email = ?,
                         password_hash = ?,
                         phone = NULL,
                         address = NULL,
                         profile_image = NULL,
                         account_status = 'deleted'
                     WHERE id = ?"
                );

                $deleteAccount->execute([
                    $deletedEmail,
                    $newPasswordHash,
                    $userId
                ]);

                $pdo->commit();

                // حذف الصورة الشخصية القديمة إن وجدت
                if (!empty($account['profile_image'])) {
                    $oldImage = __DIR__ . '/' .
                        ltrim($account['profile_image'], '/');

                    $uploadFolder = realpath(
                        __DIR__ . '/uploads/profiles'
                    );

                    $imageRealPath = realpath($oldImage);

                    if (
                        $uploadFolder &&
                        $imageRealPath &&
                        strpos(
                            $imageRealPath,
                            $uploadFolder . DIRECTORY_SEPARATOR
                        ) === 0 &&
                        is_file($imageRealPath)
                    ) {
                        unlink($imageRealPath);
                    }
                }

                session_unset();
                session_destroy();

                header('Location: login.php?account_status=deleted');
                exit;

            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log(
                    'Account deletion error: ' . $e->getMessage()
                );

                $message = $isArabic
                    ? 'تعذر حذف الحساب. يرجى المحاولة مرة أخرى.'
                    : 'Unable to delete the account. Please try again.';
                $messageType = 'error';
            }
        }
    }
}

// جلب بيانات المستخدم
$stmt = $pdo->prepare(
    'SELECT id, full_name, email, phone, profile_image
     FROM users
     WHERE id = ?'
);
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

// إحصائيات الطلبات
$orderSummary = [
    'total' => 0,
    'processing' => 0
];

$latestOrder = null;

try {
    $summaryStmt = $pdo->prepare("
        SELECT
            COUNT(*) AS total_orders,
            COALESCE(
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END),
                0
            ) AS processing_orders
        FROM orders
        WHERE user_id = ?
    ");

    $summaryStmt->execute([$_SESSION['user_id']]);
    $summaryData = $summaryStmt->fetch(PDO::FETCH_ASSOC);

    if ($summaryData) {
        $orderSummary['total'] = (int) $summaryData['total_orders'];
        $orderSummary['processing'] = (int) $summaryData['processing_orders'];
    }

    $latestStmt = $pdo->prepare("
        SELECT id, status, created_at, total_amount
        FROM orders
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");

    $latestStmt->execute([$_SESSION['user_id']]);
    $latestOrder = $latestStmt->fetch(PDO::FETCH_ASSOC) ?: null;

} catch (PDOException $e) {
    error_log('Profile orders summary error: ' . $e->getMessage());
}

$orderStatusLabels = [
    'pending' => [
        'ar' => 'قيد المراجعة',
        'en' => 'Pending'
    ],
    'confirmed' => [
        'ar' => 'تم التأكيد',
        'en' => 'Confirmed'
    ],
    'processing' => [
        'ar' => 'قيد التجهيز',
        'en' => 'Processing'
    ],
    'shipped' => [
        'ar' => 'تم الشحن',
        'en' => 'Shipped'
    ],
    'delivered' => [
        'ar' => 'تم التسليم',
        'en' => 'Delivered'
    ],
    'cancelled' => [
        'ar' => 'ملغي',
        'en' => 'Cancelled'
    ]
];

// تحديث بيانات الملف الشخصي
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    !isset($_POST['account_action'])
) {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $imagePath = $user['profile_image'] ?? null;
    $newImagePath = null;

    if ($fullName === '') {
        $message = $isArabic
            ? 'يرجى إدخال الاسم الكامل.'
            : 'Please enter your full name.';
        $messageType = 'error';

    } elseif (strlen($fullName) > 200 || strlen($phone) > 30) {
        $message = $isArabic
            ? 'الاسم أو رقم الهاتف أطول من المسموح.'
            : 'The name or phone number is too long.';
        $messageType = 'error';

    } else {
        try {
            // معالجة الصورة إن اختار المستخدم صورة جديدة
            if (
                isset($_FILES['profile_image']) &&
                $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE
            ) {
                $file = $_FILES['profile_image'];

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    throw new RuntimeException('upload');
                }

                if ($file['size'] > 2 * 1024 * 1024) {
                    throw new RuntimeException('size');
                }

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);

                $allowedTypes = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp'
                ];

                if (
                    !isset($allowedTypes[$mime]) ||
                    @getimagesize($file['tmp_name']) === false
                ) {
                    throw new RuntimeException('type');
                }

                $uploadDir = __DIR__ . '/uploads/profiles/';

                if (
                    !is_dir($uploadDir) &&
                    !mkdir($uploadDir, 0755, true)
                ) {
                    throw new RuntimeException('folder');
                }

                $fileName = bin2hex(random_bytes(16)) .
                    '.' . $allowedTypes[$mime];

                $destination = $uploadDir . $fileName;

                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    throw new RuntimeException('save');
                }

                $newImagePath = 'uploads/profiles/' . $fileName;
                $imagePath = $newImagePath;
            }

            $update = $pdo->prepare(
                'UPDATE users
                 SET full_name = ?, phone = ?, profile_image = ?
                 WHERE id = ?'
            );

            $update->execute([
                $fullName,
                $phone !== '' ? $phone : null,
                $imagePath,
                $_SESSION['user_id']
            ]);

            $_SESSION['user_name'] = $fullName;
            $_SESSION['user_phone'] = $phone;
            $_SESSION['user_image'] = $imagePath ?? '';

            // إعادة جلب البيانات المحدثة
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $message = $isArabic
                ? 'تم تحديث بياناتك بنجاح.'
                : 'Your profile has been updated successfully.';
            $messageType = 'success';

        } catch (RuntimeException $e) {
            // حذف الملف الجديد إذا لم ينجح الحفظ
            if (
                $newImagePath !== null &&
                is_file(__DIR__ . '/' . $newImagePath)
            ) {
                unlink(__DIR__ . '/' . $newImagePath);
            }

            $errors = [
                'size' => $isArabic
                    ? 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت.'
                    : 'The image must not exceed 2 MB.',
                'type' => $isArabic
                    ? 'اختاري صورة JPG أو PNG أو WEBP صالحة.'
                    : 'Choose a valid JPG, PNG, or WEBP image.',
                'folder' => $isArabic
                    ? 'تعذر إنشاء مجلد الصور.'
                    : 'Could not create the image folder.',
            ];

            $message = $errors[$e->getMessage()] ?? (
                $isArabic
                    ? 'تعذر رفع الصورة. تحققي من إعدادات المجلد وحاولي مجددًا.'
                    : 'Image upload failed. Check folder permissions and try again.'
            );
            $messageType = 'error';

        } catch (PDOException $e) {
            if (
                $newImagePath !== null &&
                is_file(__DIR__ . '/' . $newImagePath)
            ) {
                unlink(__DIR__ . '/' . $newImagePath);
            }

            error_log('Profile update error: ' . $e->getMessage());

            $message = $isArabic
                ? 'تعذر تحديث البيانات. يرجى المحاولة مرة أخرى.'
                : 'Unable to update your profile. Please try again.';
            $messageType = 'error';
        }
    }
}

// تحديث بيانات الجلسة
$_SESSION['user_name'] = $user['full_name'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_phone'] = $user['phone'] ?? '';
$_SESSION['user_image'] = $user['profile_image'] ?? '';

$initial = mb_substr($user['full_name'], 0, 1, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isArabic ? 'الملف الشخصي | Orchid Boutique' : 'My Profile | Orchid Boutique'; ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/profile.css?v=2">
    </head>
<body>

<div class="split-wrapper">

    <!-- الجانب الأيمن: تعديل البيانات والاختصارات -->
    <section class="form-side">
        <div class="top-bar">
            <a href="index.php" class="brand-title">Orchid Boutique</a>
            <a href="index.php" class="form-back">
                <i class="fa-solid fa-arrow-<?php echo $isArabic ? 'right' : 'left'; ?>"></i>
                <?php echo $isArabic ? 'العودة إلى المتجر' : 'Back to Store'; ?>
            </a>
        </div>

        <div class="form-header">
            <h1><?php echo $isArabic ? 'الملف الشخصي' : 'My Profile'; ?></h1>
            <p><?php echo $isArabic ? 'أهلاً بعودتكِ إلى Orchid Boutique، أديري معلومات حسابكِ بسهولة' : 'Welcome back to Orchid Boutique, manage your account easily'; ?></p>
        </div>

        <?php if ($message !== ''): ?>
            <div class="notice <?php echo $messageType; ?>" role="status">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <!-- اختصارات سريعة -->
        <div class="shortcuts-pills">
            <a href="orders.php?lang=<?= urlencode($current_lang) ?>" class="pill-link">
                <i class="fa-solid fa-bag-shopping"></i>
                <span><?= $isArabic ? 'طلباتي' : 'My Orders' ?> (<?= (int)$orderSummary['total'] ?>)</span>
            </a>

            <a href="wishlist.php?lang=<?= urlencode($current_lang) ?>" class="pill-link">
                <i class="fa-regular fa-heart"></i>
                <span><?= $isArabic ? 'المفضلة' : 'Wishlist' ?></span>
            </a>

            <a href="addresses.php?lang=<?= urlencode($current_lang) ?>" class="pill-link">
                <i class="fa-solid fa-location-dot"></i>
                <span><?= $isArabic ? 'العناوين' : 'Addresses' ?></span>
            </a>
        </div>

        <!-- نموذج تعديل البيانات الشخصية -->
        <form method="POST" action="profile.php" enctype="multipart/form-data">
            <input type="file" id="profile_image" name="profile_image"
                   accept="image/jpeg,image/png,image/webp" hidden>

            <div class="fields-stack">
                <div class="field-group">
                    <label for="full_name">
                        <?php echo $isArabic ? 'الاسم الكامل' : 'Full Name'; ?>
                    </label>
                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        maxlength="200"
                        required
                        value="<?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="field-group">
                    <label for="email">
                        <?php echo $isArabic ? 'البريد الإلكتروني' : 'Email Address'; ?>
                    </label>
                    <input
                        type="email"
                        id="email"
                        disabled
                        value="<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="field-group">
                    <label for="phone">
                        <?php echo $isArabic ? 'رقم الهاتف' : 'Phone Number'; ?>
                    </label>
                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        maxlength="30"
                        placeholder="059xxxxxxx"
                        value="<?php echo htmlspecialchars($user['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>

            <button type="submit" class="submit-btn">
                <?php echo $isArabic ? 'حفظ التغييرات' : 'Save Changes'; ?>
            </button>

            <div class="form-footer-note">
                <?php echo $isArabic ? 'للتبديل إلى حساب آخر؟' : 'Switch account?'; ?>
                <a href="logout.php">
                    <?php echo $isArabic ? 'تسجيل الخروج' : 'Logout'; ?>
                </a>
            </div>
        </form>

        <!-- إدارة الحساب (قسم واحد مرتب) -->
        <div class="account-management">
            <h2>
                <?= $isArabic ? 'إدارة الحساب' : 'Account Management' ?>
            </h2>

            <p class="account-management-description">
                <?= $isArabic
                    ? 'يمكنك تجميد حسابك مؤقتاً أو حذفه نهائياً.'
                    : 'You can temporarily freeze your account or permanently delete it.' ?>
            </p>

            <!-- تجميد الحساب -->
            <form method="POST" action="profile.php"
                  class="account-action-form"
                  onsubmit="return confirm('<?= $isArabic ? 'هل أنتِ متأكدة من تجميد حسابك؟' : 'Are you sure you want to freeze your account?' ?>');">

                <input type="hidden" name="account_csrf"
                       value="<?= htmlspecialchars($accountCsrf, ENT_QUOTES, 'UTF-8') ?>">

                <h3>
                    <?= $isArabic ? 'تجميد الحساب مؤقتاً' : 'Temporarily Freeze Account' ?>
                </h3>

                <p>
                    <?= $isArabic
                        ? 'سيتم تسجيل خروجك ويمكنك إعادة تفعيل حسابك لاحقاً من صفحة الدخول.'
                        : 'You will be logged out and can reactivate your account later from the login page.' ?>
                </p>

                <label for="freeze_password">
                    <?= $isArabic ? 'كلمة المرور للتأكيد' : 'Confirm Password' ?>
                </label>

                <input
                    type="password"
                    id="freeze_password"
                    name="account_password"
                    required
                    autocomplete="current-password">

                <button type="submit"
                        name="account_action"
                        value="freeze"
                        class="account-freeze-btn">
                    <?= $isArabic ? 'تجميد الحساب' : 'Freeze Account' ?>
                </button>
            </form>

            <!-- حذف الحساب -->
            <form method="POST" action="profile.php"
                  class="account-action-form delete-account-form"
                  onsubmit="return confirm('<?= $isArabic ? 'هل أنتِ متأكدة من طلب حذف حسابك؟' : 'Are you sure you want to delete your account?' ?>');">

                <input type="hidden" name="account_csrf"
                       value="<?= htmlspecialchars($accountCsrf, ENT_QUOTES, 'UTF-8') ?>">

                <h3>
                    <?= $isArabic ? 'حذف الحساب نهائياً' : 'Permanently Delete Account' ?>
                </h3>

                <p>
                    <?= $isArabic
                        ? 'سيتم إخفاء بياناتك الشخصية وحذف المفضلة، مع الاحتفاظ بسجل طلباتك.'
                        : 'Your personal information and wishlist will be removed, while your order history will be preserved.' ?>
                </p>

                <label for="delete_password">
                    <?= $isArabic ? 'كلمة المرور للتأكيد' : 'Confirm Password' ?>
                </label>

                <input
                    type="password"
                    id="delete_password"
                    name="account_password"
                    required
                    autocomplete="current-password">

                <button type="submit"
                        name="account_action"
                        value="delete"
                        class="account-delete-btn">
                    <?= $isArabic ? 'حذف الحساب نهائياً' : 'Delete Account Permanently' ?>
                </button>
            </form>
        </div>
    </section>

    <!-- الجانب الأيسر: البطاقة الزجاجية والصورة الشخصية -->
    <aside class="visual-side">
        <div class="visual-top-dot"></div>

        <div class="glass-center-card">
            <div
                class="avatar-circle"
                id="profileAvatar"
                role="button"
                tabindex="0"
                title="<?php echo $isArabic ? 'اضغطي لتغيير الصورة' : 'Click to change photo'; ?>"
            >
                <?php if (!empty($user['profile_image'])): ?>
                    <img
                        id="profilePreview"
                        src="<?php echo htmlspecialchars($user['profile_image'], ENT_QUOTES, 'UTF-8'); ?>"
                        alt="Profile">
                <?php else: ?>
                    <span id="profileInitial"><?php echo htmlspecialchars($initial, ENT_QUOTES, 'UTF-8'); ?></span>
                    <img id="profilePreview" src="" alt="" hidden>
                <?php endif; ?>
            </div>

            <h2><?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?></h2>

            <p>
                <?php echo $isArabic
                    ? 'اضغطي على الدائرة لتحديث صورتكِ الشخصية'
                    : 'Click circle to update profile photo'; ?>
            </p>

            <div class="glass-stats-row">
                <div>
                    <strong><?= (int)$orderSummary['total'] ?></strong>
                    <span><?= $isArabic ? 'طلباتي' : 'Orders' ?></span>
                </div>

                <div>
                    <strong><?= (int)$orderSummary['processing'] ?></strong>
                    <span><?= $isArabic ? 'قيد التجهيز' : 'Processing' ?></span>
                </div>
            </div>
        </div>

        <div class="visual-bottom-badge">
            <strong>
                <?php echo $isArabic
                    ? '"خامات رائعة وتوصيل سريع جداً"'
                    : '"Exquisite fabrics & fast delivery"'; ?>
            </strong>
        </div>
    </aside>

</div>

<script>
const avatarBox = document.getElementById('profileAvatar');
const fileInput = document.getElementById('profile_image');
const previewImg = document.getElementById('profilePreview');
const initialSpan = document.getElementById('profileInitial');

if (avatarBox && fileInput && previewImg) {
    avatarBox.addEventListener('click', () => {
        fileInput.click();
    });

    avatarBox.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            fileInput.click();
        }
    });

    fileInput.addEventListener('change', () => {
        const file = fileInput.files[0];
        if (!file) return;

        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            alert('اختاري صورة JPG أو PNG أو WEBP.');
            fileInput.value = '';
            return;
        }

        if (file.size > 2 * 1024 * 1024) {
            alert('حجم الصورة يجب ألا يتجاوز 2 ميجابايت.');
            fileInput.value = '';
            return;
        }

        const imageURL = URL.createObjectURL(file);

        previewImg.onload = () => {
            URL.revokeObjectURL(imageURL);
        };

        previewImg.src = imageURL;
        previewImg.hidden = false;

        if (initialSpan) {
            initialSpan.hidden = true;
        }
    });
}
</script>

</body>
</html>