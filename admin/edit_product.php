<?php
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$lang = $_SESSION['lang'] ?? 'ar';
$isArabic = $lang === 'ar';

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    exit('Invalid product ID');
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    exit($isArabic ? 'المنتج غير موجود.' : 'Product not found.');
}

$categoriesStmt = $pdo->query(
    "SELECT id, name_ar, name_en FROM categories ORDER BY id"
);
$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name_ar = trim($_POST['name_ar'] ?? '');
    $name_en = trim($_POST['name_en'] ?? '');
    $description_ar = trim($_POST['description_ar'] ?? '');
    $description_en = trim($_POST['description_en'] ?? '');
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $subcategory = trim($_POST['subcategory'] ?? '');
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $discount = filter_input(INPUT_POST, 'discount_percent', FILTER_VALIDATE_FLOAT);
    $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (
        $name_ar === '' || $name_en === '' ||
        !$category_id || $price === false || $price < 0 ||
        $discount === false || $discount < 0 || $discount > 100 ||
        $stock === false || $stock < 0
    ) {
        $error = $isArabic
            ? 'تأكدي من تعبئة الأسماء والسعر والخصم والمخزون بشكل صحيح.'
            : 'Check the names, price, discount and stock.';
    } else {
        $imagePath = $product['image'];
        $uploadError = '';

        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                $uploadError = $isArabic
                    ? 'تعذر رفع الصورة.'
                    : 'Image upload failed.';
            } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                $uploadError = $isArabic
                    ? 'حجم الصورة يجب ألا يتجاوز 5 ميغابايت.'
                    : 'Image must be 5 MB or smaller.';
            } else {
                $tmp = $_FILES['image']['tmp_name'];
                $mime = mime_content_type($tmp);
                $allowed = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp'
                ];

                if (!isset($allowed[$mime])) {
                    $uploadError = $isArabic
                        ? 'اختاري صورة JPG أو PNG أو WEBP.'
                        : 'Choose a JPG, PNG or WEBP image.';
                } else {
                    $folder = __DIR__ . '/../images/products/';

                    if (!is_dir($folder) && !mkdir($folder, 0755, true)) {
                        $uploadError = $isArabic
                            ? 'تعذر إنشاء مجلد الصور.'
                            : 'Could not create the image folder.';
                    } else {
                        $filename = 'product_' . uniqid() . '.' . $allowed[$mime];

                        if (move_uploaded_file($tmp, $folder . $filename)) {
                            $imagePath = 'images/products/' . $filename;
                        } else {
                            $uploadError = $isArabic
                                ? 'تعذر حفظ الصورة.'
                                : 'Could not save the image.';
                        }
                    }
                }
            }
        }

        if ($uploadError !== '') {
            $error = $uploadError;
        } else {
            try {
                $update = $pdo->prepare("
                    UPDATE products SET
                        category_id = ?,
                        subcategory = ?,
                        name_ar = ?,
                        name_en = ?,
                        description_ar = ?,
                        description_en = ?,
                        price = ?,
                        discount_percent = ?,
                        image = ?,
                        stock = ?,
                        is_active = ?
                    WHERE id = ?
                ");

                $update->execute([
                    $category_id,
                    $subcategory,
                    $name_ar,
                    $name_en,
                    $description_ar,
                    $description_en,
                    $price,
                    $discount,
                    $imagePath,
                    $stock,
                    $is_active,
                    $id
                ]);

                $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
                $stmt->execute([$id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                $message = $isArabic
                    ? 'تم تحديث المنتج بنجاح.'
                    : 'Product updated successfully.';
            } catch (PDOException $ex) {
                $error = $isArabic
                    ? 'حدث خطأ أثناء تحديث المنتج.'
                    : 'Could not update the product.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $isArabic ? 'ar' : 'en' ?>" dir="<?= $isArabic ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $isArabic ? 'تعديل المنتج | Orchid Boutique' : 'Edit Product | Orchid Boutique' ?></title>
<link rel="stylesheet" href="../assets/css/add_product.css?v=2">    
</head>
<body>

<div class="split-wrapper">

    <section class="form-side">
        <div class="top-bar">
            <span class="brand-title">Orchid Boutique</span>
            <a class="form-back" href="products.php">
                <?= $isArabic ? '← العودة إلى المنتجات' : 'Back to Products →' ?>
            </a>
        </div>

        <div class="form-header">
            <h1><?= $isArabic ? 'تعديل بيانات المنتج' : 'Edit Product Details' ?></h1>
            <p><?= $isArabic ? 'حدّثي معلومات القطعة أو السعر أو الصورة' : 'Update item details, price, or image' ?></p>
        </div>

        <?php if ($message !== ''): ?>
            <p class="form-message success" role="status">
                <?= e($message) ?>
            </p>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <p class="form-message" role="alert">
                <?= e($error) ?>
            </p>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="fields-grid">
                <div class="field-group">
                    <label for="name_ar"><?= $isArabic ? 'اسم المنتج بالعربي' : 'Product name (Arabic)' ?></label>
                    <input type="text" id="name_ar" name="name_ar" required value="<?= e($product['name_ar']) ?>" oninput="updateEditPreview()">
                </div>

                <div class="field-group">
                    <label for="name_en"><?= $isArabic ? 'اسم المنتج بالإنجليزي' : 'Product name (English)' ?></label>
                    <input type="text" id="name_en" name="name_en" required value="<?= e($product['name_en']) ?>">
                </div>

                <div class="field-group">
                    <label for="category_id"><?= $isArabic ? 'التصنيف' : 'Category' ?></label>
                    <select id="category_id" name="category_id" required>
                        <option value=""><?= $isArabic ? 'اختاري التصنيف' : 'Choose category' ?></option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int)$category['id'] ?>" <?= (int)$product['category_id'] === (int)$category['id'] ? 'selected' : '' ?>>
                                <?= e($isArabic ? $category['name_ar'] : $category['name_en']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field-group">
                    <label for="subcategory"><?= $isArabic ? 'التصنيف الفرعي' : 'Subcategory' ?></label>
                    <input type="text" id="subcategory" name="subcategory" value="<?= e($product['subcategory']) ?>">
                </div>

                <div class="field-group">
                    <label for="price"><?= $isArabic ? 'السعر الأصلي ($)' : 'Price ($)' ?></label>
                    <input type="number" id="price" name="price" min="0" step="0.01" required value="<?= e($product['price']) ?>" oninput="updateEditPreview()">
                </div>

                <div class="field-group">
                    <label for="discount_percent"><?= $isArabic ? 'نسبة الخصم (%)' : 'Discount (%)' ?></label>
                    <input type="number" id="discount_percent" name="discount_percent" min="0" max="100" step="0.01" required value="<?= e($product['discount_percent']) ?>">
                </div>

                <div class="field-group full">
                    <label for="stock"><?= $isArabic ? 'الكمية بالمخزون' : 'Stock Quantity' ?></label>
                    <input type="number" id="stock" name="stock" min="0" step="1" required value="<?= e($product['stock']) ?>">
                </div>

                <div class="field-group full">
                    <label for="description_ar"><?= $isArabic ? 'الوصف بالعربي' : 'Description (Arabic)' ?></label>
                    <textarea id="description_ar" name="description_ar"><?= e($product['description_ar']) ?></textarea>
                </div>

                <div class="field-group full">
                    <label for="description_en"><?= $isArabic ? 'الوصف بالإنجليزي' : 'Description (English)' ?></label>
                    <textarea id="description_en" name="description_en"><?= e($product['description_en']) ?></textarea>
                </div>
            </div>

            <div class="colors-section" style="margin-top: 22px;">
                <h3><?= $isArabic ? 'صورة المنتج وحالة الظهور' : 'Product Image & Visibility' ?></h3>
                <p><?= $isArabic ? 'اتركي حقل الصورة فارغاً إذا كنتِ لا ترغبين في تغيير الصورة الحالية.' : 'Leave image empty to keep current photo.' ?></p>

                <div class="field-group" style="margin-bottom: 14px;">
                    <label for="image"><?= $isArabic ? 'تغيير الصورة (اختياري)' : 'Change Image (Optional)' ?></label>
                    <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" style="padding: 8px 12px; background: #FFFFFF; border: 1px dashed #D5C6BC; border-radius: 20px;">
                </div>

                <label style="display: inline-flex; align-items: center; gap: 10px; font-size: 13.5px; font-weight: 800; color: #581D2C; cursor: pointer;">
                    <input type="checkbox" name="is_active" value="1" <?= !empty($product['is_active']) ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: #581D2C;">
                    <?= $isArabic ? 'إظهار المنتج للزبائن في المتجر' : 'Show product in store' ?>
                </label>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px; flex-wrap: wrap;">
                <button type="submit" class="submit-btn" style="flex: 2; margin-top: 0;">
                    <?= $isArabic ? 'حفظ التعديلات' : 'Save Changes' ?>
                </button>

                <a href="delete_product.php?id=<?= (int)$product['id'] ?>" onclick="return confirm('<?= $isArabic ? 'هل أنتِ متأكدة من حذف هذا المنتج؟' : 'Delete this product?' ?>')" style="flex: 1; height: 52px; display: inline-flex; align-items: center; justify-content: center; background: #FDECEC; color: #9E2A3B; border: 1px solid #F5C6CB; border-radius: 30px; font-size: 14px; font-weight: 800; text-decoration: none;">
                    <?= $isArabic ? 'حذف المنتج' : 'Delete' ?>
                </a>
            </div>

            <div class="form-footer-note">Orchid Boutique - Control Panel 2026 ©</div>
        </form>
    </section>

    <aside class="visual-side">
        <div class="visual-top-dot"></div>

        <div class="glass-center-card">
            <?php if (!empty($product['image'])): ?>
                <img src="../<?= e(ltrim($product['image'], '/')) ?>" alt="<?= e($product['name_ar']) ?>" style="width: 130px; height: 165px; object-fit: cover; border-radius: 20px; margin-bottom: 14px; border: 3px solid #FFFFFF; box-shadow: 0 8px 20px rgba(0,0,0,0.08);">
            <?php else: ?>
                <div class="glass-badge-circle">✿</div>
            <?php endif; ?>

            <h2 id="previewName"><?= e($isArabic ? $product['name_ar'] : $product['name_en']) ?></h2>
            <p><?= $isArabic ? 'معاينة حية للقطعة أثناء التعديل' : 'Live preview while editing' ?></p>
            <div class="live-preview-pill" id="previewPrice">
                <?= $isArabic ? 'السعر: $' : 'Price: $' ?><?= number_format((float)$product['price'], 2) ?>
            </div>
        </div>

        <div class="visual-bottom-badge">
            <strong><?= $isArabic ? 'تحديث فوري وآمن' : 'Instant & Secure Update' ?></strong>
            <span>Orchid Inventory System</span>
        </div>
    </aside>

</div>

<script>
function updateEditPreview() {
    const nameInput = document.getElementById('name_ar').value.trim();
    const priceInput = parseFloat(document.getElementById('price').value) || 0;

    if (nameInput !== '') {
        document.getElementById('previewName').textContent = nameInput;
    }
    document.getElementById('previewPrice').textContent =
        '<?= $isArabic ? 'السعر: $' : 'Price: $' ?>' + priceInput.toFixed(2);
}
</script>

</body>
</html>
