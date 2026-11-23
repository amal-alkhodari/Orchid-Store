<?php
require_once __DIR__ . '/auth.php';

$current_lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'ar';
$current_lang = $current_lang === 'en' ? 'en' : 'ar';
$_SESSION['lang'] = $current_lang;

$isArabic = $current_lang === 'ar';
$user_id = (int) $_SESSION['user_id'];

function h($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$statusLabels = [
    'pending' => ['ar' => 'قيد المراجعة', 'en' => 'Pending'],
    'confirmed' => ['ar' => 'تم التأكيد', 'en' => 'Confirmed'],
    'processing' => ['ar' => 'قيد التجهيز', 'en' => 'Processing'],
    'shipped' => ['ar' => 'تم الشحن', 'en' => 'Shipped'],
    'delivered' => ['ar' => 'تم التسليم', 'en' => 'Delivered'],
    'cancelled' => ['ar' => 'ملغي', 'en' => 'Cancelled'],
];

try {
    $stmt = $pdo->prepare(
        "SELECT * FROM orders
         WHERE user_id = ?
         ORDER BY created_at DESC"
    );
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll();

    $itemsStmt = $pdo->prepare(
        "SELECT oi.*, p.image, p.name_ar, p.name_en
         FROM order_items oi
         LEFT JOIN products p ON p.id = oi.product_id
         WHERE oi.order_id = ?
         ORDER BY oi.id ASC"
    );

    foreach ($orders as &$order) {
        $itemsStmt->execute([$order['id']]);
        $order['items'] = $itemsStmt->fetchAll();
    }
    unset($order);

} catch (PDOException $e) {
    error_log('Orchid orders error: ' . $e->getMessage());
    exit($isArabic
        ? 'تعذر تحميل الطلبات. يرجى المحاولة لاحقًا.'
        : 'Could not load orders. Please try again later.');
}
     $totalOrdersCount = count($orders);
        $totalOrdersValue = array_sum(array_map(
            fn($order) => (float)$order['total_amount'],
            $orders
        ));
        // معالجة تعديل بيانات التوصيل أو إلغاء/حذف الطلب من طرف الزبونة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $currentUserId = (int)$_SESSION['user_id'];
    $orderAction   = $_POST['order_action'] ?? '';
    $targetOrderId = (int)($_POST['order_id'] ?? 0);

    if ($targetOrderId > 0) {
        if ($orderAction === 'edit_details') {
            $newName    = trim($_POST['full_name'] ?? '');
            $newPhone   = trim($_POST['phone'] ?? '');
            $newAddress = trim($_POST['address'] ?? '');

            if ($newName !== '' && $newPhone !== '' && $newAddress !== '') {
                $upd = $pdo->prepare("
                    UPDATE orders 
                    SET full_name = ?, phone = ?, address = ? 
                    WHERE id = ? AND user_id = ? AND status IN ('pending', 'confirmed')
                ");
                $upd->execute([$newName, $newPhone, $newAddress, $targetOrderId, $currentUserId]);
                header("Location: orders.php?lang=" . urlencode($current_lang) . "&msg=updated");
                exit;
            }
        } elseif ($orderAction === 'cancel_order') {
            // حذف عناصر الطلب ثم حذف الطلب نفسه (فقط إذا كان قيد المراجعة أو التأكيد)
            $chk = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ? AND status IN ('pending', 'confirmed')");
            $chk->execute([$targetOrderId, $currentUserId]);
            if ($chk->fetch()) {
                $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$targetOrderId]);
                $pdo->prepare("DELETE FROM orders WHERE id = ? AND user_id = ?")->execute([$targetOrderId, $currentUserId]);
                header("Location: orders.php?lang=" . urlencode($current_lang) . "&msg=deleted");
                exit;
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="<?= h($current_lang) ?>" dir="<?= $isArabic ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $isArabic ? 'طلباتي | Orchid Boutique' : 'My Orders | Orchid Boutique' ?></title>
    <link rel="preconnect" href="https://googleapis.com">
    <link rel="preconnect" href="https://gstatic.com" crossorigin>
    <link href="https://googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/orders.css?v=5">
</head>

<body>
<main class="orders-page">

    <!-- ترويسة الصفحة -->
    <header class="orders-topbar">
        <div>
            <div class="orders-brand">ORCHID BOUTIQUE</div>
            <h1 class="orders-heading">
                <?= $isArabic ? 'طلباتي' : 'My Orders' ?>
            </h1>
            <p class="orders-subtitle">
                <?= $isArabic
                    ? 'تابعي حالة طلباتكِ، وعدّلي بيانات التوصيل أو ألغي الطلب قبل الشحن بكل سهولة.'
                    : 'Track your orders, edit delivery details, or cancel before shipping.' ?>
            </p>
        </div>

        <a class="store-link" href="index.php?lang=<?= h($current_lang) ?>">
            <?= $isArabic ? '← العودة للمتجر' : 'Back to Store →' ?>
        </a>
    </header>

    <!-- رسائل التنبيه عند التعديل أو الحذف -->
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
        <div class="orders-toast success">
            ✓ <?= $isArabic ? 'تم تحديث بيانات التوصيل للطلب بنجاح.' : 'Order delivery details updated successfully.' ?>
        </div>
    <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
        <div class="orders-toast warning">
            ✓ <?= $isArabic ? 'تم إلغاء وحذف الطلب من قائمتكِ.' : 'Order has been cancelled and removed.' ?>
        </div>
    <?php endif; ?>

    <?php
        $totalOrdersCount = count($orders);
        $totalOrdersValue = array_sum(array_map(
            fn($order) => (float)$order['total_amount'],
            $orders
        ));
    ?>

    <!-- كبسولة الفلترة والإحصاء السريع -->
    <nav class="customer-orders-pill-bar">
        <div class="c-nav-dropdown">
            <button type="button" class="c-pill-link active" onclick="filterCustomerOrders('all', this)">
                <span><?= $isArabic ? 'جميع الطلبات' : 'All Orders' ?></span>
                <span class="c-pill-badge"><?= $totalOrdersCount ?></span>
            </button>
        </div>

        <div class="c-nav-dropdown">
            <button type="button" class="c-pill-link" onclick="filterCustomerOrders('pending', this)">
                <span><?= $isArabic ? 'قيد المراجعة' : 'Pending' ?></span>
            </button>
        </div>

        <div class="c-nav-dropdown">
            <button type="button" class="c-pill-link" onclick="filterCustomerOrders('confirmed', this)">
                <span><?= $isArabic ? 'تم التأكيد' : 'Confirmed' ?></span>
            </button>
        </div>

        <div class="c-nav-dropdown">
            <button type="button" class="c-pill-link" onclick="filterCustomerOrders('shipped', this)">
                <span><?= $isArabic ? 'تم الشحن' : 'Shipped' ?></span>
            </button>
        </div>

        <div class="c-nav-dropdown">
            <button type="button" class="c-pill-link" onclick="filterCustomerOrders('delivered', this)">
                <span><?= $isArabic ? 'تم التسليم' : 'Delivered' ?></span>
            </button>
        </div>

        <div class="c-nav-dropdown">
            <div class="c-pill-total">
                <span><?= $isArabic ? 'الإجمالي:' : 'Total:' ?></span>
                <span class="c-pill-badge burgundy">$<?= number_format($totalOrdersValue, 2) ?></span>
            </div>
        </div>
    </nav>

    <?php if (empty($orders)): ?>
        <section class="empty-orders">
            <div class="empty-icon">🛍️</div>
            <h2><?= $isArabic ? 'لا توجد طلبات حالياً' : 'No orders yet' ?></h2>
            <p><?= $isArabic ? 'اختاري القطع التي تحبينها، وسنحتفظ بتفاصيل طلباتكِ هنا.' : 'Explore the collection and your orders will appear here.' ?></p>
            <a class="store-link" href="index.php?lang=<?= h($current_lang) ?>">
                <?= $isArabic ? 'تسوّقي الآن' : 'Shop Now' ?>
            </a>
        </section>
    <?php else: ?>

        <?php foreach ($orders as $order): ?>
            <?php
                $status     = $order['status'] ?? 'pending';
                $statusText = $statusLabels[$status][$current_lang] ?? $status;
                $canModify  = in_array($status, ['pending', 'confirmed'], true);
            ?>

            <article class="order-card" data-status="<?= h($status) ?>">

                <!-- رأس بطاقة الطلب -->
                <header class="order-header">
                    <div class="order-id-block">
                        <h2 class="order-number">
                            <?= $isArabic ? 'طلب رقم #' : 'Order #' ?><?= (int)$order['id'] ?>
                        </h2>
                        <span class="order-date">
                            <?= $isArabic ? 'بتاريخ:' : 'Date:' ?> <?= h($order['created_at']) ?>
                        </span>
                    </div>

                    <div class="order-header-actions">
                        <span class="status-pill status-<?= h($status) ?>"><?= h($statusText) ?></span>

                        <?php if ($canModify): ?>
                            <button
                                type="button"
                                class="btn-order-edit"
                                onclick="openEditOrderModal(
                                    <?= (int)$order['id'] ?>,
                                    <?= htmlspecialchars(json_encode($order['full_name']), ENT_QUOTES, 'UTF-8') ?>,
                                    <?= htmlspecialchars(json_encode($order['phone']), ENT_QUOTES, 'UTF-8') ?>,
                                    <?= htmlspecialchars(json_encode($order['address']), ENT_QUOTES, 'UTF-8') ?>
                                )"
                            >
                                ✎ <?= $isArabic ? 'تعديل البيانات' : 'Edit Info' ?>
                            </button>

                            <form method="POST" style="margin:0;" onsubmit="return confirm('<?= $isArabic ? 'هل أنتِ متأكدة من إلغاء وحذف هذا الطلب نهائياً؟' : 'Are you sure you want to cancel and delete this order?' ?>')">
                                <input type="hidden" name="order_action" value="cancel_order">
                                <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                                <button type="submit" class="btn-order-delete">
                                    ✕ <?= $isArabic ? 'إلغاء الطلب' : 'Cancel' ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </header>

                <!-- جسم بطاقة الطلب (تقسيم شبكي واضح: المنتجات يميناً + بيانات التوصيل يساراً) -->
                <div class="order-body-grid">

                    <!-- العمود الأول: المنتجات المطلوبة -->
                    <div class="order-items-col">
                        <h3 class="section-title">
                            <?= $isArabic ? 'القطع المطلوبة' : 'Ordered Items' ?>
                        </h3>

                        <?php if (empty($order['items'])): ?>
                            <p class="muted-note"><?= $isArabic ? 'لا توجد تفاصيل منتجات.' : 'No items found.' ?></p>
                        <?php else: ?>
                            <div class="items-stack">
                                <?php foreach ($order['items'] as $item): ?>
                                    <?php
                                        $productName = $isArabic
                                            ? ($item['name_ar'] ?: $item['product_name'])
                                            : ($item['name_en'] ?: $item['product_name']);

                                        $rawImage = trim((string)($item['image'] ?? ''));
                                        if ($rawImage !== '' && !str_contains($rawImage, '/') && !str_starts_with($rawImage, 'http')) {
                                            $imagePath = 'assets/images/' . $rawImage;
                                        } else {
                                            $imagePath = ltrim($rawImage, '/');
                                        }

                                        $quantity = (int)$item['quantity'];
                                        $price    = (float)$item['price'];
                                    ?>

                                    <div class="order-product">
                                        <?php if ($imagePath !== ''): ?>
                                            <img
                                                class="product-image"
                                                src="<?= h($imagePath) ?>"
                                                alt="<?= h($productName) ?>"
                                                loading="lazy"
                                                onerror="this.style.display='none';this.nextElementSibling.style.display='grid';"
                                            >
                                            <div class="product-image-placeholder" style="display:none;">✿</div>
                                        <?php else: ?>
                                            <div class="product-image-placeholder">✿</div>
                                        <?php endif; ?>

                                                                                <div class="product-info">
                                            <p class="product-name"><?= h($productName) ?></p>
                                            <div class="product-meta">
                                                <span><?= $isArabic ? 'الكمية:' : 'Qty:' ?> <?= $quantity ?></span>
                                                <?php if (!empty($item['size'])): ?>
                                                    <span>· <?= $isArabic ? 'المقاس:' : 'Size:' ?> <?= h($item['size']) ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($item['color'])): ?>
                                                    <span>· <?= $isArabic ? 'اللون:' : 'Color:' ?> <?= h($item['color']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="product-price">
                                            $<?= number_format($price * $quantity, 2) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- العمود الثاني: بيانات التوصيل والدفع -->
                    <div class="order-info-col">
                        <h3 class="section-title">
                            <?= $isArabic ? 'بيانات التوصيل والدفع' : 'Delivery & Payment' ?>
                        </h3>

                        <div class="order-details-list">
                            <div class="detail-box">
                                <span class="detail-label"><?= $isArabic ? 'المستلم:' : 'Recipient:' ?></span>
                                <span class="detail-value"><?= h($order['full_name']) ?></span>
                            </div>

                            <div class="detail-box">
                                <span class="detail-label"><?= $isArabic ? 'الهاتف:' : 'Phone:' ?></span>
                                <span class="detail-value" dir="ltr"><?= h($order['phone']) ?></span>
                            </div>

                            <div class="detail-box">
                                <span class="detail-label"><?= $isArabic ? 'العنوان:' : 'Address:' ?></span>
                                <span class="detail-value"><?= nl2br(h($order['address'])) ?></span>
                            </div>

                            <div class="detail-box">
                                <span class="detail-label"><?= $isArabic ? 'الدفع:' : 'Payment:' ?></span>
                                <span class="detail-value">
                                    <?php
                                    $paymentLabels = [
                                        'cod'           => ['ar' => 'الدفع عند الاستلام', 'en' => 'Cash on delivery'],
                                        'paypal'        => ['ar' => 'PayPal', 'en' => 'PayPal'],
                                        'bank_transfer' => ['ar' => 'تحويل بنكي', 'en' => 'Bank transfer'],
                                    ];
                                    echo h($paymentLabels[$order['payment_method']][$current_lang] ?? $order['payment_method']);
                                    ?>
                                </span>
                            </div>
                        </div>

                        <?php if (!empty($order['admin_note'])): ?>
                            <div class="store-admin-note">
                                <span class="note-badge"><?= $isArabic ? 'ملاحظة المتجر' : 'Store Note' ?></span>
                                <p class="note-text"><?= nl2br(h($order['admin_note'])) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>

                <!-- تذييل بطاقة الطلب -->
                <footer class="order-footer">
                    <span class="footer-label">
                        <?= $isArabic ? 'الإجمالي النهائي للطلب' : 'Order Final Total' ?>
                    </span>
                    <strong class="footer-total">
                        $<?= number_format((float)$order['total_amount'], 2) ?>
                    </strong>
                </footer>

            </article>
        <?php endforeach; ?>

    <?php endif; ?>

</main>

<!-- نافذة تعديل بيانات الطلب للزبونة -->
<div class="edit-modal-backdrop" id="editOrderModal" hidden>
    <div class="edit-modal-card">
        <div class="edit-modal-head">
            <h3><?= $isArabic ? 'تعديل بيانات التوصيل للطلب #' : 'Edit Delivery Details for Order #' ?><span id="modalOrderNum"></span></h3>
            <button type="button" class="modal-close-x" onclick="closeEditOrderModal()">✕</button>
        </div>

        <form method="POST" class="edit-modal-form">
            <input type="hidden" name="order_action" value="edit_details">
            <input type="hidden" name="order_id" id="modalOrderId">

            <label>
                <span><?= $isArabic ? 'اسم المستلم' : 'Recipient Name' ?></span>
                <input type="text" name="full_name" id="modalFullName" required>
            </label>

            <label>
                <span><?= $isArabic ? 'رقم الهاتف' : 'Phone Number' ?></span>
                <input type="text" name="phone" id="modalPhone" required>
            </label>

            <label>
                <span><?= $isArabic ? 'عنوان التسليم الكامل' : 'Full Delivery Address' ?></span>
                <textarea name="address" id="modalAddress" rows="3" required></textarea>
            </label>

            <div class="edit-modal-btns">
                <button type="button" class="btn-modal-cancel" onclick="closeEditOrderModal()"><?= $isArabic ? 'إلغاء' : 'Cancel' ?></button>
                <button type="submit" class="btn-modal-save"><?= $isArabic ? 'حفظ التعديلات' : 'Save Changes' ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function filterCustomerOrders(status, clickedBtn) {
    document.querySelectorAll('.c-pill-link').forEach(b => b.classList.remove('active'));
    if (clickedBtn) clickedBtn.classList.add('active');

    document.querySelectorAll('.order-card').forEach(card => {
        if (status === 'all' || card.dataset.status === status) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

function openEditOrderModal(id, name, phone, address) {
    document.getElementById('modalOrderId').value = id;
    document.getElementById('modalOrderNum').innerText = id;
    document.getElementById('modalFullName').value = name;
    document.getElementById('modalPhone').value = phone;
    document.getElementById('modalAddress').value = address;
    document.getElementById('editOrderModal').removeAttribute('hidden');
}

function closeEditOrderModal() {
    document.getElementById('editOrderModal').setAttribute('hidden', '');
}
</script>
</body>
</html>
