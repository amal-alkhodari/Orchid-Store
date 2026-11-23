<?php
session_start();
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $action    = $_POST['action'] ?? 'add';
    $userId    = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

    if ($productId > 0) {
        if ($action === 'add') {
            if ($userId) {
                $chk = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
                $chk->execute([$userId, $productId]);
                if (!$chk->fetch()) {
                    $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
                    $stmt->execute([$userId, $productId]);
                }
            } else {
                $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (NULL, ?)");
                $stmt->execute([$productId]);
            }
        } else {
            if ($userId) {
                $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$userId, $productId]);
            } else {
                $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id IS NULL AND product_id = ? LIMIT 1");
                $stmt->execute([$productId]);
            }
        }
    }
}
