<?php
session_start();

require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$current_lang = isset($_SESSION['lang'])
    ? $_SESSION['lang']
    : (isset($_GET['lang']) && $_GET['lang'] === 'en' ? 'en' : 'ar');

if (!in_array($current_lang, ['ar', 'en'], true)) {
    $current_lang = 'ar';
}

$_SESSION['lang'] = $current_lang;

$dir = ($current_lang === 'ar') ? 'rtl' : 'ltr';
$lang_attr = $current_lang;

$base_path = __DIR__ . '/';
$lang_file = $base_path . 'languages/' . $current_lang . '.php';

$lang = [];

if (file_exists($lang_file)) {
    include $lang_file;
}

function h($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

$home_text = $lang['nav_home']
    ?? (($current_lang === 'ar') ? 'الرئيسية' : 'Home');

$checkout_title = ($current_lang === 'ar')
    ? 'إتمام الطلب'
    : 'Checkout';

$total_text = $lang['total']
    ?? (($current_lang === 'ar') ? 'المجموع الكلي:' : 'Total:');
?>

<!DOCTYPE html>
<html lang="<?php echo h($lang_attr); ?>"
      dir="<?php echo h($dir); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo h($checkout_title); ?> - Orchid Boutique</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&family=Tajawal:wght@400;500;700;800&family=Cormorant+Garamond:wght@500;600;700&display=swap"
          rel="stylesheet">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    

    <!-- التصميم الجديد لصفحة إتمام الطلب -->
    <link rel="stylesheet" href="assets/css/checkout.css?v=1">
</head>

<body class="checkout-page">

    <!-- رأس الصفحة -->
    <header class="main-navbar">
        <div class="nav-container">

            <div class="nav-logo">
                <a href="index.php?lang=<?php echo h($current_lang); ?>">
                    <?php echo h($lang['store_name'] ?? 'Orchid'); ?><span>.</span>
                </a>
            </div>

            <nav class="nav-menu">
                <ul>
                    <li>
                        <a href="index.php?lang=<?php echo h($current_lang); ?>">
                            <?php echo h($home_text); ?>
                        </a>
                    </li>
                </ul>
            </nav>

        </div>
    </header>

    <!-- محتوى إتمام الطلب -->
    <main class="cart-page-container">

        <h1 class="cart-page-title">
            <i class="fa-solid fa-credit-card"></i>
            <?php echo h($checkout_title); ?>
        </h1>

        <form id="checkoutForm" onsubmit="submitOrder(event)">

            <div class="checkout-grid">

                <!-- بيانات الشحن والاستلام -->
                <section class="checkout-form-box">

                    <h3>
                        <?php echo ($current_lang === 'ar')
                            ? 'بيانات الشحن والاستلام'
                            : 'Shipping Information'; ?>
                    </h3>

                    <div class="form-group">
                        <label for="fullName">
                            <?php echo ($current_lang === 'ar')
                                ? 'الاسم الكامل'
                                : 'Full Name'; ?> *
                        </label>

                        <input
                            type="text"
                            id="fullName"
                            required
                            autocomplete="name"
                            placeholder="<?php echo ($current_lang === 'ar')
                                ? 'أدخلي اسمك الكامل'
                                : 'Enter your full name'; ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="phone">
                            <?php echo ($current_lang === 'ar')
                                ? 'رقم الهاتف'
                                : 'Phone Number'; ?> *
                        </label>

                        <input
                            type="tel"
                            id="phone"
                            required
                            autocomplete="tel"
                            placeholder="<?php echo ($current_lang === 'ar')
                                ? 'أدخلي رقم الهاتف'
                                : 'Enter phone number'; ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="address">
                            <?php echo ($current_lang === 'ar')
                                ? 'عنوان السكن (المدينة، الشارع)'
                                : 'Address (City, Street)'; ?> *
                        </label>

                        <textarea
                            id="address"
                            rows="3"
                            required
                            autocomplete="street-address"
                            placeholder="<?php echo ($current_lang === 'ar')
                                ? 'أدخلي العنوان بالتفصيل'
                                : 'Enter detailed address'; ?>"
                        ></textarea>
                    </div>

                    <!-- طريقة الدفع الحالية -->
                    <div class="form-group">

                        <label>
                            <?php echo ($current_lang === 'ar')
                                ? 'طريقة الدفع'
                                : 'Payment Method'; ?>
                        </label>

                        <div class="payment-method">

                            <label>
                                <input
                                    type="radio"
                                    name="payment"
                                    value="cod"
                                    checked
                                >

                                <?php echo ($current_lang === 'ar')
                                    ? 'الدفع عند الاستلام (Cash on Delivery)'
                                    : 'Cash on Delivery'; ?>
                            </label>

                            <label>
                                <input
                                    type="radio"
                                    name="payment"
                                    value="paypal"
                                >

                                PayPal
                            </label>

                            <label>
                                <input
                                    type="radio"
                                    name="payment"
                                    value="bank_transfer"
                                >

                                <?php echo ($current_lang === 'ar')
                                    ? 'التحويل البنكي'
                                    : 'Bank Transfer'; ?>
                            </label>

                        </div>
                    </div>

                </section>

                <!-- ملخص الطلب -->
                <section class="checkout-summary-box">

                    <h3>
                        <?php echo ($current_lang === 'ar')
                            ? 'ملخص الطلب'
                            : 'Order Summary'; ?>
                    </h3>

                    <div
                        id="checkoutItemsList"
                        style="max-height:250px; overflow-y:auto; margin-bottom:20px;"
                    >
                        <!-- تُعرض المنتجات بواسطة JavaScript -->
                    </div>

                    <div
                        style="border-top:1px solid #eee;
                               padding-top:15px;
                               margin-bottom:20px;
                               display:flex;
                               justify-content:space-between;
                               font-size:18px;
                               font-weight:700;"
                    >

                        <span><?php echo h($total_text); ?></span>

                        <span id="checkoutTotalAmount">
                            $0.00
                        </span>

                    </div>

                    <button
                        type="submit"
                        class="btn-checkout"
                        style="width:100%;
                               padding:14px;
                               text-align:center;
                               cursor:pointer;
                               border:none;
                               font-family:inherit;
                               font-size:16px;"
                    >
                        <?php echo ($current_lang === 'ar')
                            ? 'تأكيد الطلب النهائي'
                            : 'Confirm Order'; ?>
                    </button>

                </section>

            </div>
        </form>

    </main>

    <!-- تحديد اللغة التي تستخدمها وظائف JavaScript الحالية -->
    <script>
        window.ORCHID_LANG = <?php
            echo json_encode(
                $current_lang,
                JSON_UNESCAPED_UNICODE
            );
        ?>;
    </script>

    <!-- الإبقاء على ملف السلة الحالي -->
    <script src="js/cart.js"></script>

</body>
</html>