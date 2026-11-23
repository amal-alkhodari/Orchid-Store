<?php
session_start();

require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$lang = $_SESSION['lang'] ?? 'ar';

if (!in_array($lang, ['ar', 'en'], true)) {
    $lang = 'ar';
}

$isArabic = $lang === 'ar';
$message = '';
$success = false;

try {
    $categoriesStmt = $pdo->query(
        'SELECT id, name_ar, name_en FROM categories ORDER BY id'
    );
    $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
    $message = $isArabic
        ? 'تعذر تحميل التصنيفات.'
        : 'Could not load categories.';
}

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function normalizeMultiColorFiles(?array $files): array
{
    $normalized = [];
    if (!$files || !isset($files['name']) || !is_array($files['name'])) {
        return $normalized;
    }

    foreach ($files['name'] as $colorIdx => $namesList) {
        if (!is_array($namesList)) {
            $namesList = [$namesList];
        }

        $normalized[$colorIdx] = [];
        foreach ($namesList as $fileIdx => $nameVal) {
            $normalized[$colorIdx][] = [
                'name'     => $nameVal,
                'type'     => is_array($files['type'][$colorIdx] ?? null) ? ($files['type'][$colorIdx][$fileIdx] ?? '') : ($files['type'][$colorIdx] ?? ''),
                'tmp_name' => is_array($files['tmp_name'][$colorIdx] ?? null) ? ($files['tmp_name'][$colorIdx][$fileIdx] ?? '') : ($files['tmp_name'][$colorIdx] ?? ''),
                'error'    => is_array($files['error'][$colorIdx] ?? null) ? ($files['error'][$colorIdx][$fileIdx] ?? UPLOAD_ERR_NO_FILE) : ($files['error'][$colorIdx] ?? UPLOAD_ERR_NO_FILE),
                'size'     => is_array($files['size'][$colorIdx] ?? null) ? ($files['size'][$colorIdx][$fileIdx] ?? 0) : ($files['size'][$colorIdx] ?? 0),
            ];
        }
    }

    return $normalized;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nameAr = trim($_POST['name_ar'] ?? '');
    $nameEn = trim($_POST['name_en'] ?? '');
    $descriptionAr = trim($_POST['description_ar'] ?? '');
    $descriptionEn = trim($_POST['description_en'] ?? '');
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
    $discount = filter_input(INPUT_POST, 'discount_percent', FILTER_VALIDATE_FLOAT);
    $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $subcategory = trim($_POST['subcategory'] ?? '');

    $colorsAr = $_POST['color_name_ar'] ?? [];
    $colorsEn = $_POST['color_name_en'] ?? [];
    $colorsCode = $_POST['color_code'] ?? [];

    $colors = [];

    if (is_array($colorsAr) && is_array($colorsEn) && is_array($colorsCode)) {
        foreach ($colorsAr as $index => $colorAr) {
            $colorAr = trim($colorAr);
            $colorEn = trim($colorsEn[$index] ?? '');
            $colorCode = trim($colorsCode[$index] ?? '');

            if ($colorAr === '' && $colorEn === '' && $colorCode === '') {
                continue;
            }

            if ($colorAr === '' || $colorEn === '') {
                $message = $isArabic
                    ? 'يرجى إدخال اسم كل لون بالعربي والإنجليزي.'
                    : 'Enter every color name in Arabic and English.';
                break;
            }

            if (
                $colorCode !== '' &&
                !preg_match('/^#[0-9A-Fa-f]{6}$/', $colorCode)
            ) {
                $message = $isArabic
                    ? 'كود اللون غير صحيح.'
                    : 'Invalid color code.';
                break;
            }

            $colors[] = [
                'index' => $index,
                'ar' => $colorAr,
                'en' => $colorEn,
                'code' => $colorCode !== '' ? $colorCode : null
            ];
        }
    }

    if ($message === '') {
        if ($nameAr === '' || $nameEn === '') {
            $message = $isArabic
                ? 'يرجى إدخال اسم المنتج بالعربي والإنجليزي.'
                : 'Enter the product name in Arabic and English.';
        } elseif ($price === false || $price === null || $price < 0) {
            $message = $isArabic ? 'السعر غير صحيح.' : 'Invalid price.';
        } elseif ($stock === false || $stock === null || $stock < 0) {
            $message = $isArabic ? 'كمية المخزون غير صحيحة.' : 'Invalid stock quantity.';
        } elseif (
            $discount === false || $discount === null ||
            $discount < 0 || $discount > 100
        ) {
            $message = $isArabic
                ? 'الخصم يجب أن يكون بين 0 و100.'
                : 'Discount must be between 0 and 100.';
        } elseif (
            $categoryId === false || $categoryId === null ||
            !in_array($categoryId, array_map('intval', array_column($categories, 'id')), true)
        ) {
            $message = $isArabic ? 'اختاري تصنيفًا صحيحًا.' : 'Choose a valid category.';
        } elseif (count($colors) === 0) {
            $message = $isArabic
                ? 'أضيفي لونًا واحدًا على الأقل للمنتج.'
                : 'Add at least one product color.';
        }
    }

    if ($message === '') {
        $groupedFiles = normalizeMultiColorFiles($_FILES['color_image'] ?? null);
        $firstIdx = $colors[0]['index'];
        $firstColorFiles = $groupedFiles[$firstIdx] ?? [];

        $image = null;
        foreach ($firstColorFiles as $candidateFile) {
            if (
                (int) $candidateFile['error'] === UPLOAD_ERR_OK &&
                is_uploaded_file($candidateFile['tmp_name'])
            ) {
                $image = $candidateFile;
                break;
            }
        }

        $imagePath = '';
        $uploadDir = __DIR__ . '/../images/products/';

        if (
            !$image ||
            $image['error'] !== UPLOAD_ERR_OK ||
            !is_uploaded_file($image['tmp_name'])
        ) {
            $message = $isArabic
                ? 'يرجى اختيار صورة للون الأول لتكون صورة المنتج الرئيسية.'
                : 'Please select an image for the first color as the main product image.';
        } else {
            $allowedTypes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            ];

            $fileSize = (int) $image['size'];
            $mimeType = (new finfo(FILEINFO_MIME_TYPE))
                ->file($image['tmp_name']);

            if (
                !isset($allowedTypes[$mimeType]) ||
                $fileSize > 5 * 1024 * 1024
            ) {
                $message = $isArabic
                    ? 'الصورة يجب أن تكون JPG أو PNG أو WEBP وبحجم لا يتجاوز 5 ميجابايت.'
                    : 'Image must be JPG, PNG, or WEBP and no larger than 5 MB.';
            } else {
                if (
                    !is_dir($uploadDir) &&
                    !mkdir($uploadDir, 0755, true) &&
                    !is_dir($uploadDir)
                ) {
                    $message = $isArabic
                        ? 'تعذر إنشاء مجلد الصور.'
                        : 'Could not create the image folder.';
                } else {
                    $fileName = bin2hex(random_bytes(16))
                        . '.' . $allowedTypes[$mimeType];

                    if (move_uploaded_file($image['tmp_name'], $uploadDir . $fileName)) {
                        $imagePath = 'images/products/' . $fileName;

                        try {
                            $pdo->exec(
                                'CREATE TABLE IF NOT EXISTS product_color_images (
                                    id INT AUTO_INCREMENT PRIMARY KEY,
                                    product_id INT NOT NULL,
                                    color_id INT NOT NULL,
                                    image VARCHAR(255) NOT NULL,
                                    sort_order INT NOT NULL DEFAULT 0,
                                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                    INDEX idx_color_id (color_id),
                                    INDEX idx_product_id (product_id)
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
                            );

                            $pdo->beginTransaction();

                            $stmt = $pdo->prepare(
                                'INSERT INTO products
                                (
                                    category_id, subcategory,
                                    name_ar, name_en,
                                    description_ar, description_en,
                                    price, image, stock,
                                    discount_percent, is_active
                                )
                                VALUES
                                (
                                    :category_id, :subcategory,
                                    :name_ar, :name_en,
                                    :description_ar, :description_en,
                                    :price, :image, :stock,
                                    :discount_percent, 1
                                )'
                            );

                            $stmt->execute([
                                'category_id' => $categoryId,
                                'subcategory' => $subcategory,
                                'name_ar' => $nameAr,
                                'name_en' => $nameEn,
                                'description_ar' => $descriptionAr,
                                'description_en' => $descriptionEn,
                                'price' => $price,
                                'image' => $imagePath,
                                'stock' => $stock,
                                'discount_percent' => $discount
                            ]);

                            $productId = (int) $pdo->lastInsertId();

                            $colorStmt = $pdo->prepare(
                                'INSERT INTO product_colors
                                (product_id, color_name_ar, color_name_en, color_code, image)
                                VALUES (:product_id, :color_ar, :color_en, :color_code, :image)'
                            );

                            $colorImgStmt = $pdo->prepare(
                                'INSERT INTO product_color_images
                                (product_id, color_id, image, sort_order)
                                VALUES (:product_id, :color_id, :image, :sort_order)'
                            );

                            foreach ($colors as $pos => $color) {
                                $idx = $color['index'];
                                $uploadedPathsForColor = [];

                                if ($pos === 0) {
                                    $uploadedPathsForColor[] = $imagePath;
                                }

                                $filesForThisColor = $groupedFiles[$idx] ?? [];
                                foreach ($filesForThisColor as $fItem) {
                                    if ($pos === 0 && $fItem === $image) {
                                        continue;
                                    }

                                    if (
                                        (int) $fItem['error'] === UPLOAD_ERR_OK &&
                                        is_uploaded_file($fItem['tmp_name'])
                                    ) {
                                        $cMime = (new finfo(FILEINFO_MIME_TYPE))->file($fItem['tmp_name']);
                                        $cSize = (int) $fItem['size'];

                                        if (isset($allowedTypes[$cMime]) && $cSize <= 5 * 1024 * 1024) {
                                            $cFileName = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$cMime];
                                            if (move_uploaded_file($fItem['tmp_name'], $uploadDir . $cFileName)) {
                                                $uploadedPathsForColor[] = 'images/products/' . $cFileName;
                                            }
                                        }
                                    }
                                }

                                $mainColorImg = $uploadedPathsForColor[0] ?? null;

                                $colorStmt->execute([
                                    'product_id' => $productId,
                                    'color_ar' => $color['ar'],
                                    'color_en' => $color['en'],
                                    'color_code' => $color['code'],
                                    'image' => $mainColorImg
                                ]);

                                $colorId = (int) $pdo->lastInsertId();

                                foreach ($uploadedPathsForColor as $orderIdx => $imgRelPath) {
                                    $colorImgStmt->execute([
                                        'product_id' => $productId,
                                        'color_id'   => $colorId,
                                        'image'      => $imgRelPath,
                                        'sort_order' => $orderIdx
                                    ]);
                                }
                            }

                            $pdo->commit();
                            $success = true;
                            $message = $isArabic
                                ? 'تم إضافة المنتج وألوانه وصوره بنجاح!'
                                : 'Product, colors, and images added successfully!';
                        } catch (PDOException $e) {
                            if ($pdo->inTransaction()) {
                                $pdo->rollBack();
                            }
                            error_log($e->getMessage());
                            $message = $isArabic
                                ? 'حدث خطأ أثناء حفظ المنتج في قاعدة البيانات.'
                                : 'Database error while saving product.';
                        }
                    } else {
                        $message = $isArabic
                            ? 'فشل رفع صورة المنتج.'
                            : 'Failed to upload product image.';
                    }
                }
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
    <title><?= $isArabic ? 'إضافة منتج | Orchid Boutique' : 'Add Product | Orchid Boutique' ?></title>
    <!-- تم ضبط ربط ملف الـ CSS ليعمل بشكل صحيح تماماً -->
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
            <h1><?= $isArabic ? 'إضافة منتج جديد' : 'Add New Product' ?></h1>
            <p><?= $isArabic ? 'أدخلي تفاصيل القطعة وألوانها لإضافتها إلى تشكيلة المتجر' : 'Enter product details and colors to publish to store' ?></p>
        </div>

        <?php if ($message !== ''): ?>
            <p class="form-message <?= $success ? 'success' : '' ?>" role="status">
                <?= h($message) ?>
            </p>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="fields-grid">
                <div class="field-group">
                    <label for="name_ar">اسم المنتج بالعربي</label>
                    <input type="text" id="name_ar" name="name_ar" maxlength="255" placeholder="مثال: بنطلون قماشي أنيق" oninput="updatePreview()" required>
                </div>

                <div class="field-group">
                    <label for="name_en">Product name in English</label>
                    <input type="text" id="name_en" name="name_en" maxlength="255" placeholder="e.g. Elegant Fabric Pants" required>
                </div>

                <div class="field-group">
                    <label for="category_id">التصنيف</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">اختاري التصنيف</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int) $category['id'] ?>">
                                <?= h($isArabic ? $category['name_ar'] : $category['name_en']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field-group">
                    <label for="subcategory">التصنيف الفرعي (اختياري)</label>
                    <input type="text" id="subcategory" name="subcategory" maxlength="100" placeholder="مثال: بنطلون / كاجوال">
                </div>

                <div class="field-group">
                    <label for="price">السعر الأصلي ($)</label>
                    <input type="number" id="price" name="price" min="0" step="0.01" placeholder="0.00" oninput="updatePreview()" required>
                </div>

                <div class="field-group">
                    <label for="discount_percent">نسبة الخصم (%)</label>
                    <input type="number" id="discount_percent" name="discount_percent" min="0" max="100" step="0.01" value="0" required>
                </div>

                <div class="field-group full">
                    <label for="stock">كمية المخزون الإجمالية</label>
                    <input type="number" id="stock" name="stock" min="0" step="1" placeholder="10" required>
                </div>

                <div class="field-group full">
                    <label for="description_ar">وصف المنتج بالعربي</label>
                    <textarea id="description_ar" name="description_ar" placeholder="اكتبي تفاصيل القماش والقصة..."></textarea>
                </div>

                <div class="field-group full">
                    <label for="description_en">Product description in English</label>
                    <textarea id="description_en" name="description_en" placeholder="Describe fabric and fit..."></textarea>
                </div>
            </div>

            <div class="colors-section">
                <h3><?= $isArabic ? 'ألوان المنتج وصورها' : 'Product Colors & Images' ?></h3>
                <p><?= $isArabic
                    ? 'أضيفي لونًا أو أكثر ويمكنكِ اختيار عدة صور لنفس اللون. صورة اللون الأول ستكون هي صورة المنتج الرئيسية.'
                    : 'Add one or more colors and select multiple images per color. The first image of the first color will be the main product image.' ?></p>

                <div id="colorsContainer">
                    <div class="color-row" data-color-index="0">
                        <div>
                            <label><?= $isArabic ? 'اسم اللون بالعربي' : 'Color (AR)' ?></label>
                            <input type="text" name="color_name_ar[]" placeholder="أسود" required>
                        </div>

                        <div>
                            <label><?= $isArabic ? 'اسم اللون بالإنجليزي' : 'Color (EN)' ?></label>
                            <input type="text" name="color_name_en[]" placeholder="Black" required>
                        </div>

                        <div>
                            <label><?= $isArabic ? 'اللون' : 'Code' ?></label>
                            <input type="color" name="color_code[]" value="#000000">
                        </div>

                        <div class="color-file-box">
                            <label><?= $isArabic ? 'صور هذا اللون (يمكن اختيار أكثر من صورة)' : 'Color Images (Multiple allowed)' ?></label>
                            <input type="file" name="color_image[0][]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple onchange="renderColorThumbs(this)" required>
                            <div class="color-thumbs-preview" style="display:flex;flex-wrap:wrap;gap:6px;margin-top:6px;"></div>
                        </div>

                        <button type="button" class="remove-color" onclick="removeColor(this)">
                            <?= $isArabic ? 'حذف' : 'Remove' ?>
                        </button>
                    </div>
                </div>

                <button type="button" class="add-color-btn" onclick="addColor()">
                    + <?= $isArabic ? 'إضافة لون آخر' : 'Add Another Color' ?>
                </button>
            </div>

            <button type="submit" class="submit-btn">
                <?= $isArabic ? 'حفظ المنتج ونشره في المتجر' : 'Save & Publish Product' ?>
            </button>

            <div class="form-footer-note">Orchid Boutique - Control Panel 2026 ©</div>
        </form>
    </section>

    <aside class="visual-side">
        <div class="visual-top-dot"></div>

        <div class="glass-center-card">
            <div class="glass-badge-circle">✿</div>
            <h2 id="previewName"><?= $isArabic ? 'إدارة تشكيلة المتجر' : 'Store Collection Manager' ?></h2>
            <p><?= $isArabic ? 'معاينة فورية لبيانات القطعة الجديدة قبل نشرها للزبائن' : 'Live preview of new item details before publishing' ?></p>
            <div class="live-preview-pill" id="previewPrice">
                <?= $isArabic ? 'السعر: $0.00' : 'Price: $0.00' ?>
            </div>
        </div>

        <div class="visual-bottom-badge">
            <strong><?= $isArabic ? 'نشر فوري وآمن' : 'Instant & Secure Publishing' ?></strong>
            <span>Orchid Inventory System</span>
        </div>
    </aside>

</div>

<script>
let colorIndexCounter = 1;

function updatePreview() {
    const nameInput = document.getElementById('name_ar').value.trim();
    const priceInput = parseFloat(document.getElementById('price').value) || 0;

    document.getElementById('previewName').textContent =
        nameInput !== '' ? nameInput : '<?= $isArabic ? 'إدارة تشكيلة المتجر' : 'Store Collection Manager' ?>';

    document.getElementById('previewPrice').textContent =
        '<?= $isArabic ? 'السعر: $' : 'Price: $' ?>' + priceInput.toFixed(2);
}

function renderColorThumbs(input) {
    const box = input.closest('.color-file-box');
    const preview = box ? box.querySelector('.color-thumbs-preview') : null;
    if (!preview) return;

    preview.innerHTML = '';
    const files = Array.from(input.files || []);

    files.forEach((file) => {
        if (!file.type.startsWith('image/')) return;
        const reader = new FileReader();
        reader.onload = function (e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.alt = file.name;
            img.style.width = '44px';
            img.style.height = '44px';
            img.style.objectFit = 'cover';
            img.style.borderRadius = '8px';
            preview.appendChild(img);
        };
        reader.readAsDataURL(file);
    });
}

function addColor() {
    const container = document.getElementById('colorsContainer');
    const currentIdx = colorIndexCounter++;
    const row = document.createElement('div');
    row.className = 'color-row';
    row.setAttribute('data-color-index', currentIdx);

    row.innerHTML = `
        <div>
            <label><?= $isArabic ? 'اسم اللون بالعربي' : 'Color (AR)' ?></label>
            <input type="text" name="color_name_ar[]" placeholder="أسود" required>
        </div>

        <div>
            <label><?= $isArabic ? 'اسم اللون بالإنجليزي' : 'Color (EN)' ?></label>
            <input type="text" name="color_name_en[]" placeholder="Black" required>
        </div>

        <div>
            <label><?= $isArabic ? 'اللون' : 'Code' ?></label>
            <input type="color" name="color_code[]" value="#000000">
        </div>

        <div class="color-file-box">
            <label><?= $isArabic ? 'صور هذا اللون (يمكن اختيار أكثر من صورة)' : 'Color Images (Multiple allowed)' ?></label>
            <input type="file" name="color_image[${currentIdx}][]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple onchange="renderColorThumbs(this)">
            <div class="color-thumbs-preview" style="display:flex;flex-wrap:wrap;gap:6px;margin-top:6px;"></div>
        </div>

        <button type="button" class="remove-color" onclick="removeColor(this)">
            <?= $isArabic ? 'حذف' : 'Remove' ?>
        </button>
    `;
    container.appendChild(row);
}

function removeColor(button) {
    const container = document.getElementById('colorsContainer');
    if (container.children.length > 1) {
        button.closest('.color-row').remove();
    } else {
        alert('<?= $isArabic ? 'يجب إضافة لون واحد على الأقل.' : 'At least one color is required.' ?>');
    }
}
</script>

</body>
</html>