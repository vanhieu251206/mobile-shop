<?php
session_start();
require_once "db.php";

function e($str){ return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }

// Bắt buộc đăng nhập
if (empty($_SESSION['user_id'])) {
    header("Location: dangnhap.php");
    exit;
}

// Bắt buộc có POST place_order
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['place_order'])) {
    header("Location: checkout.php");
    exit;
}

/**
 * Helper lấy số lượng từ item cart (tương thích nhiều kiểu lưu)
 * cart có thể lưu: qty / so_luong / quantity
 */
function cart_qty($cartItem) {
    if (!is_array($cartItem)) return 1;
    if (isset($cartItem['qty'])) return (int)$cartItem['qty'];
    if (isset($cartItem['so_luong'])) return (int)$cartItem['so_luong'];
    if (isset($cartItem['quantity'])) return (int)$cartItem['quantity'];
    return 1;
}

// Lấy cart (ưu tiên cart, fallback gio_hang)
$cart = $_SESSION['cart'] ?? [];
if (!$cart && isset($_SESSION['gio_hang'])) {
    $cart = $_SESSION['gio_hang'];
}

// Bắt buộc giỏ hàng phải có
if (empty($cart) || !is_array($cart)) {
    die("Giỏ hàng trống.");
}

// ====== LẤY GIỎ HÀNG TỪ SESSION + TÍNH LẠI TIỀN THEO DB ======
$ids  = array_keys($cart);
$cart_items = [];
$total_amount = 0;

try {
    $in = implode(',', array_fill(0, count($ids), '?'));
    $stm = $pdo->prepare("SELECT id, ten_san_pham, gia, hinh_anh FROM san_pham WHERE id IN ($in)");
    $stm->execute($ids);
    $products = $stm->fetchAll();

    if (!$products) die("Không tìm thấy sản phẩm trong giỏ.");

    foreach($products as $p){
        $pid = (int)$p['id'];

        $qty = cart_qty($cart[$pid] ?? null);
        if ($qty <= 0) $qty = 1;

        $price = (float)$p['gia'];
        $sub   = $qty * $price;

        $total_amount += $sub;

        $cart_items[] = [
            'id' => $pid,
            'ten' => $p['ten_san_pham'],
            'gia' => $price,
            'qty' => $qty,
            'sub' => $sub
        ];
    }

} catch(Exception $ex){
    die("Lỗi lấy giỏ hàng: ".$ex->getMessage());
}


// ====== NHẬN DỮ LIỆU CHECKOUT ======
$customer_name  = trim($_POST['customer_name'] ?? '');
$customer_email = trim($_POST['customer_email'] ?? '');
$customer_phone = trim($_POST['customer_phone'] ?? '');
$customer_note  = trim($_POST['customer_note'] ?? '');

$ship_name      = trim($_POST['ship_name'] ?? '');
$ship_phone     = trim($_POST['ship_phone'] ?? '');
$ship_address   = trim($_POST['ship_address'] ?? '');
$ship_city      = trim($_POST['ship_city'] ?? '');
$ship_district  = trim($_POST['ship_district'] ?? '');
$ship_ward      = trim($_POST['ship_ward'] ?? '');
$ship_note      = trim($_POST['ship_note'] ?? '');

$payment_method = $_POST['payment_method'] ?? 'cod'; // cod | bank | wallet | installment
$wallet_type    = $_POST['wallet_type'] ?? null;
$install_months = $_POST['installment_months'] ?? null;
$install_bank   = $_POST['installment_bank'] ?? null;

// Validate tối thiểu
if ($customer_name === '' || $customer_phone === '') {
    die("Thiếu họ tên hoặc số điện thoại.");
}
if ($ship_address === '' || $ship_city === '' || $ship_district === '' || $ship_ward === '') {
    die("Thiếu địa chỉ nhận hàng.");
}

// Ghép địa chỉ đầy đủ
$full_address = trim("$ship_address, $ship_ward, $ship_district, $ship_city", " ,");

// ====== UPLOAD BIÊN LAI (nếu bank) ======
$receipt_path = null;
if ($payment_method === 'bank' && !empty($_FILES['bank_receipt']['name'])) {

    // giới hạn loại file
    $allow_ext = ['jpg','jpeg','png','webp','pdf'];
    $ext = strtolower(pathinfo($_FILES['bank_receipt']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allow_ext)) {
        die("File biên lai không hợp lệ. Chỉ nhận jpg/png/webp/pdf.");
    }

    // giới hạn size ~ 3MB
    if ($_FILES['bank_receipt']['size'] > 3 * 1024 * 1024) {
        die("File biên lai quá lớn (tối đa 3MB).");
    }

    $upload_dir = __DIR__ . "/uploads/receipts";
    if (!is_dir($upload_dir)) @mkdir($upload_dir, 0777, true);

    $safe_name = "receipt_" . time() . "_" . rand(1000,9999) . "." . $ext;
    $target = $upload_dir . "/" . $safe_name;

    if (move_uploaded_file($_FILES['bank_receipt']['tmp_name'], $target)) {
        $receipt_path = "uploads/receipts/" . $safe_name;
    }
}

// info payment để lưu (nếu DB có cột)
$payment_info = [
    'method' => $payment_method,
    'wallet_type' => $wallet_type,
    'install_months' => $install_months,
    'install_bank' => $install_bank,
    'receipt' => $receipt_path
];
$payment_info_json = json_encode($payment_info, JSON_UNESCAPED_UNICODE);


// ====== TẠO ĐƠN ======
$order_id = 0;

