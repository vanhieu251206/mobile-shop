<?php
session_start();
require_once 'db.php';

function e($str){ return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }

// Đếm sản phẩm trong giỏ
$cartCount = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach($_SESSION['cart'] as $item){
        $cartCount += (int)($item['so_luong'] ?? $item['qty'] ?? 0);
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Liên hệ - MobiShop</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

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
</head>

<body>
<!-- Topbar Start (y hệt index.php) -->
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


<!-- Navbar Start (y hệt index.php) -->
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
                        <a href="gio_hang.php" class="nav-item nav-link">Giỏ hàng</a>
                        <a href="contact.php" class="nav-item nav-link active">Liên hệ</a>
                    </div>

                    <div class="navbar-nav ml-auto py-0 d-none d-lg-block">
                        <a href="gio_hang.php" class="btn px-0 ml-3">
                            <i class="fas fa-shopping-cart text-primary"></i>
                            <span class="badge text-secondary border border-secondary rounded-circle"
                                  style="padding-bottom: 2px;">
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


<!-- Breadcrumb Start -->
<div class="container-fluid">
    <div class="row px-xl-5">
        <div class="col-12">
            <nav class="breadcrumb bg-light mb-30">
                <a class="breadcrumb-item text-dark" href="index.php">Trang chủ</a>
                <span class="breadcrumb-item active">Liên hệ</span>
            </nav>
        </div>
    </div>
</div>
<!-- Breadcrumb End -->


<!-- Contact Start (giữ nguyên layout, đổi text nhẹ) -->
<div class="container-fluid">
    <h2 class="section-title position-relative text-uppercase mx-xl-5 mb-4">
        <span class="bg-secondary pr-3">Liên hệ với MobiShop</span>
    </h2>
    <div class="row px-xl-5">
        <div class="col-lg-7 mb-5">
            <div class="contact-form bg-light p-30">
                <div id="success"></div>
                <form name="sentMessage" id="contactForm" novalidate="novalidate">
                    <div class="control-group">
                        <input type="text" class="form-control" id="name" placeholder="Họ và tên"
                               required="required" data-validation-required-message="Vui lòng nhập họ tên" />
                        <p class="help-block text-danger"></p>
                    </div>
                    <div class="control-group">
                        <input type="email" class="form-control" id="email" placeholder="Email"
                               required="required" data-validation-required-message="Vui lòng nhập email" />
                        <p class="help-block text-danger"></p>
                    </div>
                    <div class="control-group">
                        <input type="text" class="form-control" id="subject" placeholder="Tiêu đề"
                               required="required" data-validation-required-message="Vui lòng nhập tiêu đề" />
                        <p class="help-block text-danger"></p>
                    </div>
                    <div class="control-group">
                        <textarea class="form-control" rows="8" id="message" placeholder="Nội dung"
                                  required="required"
                                  data-validation-required-message="Vui lòng nhập nội dung"></textarea>
                        <p class="help-block text-danger"></p>
                    </div>
                    <div>
                        <button class="btn btn-primary py-2 px-4" type="submit" id="sendMessageButton">
                            Gửi liên hệ
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-lg-5 mb-5">
            <div class="bg-light p-30 mb-30">
                <iframe style="width: 100%; height: 250px;"
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3001156.4288297426!2d-78.01371936852176!3d42.72876761954724!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x4ccc4bf0f123a5a9%3A0xddcfc6c1de189567!2sNew%20York%2C%20USA!5e0!3m2!1sen!2sbd!4v1603794290143!5m2!1sen!2sbd"
                        frameborder="0" style="border:0;" allowfullscreen="" aria-hidden="false"
                        tabindex="0"></iframe>
            </div>
            <div class="bg-light p-30 mb-3">
                <p class="mb-2"><i class="fa fa-map-marker-alt text-primary mr-3"></i>123 Street, New York, USA</p>
                <p class="mb-2"><i class="fa fa-envelope text-primary mr-3"></i>support@mobishop.vn</p>
                <p class="mb-2"><i class="fa fa-phone-alt text-primary mr-3"></i>1900 6868</p>
            </div>
        </div>
    </div>
</div>
<!-- Contact End -->


<!-- Footer Start (giống index.php) -->
<div class="container-fluid bg-dark text-secondary mt-5 pt-5">
    <div class="row px-xl-5 pt-5">
        <div class="col-lg-4 col-md-12 mb-5 pr-3 pr-xl-5">
            <h5 class="text-secondary text-uppercase mb-4">MobiShop</h5>
            <p class="mb-4">Chuỗi cửa hàng bán lẻ điện thoại & phụ kiện chính hãng, cam kết giá tốt và dịch vụ hậu mãi tận tâm.</p>
            <p class="mb-2"><i class="fa fa-map-marker-alt text-primary mr-3"></i>123 Street, New York, USA</p>
            <p class="mb-2"><i class="fa fa-envelope text-primary mr-3"></i>support@mobishop.vn</p>
            <p class="mb-0"><i class="fa fa-phone-alt text-primary mr-3"></i>1900 6868</p>
        </div>
        <div class="col-lg-8 col-md-12">
            <div class="row">
                <div class="col-md-4 mb-5">
                    <h5 class="text-secondary text-uppercase mb-4">Quick Shop</h5>
                    <div class="d-flex flex-column justify-content-start">
                        <a class="text-secondary mb-2" href="index.php"><i class="fa fa-angle-right mr-2"></i>Trang chủ</a>
                        <a class="text-secondary mb-2" href="shop.php"><i class="fa fa-angle-right mr-2"></i>Cửa hàng</a>
                        <a class="text-secondary mb-2" href="gio_hang.php"><i class="fa fa-angle-right mr-2"></i>Giỏ hàng</a>
                        <a class="text-secondary" href="contact.php"><i class="fa fa-angle-right mr-2"></i>Liên hệ</a>
                    </div>
                </div>
                <div class="col-md-4 mb-5">
                    <h5 class="text-secondary text-uppercase mb-4">Tài khoản</h5>
                    <div class="d-flex flex-column justify-content-start">
                        <?php if(!isset($_SESSION['user_id'])): ?>
                            <a class="text-secondary mb-2" href="dangnhap.php"><i class="fa fa-angle-right mr-2"></i>Đăng nhập</a>
                            <a class="text-secondary mb-2" href="dangky.php"><i class="fa fa-angle-right mr-2"></i>Đăng ký</a>
                        <?php else: ?>
                            <a class="text-secondary mb-2" href="index.php?logout=1"><i class="fa fa-angle-right mr-2"></i>Đăng xuất</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-4 mb-5">
                    <h5 class="text-secondary text-uppercase mb-4">Nhận tin khuyến mãi</h5>
                    <p>Đừng bỏ lỡ các deal hot và chương trình độc quyền từ MobiShop.</p>
                    <form action="">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Email của bạn">
                            <div class="input-group-append">
                                <button class="btn btn-primary">Đăng ký</button>
                            </div>
                        </div>
                    </form>
                    <h6 class="text-secondary text-uppercase mt-4 mb-3">Kết nối với chúng tôi</h6>
                    <div class="d-flex">
                        <a class="btn btn-primary btn-square mr-2" href="#"><i class="fab fa-facebook-f"></i></a>
                        <a class="btn btn-primary btn-square mr-2" href="#"><i class="fab fa-youtube"></i></a>
                        <a class="btn btn-primary btn-square mr-2" href="#"><i class="fab fa-tiktok"></i></a>
                        <a class="btn btn-primary btn-square" href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row border-top mx-xl-5 py-4" style="border-color: rgba(256,256,256,.1) !important;">
        <div class="col-md-6 px-xl-0">
            <p class="mb-md-0 text-center text-md-left text-secondary">
                &copy; <a class="text-primary" href="#">MobiShop</a>. All Rights Reserved.
            </p>
        </div>
        <div class="col-md-6 px-xl-0 text-center text-md-right">
            <img class="img-fluid" src="img/payments.png" alt="">
        </div>
    </div>
</div>
<!-- Footer End -->


<!-- Back to Top -->
<a href="#" class="btn btn-primary back-to-top"><i class="fa fa-angle-double-up"></i></a>

<!-- JavaScript Libraries -->
<script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
<script src="lib/easing/easing.min.js"></script>
<script src="lib/owlcarousel/owl.carousel.min.js"></script>

<!-- Contact Javascript File -->
<script src="mail/jqBootstrapValidation.min.js"></script>
<script src="mail/contact.js"></script>

<!-- Template Javascript -->
<script src="js/main.js"></script>
</body>
</html>
