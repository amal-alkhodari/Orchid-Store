<?php
require_once __DIR__ . '/auth.php';

$lang = $_GET['lang'] ?? ($_SESSION['lang'] ?? 'ar');
$lang = $lang === 'en' ? 'en' : 'ar';

$userId = (int) $_SESSION['user_id'];
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($fullName === '' || $email === '' || $phone === '' || $address === '') {
        $message = $lang === 'ar'
            ? 'يرجى تعبئة جميع الحقول.'
            : 'Please fill in all fields.';
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = $lang === 'ar'
            ? 'يرجى إدخال بريد إلكتروني صحيح.'
            : 'Please enter a valid email address.';
        $messageType = 'error';
    } else {
        try {
            $stmt = $pdo->prepare(
                'UPDATE users
                 SET full_name = ?, email = ?, phone = ?, address = ?
                 WHERE id = ?'
            );

            $stmt->execute([$fullName, $email, $phone, $address, $userId]);

            $_SESSION['user_name'] = $fullName;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_phone'] = $phone;

            $message = $lang === 'ar'
                ? 'تم حفظ بياناتك بنجاح.'
                : 'Your information has been saved successfully.';
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = $lang === 'ar'
                ? 'تعذر حفظ البيانات. قد يكون البريد الإلكتروني مستخدمًا لحساب آخر.'
                : 'Could not save your information. The email may already be in use.';
            $messageType = 'error';
        }
    }
}

$stmt = $pdo->prepare(
    'SELECT full_name, email, phone, address FROM users WHERE id = ?'
);
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    exit($lang === 'ar'
        ? 'لم يتم العثور على بيانات الحساب.'
        : 'Account information could not be found.');
}

$fullName = $user['full_name'] ?? '';
$email = $user['email'] ?? '';
$phone = $user['phone'] ?? '';
$address = $user['address'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang === 'ar' ? 'العناوين ومعلومات التواصل | Orchid Boutique' : 'Addresses & Contact | Orchid Boutique' ?></title>
    <link rel="stylesheet" href="assets/css/addresses.css">
</head>
<body>

<div class="split-wrapper">

    <!-- الجانب الأيمن: نموذج العنوان ومعلومات التواصل -->
    <section class="form-side">
        <div class="top-bar">
            <a href="index.php?lang=<?= urlencode($lang) ?>" class="brand-title">Orchid Boutique</a>
            <a class="form-back" href="index.php?lang=<?= urlencode($lang) ?>">
                <?= $lang === 'ar' ? '← العودة إلى المتجر' : 'Back to Store →' ?>
            </a>
        </div>

        <div class="form-header">
            <h1><?= $lang === 'ar' ? 'العناوين ومعلومات التواصل' : 'Addresses & Contact' ?></h1>
            <p>
                <?= $lang === 'ar'
                    ? 'عدّلي بيانات حسابكِ وعنوان التوصيل واحفظي التغييرات لتسهيل طلباتكِ القادمة.'
                    : 'Edit your contact details and delivery address for faster checkout.' ?>
            </p>
        </div>

        <?php if ($message !== ''): ?>
            <div class="form-message <?= $messageType === 'success' ? 'success' : 'error' ?>" role="status">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="addresses.php?lang=<?= urlencode($lang) ?>">
            <div class="fields-stack">
                <div class="field-group">
                    <label for="full_name"><?= $lang === 'ar' ? 'الاسم الكامل' : 'Full Name' ?></label>
                    <input
                        id="full_name"
                        name="full_name"
                        type="text"
                        value="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>"
                        autocomplete="name"
                        required
                    >
                </div>

                <div class="field-group">
                    <label for="email"><?= $lang === 'ar' ? 'البريد الإلكتروني' : 'Email Address' ?></label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="field-group">
                    <label for="phone"><?= $lang === 'ar' ? 'رقم الهاتف' : 'Phone Number' ?></label>
                    <input
                        id="phone"
                        name="phone"
                        type="tel"
                        value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>"
                        autocomplete="tel"
                        oninput="updateAddressPreview()"
                        required
                    >
                </div>

                <div class="field-group">
                    <label for="address"><?= $lang === 'ar' ? 'عنوان التوصيل' : 'Delivery Address' ?></label>
                    <textarea
                        id="address"
                        name="address"
                        rows="3"
                        autocomplete="street-address"
                        oninput="updateAddressPreview()"
                        placeholder="<?= $lang === 'ar'
                            ? 'أدخلي المدينة والمنطقة والشارع وأي تفاصيل تساعد في التوصيل'
                            : 'Enter city, area, street, and delivery details' ?>"
                        required
                    ><?= htmlspecialchars($address, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>

            <button type="submit" class="submit-btn">
                <?= $lang === 'ar' ? 'حفظ التغييرات' : 'Save Changes' ?>
            </button>

            <div class="form-footer-note">
                <a href="profile.php"><?= $lang === 'ar' ? 'العودة إلى الملف الشخصي' : 'Back to Profile' ?></a>
            </div>
        </form>
    </section>

    <!-- الجانب الأيسر: البطاقة الزجاجية ومعاينة التوصيل -->
    <aside class="visual-side">
        <div class="visual-top-dot"></div>

        <div class="glass-center-card">
            <div class="glass-badge-circle">📍</div>
            <h2><?= $lang === 'ar' ? 'بيانات التوصيل المعتمدة' : 'Saved Delivery Details' ?></h2>
            <p id="previewAddress">
                <?= $address !== ''
                    ? htmlspecialchars($address, ENT_QUOTES, 'UTF-8')
                    : ($lang === 'ar' ? 'لم يتم تحديد عنوان توصيل بعد' : 'No delivery address saved yet') ?>
            </p>
            <div class="live-preview-pill" id="previewPhone">
                <?= $phone !== ''
                    ? htmlspecialchars($phone, ENT_QUOTES, 'UTF-8')
                    : ($lang === 'ar' ? 'رقم التواصل' : 'Contact Phone') ?>
            </div>
        </div>

        <div class="visual-bottom-badge">
            <strong><?= $lang === 'ar' ? '"توصيل سريع وآمن لباب منزلكِ"' : '"Fast & safe delivery to your door"' ?></strong>
        </div>
    </aside>

</div>

<script>
function updateAddressPreview() {
    const addr = document.getElementById('address').value.trim();
    const phone = document.getElementById('phone').value.trim();

    document.getElementById('previewAddress').textContent =
        addr !== '' ? addr : '<?= $lang === 'ar' ? 'لم يتم تحديد عنوان توصيل بعد' : 'No delivery address saved yet' ?>';

    document.getElementById('previewPhone').textContent =
        phone !== '' ? phone : '<?= $lang === 'ar' ? 'رقم التواصل' : 'Contact Phone' ?>';
}
</script>

</body>
</html>
