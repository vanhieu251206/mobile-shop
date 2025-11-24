<?php
session_start();

// Nếu giỏ hàng chưa có, tạo mảng rỗng
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Tính subtotal
$subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['gia'] * $item['so_luong'];
}

// Phí vận chuyển cố định
$shipping = 20000; // 20.000đ
$total = $subtotal + $shipping;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Giỏ Hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Favicon -->
    <link href="img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">  

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/style.css" rel="stylesheet">
    <style>
        .cart-item img { width: 60px; height: 60px; object-fit: cover; }
        .quantity input { width: 60px; text-align: center; }
        .cart-summary { background: #f8f9fa; padding: 20px; }
        .product-name { margin-left: 10px; }
        .table td, .table th { vertical-align: middle; }
    </style>
</head>
<body>
  <!-- Topbar Start -->
    <div class="container-fluid">
        <div class="row bg-secondary py-1 px-xl-5">
            <div class="col-lg-6 d-none d-lg-block">
                <div class="d-inline-flex align-items-center h-100">
                    <a class="text-body mr-3" href="">Giới thiệu</a>
                    <a class="text-body mr-3" href="">Liên hệ</a>
                    <a class="text-body mr-3" href="">Hỗ trợ</a>
                    <a class="text-body mr-3" href="">Câu hỏi thường gặp</a>
                </div>
            </div>
            <div class="col-lg-6 text-center text-lg-right">
                <div class="d-inline-flex align-items-center">
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-light dropdown-toggle" data-toggle="dropdown">Tài khoản</button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <button class="dropdown-item" type="button">Đăng nhập</button>
                            <button class="dropdown-item" type="button">Đăng ký </button>
                        </div>
                    </div>
                </div>
                <div class="d-inline-flex align-items-center d-block d-lg-none">
                    <a href="" class="btn px-0 ml-2">
                        <i class="fas fa-heart text-dark"></i>
                        <span class="badge text-dark border border-dark rounded-circle" style="padding-bottom: 2px;">0</span>
                    </a>
                    <a href="" class="btn px-0 ml-2">
                        <i class="fas fa-shopping-cart text-dark"></i>
                        <span class="badge text-dark border border-dark rounded-circle" style="padding-bottom: 2px;">0</span>
                    </a>
                </div>
            </div>
        </div>
        <div class="row align-items-center bg-light py-3 px-xl-5 d-none d-lg-flex">
            <div class="col-lg-4">
                <a href="" class="text-decoration-none">
                    <span class="h1 text-uppercase text-primary bg-dark px-2">Mobi</span>
                    <span class="h1 text-uppercase text-dark bg-primary px-2 ml-n1">Shop</span>
                </a>
            </div>
            <div class="col-lg-4 col-6 text-left">
   <form action="search.php" method="GET">
    <div class="input-group">
        <input type="text" name="keyword" class="form-control" placeholder="Tìm kiếm sản phẩm...">
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
            </div>
        </div>
    </div>
    <!-- Topbar End -->


    <!-- Navbar Start -->
    <div class="container-fluid bg-dark mb-30">
        <div class="row px-xl-5">
            <div class="col-lg-3 d-none d-lg-block">
                <a class="btn d-flex align-items-center justify-content-between bg-primary w-100" data-toggle="collapse" href="#navbar-vertical" style="height: 65px; padding: 0 30px;">
                    <h6 class="text-dark m-0"><i class="fa fa-bars mr-2"></i>Danh mục sản phẩm</h6>
                    <i class="fa fa-angle-down text-dark"></i>
                </a>
                <nav class="collapse position-absolute navbar navbar-vertical navbar-light align-items-start p-0 bg-light" id="navbar-vertical" style="width: calc(100% - 30px); z-index: 999;">
                    <div class="navbar-nav w-100">
                        <a href="#" class="nav-item nav-link">Samsung</a>
                        <a href="#" class="nav-item nav-link">iPhone</a>
                        <a href="#" class="nav-item nav-link">Xiaomi</a>
                        <a href="#" class="nav-item nav-link">Oppo</a>
                        <a href="#" class="nav-item nav-link">Phụ kiện</a>
                        <a href="#" class="nav-item nav-link">Tai nghe Bluetooth</a>
                        <a href="#" class="nav-item nav-link">Sạc dự phòng</a>
                        <a href="#" class="nav-item nav-link">Ốp lưng</a>
                        <a href="#" class="nav-item nav-link">Cáp sạc</a>
                        <a href="#" class="nav-item nav-link">Khuyến mãi</a>
                    </div>
                </nav>
            </div>
            <div class="col-lg-9">
                <nav class="navbar navbar-expand-lg bg-dark navbar-dark py-3 py-lg-0 px-0">
                    <a href="" class="text-decoration-none d-block d-lg-none">
                        <span class="h1 text-uppercase text-dark bg-light px-2">Multi</span>
                        <span class="h1 text-uppercase text-light bg-primary px-2 ml-n1">Shop</span>
                    </a>
                    <button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#navbarCollapse">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse justify-content-between" id="navbarCollapse">
                        <div class="navbar-nav mr-auto py-0">
                             <a href="index.php" class="nav-item nav-link active"
                  >Trang chủ</a
                >
                <a href="shop.php" class="nav-item nav-link">Cửa hàng</a>
                <a href="detail.php" class="nav-item nav-link"
                  >Chi tiết sản phẩm</a
                >
                <a href="contact.php" class="nav-item nav-link">Liên hệ</a>
                <di class="nav-item dropdown">
                  <a
                    href="#"
                    class="nav-link dropdown-toggle active"
                    data-toggle="dropdown"
                    >Trang <i class="fa fa-angle-down mt-1"></i
                  ></a>
                  <div class="dropdown-menu bg-primary rounded-0 border-0 m-0">
                    <a href="cart.php" class="dropdown-item">Thống kê báo cáo</a>
                    <a href="checkout.php" class="dropdown-item active"
                      >Quản lý nội dung </a
                    >
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
        <div class="col-lg-8 table-responsive mb-5">
            <?php if(empty($_SESSION['cart'])): ?>
                <p class="text-center">Giỏ hàng của bạn đang trống.</p>
            <?php else: ?>
            <table class="table table-light table-borderless table-hover text-center mb-0">
                <thead class="thead-dark">
                    <tr>
                        <th>Hình Ảnh</th>
                        <th>Sản Phẩm</th>
                        <th>Giá</th>
                        <th>Số Lượng</th>
                        <th>Tổng Cộng</th>
                        <th>Xóa</th>
                    </tr>
                </thead>
                <tbody class="align-middle">
                    <?php foreach ($_SESSION['cart'] as $id => $item): ?>
                    <tr class="cart-item">
                        <td class="align-middle">
                            <img src="img/<?= $item['hinh_anh'] ?>" alt="<?= $item['ten_san_pham'] ?>">
                        </td>
                        <td class="align-middle"><?= $item['ten_san_pham'] ?></td>
                        <td class="align-middle"><?= number_format($item['gia'],0,",",".") ?>₫</td>
                        <td class="align-middle">
                            <div class="input-group quantity mx-auto" style="width:100px;">
                                <a href="update_cart.php?action=decrease&id=<?= $id ?>" class="btn btn-sm btn-warning btn-minus"><i class="fa fa-minus"></i></a>
                                <input type="text" class="form-control form-control-sm bg-secondary border-0 text-center" value="<?= $item['so_luong'] ?>" readonly>
                                <a href="update_cart.php?action=increase&id=<?= $id ?>" class="btn btn-sm btn-warning btn-plus"><i class="fa fa-plus"></i></a>
                            </div>
                        </td>
                        <td class="align-middle"><?= number_format($item['gia'] * $item['so_luong'],0,",",".") ?>₫</td>
                        <td class="align-middle">
                            <a href="update_cart.php?action=remove&id=<?= $id ?>" class="btn btn-sm btn-danger"><i class="fa fa-times"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- Cart Summary -->
        <div class="col-lg-4">
            <form class="mb-3" action="">
                <div class="input-group">
                    <input type="text" class="form-control border-0 p-4" placeholder="Coupon Code">
                    <div class="input-group-append">
                        <button class="btn btn-warning">Áp dụng phiếu giảm giá</button>
                    </div>
                </div>
            </form>

            <h5 class="section-title position-relative text-uppercase mb-3">
                <span class="bg-secondary pr-3">Tóm tắt giỏ hàng</span>
            </h5>
            <div class="cart-summary">
                <div class="d-flex justify-content-between mb-2">
                    <h6>Giá bán</h6>
                    <h6><?= number_format($subtotal,0,",",".") ?>₫</h6>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <h6>Vận chuyển</h6>
                    <h6><?= number_format($shipping,0,",",".") ?>₫</h6>
                </div>
                <hr>
                <div class="d-flex justify-content-between mt-2">
                    <h5>Tổng cộng</h5>
                    <h5><?= number_format($total,0,",",".") ?>₫</h5>
                </div>
                <a href="checkout.php" class="btn btn-block btn-warning font-weight-bold my-3 py-3">Tiến hành thanh toán</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
