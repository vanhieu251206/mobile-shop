<?php
session_start();

/**
 * 1) KẾT NỐI DB
 */
$host = 'localhost';
$db   = 'shop_phone4';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (Exception $e) {
    die("Lỗi kết nối DB: " . $e->getMessage());
}

// Hàm redirect + flash message
function back_with_msg($product_id, $msg, $type="danger") {
    $_SESSION['flash_msg'] = $msg;
    $_SESSION['flash_type'] = $type;
    header("Location: detail.php?id=".(int)$product_id . "#tab-d3"); // nhảy về tab review
    exit;
}


/**
 * 2) KIỂM TRA POST
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: shop.php");
    exit;
}

$product_id = isset($_POST['san_pham_id']) ? (int)$_POST['san_pham_id'] : 0;
$so_sao     = isset($_POST['so_sao']) ? (int)$_POST['so_sao'] : 0;
$nhan_xet   = trim($_POST['nhan_xet'] ?? '');

if ($product_id <= 0 || $so_sao < 1 || $so_sao > 5 || $nhan_xet === '') {
    back_with_msg($product_id, "Dữ liệu đánh giá không hợp lệ!");
}

/**
 * 3) BỎ QUA ĐIỀU KIỆN ĐĂNG NHẬP (DEV TEST)
 * - Nếu có login thì lấy user_id như thường
 * - Nếu không login thì tự pick user_id từ đơn hoàn thành gần nhất của sản phẩm này
 */
$user_id = $_SESSION['user_id'] ?? 0;
$purchase = null;

if (!$user_id) {
    // DEV: tìm đơn hoàn thành gần nhất có chứa sản phẩm này
    $stmt = $pdo->prepare("
        SELECT dh.id AS don_hang_id, dh.nguoi_dung_id
        FROM don_hang dh
        JOIN chi_tiet_don_hang ctdh ON ctdh.don_hang_id = dh.id
        WHERE dh.trang_thai = 'hoan_thanh'
          AND ctdh.san_pham_id = ?
        ORDER BY dh.id DESC
        LIMIT 1
    ");
    $stmt->execute([$product_id]);
    $purchase = $stmt->fetch();

    if ($purchase) {
        $user_id = (int)$purchase['nguoi_dung_id'];
    } else {
        back_with_msg($product_id, "(DEV) Chưa có đơn hoàn thành nào cho sản phẩm này nên không thể gán user test!");
    }
}

/**
 * 4) LOGIC: CHỈ ĐƯỢC ĐÁNH GIÁ SAU KHI MUA
 * Nếu đã có $purchase từ bước DEV thì khỏi query lại.
 * Nếu có login, vẫn check đúng user đó đã mua hay chưa.
 */
if (!$purchase) {
    $stmt = $pdo->prepare("
        SELECT dh.id AS don_hang_id
        FROM don_hang dh
        JOIN chi_tiet_don_hang ctdh ON ctdh.don_hang_id = dh.id
        WHERE dh.nguoi_dung_id = ?
          AND dh.trang_thai = 'hoan_thanh'
          AND ctdh.san_pham_id = ?
        ORDER BY dh.id DESC
        LIMIT 1
    ");
    $stmt->execute([$user_id, $product_id]);
    $purchase = $stmt->fetch();

    if (!$purchase) {
        back_with_msg($product_id, "Bạn chỉ có thể đánh giá sau khi đã mua và đơn hàng hoàn thành!");
    }
}

/**
 * 5) INSERT ĐÁNH GIÁ
 * Unique (nguoi_dung_id, san_pham_id) sẽ chặn đánh giá trùng.
 */
try {
    $stmt = $pdo->prepare("
        INSERT INTO danh_gia(nguoi_dung_id, san_pham_id, don_hang_id, so_sao, nhan_xet, trang_thai)
        VALUES (?, ?, ?, ?, ?, 'cho_duyet')
    ");
    $stmt->execute([
        $user_id,
        $product_id,
        $purchase['don_hang_id'],
        $so_sao,
        $nhan_xet
    ]);

    back_with_msg($product_id, "Cảm ơn bạn! Đánh giá đã gửi và đang chờ duyệt.", "success");

} catch (PDOException $e) {
    // Lỗi trùng unique => đã đánh giá rồi
    if ($e->getCode() == 23000) {
        back_with_msg($product_id, "Bạn đã đánh giá sản phẩm này rồi!");
    }
    back_with_msg($product_id, "Lỗi khi gửi đánh giá: ".$e->getMessage());
}
