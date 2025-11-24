<?php
session_start();

// Nếu không có action hoặc id → quay lại giỏ hàng
if (!isset($_GET['action']) || !isset($_GET['id'])) {
    header("Location: gio_hang.php");
    exit;
}

$action = $_GET['action'];
$id = $_GET['id'];

// Nếu giỏ hàng chưa tồn tại
if (!isset($_SESSION['cart'][$id])) {
    header("Location: gio_hang.php");
    exit;
}

// ========== XỬ LÝ CHỨC NĂNG ==========

// 🔽 GIẢM SỐ LƯỢNG
if ($action == "decrease") {
    if ($_SESSION['cart'][$id]['so_luong'] > 1) {
        $_SESSION['cart'][$id]['so_luong']--;
    } else {
        // Nếu còn 1 thì xóa luôn
        unset($_SESSION['cart'][$id]);
    }
}

// 🔼 TĂNG SỐ LƯỢNG
if ($action == "increase") {
    $_SESSION['cart'][$id]['so_luong']++;
}

// ❌ XÓA SẢN PHẨM
if ($action == "remove") {
    unset($_SESSION['cart'][$id]);
}

// 🔄 Nếu giỏ hàng rỗng → reset mảng để tránh lỗi
if (empty($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Quay lại giỏ hàng
header("Location: gio_hang.php");
exit;
?>
