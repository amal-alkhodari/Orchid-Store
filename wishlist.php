<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

$current_lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'ar';
$current_lang = in_array($current_lang, ['ar', 'en'], true) ? $current_lang : 'ar';
$_SESSION['lang'] = $current_lang;

$is_ar = $current_lang === 'ar';
$dir = $is_ar ? 'rtl' : 'ltr';

function ch($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// جلب صورة المستخدم المسجل
$userAvatarUrl = '';
if (isset($_SESSION['user_id'])) {
    try {
        $uStmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $uStmt->execute([(int)$_SESSION['user_id']]);
        $uRow = $uStmt->fetch(PDO::FETCH_ASSOC);
        if ($uRow) {
            $rawAvatar = $uRow['avatar'] ?? $uRow['profile_image'] ?? $uRow['image'] ?? '';
            if (!empty($rawAvatar)) {
                $userAvatarUrl = ltrim($rawAvatar, '/');
            }
        }
    } catch (PDOException $e) {}
}
?>
<!DOCTYPE html>
<html lang="<?= ch($current_lang) ?>" dir="<?= ch($dir) ?>" class="notranslate" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google" content="notranslate">
    <title><?= $is_ar ? 'قائمة المفضلة | Orchid Boutique' : 'Wishlist | Orchid Boutique' ?></title>

    <link rel="preconnect" href="https://googleapis.com">
    <link rel="preconnect" href="https://gstatic.com" crossorigin>
    <link href="https://googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Montserrat:wght@400;600;700&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/wishlist.css?v=10">
</head>
<body class="orchid-cart-page">

<div class="cart-topbar">
    <div class="cart-container cart-topbar-inner">
        <span>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
            <?= $is_ar ? 'القطع التي نالت إعجابكِ في أوركيد' : 'Your saved favorites at Orchid' ?>
        </span>
    </div>
</div>

<header class="cart-header">
    <div class="cart-container cart-header-inner">
        <a class="cart-brand" href="index.php?lang=<?= ch($current_lang) ?>">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#581D2C" stroke-width="2"><path d="M12 7.5a4.5 4.5 0 1 1 4.5 4.5M12 7.5A4.5 4.5 0 1 0 7.5 12M12 7.5V9m-4.5 3a4.5 4.5 0 1 0 4.5 4.5M7.5 12H9m7.5 0a4.5 4.5 0 1 1-4.5 4.5m4.5-4.5H15m-3 4.5V15"/><circle cx="12" cy="12" r="3"/></svg>
            <span>Orchid<small>B O U T I Q U E</small></span>
        </a>

        <nav class="cart-nav">
            <a href="index.php?lang=<?= ch($current_lang) ?>"><?= $is_ar ? 'الرئيسية' : 'Home' ?></a>
            <a href="category.php?cat=casual&lang=<?= ch($current_lang) ?>"><?= $is_ar ? 'الأزياء اليومية' : 'Casual' ?></a>
            <a href="category.php?cat=abaya&lang=<?= ch($current_lang) ?>"><?= $is_ar ? 'العبايات' : 'Abayas' ?></a>
            <a href="category.php?cat=dresses&lang=<?= ch($current_lang) ?>"><?= $is_ar ? 'فساتين السهرة' : 'Dresses' ?></a>
        </nav>

        <div class="cart-header-actions">
            <!-- دائرة حقيبة التسوق بجانب الاسم -->
            <a href="cart.php?lang=<?= ch($current_lang) ?>" title="<?= $is_ar ? 'سلة التسوق' : 'Shopping Cart' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#581D2C" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            </a>

            <?php if (isset($_SESSION['user_id'])): ?>
                <a class="cart-account-btn" href="profile.php"><?= ch($_SESSION['user_name'] ?? ($is_ar ? 'حسابي' : 'Account')) ?></a>
            <?php else: ?>
                <a class="cart-account-btn" href="login.php?lang=<?= ch($current_lang) ?>"><?= $is_ar ? 'تسجيل الدخول' : 'Login' ?></a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="cart-main">
    <div class="cart-container">

        <section class="cart-page-intro">
            <nav class="cart-breadcrumb">
                <a href="index.php?lang=<?= ch($current_lang) ?>"><?= $is_ar ? 'الرئيسية' : 'Home' ?></a>
                <span>‹</span>
                <span><?= $is_ar ? 'قائمة المفضلة' : 'Wishlist' ?></span>
            </nav>

            <div class="cart-title-row">
                <div>
                    <span class="cart-eyebrow">ORCHID BOUTIQUE</span>
                    <h1><?= $is_ar ? 'قائمة المفضلة' : 'My Wishlist' ?></h1>
                    <p><?= $is_ar ? 'احتفظي بقطعكِ المفضلة للعودة إليها وإضافتها للسلة في أي وقت.' : 'Save your favorite pieces to shop anytime.' ?></p>
                </div>

                <div class="cart-title-icon" style="overflow:hidden; padding:0;">
                    <?php if (!empty($userAvatarUrl)): ?>
                        <img src="<?= ch($userAvatarUrl) ?>" alt="User" style="width:100%; height:100%; object-fit:cover; display:block;">
                    <?php elseif (isset($_SESSION['user_name'])): ?>
                        <span style="font-size:22px; font-weight:800; color:#581D2C;">
                            <?= ch(mb_substr(trim($_SESSION['user_name']), 0, 1)) ?>
                        </span>
                    <?php else: ?>
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#581D2C" stroke-width="2"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="cart-content-section" id="wishlistContainer"></section>

    </div>
</main>

<script>
const isAr = <?= json_encode($is_ar) ?>;
const lang = <?= json_encode($current_lang) ?>;
const container = document.getElementById('wishlistContainer');

function getWishlist() {
    const keys = ['orchid_wishlist', 'wishlist', 'favorites'];
    for (const k of keys) {
        try {
            const data = JSON.parse(localStorage.getItem(k));
            if (Array.isArray(data) && data.length > 0) {
                // تنظيف العناصر القديمة ذات الأرقام العشوائية الطويلة (أكبر من 99999)
                const validItems = data.filter(item => Number(item.id) > 0 && Number(item.id) < 100000);
                if (validItems.length !== data.length) {
                    localStorage.setItem(k, JSON.stringify(validItems));
                }
                return { key: k, items: validItems };
            }
        } catch (e) {}
    }
    return { key: 'orchid_wishlist', items: [] };
}

function renderWishlist() {
    const { key, items } = getWishlist();

    if (!items.length) {
        container.innerHTML = `
            <div class="wish-empty-box">
                <div class="wish-empty-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#581D2C" stroke-width="2"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                </div>
                <h2>${isAr ? 'قائمة المفضلة فارغة حالياً' : 'Your wishlist is empty'}</h2>
                <p>${isAr ? 'اضغطي على أيقونة القلب بجانب أي منتج لحفظه هنا.' : 'Tap the heart icon on any product to save it here.'}</p>
                <a href="index.php?lang=${lang}#categories" class="wish-explore-btn">
                    ${isAr ? 'استكشفي المنتجات' : 'Explore Products'}
                </a>
            </div>
        `;
        return;
    }

    container.innerHTML = `<div class="wishlist-grid">` + items.map((item, idx) => `
        <article class="wish-card">
            <img src="${item.image || 'images/placeholder.jpg'}" alt="${item.name || ''}">
            <div class="wish-body">
                <h3>${isAr ? (item.name_ar || item.name || '') : (item.name_en || item.name || '')}</h3>
                <div class="wish-price">₪${parseFloat(item.price || 0).toFixed(2)}</div>
                <div class="wish-actions">
                    <a class="wish-btn-view" href="product-details.php?id=${item.id}&lang=${lang}">
                        ${isAr ? 'عرض المنتج' : 'View Product'}
                    </a>
                    <button type="button" class="wish-btn-remove" onclick="removeWishItem('${key}', ${idx})" title="${isAr ? 'حذف من المفضلة' : 'Remove'}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                    </button>
                </div>
            </div>
        </article>
    `).join('') + `</div>`;
}

function removeWishItem(key, index) {
    const list = JSON.parse(localStorage.getItem(key) || '[]');
    const removedItem = list[index];

    if (removedItem && removedItem.id) {
        fetch('wishlist_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `product_id=${encodeURIComponent(removedItem.id)}&action=remove`
        }).catch(() => {});
    }

    list.splice(index, 1);
    localStorage.setItem(key, JSON.stringify(list));
    renderWishlist();
}


renderWishlist();
</script>

</body>
</html>
