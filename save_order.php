
<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

function respond($success, $message, $status = 200) {
    http_response_code($status);
    echo json_encode([
        'success' => $success,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    respond(false, 'يرجى تسجيل الدخول أولًا.', 401);
}

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'طريقة الطلب غير صحيحة.', 405);
}

$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    respond(false, 'بيانات الطلب غير صحيحة.', 400);
}

$fullName = trim($data['fullName'] ?? '');
$phone = trim($data['phone'] ?? '');
$address = trim($data['address'] ?? '');
$payment = $data['payment'] ?? 'cod';
$items = $data['items'] ?? [];

if ($fullName === '' || $phone === '' || $address === '') {
    respond(false, 'يرجى تعبئة جميع البيانات المطلوبة.', 400);
}

if (!in_array($payment, ['cod', 'paypal', 'bank_transfer'], true)) {
    respond(false, 'طريقة الدفع غير صحيحة.', 400);
}

if (!is_array($items) || count($items) === 0 || count($items) > 100) {
    respond(false, 'السلة فارغة أو بياناتها غير صحيحة.', 400);
}

try {
    $pdo->beginTransaction();

    $checkedItems = [];
    $total = 0;
    $productQuantities = [];
    $lockedProducts = [];

    // الاستعلامات
    $productQuery = $pdo->prepare(
        'SELECT id, name_ar, name_en, price, stock
         FROM products
         WHERE id = ?
         FOR UPDATE'
    );

    $colorQuery = $pdo->prepare(
        'SELECT id
         FROM product_colors
         WHERE product_id = ?
         AND (
             color_name_ar = ?
             OR color_name_en = ?
         )
         LIMIT 1'
    );

    // التحقق من المنتجات والألوان والكميات
    foreach ($items as $item) {

        $productId = filter_var(
            $item['id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        $quantity = filter_var(
            $item['quantity'] ?? 1,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 100]]
        );

        if (!$productId || !$quantity) {
            throw new RuntimeException(
                'يوجد منتج في السلة لا يحتوي على رقم أو كمية صحيحة.'
            );
        }

        // قفل المنتج وقراءة بياناته مرة واحدة
        if (!isset($lockedProducts[$productId])) {
            $productQuery->execute([$productId]);
            $product = $productQuery->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                throw new RuntimeException(
                    'أحد المنتجات لم يعد موجودًا.'
                );
            }

            $lockedProducts[$productId] = $product;
            $productQuantities[$productId] = 0;
        }

        $product = $lockedProducts[$productId];

        // جمع كمية المنتج بجميع ألوانه ومقاساته
        $productQuantities[$productId] += $quantity;

        $size = trim((string)($item['size'] ?? ''));
        $color = trim((string)($item['color'] ?? ''));

        // التحقق من اللون إذا كان المنتج يحتوي على ألوان
        $colorsCheck = $pdo->prepare(
            'SELECT COUNT(*)
             FROM product_colors
             WHERE product_id = ?'
        );
        $colorsCheck->execute([$productId]);
        $hasColors = (int)$colorsCheck->fetchColumn() > 0;

        if ($hasColors && $color === '') {
            throw new RuntimeException(
                'يرجى اختيار لون للمنتج: ' . $product['name_ar']
            );
        }

        if ($color !== '') {
            $colorQuery->execute([
                $productId,
                $color,
                $color
            ]);

            if (!$colorQuery->fetch()) {
                throw new RuntimeException(
                    'اللون المختار غير متوفر للمنتج: ' .
                    $product['name_ar']
                );
            }
        }

        // السعر من قاعدة البيانات
        $price = (float)$product['price'];
        $total += $price * $quantity;

        $checkedItems[] = [
            'id' => (int)$product['id'],
            'name' => $product['name_ar'],
            'price' => $price,
            'quantity' => $quantity,
            'size' => $size,
            'color' => $color
        ];
    }

    // التحقق من المخزون الإجمالي لكل منتج
    foreach ($productQuantities as $productId => $requestedQuantity) {

        $availableStock = (int)$lockedProducts[$productId]['stock'];

        if ($requestedQuantity > $availableStock) {
            throw new RuntimeException(
                'الكمية المطلوبة غير متوفرة للمنتج: ' .
                $lockedProducts[$productId]['name_ar'] .
                '. المخزون المتاح: ' . $availableStock
            );
        }
    }

    // إنشاء الطلب
    $orderQuery = $pdo->prepare(
        'INSERT INTO orders
        (user_id, full_name, phone, address, payment_method, total_amount, status)
        VALUES (?, ?, ?, ?, ?, ?, ?)'
    );

    $orderQuery->execute([
        (int)$_SESSION['user_id'],
        $fullName,
        $phone,
        $address,
        $payment,
        number_format($total, 2, '.', ''),
        'pending'
    ]);

    $orderId = (int)$pdo->lastInsertId();

    // حفظ تفاصيل المنتجات
    $itemQuery = $pdo->prepare(
        'INSERT INTO order_items
        (order_id, product_id, product_name, size, color, quantity, price)
        VALUES (?, ?, ?, ?, ?, ?, ?)'
    );

    foreach ($checkedItems as $item) {
        $itemQuery->execute([
            $orderId,
            $item['id'],
            $item['name'],
            $item['size'] !== '' ? $item['size'] : null,
            $item['color'] !== '' ? $item['color'] : null,
            $item['quantity'],
            $item['price']
        ]);
    }

    // خصم المخزون مرة واحدة لكل منتج
    $stockQuery = $pdo->prepare(
        'UPDATE products
         SET stock = stock - ?
         WHERE id = ? AND stock >= ?'
    );

    foreach ($productQuantities as $productId => $requestedQuantity) {

        $stockQuery->execute([
            $requestedQuantity,
            $productId,
            $requestedQuantity
        ]);

        if ($stockQuery->rowCount() !== 1) {
            throw new RuntimeException(
                'تعذر تحديث مخزون المنتج: ' .
                $lockedProducts[$productId]['name_ar']
            );
        }
    }

    $pdo->commit();

    respond(
        true,
        'تم حفظ الطلب بنجاح. رقم الطلب: ' . $orderId
    );

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($e instanceof RuntimeException) {
        respond(false, $e->getMessage(), 400);
    }

    error_log('Orchid order error: ' . $e->getMessage());

    respond(
        false,
        'تعذر حفظ الطلب. تحققي من إعدادات قاعدة البيانات.',
        500
    );
}
