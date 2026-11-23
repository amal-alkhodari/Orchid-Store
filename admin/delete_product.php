<?php
session_start();
require_once __DIR__ . '/../db.php';

// التحقق من تسجيل دخول الأدمن
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$productId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($productId > 0) {
    try {
        // 1. جلب مسار صورة المنتج لحذفها من السيرفر إن وجدت
        $stmtImg = $pdo->prepare("SELECT image FROM products WHERE id = ? LIMIT 1");
        $stmtImg->execute([$productId]);
        $product = $stmtImg->fetch(PDO::FETCH_ASSOC);

        // 2. حذف أي ارتباطات للمنتج في المفضلة أو تفاصيل الطلبات لمنع رفض الحذف بسبب القيود (Foreign Keys)
        try {
            $pdo->prepare("DELETE FROM wishlist WHERE product_id = ?")->execute([$productId]);
        } catch (PDOException $e) {}

        try {
            $pdo->prepare("DELETE FROM order_items WHERE product_id = ?")->execute([$productId]);
        } catch (PDOException $e) {}

        // 3. حذف المنتج من جدول المنتجات
        $delStmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $delStmt->execute([$productId]);

        // 4. حذف ملف الصورة من المجلد إذا لم تكن صورة افتراضية
        if ($product && !empty($product['image'])) {
            $imgPath = __DIR__ . '/../' . ltrim($product['image'], '/');
            if (is_file($imgPath)) {
                @unlink($imgPath);
            }
        }

        header('Location: products.php?deleted=1');
        exit;
    } catch (PDOException $e) {
        die('خطأ أثناء حذف المنتج: ' . htmlspecialchars($e->getMessage()));
    }
}

header('Location: products.php');
exit;
