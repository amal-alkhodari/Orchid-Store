
<?php
session_start();


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
?>
<!DOCTYPE html>
<html lang="<?= h($current_lang) ?>" dir="<?= h($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $is_ar ? 'عن أوركيد | Orchid Boutique' : 'About Us | Orchid Boutique' ?></title>

    <meta name="description" content="<?= $is_ar
        ? 'اكتشفي قصة أوركيد بوتيك، حيث تلتقي الأناقة بالتفاصيل التي تعبّر عنكِ.'
        : 'Discover the story of Orchid Boutique, where elegance meets the details that express you.' ?>">

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
            <a href="about.php?lang=<?= h($next_lang) ?>">
                <?= $is_ar ? 'EN | العربية' : 'EN | AR' ?>
            </a>
            <a href="#instagram" aria-label="Instagram">
                <i class="fa-brands fa-instagram"></i>
            </a>
            <a href="#instagram" aria-label="Facebook">
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

            <?php if (isset($_SESSION['user_id'])): ?>
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

<main class="about-page">

    
    <section class="about-hero">
        <div class="about-hero-content">
            <span class="about-kicker">THE STORY OF ORCHID</span>

            <h1>
                <?= $is_ar ? 'أكثر من مجرد أناقة' : 'More Than Just Elegance' ?>
            </h1>

            <p>
                <?= $is_ar
                    ? 'في أوركيد، نؤمن أن لكل امرأة أسلوبًا يميزها. نحتفي بجمالكِ ونمنحكِ مساحة تعبّرين فيها عن نفسكِ بثقة وأناقة.'
                    : 'At Orchid, we believe every woman has a style of her own. We celebrate your beauty and give you space to express yourself with confidence and elegance.' ?>
            </p>
        </div>
    </section>

    
    <section class="about-section">
        <div class="about-story">

            <div class="story-art">
                <div class="story-art-inner">
                    <i class="fa-brands fa-pagelines"></i>
                    <strong>Orchid</strong>
                    <small>BOUTIQUE</small>
                </div>
            </div>

            <div class="story-text">
                <span class="section-label">
                    <?= $is_ar ? 'حكايتنا' : 'OUR STORY' ?>
                </span>

                <h2>
                    <?= $is_ar ? 'أناقتكِ هي حكايتنا' : 'Your Style Is Our Story' ?>
                </h2>

                <p>
                    <?= $is_ar
                        ? 'وُلدت فكرة أوركيد من حب التفاصيل الجميلة، والإيمان بأن الأناقة الحقيقية تبدأ عندما تشعر المرأة بالراحة والثقة في اختياراتها.'
                        : 'Orchid was born from a love of beautiful details and the belief that true elegance begins when a woman feels comfortable and confident in her choices.' ?>
                </p>

                <p>
                    <?= $is_ar
                        ? 'لهذا نحرص على تقديم تشكيلة متنوعة تجمع بين العبايات والفساتين والأزياء اليومية، لتجدي ما يناسب ذوقكِ ومناسباتكِ المختلفة.'
                        : 'That is why we bring together abayas, dresses and everyday fashion, helping you find pieces that suit your taste and every occasion.' ?>
                </p>
            </div>

        </div>
    </section>

    
    <section class="about-values">
        <div class="values-heading">
            <span class="section-label">
                <?= $is_ar ? 'ما نؤمن به' : 'WHAT WE BELIEVE IN' ?>
            </span>

            <h2>
                <?= $is_ar ? 'تفاصيل تصنع الفرق' : 'The Details Make The Difference' ?>
            </h2>

            <p>
                <?= $is_ar
                    ? 'كل اختيار في أوركيد يعكس اهتمامنا بالأناقة والتجربة التي تستحقينها.'
                    : 'Every choice at Orchid reflects our care for elegance and the experience you deserve.' ?>
            </p>
        </div>

        <div class="values-grid">

            <article class="value-card">
                <div class="value-icon">
                    <i class="fa-solid fa-gem"></i>
                </div>
                <h3><?= $is_ar ? 'أناقة مميزة' : 'Distinctive Elegance' ?></h3>
                <p>
                    <?= $is_ar
                        ? 'نختار تصاميم تمنحكِ حضورًا أنيقًا وتعكس ذوقكِ الخاص.'
                        : 'We select designs that give you an elegant presence and reflect your personal taste.' ?>
                </p>
            </article>

            <article class="value-card">
                <div class="value-icon">
                    <i class="fa-solid fa-heart"></i>
                </div>
                <h3><?= $is_ar ? 'اهتمام بالتفاصيل' : 'Attention to Detail' ?></h3>
                <p>
                    <?= $is_ar
                        ? 'نهتم بالتفاصيل الصغيرة التي تجعل تجربة التسوق أكثر تميزًا.'
                        : 'We care about the little details that make your shopping experience special.' ?>
                </p>
            </article>

            <article class="value-card">
                <div class="value-icon">
                    <i class="fa-solid fa-star"></i>
                </div>
                <h3><?= $is_ar ? 'اختيارات متنوعة' : 'Versatile Choices' ?></h3>
                <p>
                    <?= $is_ar
                        ? 'تشكيلة تناسب إطلالاتكِ اليومية ومناسباتكِ الخاصة.'
                        : 'A collection for your everyday looks and special occasions.' ?>
                </p>
            </article>

        </div>
    </section>

    
    <section class="about-quote">
        <i class="fa-solid fa-quote-right"></i>

        <h2>
            <?= $is_ar
                ? 'كوني أنتِ، واختاري ما يشبهكِ، ودعي أناقتكِ تحكي قصتكِ.'
                : 'Be yourself, choose what reflects you, and let your style tell your story.' ?>
        </h2>

        <p>ORCHID BOUTIQUE</p>
    </section>

    
    <section class="about-cta">
        <h2>
            <?= $is_ar ? 'اكتشفي عالم أوركيد' : 'Discover Orchid' ?>
        </h2>

        <p>
            <?= $is_ar
                ? 'اختاري إطلالتكِ القادمة من مجموعتنا.'
                : 'Find your next favorite look in our collection.' ?>
        </p>

        <a href="new-arrivals.php?lang=<?= h($current_lang) ?>">
            <?= $is_ar ? 'اكتشفي أحدث المنتجات' : 'Explore New Arrivals' ?>
            <i class="fa-solid fa-arrow-left"></i>
        </a>
    </section>

</main>

</body>
</html>
