<?php
session_start();
require_once 'db.php';

// Helper nhỏ để lấy input từ POST hoặc GET
function input($key, $default = null) {
    if (isset($_POST[$key])) return $_POST[$key];
    if (isset($_GET[$key]))  return $_GET[$key];
    return $default;
}

// 1) Lấy id sản phẩm
$id = (int) input('id', 0);
if ($id <= 0) {
    header("Location: index.php");
    exit;
}

// 2) Lấy sản phẩm từ DB
$stmt = $pdo->prepare("SELECT id, ten_san_pham, gia, hinh_anh, ton_kho FROM san_pham WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: index.php");
    exit;
}

// 3) Lấy số lượng gửi lên
// Ưu tiên: POST.so_luong -> POST.qty -> GET.so_luong -> GET.qty -> mặc định 1
$qtyRaw = input('so_luong');
if ($qtyRaw === null) {
    $qtyRaw = input('qty', 1);
}
$qty = (int)$qtyRaw;
if ($qty < 1) $qty = 1;

// 4) Giới hạn theo tồn kho
$stock = (int)($product['ton_kho'] ?? 0);
if ($stock > 0 && $qty > $stock) {
    $qty = $stock;
}

// 5) Khởi tạo giỏ nếu chưa có
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// 6) Thêm/cộng dồn vào giỏ
if (isset($_SESSION['cart'][$id])) {
    $_SESSION['cart'][$id]['so_luong'] += $qty;

    // Clamp theo tồn kho
    if ($stock > 0 && $_SESSION['cart'][$id]['so_luong'] > $stock) {
        $_SESSION['cart'][$id]['so_luong'] = $stock;
    }
} else {
    $_SESSION['cart'][$id] = [
        'id'           => $product['id'],
        'ten_san_pham' => $product['ten_san_pham'],
        'gia'          => (float)$product['gia'],
        'hinh_anh'     => $product['hinh_anh'],
        'so_luong'     => $qty,
    ];
}

// 7) Chuyển sang giỏ hàng
header("Location: gio_hang.php");
exit;
