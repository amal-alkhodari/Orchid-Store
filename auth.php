
<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT account_status FROM users WHERE id = ? LIMIT 1'
);
$stmt->execute([$_SESSION['user_id']]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$account || $account['account_status'] !== 'active') {
    $status = $account['account_status'] ?? 'deleted';

    session_unset();
    session_destroy();

    header('Location: login.php?account_status=' . urlencode($status));
    exit;
}