try {
    $pdo->beginTransaction();

    /**
     * Thử insert đầy đủ (nếu bảng don_hang có các cột này)
     * Nếu không có => catch và fallback tối thiểu.
     */
    try {
        $insertOrder = $pdo->prepare("
            INSERT INTO don_hang
            (nguoi_dung_id, tong_tien, trang_thai, ngay_tao,
             ten_khach_hang, email_khach_hang, sdt_khach_hang,
             ten_nguoi_nhan, sdt_nguoi_nhan, dia_chi_giao,
             ghi_chu_khach, ghi_chu_giao, phuong_thuc_thanh_toan, thong_tin_thanh_toan, bien_lai)
            VALUES
            (?, ?, 'cho_xu_ly', NOW(),
             ?, ?, ?,
             ?, ?, ?,
             ?, ?, ?, ?, ?)
        ");
        $insertOrder->execute([
            (int)$_SESSION['user_id'],
            $total_amount,

            $customer_name,
            $customer_email,
            $customer_phone,

            $ship_name ?: $customer_name,
            $ship_phone ?: $customer_phone,
            $full_address,

            $customer_note,
            $ship_note,
            $payment_method,
            $payment_info_json,
            $receipt_path
        ]);
    } catch (PDOException $e) {
        // fallback tối thiểu (an toàn với schema hiện tại của bạn)
        $insertOrder = $pdo->prepare("
            INSERT INTO don_hang (nguoi_dung_id, tong_tien, trang_thai, ngay_tao)
            VALUES (?, ?, 'cho_xu_ly', NOW())
        ");
        $insertOrder->execute([
            (int)$_SESSION['user_id'],
            $total_amount
        ]);
    }

    $order_id = (int)$pdo->lastInsertId();
    if ($order_id <= 0) throw new Exception("Không tạo được đơn hàng.");

    // Insert chi tiết đơn hàng
    $insertItem = $pdo->prepare("
        INSERT INTO chi_tiet_don_hang (don_hang_id, san_pham_id, so_luong, gia)
        VALUES (?, ?, ?, ?)
    ");

    foreach($cart_items as $it){
        $insertItem->execute([
            $order_id,
            $it['id'],
            $it['qty'],
            $it['gia']
        ]);
    }

    $pdo->commit();

    // Clear cart sau khi tạo thành công
    unset($_SESSION['cart'], $_SESSION['gio_hang']);

} catch(Exception $ex){
    if ($pdo->inTransaction()) $pdo->rollBack();
    die("Lỗi đặt hàng: ".$ex->getMessage());
}


// ====== TRANG THÀNH CÔNG ======
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đặt hàng thành công</title>
    <link href="css/style.css" rel="stylesheet">
    <style>
        body{background:#f6f7fb;font-family:Roboto,Arial;}
        .wrap{max-width:800px;margin:40px auto;background:#fff;padding:24px;border-radius:12px;box-shadow:0 4px 16px rgba(0,0,0,.06);}
        .success{font-size:22px;font-weight:800;color:#1a7f37;}
        .muted{color:#666;}
        .box{background:#f8fafc;border:1px dashed #ddd;padding:12px;border-radius:8px;margin-top:8px;}
        .btnx{display:inline-block;padding:10px 16px;border-radius:8px;text-decoration:none;}
        .btn-primary{background:#007bff;color:#fff;}
        .btn-light{background:#eee;color:#111;}
    </style>
</head>
<body>
<div class="wrap">
    <div class="success">✅ Đặt hàng thành công!</div>
    <p class="muted">Mã đơn hàng của bạn: <b>#<?= $order_id ?></b></p>

    <h5>Thông tin nhận hàng</h5>
    <div class="box">
        <div><b>Người nhận:</b> <?= e($ship_name ?: $customer_name) ?></div>
        <div><b>SĐT:</b> <?= e($ship_phone ?: $customer_phone) ?></div>
        <div><b>Địa chỉ:</b> <?= e($full_address) ?></div>
        <?php if($ship_note): ?>
            <div><b>Ghi chú:</b> <?= e($ship_note) ?></div>
        <?php endif; ?>
    </div>

    <h5 class="mt-3">Thanh toán</h5>
    <div class="box">
        <?php if($payment_method === 'cod'): ?>
            <p><b>Phương thức:</b> COD (Thanh toán tiền mặt khi nhận hàng)</p>

        <?php elseif($payment_method === 'bank'): ?>
            <p><b>Phương thức:</b> Chuyển khoản ngân hàng</p>
            <p><b>Vietcombank</b> - STK: <b>0123 456 789</b> - Chủ TK: <b>MOBISHOP</b></p>
            <p>Nội dung CK: <b><?= e($customer_phone) ?> #<?= $order_id ?></b></p>
            <?php if($receipt_path): ?>
                <p>✅ Đã nhận biên lai: <?= e($receipt_path) ?></p>
            <?php endif; ?>

        <?php elseif($payment_method === 'wallet'): ?>
            <p><b>Phương thức:</b> Ví điện tử (<?= e($wallet_type) ?>)</p>
            <p>Shop sẽ liên hệ / hoặc chuyển cổng thanh toán sau.</p>

        <?php elseif($payment_method === 'installment'): ?>
            <p><b>Phương thức:</b> Trả góp</p>
            <p>Kỳ hạn: <b><?= e($install_months) ?> tháng</b> - Ngân hàng: <b><?= e($install_bank) ?></b></p>
            <p>Shop sẽ gọi xác nhận hồ sơ trả góp.</p>
        <?php endif; ?>
    </div>

    <h5 class="mt-3">Tổng tiền:
        <span style="color:#d0021b;font-weight:800;">
            <?= number_format($total_amount,0,',','.') ?>₫
        </span>
    </h5>

    <div class="mt-4">
        <a class="btnx btn-primary" href="index.php">Về trang chủ</a>
        <a class="btnx btn-light" href="shop.php">Tiếp tục mua sắm</a>
    </div>
</div>
</body>
</html>
