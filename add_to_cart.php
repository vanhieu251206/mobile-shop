<?php
session_start();
require_once 'db.php';

$id = $_GET['id'] ?? 0;
if ($id <= 0) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM san_pham WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: index.php");
    exit;
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (isset($_SESSION['cart'][$id])) {
    $_SESSION['cart'][$id]['so_luong']++;
} else {
    $_SESSION['cart'][$id] = [
        'id' => $product['id'],
        'ten_san_pham' => $product['ten_san_pham'],
        'gia' => $product['gia'],
        'hinh_anh' => $product['hinh_anh'],
        'so_luong' => 1
    ];
}

header("Location: gio_hang.php");
exit;
?>
