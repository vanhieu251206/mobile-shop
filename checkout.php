<?php
session_start();
require_once "db.php";

/**
 * Nếu bạn muốn bắt buộc login mới checkout:
 */
if (empty($_SESSION['user_id'])) {
    header("Location: dangnhap.php");
    exit;
}

function e($str){ return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }

/** ================= LẤY THÔNG TIN USER ================= */
$user = null;
try {
    $stm = $pdo->prepare("SELECT id, ten_dang_nhap, email FROM nguoi_dung WHERE id=? LIMIT 1");
    $stm->execute([ (int)$_SESSION['user_id'] ]);
    $user = $stm->fetch();
} catch(Exception $ex){
    $user = null;
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

/** ================= LẤY GIỎ HÀNG ================= */
$cart_items = [];
$cart_total = 0;

$cart = $_SESSION['cart'] ?? [];          // ƯU TIÊN cart
if (!$cart && isset($_SESSION['gio_hang'])) {
    $cart = $_SESSION['gio_hang'];        // fallback nếu project cũ dùng gio_hang
}

if (!empty($cart) && is_array($cart)) {

    // Nhiều project lưu cart dạng: [id => ['so_luong'=>..., ...], ...]
    $ids = array_keys($cart);

    if ($ids) {
        $in  = implode(',', array_fill(0, count($ids), '?'));
        $stm = $pdo->prepare("SELECT * FROM san_pham WHERE id IN ($in)");
        $stm->execute($ids);
        $products = $stm->fetchAll();

        foreach($products as $p){
            $pid = (int)$p['id'];

            // lấy qty đúng key
            $qty = cart_qty($cart[$pid] ?? null);
            if ($qty <= 0) $qty = 1;

            // nếu session có lưu đè giá/tên/ảnh thì ưu tiên (đỡ lệch lúc sale)
            $sessionTen = $cart[$pid]['ten_san_pham'] ?? $cart[$pid]['ten'] ?? null;
            $sessionGia = $cart[$pid]['gia'] ?? null;
            $sessionImg = $cart[$pid]['hinh_anh'] ?? $cart[$pid]['img'] ?? null;

            $ten = $sessionTen ?: $p['ten_san_pham'];
            $gia = is_numeric($sessionGia) ? (float)$sessionGia : (float)$p['gia'];
            $img = $sessionImg ?: (explode(',', $p['hinh_anh'] ?? '')[0] ?? 'no-image.png');

            $sub = $qty * $gia;
            $cart_total += $sub;

            $cart_items[] = [
                'id' => $pid,
                'ten' => $ten,
                'gia' => $gia,
                'qty' => $qty,
                'sub' => $sub,
                'img' => $img
            ];
        }
    }
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8" />
    <title>Checkout - MultiShop</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />

    <link href="img/favicon.ico" rel="icon" />
    <link rel="preconnect" href="https://fonts.gstatic.com" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet"/>
    <link href="lib/animate/animate.min.css" rel="stylesheet" />
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet" />
    <link href="css/style.css" rel="stylesheet" />

    <style>
        .checkout-step .card-header{ cursor:pointer; }
        .payment-box{
            border:1px dashed #ddd;padding:12px;border-radius:8px;background:#fff;
            margin-top:8px;display:none;
        }
        .payment-box.active{ display:block; }
        .summary-img{
            width:55px;height:55px;object-fit:cover;border-radius:6px;border:1px solid #eee;
        }
    </style>
</head>

<body>
<!-- Topbar Start -->
<div class="container-fluid">
    <div class="row bg-secondary py-1 px-xl-5">
        <div class="col-lg-6 d-none d-lg-block">
            <div class="d-inline-flex align-items-center h-100">
                <a class="text-body mr-3" href="">About</a>
                <a class="text-body mr-3" href="">Contact</a>
                <a class="text-body mr-3" href="">Help</a>
                <a class="text-body mr-3" href="">FAQs</a>
            </div>
        </div>
        <div class="col-lg-6 text-center text-lg-right">
            <div class="d-inline-flex align-items-center">
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-light dropdown-toggle" data-toggle="dropdown">
                        My Account
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="dangnhap.php">Sign in</a>
                        <a class="dropdown-item" href="dangky.php">Sign up</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row align-items-center bg-light py-3 px-xl-5 d-none d-lg-flex">
        <div class="col-lg-4">
            <a href="index.php" class="text-decoration-none">
                <span class="h1 text-uppercase text-primary bg-dark px-2">Multi</span>
                <span class="h1 text-uppercase text-dark bg-primary px-2 ml-n1">Shop</span>
            </a>
        </div>
        <div class="col-lg-4 col-6 text-left">
            <form action="shop.php">
                <div class="input-group">
                    <input type="text" class="form-control" name="q" placeholder="Search for products" />
                    <div class="input-group-append">
                        <button class="input-group-text bg-transparent text-primary">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <div class="col-lg-4 col-6 text-right">
            <p class="m-0">Customer Service</p>
            <h5 class="m-0">+012 345 6789</h5>
        </div>
    </div>
</div>
<!-- Topbar End -->

<!-- Navbar Start -->
<div class="container-fluid bg-dark mb-30">
    <div class="row px-xl-5">
        <div class="col-lg-12">
            <nav class="navbar navbar-expand-lg bg-dark navbar-dark py-3 py-lg-0 px-0">
                <a href="index.php" class="text-decoration-none d-block d-lg-none">
                    <span class="h1 text-uppercase text-dark bg-light px-2">Multi</span>
                    <span class="h1 text-uppercase text-light bg-primary px-2 ml-n1">Shop</span>
                </a>
                <button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#navbarCollapse">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse justify-content-between" id="navbarCollapse">
                    <div class="navbar-nav mr-auto py-0">
                        <a href="index.php" class="nav-item nav-link">Trang chủ</a>
                        <a href="shop.php" class="nav-item nav-link">Cửa hàng</a>
                        <a href="gio_hang.php" class="nav-item nav-link">Giỏ hàng</a>
                        <a href="checkout.php" class="nav-item nav-link active">Thanh toán</a>
                    </div>
                </div>
            </nav>
        </div>
    </div>
</div>
<!-- Navbar End -->


<div class="container-fluid">
    <div class="row px-xl-5">
        <div class="col-lg-8">

            <h4 class="mb-3">Thanh toán đơn hàng</h4>

            <?php if(!$cart_items): ?>
                <div class="alert alert-warning">
                    Giỏ hàng đang trống. <a href="shop.php">Quay lại cửa hàng</a>.
                </div>
            <?php endif; ?>

            <!-- ACCORDION STEPS -->
            <div id="checkoutAccordion" class="checkout-step">

                <!-- STEP 1 -->
                <div class="card mb-3">
                    <div class="card-header bg-light" id="step1Head" data-toggle="collapse" data-target="#step1">
                        <h6 class="m-0 text-uppercase">
                            <i class="fa fa-user mr-2 text-primary"></i>
                            Bước 1: Thông tin khách hàng
                        </h6>
                    </div>
                    <div id="step1" class="collapse show" data-parent="#checkoutAccordion">
                        <div class="card-body bg-white">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>Họ tên</label>
                                    <input class="form-control" name="customer_name" form="checkoutForm"
                                           placeholder="Nhập họ tên"
                                           value="<?= e($_SESSION['customer_name'] ?? '') ?>">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Email</label>
                                    <input class="form-control" name="customer_email" form="checkoutForm" type="email"
                                           placeholder="Email"
                                           value="<?= e($user['email'] ?? '') ?>">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Số điện thoại</label>
                                    <input class="form-control" name="customer_phone" form="checkoutForm"
                                           placeholder="SĐT"
                                           value="<?= e($_SESSION['customer_phone'] ?? '') ?>">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Ghi chú khách hàng (tuỳ chọn)</label>
                                    <input class="form-control" name="customer_note" form="checkoutForm"
                                           placeholder="Ví dụ: cần gọi trước khi giao">
                                </div>
                            </div>

                            <div class="text-right">
                                <button class="btn btn-primary" type="button"
                                        data-toggle="collapse" data-target="#step2">
                                    Tiếp tục <i class="fa fa-arrow-right ml-1"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 2 -->
                <div class="card mb-3">
                    <div class="card-header bg-light" id="step2Head" data-toggle="collapse" data-target="#step2">
                        <h6 class="m-0 text-uppercase">
                            <i class="fa fa-truck mr-2 text-primary"></i>
                            Bước 2: Thông tin nhận hàng
                        </h6>
                    </div>

                    <div id="step2" class="collapse" data-parent="#checkoutAccordion">
                        <div class="card-body bg-white">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>Người nhận</label>
                                    <input class="form-control" name="ship_name" form="checkoutForm"
                                           placeholder="Tên người nhận">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>SĐT người nhận</label>
                                    <input class="form-control" name="ship_phone" form="checkoutForm"
                                           placeholder="SĐT người nhận">
                                </div>

                                <div class="col-md-12 form-group">
                                    <label>Địa chỉ chi tiết</label>
                                    <input class="form-control" name="ship_address" form="checkoutForm"
                                           placeholder="Số nhà, đường, khu vực">
                                </div>

                                <div class="col-md-4 form-group">
                                    <label>Tỉnh / Thành phố</label>
                                    <input class="form-control" name="ship_city" form="checkoutForm"
                                           placeholder="Ví dụ: Hồ Chí Minh">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>Quận / Huyện</label>
                                    <input class="form-control" name="ship_district" form="checkoutForm"
                                           placeholder="Ví dụ: Quận 1">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>Phường / Xã</label>
                                    <input class="form-control" name="ship_ward" form="checkoutForm"
                                           placeholder="Ví dụ: Bến Nghé">
                                </div>

                                <div class="col-md-12 form-group">
                                    <label>Ghi chú giao hàng</label>
                                    <textarea class="form-control" name="ship_note" form="checkoutForm" rows="3"
                                              placeholder="Ví dụ: giao giờ hành chính, gọi trước 10 phút"></textarea>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <button class="btn btn-light" type="button"
                                        data-toggle="collapse" data-target="#step1">
                                    <i class="fa fa-arrow-left mr-1"></i> Quay lại
                                </button>
                                <button class="btn btn-primary" type="button"
                                        data-toggle="collapse" data-target="#step3">
                                    Tiếp tục <i class="fa fa-arrow-right ml-1"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 3 -->
                <div class="card mb-3">
                    <div class="card-header bg-light" id="step3Head" data-toggle="collapse" data-target="#step3">
                        <h6 class="m-0 text-uppercase">
                            <i class="fa fa-clipboard-check mr-2 text-primary"></i>
                            Bước 3: Xác nhận đơn hàng
                        </h6>
                    </div>

                    <div id="step3" class="collapse" data-parent="#checkoutAccordion">
                        <div class="card-body bg-white">

                            <?php if(!$cart_items): ?>
                                <div class="alert alert-warning">
                                    Giỏ hàng trống, không thể thanh toán.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered text-center mb-0">
                                        <thead class="bg-primary text-white">
                                        <tr>
                                            <th>Sản phẩm</th>
                                            <th>Giá</th>
                                            <th>SL</th>
                                            <th>Tạm tính</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach($cart_items as $it): ?>
                                            <tr>
                                                <td class="text-left">
                                                    <img src="img/<?= e($it['img']) ?>" class="summary-img mr-2">
                                                    <?= e($it['ten']) ?>
                                                </td>
                                                <td><?= number_format($it['gia'],0,',','.') ?>₫</td>
                                                <td><?= (int)$it['qty'] ?></td>
                                                <td><?= number_format($it['sub'],0,',','.') ?>₫</td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mt-3 text-right">
                                    <h5>Tổng cộng: <span class="text-primary">
                                        <?= number_format($cart_total,0,',','.') ?>₫
                                    </span></h5>
                                </div>
                            <?php endif; ?>

                            <div class="d-flex justify-content-between mt-3">
                                <button class="btn btn-light" type="button"
                                        data-toggle="collapse" data-target="#step2">
                                    <i class="fa fa-arrow-left mr-1"></i> Quay lại
                                </button>
                                <button class="btn btn-primary" type="button"
                                        data-toggle="collapse" data-target="#step4"
                                        <?= !$cart_items ? 'disabled' : '' ?>>
                                    Tiếp tục thanh toán <i class="fa fa-arrow-right ml-1"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 4 -->
                <div class="card mb-3">
                    <div class="card-header bg-light" id="step4Head" data-toggle="collapse" data-target="#step4">
                        <h6 class="m-0 text-uppercase">
                            <i class="fa fa-credit-card mr-2 text-primary"></i>
                            Bước 4: Chọn phương thức thanh toán
                        </h6>
                    </div>

                    <div id="step4" class="collapse" data-parent="#checkoutAccordion">
                        <div class="card-body bg-white">

                            <form id="checkoutForm" method="POST" action="checkout_process.php" enctype="multipart/form-data">
                                <input type="hidden" name="total_amount" value="<?= (float)$cart_total ?>">

                                <div class="custom-control custom-radio mb-2">
                                    <input type="radio" class="custom-control-input pay-radio"
                                           id="pay_cod" name="payment_method" value="cod" checked>
                                    <label class="custom-control-label" for="pay_cod">
                                        Thanh toán khi nhận hàng (Tiền mặt - COD)
                                    </label>
                                    <div class="payment-box active" id="box_cod">
                                        <p class="mb-0 text-muted">
                                            Bạn sẽ thanh toán trực tiếp cho shipper khi nhận hàng.
                                        </p>
                                    </div>
                                </div>

                                <div class="custom-control custom-radio mb-2">
                                    <input type="radio" class="custom-control-input pay-radio"
                                           id="pay_bank" name="payment_method" value="bank">
                                    <label class="custom-control-label" for="pay_bank">
                                        Chuyển khoản ngân hàng
                                    </label>
                                    <div class="payment-box" id="box_bank">
                                        <p><b>Ngân hàng:</b> Vietcombank</p>
                                        <p><b>STK:</b> 0123 456 789</p>
                                        <p><b>Chủ TK:</b> MOBISHOP</p>
                                        <p class="mb-2 text-muted">Nội dung CK: SDT + Mã đơn (sau khi đặt hàng)</p>
                                        <label>Ảnh biên lai (tuỳ chọn)</label>
                                        <input type="file" name="bank_receipt" class="form-control-file">
                                    </div>
                                </div>

                                <div class="custom-control custom-radio mb-2">
                                    <input type="radio" class="custom-control-input pay-radio"
                                           id="pay_wallet" name="payment_method" value="wallet">
                                    <label class="custom-control-label" for="pay_wallet">
                                        Ví điện tử (Momo / ZaloPay / VNPay)
                                    </label>
                                    <div class="payment-box" id="box_wallet">
                                        <label>Chọn ví</label>
                                        <select class="custom-select" name="wallet_type">
                                            <option value="momo">Momo</option>
                                            <option value="zalopay">ZaloPay</option>
                                            <option value="vnpay">VNPay</option>
                                        </select>
                                        <small class="text-muted d-block mt-2">
                                            Sau khi bấm "Đặt hàng", hệ thống sẽ chuyển sang cổng ví bạn chọn.
                                        </small>
                                    </div>
                                </div>

                                <div class="custom-control custom-radio mb-2">
                                    <input type="radio" class="custom-control-input pay-radio"
                                           id="pay_installment" name="payment_method" value="installment">
                                    <label class="custom-control-label" for="pay_installment">
                                        Trả góp (0% / qua thẻ tín dụng)
                                    </label>
                                    <div class="payment-box" id="box_installment">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label>Kỳ hạn trả góp</label>
                                                <select class="custom-select" name="installment_months">
                                                    <option value="3">3 tháng</option>
                                                    <option value="6">6 tháng</option>
                                                    <option value="9">9 tháng</option>
                                                    <option value="12">12 tháng</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label>Ngân hàng hỗ trợ</label>
                                                <select class="custom-select" name="installment_bank">
                                                    <option value="vcb">Vietcombank</option>
                                                    <option value="tcb">Techcombank</option>
                                                    <option value="acb">ACB</option>
                                                    <option value="vp">VPBank</option>
                                                </select>
                                            </div>
                                        </div>
                                        <small class="text-muted d-block mt-2">
                                            Hệ thống sẽ liên hệ xác nhận trả góp sau khi bạn đặt hàng.
                                        </small>
                                    </div>
                                </div>

                                <hr>

                                <div class="d-flex justify-content-between">
                                    <button class="btn btn-light" type="button"
                                            data-toggle="collapse" data-target="#step3">
                                        <i class="fa fa-arrow-left mr-1"></i> Quay lại
                                    </button>

                                    <button class="btn btn-primary px-5" type="submit" name="place_order"
                                        <?= !$cart_items ? 'disabled' : '' ?>>
                                        Đặt hàng
                                    </button>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>

            </div><!-- /accordion -->
        </div>

        <!-- RIGHT SUMMARY -->
        <div class="col-lg-4">
            <div class="bg-light p-30 mb-5">
                <h5 class="section-title position-relative text-uppercase mb-3">
                    <span class="bg-secondary pr-3">Tóm tắt đơn</span>
                </h5>

                <?php if(!$cart_items): ?>
                    <p class="text-muted">Chưa có sản phẩm.</p>
                <?php else: ?>
                    <?php foreach($cart_items as $it): ?>
                        <div class="d-flex justify-content-between">
                            <p><?= e($it['ten']) ?> x <?= (int)$it['qty'] ?></p>
                            <p><?= number_format($it['sub'],0,',','.') ?>₫</p>
                        </div>
                    <?php endforeach; ?>
                    <hr>

                    <div class="d-flex justify-content-between mt-2">
                        <h6 class="font-weight-medium">Tổng cộng</h6>
                        <h6 class="font-weight-medium text-primary">
                            <?= number_format($cart_total,0,',','.') ?>₫
                        </h6>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<a href="#" class="btn btn-primary back-to-top"><i class="fa fa-angle-double-up"></i></a>

<script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
<script src="lib/easing/easing.min.js"></script>
<script src="lib/owlcarousel/owl.carousel.min.js"></script>
<script src="mail/jqBootstrapValidation.min.js"></script>
<script src="mail/contact.js"></script>
<script src="js/main.js"></script>

<script>
    // Show/hide payment detail boxes
    const radios = document.querySelectorAll('.pay-radio');
    const boxes = {
        cod: document.getElementById('box_cod'),
        bank: document.getElementById('box_bank'),
        wallet: document.getElementById('box_wallet'),
        installment: document.getElementById('box_installment'),
    };

    function updateBox() {
        Object.values(boxes).forEach(b => b.classList.remove('active'));
        const checked = document.querySelector('.pay-radio:checked');
        if(checked && boxes[checked.value]) {
            boxes[checked.value].classList.add('active');
        }
    }
    radios.forEach(r => r.addEventListener('change', updateBox));
    updateBox();
</script>
</body>
</html>
