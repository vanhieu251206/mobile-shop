<?php
require_once "db.php";
session_start();

/* =========================
   Helpers
========================= */
function e($str){
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
function money($n){
    return number_format((float)$n, 0, ",", ".") . "₫";
}

/* =========================
   1) Init cart
========================= */
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/* =========================
   2) Đồng bộ giỏ với DB
========================= */
$cart = &$_SESSION['cart'];
$ids = array_keys($cart);

if ($ids) {
    $placeholders = implode(",", array_fill(0, count($ids), "?"));
    $stm = $pdo->prepare("
        SELECT id, ten_san_pham, gia, ton_kho, hinh_anh
        FROM san_pham
        WHERE id IN ($placeholders)
    ");
    $stm->execute($ids);
    $dbProducts = [];
    foreach ($stm->fetchAll() as $p) {
        $dbProducts[(int)$p['id']] = $p;
    }

    foreach ($cart as $id => &$item) {
        $id = (int)$id;
        if (!isset($dbProducts[$id])) {
            unset($cart[$id]); // sản phẩm không còn
            continue;
        }

        $p = $dbProducts[$id];

        // cập nhật info chuẩn
        $item['ten_san_pham'] = $p['ten_san_pham'];
        $item['gia']          = (float)$p['gia'];
        $item['hinh_anh']     = $p['hinh_anh'] ?: 'no-image.png';

        // clamp số lượng theo tồn kho
        $ton = (int)$p['ton_kho'];
        $qty = max(1, (int)($item['so_luong'] ?? 1));
        if ($ton > 0 && $qty > $ton) $qty = $ton;
        if ($ton <= 0) $qty = 1;

        $item['so_luong'] = $qty;
        $item['ton_kho']  = $ton;
    }
    unset($item);
}

/* =========================
   3) Tính tiền
========================= */
$subtotal   = 0;
$total_qty  = 0;
foreach ($cart as $item) {
    $subtotal  += (float)$item['gia'] * (int)$item['so_luong'];
    $total_qty += (int)$item['so_luong'];
}
$cartCount = $total_qty;

// Chính sách ship
$freeShipThreshold = 3000000;
$shipping = ($subtotal >= $freeShipThreshold || $subtotal == 0) ? 0 : 20000;
$total = $subtotal + $shipping;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Giỏ Hàng - MobiShop</title>

    <!-- (giữ nguyên libs của project) -->
    <link href="img/favicon.ico" rel="icon">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">

    <style>
        .cart-item img { width: 70px; height: 70px; object-fit: contain; background:#fff; border-radius:8px; }
        .quantity input { width: 60px; text-align: center; }
        .cart-summary { background: #f8f9fa; padding: 18px; border-radius:10px; }
        .table td, .table th { vertical-align: middle; }
        .empty-box{background:#fff;border-radius:12px;padding:30px;text-align:center;}
        .badge-qty{background:#ffcc00;color:#000;border-radius:999px;padding:2px 7px;font-weight:700;font-size:12px;margin-left:6px;}
        .stock-note{font-size:12px;color:#666;margin-top:4px;}
        .cart-actions{display:flex;gap:8px;flex-wrap:wrap;}
        .cart-actions a{flex:1;min-width:160px;}
    </style>
</head>
<body>

<!-- Topbar Start (giống index.php) -->
<div class="container-fluid">
    <div class="row bg-secondary py-1 px-xl-5">
        <div class="col-lg-6 d-none d-lg-block">
            <div class="d-inline-flex align-items-center h-100">
                <a class="text-body mr-3" href="#">Giới thiệu</a>
                <a class="text-body mr-3" href="#">Liên hệ</a>
                <a class="text-body mr-3" href="#">Hỗ trợ</a>
                <a class="text-body mr-3" href="#">Câu hỏi thường gặp</a>
            </div>
        </div>

        <div class="col-lg-6 text-center text-lg-right">
            <div class="d-inline-flex align-items-center">
                <div class="btn-group">
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <button type="button" class="btn btn-sm btn-light dropdown-toggle" data-toggle="dropdown">
                            Tài khoản
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="dangnhap.php">Đăng nhập</a>
                            <a class="dropdown-item" href="dangky.php">Đăng ký</a>
                        </div>
                    <?php else: ?>
                        <button type="button" class="btn btn-sm btn-light dropdown-toggle" data-toggle="dropdown">
                            Xin chào, <?= e($_SESSION['username']) ?>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <?php if (($_SESSION['vai_tro'] ?? '') === 'admin'): ?>
                                <a class="dropdown-item" href="staff.php">Trang Staff/Admin</a>
                            <?php endif; ?>
                            <a class="dropdown-item" href="index.php?logout=1">Đăng xuất</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Cart mobile -->
            <div class="d-inline-flex align-items-center d-block d-lg-none">
                <a href="gio_hang.php" class="btn px-0 ml-2">
                    <i class="fas fa-shopping-cart text-dark"></i>
                    <span class="badge text-dark border border-dark rounded-circle" style="padding-bottom: 2px;">
                        <?= $cartCount ?>
                    </span>
                </a>
            </div>
        </div>
    </div>

    <div class="row align-items-center bg-light py-3 px-xl-5 d-none d-lg-flex">
        <div class="col-lg-4">
            <a href="index.php" class="text-decoration-none">
                <span class="h1 text-uppercase text-primary bg-dark px-2">Mobi</span>
                <span class="h1 text-uppercase text-dark bg-primary px-2 ml-n1">Shop</span>
            </a>
            <div class="text-muted small mt-1">Hệ sinh thái điện thoại & phụ kiện cao cấp</div>
        </div>
        <div class="col-lg-4 col-6 text-left">
            <form action="search.php" method="GET">
                <div class="input-group">
                    <input type="text" name="keyword" class="form-control" placeholder="Tìm kiếm theo tên, hãng, dòng máy...">
                    <div class="input-group-append">
                        <button type="submit" class="input-group-text bg-transparent text-primary">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <div class="col-lg-4 col-6 text-right">
            <p class="m-0">Hỗ trợ khách hàng</p>
            <h5 class="m-0">1900 6868</h5>
            <small class="text-muted">9:00 - 21:00 (T2 - CN)</small>
        </div>
    </div>
</div>
<!-- Topbar End -->

<!-- Navbar Start (giống index.php) -->
<div class="container-fluid bg-dark mb-30">
    <div class="row px-xl-5">
        <div class="col-lg-3 d-none d-lg-block">
            <a class="btn d-flex align-items-center justify-content-between bg-primary w-100"
               data-toggle="collapse" href="#navbar-vertical" style="height: 65px; padding: 0 30px;">
                <h6 class="text-dark m-0"><i class="fa fa-bars mr-2"></i>Danh mục sản phẩm</h6>
                <i class="fa fa-angle-down text-dark"></i>
            </a>
            <nav class="collapse position-absolute navbar navbar-vertical navbar-light align-items-start p-0 bg-light"
                 id="navbar-vertical" style="width: calc(100% - 30px); z-index: 999;">
                <div class="navbar-nav w-100">
                    <a href="shop.php?brand=Samsung" class="nav-item nav-link">Samsung Galaxy</a>
                    <a href="shop.php?brand=iPhone" class="nav-item nav-link">Apple iPhone</a>
                    <a href="shop.php?brand=Xiaomi" class="nav-item nav-link">Xiaomi / Redmi</a>
                    <a href="shop.php?brand=Oppo" class="nav-item nav-link">OPPO / Realme</a>
                    <a href="shop.php?cat=2" class="nav-item nav-link">Phụ kiện chính hãng</a>
                </div>
            </nav>
        </div>

        <div class="col-lg-9">
            <nav class="navbar navbar-expand-lg bg-dark navbar-dark py-3 py-lg-0 px-0">
                <a href="index.php" class="text-decoration-none d-block d-lg-none">
                    <span class="h1 text-uppercase text-dark bg-light px-2">Mobi</span>
                    <span class="h1 text-uppercase text-light bg-primary px-2 ml-n1">Shop</span>
                </a>
                <button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#navbarCollapse">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse justify-content-between" id="navbarCollapse">
                    <div class="navbar-nav mr-auto py-0">
                        <a href="index.php" class="nav-item nav-link">Trang chủ</a>
                        <a href="shop.php" class="nav-item nav-link">Cửa hàng</a>
                        <a href="gio_hang.php" class="nav-item nav-link active">Giỏ hàng</a>
                        <a href="contact.php" class="nav-item nav-link">Liên hệ</a>
                    </div>

                    <div class="navbar-nav ml-auto py-0 d-none d-lg-block">
                        <a href="gio_hang.php" class="btn px-0 ml-3">
                            <i class="fas fa-shopping-cart text-primary"></i>
                            <span class="badge text-secondary border border-secondary rounded-circle"
                                  style="padding-bottom:2px;">
                                <?= $cartCount ?>
                            </span>
                        </a>
                    </div>
                </div>
            </nav>
        </div>
    </div>
</div>
<!-- Navbar End -->

<div class="container-fluid my-5">
    <div class="row px-xl-5">

        <!-- Cart Items -->
        <div class="col-lg-8 table-responsive mb-4">

            <?php if(empty($cart)): ?>
                <div class="empty-box">
                    <img src="img/empty-cart.png" alt="" style="width:120px;opacity:.7" onerror="this.style.display='none'">
                    <h4 class="mt-3">Giỏ hàng của bạn đang trống</h4>
                    <p class="text-muted">Hãy quay lại cửa hàng để chọn sản phẩm bạn thích nhé.</p>
                    <a href="shop.php" class="btn btn-primary px-4">Tiếp tục mua sắm</a>
                </div>
            <?php else: ?>

            <table class="table table-light table-borderless table-hover text-center mb-0">
                <thead class="thead-dark">
                    <tr>
                        <th>Hình ảnh</th>
                        <th>Sản phẩm</th>
                        <th>Đơn giá</th>
                        <th>Số lượng</th>
                        <th>Tạm tính</th>
                        <th>Xóa</th>
                    </tr>
                </thead>
                <tbody class="align-middle">

                <?php foreach ($cart as $id => $item):
                    $id = (int)$id;
                    $lineTotal = (float)$item['gia'] * (int)$item['so_luong'];
                    $ton = (int)($item['ton_kho'] ?? 0);
                ?>
                    <tr class="cart-item">
                        <td class="align-middle">
                            <a href="detail.php?id=<?= $id ?>">
                                <img src="img/<?= e($item['hinh_anh']) ?>" alt="<?= e($item['ten_san_pham']) ?>">
                            </a>
                        </td>

                        <td class="align-middle text-left">
                            <a href="detail.php?id=<?= $id ?>" class="font-weight-bold">
                                <?= e($item['ten_san_pham']) ?>
                            </a>
                            <div class="stock-note">
                                <?= $ton>0 ? "Còn $ton sản phẩm" : "Hết hàng" ?>
                            </div>
                        </td>

                        <td class="align-middle"><?= money($item['gia']) ?></td>

                        <td class="align-middle">
                            <div class="input-group quantity mx-auto" style="width:110px;">
                                <a href="update_cart.php?action=decrease&id=<?= $id ?>"
                                   class="btn btn-sm btn-warning btn-minus"><i class="fa fa-minus"></i></a>

                                <input type="text" class="form-control form-control-sm bg-secondary border-0 text-center"
                                       value="<?= (int)$item['so_luong'] ?>" readonly>

                                <a href="update_cart.php?action=increase&id=<?= $id ?>"
                                   class="btn btn-sm btn-warning btn-plus"
                                   <?= ($ton>0 && $item['so_luong'] >= $ton) ? 'style="pointer-events:none;opacity:.5"' : '' ?>>
                                    <i class="fa fa-plus"></i>
                                </a>
                            </div>
                        </td>

                        <td class="align-middle font-weight-bold"><?= money($lineTotal) ?></td>

                        <td class="align-middle">
                            <a href="update_cart.php?action=remove&id=<?= $id ?>"
                               class="btn btn-sm btn-danger" title="Xóa khỏi giỏ">
                                <i class="fa fa-times"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>

                </tbody>
            </table>

            <div class="cart-actions mt-3">
                <a href="shop.php" class="btn btn-outline-dark">
                    <i class="fa fa-arrow-left mr-1"></i> Tiếp tục mua sắm
                </a>
                <a href="update_cart.php?action=clear" class="btn btn-outline-danger"
                   onclick="return confirm('Xóa toàn bộ giỏ hàng?')">
                    <i class="fa fa-trash mr-1"></i> Xóa giỏ hàng
                </a>
            </div>

            <?php endif; ?>
        </div>

        <!-- Cart Summary -->
        <div class="col-lg-4">
            <form class="mb-3" action="" onsubmit="return false;">
                <div class="input-group">
                    <input type="text" class="form-control border-0 p-4" placeholder="Mã giảm giá (chưa hỗ trợ)">
                    <div class="input-group-append">
                        <button class="btn btn-warning" disabled>Áp dụng</button>
                    </div>
                </div>
            </form>

            <h5 class="section-title position-relative text-uppercase mb-3">
                <span class="bg-secondary pr-3">Tóm tắt giỏ hàng</span>
            </h5>

            <div class="cart-summary">
                <div class="d-flex justify-content-between mb-2">
                    <h6>Tạm tính</h6>
                    <h6><?= money($subtotal) ?></h6>
                </div>

                <div class="d-flex justify-content-between mb-2">
                    <h6>Vận chuyển</h6>
                    <h6><?= $shipping ? money($shipping) : "Miễn phí" ?></h6>
                </div>

                <?php if($subtotal < $freeShipThreshold && $subtotal>0): ?>
                    <small class="text-muted d-block mb-2">
                        Mua thêm <b><?= money($freeShipThreshold - $subtotal) ?></b> để được miễn phí vận chuyển.
                    </small>
                <?php endif; ?>

                <hr>

                <div class="d-flex justify-content-between mt-2">
                    <h5>Tổng cộng</h5>
                    <h5 class="text-danger"><?= money($total) ?></h5>
                </div>

                <a href="checkout.php"
                   class="btn btn-block btn-warning font-weight-bold my-3 py-3 <?= empty($cart) ? 'disabled' : '' ?>">
                    Tiến hành thanh toán
                </a>

                <div class="text-muted" style="font-size:13px;">
                    <div><i class="fa fa-shield-alt text-success mr-1"></i> Thanh toán an toàn</div>
                    <div><i class="fa fa-truck text-success mr-1"></i> Giao nhanh nội thành</div>
                    <div><i class="fa fa-sync text-success mr-1"></i> 1 đổi 1 nếu lỗi</div>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
<script src="lib/easing/easing.min.js"></script>
<script src="lib/owlcarousel/owl.carousel.min.js"></script>
<script src="js/main.js"></script>
</body>
</html>
