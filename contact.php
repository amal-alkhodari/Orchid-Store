<?php
session_start();
require_once 'db.php';

$current_lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'ar';

if (!in_array($current_lang, ['ar', 'en'], true)) {
    $current_lang = 'ar';
}

$_SESSION['lang'] = $current_lang;

$is_ar = $current_lang === 'ar';
$dir = $is_ar ? 'rtl' : 'ltr';
$next_lang = $is_ar ? 'en' : 'ar';

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$is_logged_in = isset($_SESSION['user_id']);

$success = false;
$error = false;
$login_required = false;
$email_sent = false;
$email_error = false;

$name = $is_logged_in ? ($_SESSION['user_name'] ?? '') : '';
$email = $is_logged_in ? ($_SESSION['user_email'] ?? '') : '';

$message_type = 'inquiry';
$subject = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Read submitted values
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message_type = trim($_POST['message_type'] ?? 'inquiry');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Prevent sending messages without login
    if (!$is_logged_in) {

        $login_required = true;
        $success = false;
        $error = false;

    } else {

        $allowed_types = ['complaint', 'inquiry', 'suggestion', 'order'];

        if (
            $name !== '' &&
            filter_var($email, FILTER_VALIDATE_EMAIL) &&
            in_array($message_type, $allowed_types, true) &&
            $subject !== '' &&
            $message !== ''
        ) {
          try {

    // Save message in database
    $stmt = $pdo->prepare("
        INSERT INTO contact_messages
        (name, email, message_type, subject, message)
        VALUES
        (:name, :email, :message_type, :subject, :message)
    ");

    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':message_type' => $message_type,
        ':subject' => $subject,
        ':message' => $message
    ]);

    // Send email notification to admin
    try {

        require_once __DIR__ . '/vendor/autoload.php';

        $mailConfig = require __DIR__ . '/config/mail.php';

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = $mailConfig['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $mailConfig['username'];
        $mail->Password   = $mailConfig['password'];
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $mailConfig['port'];

        $mail->CharSet = 'UTF-8';

        $mail->setFrom(
            $mailConfig['from_email'],
            $mailConfig['from_name']
        );

        $mail->addAddress($mailConfig['admin_email']);

        $mail->addReplyTo($email, $name);

        $mail->isHTML(true);

        $mail->Subject = 'New message - Orchid Boutique';

        $mail->Body = "
            <h2>New message from Orchid Boutique</h2>
            <p><strong>Name:</strong> " . h($name) . "</p>
            <p><strong>Email:</strong> " . h($email) . "</p>
            <p><strong>Type:</strong> " . h($message_type) . "</p>
            <p><strong>Subject:</strong> " . h($subject) . "</p>
            <p><strong>Message:</strong></p>
            <p>" . nl2br(h($message)) . "</p>
        ";

        $mail->AltBody =
            "Name: $name\n" .
            "Email: $email\n" .
            "Type: $message_type\n" .
            "Subject: $subject\n" .
            "Message: $message";

        $mail->send();

        $email_sent = true;

    } catch (\Throwable $mailException) {

        $email_error = true;
    }

    // Database save succeeded
    $success = true;

    // Clear form
    $name = $_SESSION['user_name'] ?? '';
    $email = $_SESSION['user_email'] ?? '';
    $message_type = 'inquiry';
    $subject = '';
    $message = '';

} catch (PDOException $e) {

    $error = true;
}

        } else {
            $error = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= h($current_lang) ?>" dir="<?= h($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $is_ar ? 'تواصلي معنا | Orchid Boutique' : 'Contact Us | Orchid Boutique' ?></title>

    <meta name="description" content="<?= $is_ar
        ? 'تواصلي مع أوركيد بوتيك، يسعدنا استقبال استفساراتكِ ورسائلكِ.'
        : 'Get in touch with Orchid Boutique. We would love to hear from you.' ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Montserrat:wght@400;500;600;700&family=Tajawal:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="assets/css/orchid-home.css?v=2">
</head>

<body dir="<?= h($dir) ?>">

<div class="topbar">
    <div class="container topbar-inner">
        <span>
            <i class="fa-solid fa-truck-fast"></i>
            <?= $is_ar ? 'أزياء أنيقة، بتفاصيل تعبّر عنكِ' : 'Elegant fashion, made for your style' ?>
        </span>

        <div class="topbar-right">
            <a href="contact.php?lang=<?= h($next_lang) ?>">
                <?= $is_ar ? 'EN | العربية' : 'EN | AR' ?>
            </a>
            <a href="#social" aria-label="Instagram">
                <i class="fa-brands fa-instagram"></i>
            </a>
            <a href="#social" aria-label="Facebook">
                <i class="fa-brands fa-facebook-f"></i>
            </a>
        </div>
    </div>
</div>

<header class="site-header">
    <div class="container header-inner">

        <a class="brand" href="index.php?lang=<?= h($current_lang) ?>">
            <span class="brand-flower">
                <i class="fa-brands fa-pagelines"></i>
            </span>
            <span>
                Orchid
                <small>B O U T I Q U E</small>
            </span>
        </a>

        <nav class="main-nav" aria-label="Main navigation">
            <a href="index.php?lang=<?= h($current_lang) ?>">
                <?= $is_ar ? 'الرئيسية' : 'Home' ?>
            </a>

            <div class="nav-dropdown">
                <a href="javascript:void(0)">
                    <?= $is_ar ? 'التصنيفات' : 'Categories' ?>
                    <i class="fa-solid fa-chevron-down nav-chevron"></i>
                </a>

                <div class="nav-dropdown-menu">
                    <a href="category.php?cat=abaya&lang=<?= h($current_lang) ?>">
                        <?= $is_ar ? 'العبايات' : 'Abayas' ?>
                    </a>

                    <a href="category.php?cat=dresses&lang=<?= h($current_lang) ?>">
                        <?= $is_ar ? 'الفساتين' : 'Dresses' ?>
                    </a>

                    <div class="nav-subdropdown">
                        <a href="category.php?cat=casual&lang=<?= h($current_lang) ?>">
                            <?= $is_ar ? 'الأزياء اليومية' : 'Casual Wear' ?>
                        </a>

                        <div class="nav-subdropdown-menu">
                            <a href="category.php?cat=casual&lang=<?= h($current_lang) ?>">
                                <?= $is_ar ? 'كل الأزياء اليومية' : 'All Casual Wear' ?>
                            </a>

                            <a href="category.php?cat=casual&sub=pants&lang=<?= h($current_lang) ?>">
                                <?= $is_ar ? 'البناطيل' : 'Pants' ?>
                            </a>

                            <a href="category.php?cat=casual&sub=tops&lang=<?= h($current_lang) ?>">
                                <?= $is_ar ? 'البلايز والقمصان' : 'Tops & Shirts' ?>
                            </a>

                            <a href="category.php?cat=casual&sub=summer&lang=<?= h($current_lang) ?>">
                                <?= $is_ar ? 'الملابس الصيفية' : 'Summer Wear' ?>
                            </a>

                            <a href="category.php?cat=casual&sub=winter&lang=<?= h($current_lang) ?>">
                                <?= $is_ar ? 'الملابس الشتوية' : 'Winter Wear' ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <a href="new-arrivals.php?lang=<?= h($current_lang) ?>">
                <?= $is_ar ? 'أحدث المنتجات' : 'New Arrivals' ?>
            </a>

            <a href="offers.php?lang=<?= h($current_lang) ?>">
                <?= $is_ar ? 'العروض' : 'Offers' ?>
            </a>

            <a href="about.php?lang=<?= h($current_lang) ?>">
                <?= $is_ar ? 'عن أوركيد' : 'About' ?>
            </a>

            <a href="contact.php?lang=<?= h($current_lang) ?>">
                <?= $is_ar ? 'التواصل' : 'Contact' ?>
            </a>
        </nav>

        <div class="header-actions">
            <a href="wishlist.php?lang=<?= h($current_lang) ?>" aria-label="Wishlist">
                <i class="fa-regular fa-heart"></i>
            </a>

            <a class="cart-icon-link" href="cart.php?lang=<?= h($current_lang) ?>" aria-label="Cart">
                <i class="fa-solid fa-bag-shopping"></i>
                <span class="badge cart-badge">0</span>
            </a>

            <?php if ($is_logged_in): ?>
                <a class="account-link" href="profile.php?lang=<?= h($current_lang) ?>">
                    <?= $is_ar ? 'حسابي' : 'My Account' ?>
                </a>
            <?php else: ?>
                <a class="account-link" href="login.php?lang=<?= h($current_lang) ?>">
                    <?= $is_ar ? 'تسجيل الدخول' : 'Login / Sign Up' ?>
                </a>
            <?php endif; ?>
        </div>

    </div>
</header>

<main class="contact-page">

    <section class="contact-hero">
        <span class="contact-kicker">WE WOULD LOVE TO HEAR FROM YOU</span>

        <h1>
            <?= $is_ar ? 'يسعدنا تواصلكِ معنا' : 'Get In Touch' ?>
        </h1>

        <p>
            <?= $is_ar
                ? 'لديكِ سؤال أو استفسار؟ نحن هنا للاستماع إليكِ ومساعدتكِ. أرسلي لنا رسالتكِ وسنسعد بالتواصل معكِ.'
                : 'Have a question or need assistance? We are here to listen and help. Send us a message and we will be happy to hear from you.' ?>
        </p>
    </section>

    <section class="contact-section">
        <div class="contact-layout">

            <aside class="contact-info">
                <h2><?= $is_ar ? 'معلومات التواصل' : 'Contact Information' ?></h2>

                <p>
                    <?= $is_ar
                        ? 'يسعدنا أن نكون بالقرب منكِ. تواصلي معنا عبر قنواتنا المتاحة.'
                        : 'We would love to stay connected. Reach out to us through our available channels.' ?>
                </p>

                <div class="contact-detail">
                    <div class="contact-detail-icon">
                        <i class="fa-regular fa-envelope"></i>
                    </div>
                    <div>
                        <strong><?= $is_ar ? 'البريد الإلكتروني' : 'Email' ?></strong>
                        <span><?= $is_ar ? 'أضيفي بريد المتجر هنا' : 'Add your store email here' ?></span>
                    </div>
                </div>

                <div class="contact-detail">
                    <div class="contact-detail-icon">
                        <i class="fa-solid fa-phone"></i>
                    </div>
                    <div>
                        <strong><?= $is_ar ? 'رقم الهاتف' : 'Phone' ?></strong>
                        <span><?= $is_ar ? 'أضيفي رقم المتجر هنا' : 'Add your store phone here' ?></span>
                    </div>
                </div>

                <div class="contact-detail">
                    <div class="contact-detail-icon">
                        <i class="fa-regular fa-clock"></i>
                    </div>
                    <div>
                        <strong><?= $is_ar ? 'خدمة العملاء' : 'Customer Care' ?></strong>
                        <span><?= $is_ar ? 'نسعد بخدمتكِ' : 'We are happy to assist you' ?></span>
                    </div>
                </div>

                <div class="contact-socials" id="social">
                    <a href="#social" aria-label="Instagram">
                        <i class="fa-brands fa-instagram"></i>
                    </a>
                    <a href="#social" aria-label="Facebook">
                        <i class="fa-brands fa-facebook-f"></i>
                    </a>
                    <a href="#social" aria-label="WhatsApp">
                        <i class="fa-brands fa-whatsapp"></i>
                    </a>
                </div>
            </aside>

            <div class="contact-form-box">
                <span class="section-label">
                    <?= $is_ar ? 'رسالتكِ تهمنا' : 'YOUR MESSAGE MATTERS' ?>
                </span>

                <h2><?= $is_ar ? 'أرسلي لنا رسالة' : 'Send Us A Message' ?></h2>

                <p>
                    <?= $is_ar
                        ? 'املئي البيانات التالية، وسنكون سعداء بالاستماع إلى استفساركِ.'
                        : 'Fill in the details below. We would be happy to hear from you.' ?>
                </p>

                <?php if ($login_required): ?>
                    <div class="contact-alert error" role="alert">
                        <?= $is_ar
                            ? 'يجب عليكِ تسجيل الدخول أولًا لإرسال رسالتكِ.'
                            : 'You must log in first to send your message.' ?>

                        <a href="login.php?lang=<?= h($current_lang) ?>">
                            <?= $is_ar ? 'تسجيل الدخول' : 'Log In' ?>
                        </a>
                    </div>

                <?php elseif ($success): ?>
                    <div class="contact-alert success" role="status">
                        <?= $is_ar
                            ? 'تم استلام رسالتكِ بنجاح، شكرًا لتواصلكِ معنا 💜'
                            : 'Your message has been received successfully. Thank you! 💜' ?>
                    </div>

                <?php elseif ($error): ?>
                    <div class="contact-alert error" role="alert">
                        <?= $is_ar
                            ? 'تعذر إرسال رسالتكِ. تأكدي من صحة البيانات وحاولي مجددًا.'
                            : 'Unable to submit your message. Please check your details and try again.' ?>
                    </div>
                <?php endif; ?>

                <form class="contact-form" action="contact.php?lang=<?= h($current_lang) ?>" method="post">

                    <div>
                        <label for="contact-name">
                            <?= $is_ar ? 'الاسم الكامل' : 'Full Name' ?>
                        </label>

                        <input
                            type="text"
                            id="contact-name"
                            name="name"
                            value="<?= h($name) ?>"
                            placeholder="<?= $is_ar ? 'اكتبي اسمكِ' : 'Enter your name' ?>"
                            required
                        >
                    </div>

                    <div>
                        <label for="contact-email">
                            <?= $is_ar ? 'البريد الإلكتروني' : 'Email Address' ?>
                        </label>

                        <input
                            type="email"
                            id="contact-email"
                            name="email"
                            value="<?= h($email) ?>"
                            placeholder="example@email.com"
                            required
                        >
                    </div>

                    <div>
                        <label for="contact-type">
                            <?= $is_ar ? 'نوع الرسالة' : 'Message Type' ?>
                        </label>

                        <select id="contact-type" name="message_type" required>
                            <option value="inquiry" <?= $message_type === 'inquiry' ? 'selected' : '' ?>>
                                <?= $is_ar ? 'استفسار' : 'Inquiry' ?>
                            </option>

                            <option value="complaint" <?= $message_type === 'complaint' ? 'selected' : '' ?>>
                                <?= $is_ar ? 'شكوى' : 'Complaint' ?>
                            </option>

                            <option value="suggestion" <?= $message_type === 'suggestion' ? 'selected' : '' ?>>
                                <?= $is_ar ? 'اقتراح' : 'Suggestion' ?>
                            </option>

                            <option value="order" <?= $message_type === 'order' ? 'selected' : '' ?>>
                                <?= $is_ar ? 'مشكلة في طلب' : 'Order Issue' ?>
                            </option>
                        </select>
                    </div>

                    <div>
                        <label for="contact-subject">
                            <?= $is_ar ? 'موضوع الرسالة' : 'Subject' ?>
                        </label>

                        <input
                            type="text"
                            id="contact-subject"
                            name="subject"
                            value="<?= h($subject) ?>"
                            placeholder="<?= $is_ar ? 'موضوع استفساركِ' : 'Message subject' ?>"
                            required
                        >
                    </div>

                    <div>
                        <label for="contact-message">
                            <?= $is_ar ? 'رسالتكِ' : 'Your Message' ?>
                        </label>

                        <textarea
                            id="contact-message"
                            name="message"
                            placeholder="<?= $is_ar ? 'اكتبي رسالتكِ هنا...' : 'Write your message here...' ?>"
                            required
                        ><?= h($message) ?></textarea>
                    </div>

                    <button type="submit">
                        <?= $is_ar ? 'إرسال الرسالة' : 'Send Message' ?>
                        <i class="fa-regular fa-paper-plane"></i>
                    </button>

                </form>
            </div>

        </div>
    </section>

    <section class="contact-note">
        <i class="fa-solid fa-heart"></i>

        <h3>
            <?= $is_ar ? 'شكرًا لاختياركِ أوركيد' : 'Thank You For Choosing Orchid' ?>
        </h3>

        <p>
            <?= $is_ar
                ? 'ثقتكِ واهتمامكِ يعنيان لنا الكثير.'
                : 'Your trust and support mean so much to us.' ?>
        </p>
    </section>

</main>

</body>
</html>
