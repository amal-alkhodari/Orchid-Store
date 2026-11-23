<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once '../db.php';

$stmt = $pdo->query("
    SELECT *
    FROM contact_messages
    ORDER BY created_at DESC
");

$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalMessages = count($messages);
$newMessages = 0;
$complaints = 0;
$inquiries = 0;
$suggestions = 0;
$orders = 0;

foreach ($messages as $msg) {
    if (($msg['status'] ?? '') === 'new') {
        $newMessages++;
    }

    switch ($msg['message_type'] ?? '') {
        case 'complaint':
            $complaints++;
            break;
        case 'inquiry':
            $inquiries++;
            break;
        case 'suggestion':
            $suggestions++;
            break;
        case 'order':
            $orders++;
            break;
    }
}

$types = [
    'complaint' => 'شكوى',
    'inquiry' => 'استفسار',
    'suggestion' => 'اقتراح',
    'order' => 'طلب'
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>صندوق الرسائل | Orchid Boutique</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<link rel="stylesheet" href="../assets/css/admin-messages.css?v=14">
    </head>

<body>

<div class="messages-page">

    <div class="messages-header">
        <h1>صندوق الرسائل</h1>
        <p>إدارة شكاوى واستفسارات واقتراحات وطلبات العملاء.</p>
    </div>

    <div class="messages-container">

        <div class="messages-toolbar">
            <h2 id="currentViewTitle">جميع الرسائل</h2>

            <div class="toolbar-left-actions">
                <a href="dashboard.php" class="back-dash-pill">
                    <i data-lucide="arrow-right"></i>
                    <span>لوحة التحكم</span>
                </a>

                <!-- منيو زجاجية موحدة للفلترة والعدادات -->
                <div class="glass-filter-menu-wrapper" id="glassFilterMenu">
                    <button type="button" class="glass-menu-trigger" id="glassMenuToggle" aria-expanded="false">
                        <i data-lucide="sliders-horizontal"></i>
                        <span id="glassMenuLabel">جميع الرسائل</span>
                        <span class="glass-menu-count" id="glassMenuCount"><?= $totalMessages ?></span>
                        <i data-lucide="chevron-down"></i>
                    </button>

                    <div class="glass-menu-dropdown" role="menu">
                        <button type="button" class="glass-menu-option active" data-filter-type="all" data-filter-status="" data-label="جميع الرسائل" data-count="<?= $totalMessages ?>">
                            <span>جميع الرسائل</span>
                            <span class="opt-badge"><?= $totalMessages ?></span>
                        </button>

                        <button type="button" class="glass-menu-option" data-filter-type="all" data-filter-status="new" data-label="رسائل جديدة" data-count="<?= $newMessages ?>">
                            <span>رسائل جديدة</span>
                            <span class="opt-badge"><?= $newMessages ?></span>
                        </button>

                        <div class="glass-menu-divider"></div>

                        <button type="button" class="glass-menu-option" data-filter-type="complaint" data-filter-status="" data-label="الشكاوى" data-count="<?= $complaints ?>">
                            <span>الشكاوى</span>
                            <span class="opt-badge"><?= $complaints ?></span>
                        </button>

                        <button type="button" class="glass-menu-option" data-filter-type="inquiry" data-filter-status="" data-label="الاستفسارات" data-count="<?= $inquiries ?>">
                            <span>الاستفسارات</span>
                            <span class="opt-badge"><?= $inquiries ?></span>
                        </button>

                        <button type="button" class="glass-menu-option" data-filter-type="suggestion" data-filter-status="" data-label="الاقتراحات" data-count="<?= $suggestions ?>">
                            <span>الاقتراحات</span>
                            <span class="opt-badge"><?= $suggestions ?></span>
                        </button>

                        <button type="button" class="glass-menu-option" data-filter-type="order" data-filter-status="" data-label="الطلبات" data-count="<?= $orders ?>">
                            <span>الطلبات</span>
                            <span class="opt-badge"><?= $orders ?></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-wrapper">

            <table class="messages-table">

                <thead>
                    <tr>
                        <th>رقم</th>
                        <th>اسم العميل</th>
                        <th>الموضوع</th>
                        <th>النوع</th>
                        <th>الحالة</th>
                        <th>التاريخ</th>
                        <th>التفاصيل</th>
                    </tr>
                </thead>

                <tbody id="messagesTableBody">

                    <?php foreach ($messages as $msg): ?>

                        <tr
                            data-type="<?= htmlspecialchars($msg['message_type'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            data-status="<?= htmlspecialchars($msg['status'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        >

                            <td><?= (int)$msg['id'] ?></td>

                            <td><?= htmlspecialchars($msg['name'], ENT_QUOTES, 'UTF-8') ?></td>

                            <td><?= htmlspecialchars($msg['subject'], ENT_QUOTES, 'UTF-8') ?></td>

                            <td>
                                <span class="message-type">
                                    <?= htmlspecialchars($types[$msg['message_type']] ?? 'أخرى', ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>

                            <td class="message-status">
                                <?= htmlspecialchars($msg['status'], ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($msg['created_at'], ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                <button
                                    type="button"
                                    class="view-message"
                                    data-name="<?= htmlspecialchars($msg['name'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-email="<?= htmlspecialchars($msg['email'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-subject="<?= htmlspecialchars($msg['subject'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-message="<?= htmlspecialchars($msg['message'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-type="<?= htmlspecialchars($types[$msg['message_type']] ?? 'أخرى', ENT_QUOTES, 'UTF-8') ?>"
                                    data-date="<?= htmlspecialchars($msg['created_at'], ENT_QUOTES, 'UTF-8') ?>"
                                >
                                    عرض الرسالة
                                </button>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    <?php if (empty($messages)): ?>
                        <tr>
                            <td colspan="7" class="empty-messages">
                                لا توجد رسائل حتى الآن.
                            </td>
                        </tr>
                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>


<div class="message-modal-overlay" id="messageModal">

    <div class="message-modal">

        <div class="message-modal-header">

            <div class="modal-title-group">
                <span class="modal-icon">
                    <i data-lucide="mail"></i>
                </span>

                <div>
                    <h2>تفاصيل الرسالة</h2>
                    <p>Orchid Boutique</p>
                </div>
            </div>

            <button type="button" class="modal-close" id="closeMessageModal">
                &times;
            </button>

        </div>

        <div class="message-modal-body">

            <div class="message-info-grid">

                <div class="message-info-item">
                    <span>اسم العميل</span>
                    <strong id="modalName"></strong>
                </div>

                <div class="message-info-item">
                    <span>البريد الإلكتروني</span>
                    <strong id="modalEmail"></strong>
                </div>

                <div class="message-info-item">
                    <span>نوع الرسالة</span>
                    <strong id="modalType"></strong>
                </div>

                <div class="message-info-item">
                    <span>تاريخ الإرسال</span>
                    <strong id="modalDate"></strong>
                </div>

            </div>

            <div class="message-subject-box">
                <span>الموضوع</span>
                <h3 id="modalSubject"></h3>
            </div>

            <div class="message-content-box">
                <span>نص الرسالة</span>
                <p id="modalMessage"></p>
            </div>

        </div>

        <div class="message-modal-footer">
            <button type="button" id="modalDone">
                إغلاق النافذة
            </button>
        </div>

    </div>

</div>

<script src="https://unpkg.com/lucide@latest"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // Glass filter dropdown menu
    const menuWrapper = document.getElementById('glassFilterMenu');
    const menuToggle = document.getElementById('glassMenuToggle');
    const menuLabel = document.getElementById('glassMenuLabel');
    const menuCount = document.getElementById('glassMenuCount');
    const currentViewTitle = document.getElementById('currentViewTitle');
    const menuOptions = document.querySelectorAll('.glass-menu-option');

    if (menuToggle && menuWrapper) {
        menuToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            const isOpen = menuWrapper.classList.toggle('open');
            menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        document.addEventListener('click', function (e) {
            if (!menuWrapper.contains(e.target)) {
                menuWrapper.classList.remove('open');
                menuToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    menuOptions.forEach(function (option) {
        option.addEventListener('click', function () {
            const selectedType = this.dataset.filterType || 'all';
            const selectedStatus = this.dataset.filterStatus || '';
            const labelText = this.dataset.label || 'جميع الرسائل';
            const countText = this.dataset.count || '0';

            menuOptions.forEach(function (btn) {
                btn.classList.remove('active');
            });
            this.classList.add('active');

            if (menuLabel) menuLabel.textContent = labelText;
            if (menuCount) menuCount.textContent = countText;
            if (currentViewTitle) currentViewTitle.textContent = labelText;

            document.querySelectorAll('#messagesTableBody tr[data-type]').forEach(function (row) {
                const typeMatch = (selectedType === 'all') || (row.dataset.type === selectedType);
                const statusMatch = (!selectedStatus) || (row.dataset.status === selectedStatus);
                row.style.display = (typeMatch && statusMatch) ? '' : 'none';
            });

            if (menuWrapper && menuToggle) {
                menuWrapper.classList.remove('open');
                menuToggle.setAttribute('aria-expanded', 'false');
            }
        });
    });

    // Modal elements
    const modal = document.getElementById('messageModal');
    const closeButton = document.getElementById('closeMessageModal');
    const doneButton = document.getElementById('modalDone');

    document.querySelectorAll('.view-message').forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById('modalName').textContent = this.dataset.name;
            document.getElementById('modalEmail').textContent = this.dataset.email;
            document.getElementById('modalType').textContent = this.dataset.type;
            document.getElementById('modalDate').textContent = this.dataset.date;
            document.getElementById('modalSubject').textContent = this.dataset.subject;
            document.getElementById('modalMessage').textContent = this.dataset.message;
            modal.classList.add('active');
        });
    });

    function closeModal() {
        modal.classList.remove('active');
    }

    closeButton.addEventListener('click', closeModal);
    doneButton.addEventListener('click', closeModal);

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });

    if (window.lucide) {
        lucide.createIcons();
    }

});
</script>

</body>
</html>