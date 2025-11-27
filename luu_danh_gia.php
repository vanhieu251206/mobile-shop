<?php
session_start();
require_once 'db.php';

function e($str){ return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }

// Bắt buộc đăng nhập
if (empty($_SESSION['user_id'])) {
    header("Location: dangnhap.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$userId      = (int)$_SESSION['user_id'];
$san_pham_id = (int)($_POST['san_pham_id'] ?? 0);
$don_hang_id = (int)($_POST['don_hang_id'] ?? 0);
$so_sao      = (int)($_POST['so_sao'] ?? 0);
$nhan_xet    = trim($_POST['noi_dung'] ?? '');

// Validate cơ bản
if ($san_pham_id <= 0 || $don_hang_id <= 0) {
    die("Thiếu thông tin sản phẩm hoặc đơn hàng.");
}
if ($so_sao < 1 || $so_sao > 5) {
    $so_sao = 5;
}
if ($nhan_xet === '') {
    $nhan_xet = 'Không có nhận xét.';
}

// 1) Kiểm tra đơn hàng có thuộc user này và có chứa sản phẩm này không
$check = $pdo->prepare("
    SELECT 1
    FROM don_hang dh
    JOIN chi_tiet_don_hang ctdh ON ctdh.don_hang_id = dh.id
    WHERE dh.id = ? AND dh.nguoi_dung_id = ? AND ctdh.san_pham_id = ?
    LIMIT 1
");
$check->execute([$don_hang_id, $userId, $san_pham_id]);
if (!$check->fetch()) {
    die("Bạn không thể đánh giá sản phẩm này (không tìm thấy trong đơn hàng của bạn).");
}

// 2) Lưu vào bảng danh_gia
$sql = "
    INSERT INTO danh_gia
    (nguoi_dung_id, san_pham_id, don_hang_id, so_sao, nhan_xet, trang_thai, ngay_tao)
    VALUES
    (?, ?, ?, ?, ?, 'cho_duyet', NOW())
    ON DUPLICATE KEY UPDATE
        so_sao   = VALUES(so_sao),
        nhan_xet = VALUES(nhan_xet),
        trang_thai = 'cho_duyet',
        ngay_tao = NOW()
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$userId, $san_pham_id, $don_hang_id, $so_sao, $nhan_xet]);

// Quay lại trang tra cứu của đơn
header("Location: tra_cuu.php?ma=" . $don_hang_id . "&rv=ok");
exit;
