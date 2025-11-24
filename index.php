<?php
session_start();
require_once 'db.php';

// Logout nhanh ngay trong index
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}

// Lấy tất cả sản phẩm
$stmt = $pdo->query("SELECT * FROM san_pham ORDER BY id ASC");
$products = $stmt->fetchAll();

// Lấy 8 sản phẩm gần nhất để show "Recent"
$recentProducts = array_slice($products, -8);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>MobiShop - Trang chủ</title>
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
                            Xin chào, <?= htmlspecialchars($_SESSION['username']) ?>
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

            <div class="d-inline-flex align-items-center d-block d-lg-none">
                <a href="cart.php" class="btn px-0 ml-2">
                    <i class="fas fa-shopping-cart text-dark"></i>
                    <span class="badge text-dark border border-dark rounded-circle" style="padding-bottom: 2px;">
                        <?= isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'],'so_luong')) : 0 ?>
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
            <a class="btn d-flex align-items-center justify-content-between bg-primary w-100"
               data-toggle="collapse" href="#navbar-vertical" style="height: 65px; padding: 0 30px;">
                <h6 class="text-dark m-0"><i class="fa fa-bars mr-2"></i>Danh mục sản phẩm</h6>
                <i class="fa fa-angle-down text-dark"></i>
            </a>
            <nav class="collapse position-absolute navbar navbar-vertical navbar-light align-items-start p-0 bg-light"
                 id="navbar-vertical" style="width: calc(100% - 30px); z-index: 999;">
                <div class="navbar-nav w-100">
                    <a href="shop.php?brand=Samsung" class="nav-item nav-link">Samsung</a>
                    <a href="shop.php?brand=iPhone" class="nav-item nav-link">iPhone</a>
                    <a href="shop.php?brand=Xiaomi" class="nav-item nav-link">Xiaomi</a>
                    <a href="shop.php?brand=Oppo" class="nav-item nav-link">Oppo</a>
                    <a href="shop.php?cat=2" class="nav-item nav-link">Phụ kiện</a>
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
                        <a href="index.php" class="nav-item nav-link active">Trang chủ</a>
                        <a href="shop.php" class="nav-item nav-link">Cửa hàng</a>
                        <a href="cart.php" class="nav-item nav-link">Giỏ hàng</a>
                        <a href="contact.php" class="nav-item nav-link">Liên hệ</a>
                    </div>

                    <div class="navbar-nav ml-auto py-0 d-none d-lg-block">
                        <a href="cart.php" class="btn px-0 ml-3">
                            <i class="fas fa-shopping-cart text-primary"></i>
                            <span class="badge text-secondary border border-secondary rounded-circle"
                                  style="padding-bottom: 2px;">
                                <?= isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'],'so_luong')) : 0 ?>
                            </span>
                        </a>
                    </div>

                </div>
            </nav>
        </div>
    </div>
</div>
<!-- Navbar End -->


<!-- Carousel Start (giữ nguyên) -->
<div class="container-fluid mb-3">
    <div class="row px-xl-5">
        <div class="col-lg-8">
            <div id="header-carousel" class="carousel slide carousel-fade mb-30 mb-lg-0" data-ride="carousel">
                <ol class="carousel-indicators">
                    <li data-target="#header-carousel" data-slide-to="0" class="active"></li>
                    <li data-target="#header-carousel" data-slide-to="1"></li>
                    <li data-target="#header-carousel" data-slide-to="2"></li>
                </ol>
                <div class="carousel-inner">
                    <div class="carousel-item position-relative active" style="height: 430px;">
                        <img class="position-absolute w-100 h-100" src="img/banner1.png" style="object-fit: cover;">
                        <div class="carousel-caption d-flex flex-column align-items-center justify-content-center">
                            <div class="p-3" style="max-width: 700px;">
                                <h1 class="display-4 text-white mb-3 animate__animated animate__fadeInDown">SỨC MẠNH ĐỈNH CAO</h1>
                                <p class="mx-md-4 px-5 animate__animated animate__bounceIn">
                                    Trải nghiệm hiệu năng bứt phá với chip A19 Bionic mới nhất...
                                </p>
                                <a class="btn btn-outline-light py-2 px-4 mt-3 animate__animated animate__fadeInUp" href="shop.php">Mua ngay</a>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item position-relative" style="height: 430px;">
                        <img class="position-absolute w-100 h-100" src="img/banner2.png" style="object-fit: cover;">
                        <div class="carousel-caption d-flex flex-column align-items-center justify-content-center">
                            <div class="p-3" style="max-width: 700px;">
                                <h1 class="display-4 text-white mb-3 animate__animated animate__fadeInDown">GIẢM ĐẾN 50%</h1>
                                <p class="mx-md-5 px-5 animate__animated animate__bounceIn">
                                    Ưu đãi cực khủng cho dòng điện thoại hot nhất tuần này
                                </p>
                                <a class="btn btn-outline-light py-2 px-4 mt-3 animate__animated animate__fadeInUp" href="shop.php">Mua ngay</a>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item position-relative" style="height: 430px;">
                        <img class="position-absolute w-100 h-100" src="img/banner3.png" style="object-fit: cover;">
                        <div class="carousel-caption d-flex flex-column align-items-center justify-content-center">
                            <div class="p-3" style="max-width: 700px;">
                                <h1 class="display-4 text-white mb-3 animate__animated animate__fadeInDown">
                                    ĐỔI CŨ LẤY MỚI <br> TRỢ GIÁ LÊN ĐẾN 3 TRIỆU
                                </h1>
                                <p class="mx-md-5 px-5 animate__animated animate__bounceIn">
                                    Nâng cấp smartphone dễ dàng, tiết kiệm hơn bao giờ hết
                                </p>
                                <a class="btn btn-outline-light py-2 px-4 mt-3 animate__animated animate__fadeInUp" href="shop.php">Mua ngay</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="product-offer mb-30" style="height: 200px;">
                <img class="img-fluid" src="img/offer1.jpg" alt="">
                <div class="offer-text">
                    <h3 class="text-white mb-3">PHỤ KIỆN DI ĐỘNG</h3>
                    <a href="shop.php?cat=2" class="btn btn-primary">Mua ngay</a>
                </div>
            </div>
            <div class="product-offer mb-30" style="height: 200px;">
                <img class="img-fluid" src="img/offer2.png" alt="">
                <div class="offer-text">
                    <h3 class="text-white mb-3">ỐP LƯNG</h3>
                    <a href="shop.php?cat=2" class="btn btn-primary">Mua ngay</a>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Carousel End -->


<!-- Featured Start (giữ nguyên) -->
<div class="container-fluid pt-5">
    <div class="row px-xl-5 pb-3">
        <div class="col-lg-3 col-md-6 col-sm-12 pb-1">
            <div class="d-flex align-items-center bg-light mb-4" style="padding: 30px;">
                <h1 class="fa fa-check text-primary m-0 mr-3"></h1>
                <h5 class="font-weight-semi-bold m-0">Chất lượng đảm bảo</h5>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12 pb-1">
            <div class="d-flex align-items-center bg-light mb-4" style="padding: 30px;">
                <h1 class="fa fa-shipping-fast text-primary m-0 mr-2"></h1>
                <h5 class="font-weight-semi-bold m-0">Miễn phí giao hàng</h5>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12 pb-1">
            <div class="d-flex align-items-center bg-light mb-4" style="padding: 30px;">
                <h1 class="fas fa-exchange-alt text-primary m-0 mr-3"></h1>
                <h5 class="font-weight-semi-bold m-0">Đổi trả trong 14 ngày</h5>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12 pb-1">
            <div class="d-flex align-items-center bg-light mb-4" style="padding: 30px;">
                <h1 class="fa fa-phone-volume text-primary m-0 mr-3"></h1>
                <h5 class="font-weight-semi-bold m-0">Hỗ trợ 24/7</h5>
            </div>
        </div>
    </div>
</div>
<!-- Featured End -->


<!-- Products Start -->
<div class="container-fluid pt-5 pb-3">
    <h2 class="section-title position-relative text-uppercase mx-xl-5 mb-4">
        <span class="bg-secondary pr-3">Featured Products</span>
    </h2>
    <div class="row px-xl-5">
        <?php foreach($products as $product): ?>
        <div class="col-lg-3 col-md-4 col-sm-6 pb-1">
            <div class="product-item bg-light mb-4">
                <div class="product-img position-relative overflow-hidden">
                    <a href="detail.php?id=<?= (int)$product['id'] ?>">
                        <img class="img-fluid w-100"
                             src="img/<?= htmlspecialchars($product['hinh_anh']) ?>"
                             alt="<?= htmlspecialchars($product['ten_san_pham']) ?>">
                    </a>

                    <div class="product-action">
                        <a class="btn btn-outline-dark btn-square" href="add_to_cart.php?id=<?= (int)$product['id'] ?>">
                            <i class="fa fa-shopping-cart"></i>
                        </a>
                        <a class="btn btn-outline-dark btn-square" href="detail.php?id=<?= (int)$product['id'] ?>">
                            <i class="fa fa-search"></i>
                        </a>
                    </div>
                </div>

                <div class="text-center py-4">
                    <a class="h6 text-decoration-none text-truncate"
                       href="detail.php?id=<?= (int)$product['id'] ?>">
                        <?= htmlspecialchars($product['ten_san_pham']) ?>
                    </a>
                    <div class="d-flex align-items-center justify-content-center mt-2">
                        <h5><?= number_format($product['gia'], 0, ',', '.') ?>₫</h5>
                    </div>
                    <div class="d-flex align-items-center justify-content-center mb-1">
                        <?php for($i=0; $i<5; $i++): ?>
                            <small class="fa fa-star text-primary mr-1"></small>
                        <?php endfor; ?>
                        <small>(<?= rand(10,200) ?>)</small>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<!-- Products End -->


<!-- Recent Products Start -->
<div class="container-fluid pt-5 pb-3">
    <h2 class="section-title position-relative text-uppercase mx-xl-5 mb-4">
        <span class="bg-secondary pr-3">Recent Products</span>
    </h2>

    <div class="row px-xl-5">
        <?php foreach($recentProducts as $rp): ?>
        <div class="col-lg-3 col-md-4 col-sm-6 pb-1">
            <div class="product-item bg-light mb-4">
                <div class="product-img position-relative overflow-hidden">
                    <a href="detail.php?id=<?= (int)$rp['id'] ?>">
                        <img class="img-fluid w-100"
                             src="img/<?= htmlspecialchars($rp['hinh_anh']) ?>"
                             alt="<?= htmlspecialchars($rp['ten_san_pham']) ?>">
                    </a>
                    <div class="product-action">
                        <a class="btn btn-outline-dark btn-square" href="add_to_cart.php?id=<?= (int)$rp['id'] ?>">
                            <i class="fa fa-shopping-cart"></i>
                        </a>
                        <a class="btn btn-outline-dark btn-square" href="detail.php?id=<?= (int)$rp['id'] ?>">
                            <i class="fa fa-search"></i>
                        </a>
                    </div>
                </div>

                <div class="text-center py-4">
                    <a class="h6 text-decoration-none text-truncate"
                       href="detail.php?id=<?= (int)$rp['id'] ?>">
                        <?= htmlspecialchars($rp['ten_san_pham']) ?>
                    </a>
                    <div class="d-flex align-items-center justify-content-center mt-2">
                        <h5><?= number_format($rp['gia'], 0, ',', '.') ?>₫</h5>
                    </div>
                    <div class="d-flex align-items-center justify-content-center mb-1">
                        <?php for($i=0; $i<5; $i++): ?>
                            <small class="fa fa-star text-primary mr-1"></small>
                        <?php endfor; ?>
                        <small>(<?= rand(10,200) ?>)</small>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<!-- Recent Products End -->


<!-- Footer Start (giữ nguyên template) -->
<div class="container-fluid bg-dark text-secondary mt-5 pt-5">
    <div class="row px-xl-5 pt-5">
        <div class="col-lg-4 col-md-12 mb-5 pr-3 pr-xl-5">
            <h5 class="text-secondary text-uppercase mb-4">Get In Touch</h5>
            <p class="mb-4">No dolore ipsum accusam no lorem...</p>
            <p class="mb-2"><i class="fa fa-map-marker-alt text-primary mr-3"></i>123 Street, New York, USA</p>
            <p class="mb-2"><i class="fa fa-envelope text-primary mr-3"></i>info@example.com</p>
            <p class="mb-0"><i class="fa fa-phone-alt text-primary mr-3"></i>+012 345 67890</p>
        </div>
        <div class="col-lg-8 col-md-12">
            <div class="row">
                <div class="col-md-4 mb-5">
                    <h5 class="text-secondary text-uppercase mb-4">Quick Shop</h5>
                    <div class="d-flex flex-column justify-content-start">
                        <a class="text-secondary mb-2" href="index.php"><i class="fa fa-angle-right mr-2"></i>Trang chủ</a>
                        <a class="text-secondary mb-2" href="shop.php"><i class="fa fa-angle-right mr-2"></i>Cửa hàng</a>
                        <a class="text-secondary mb-2" href="cart.php"><i class="fa fa-angle-right mr-2"></i>Giỏ hàng</a>
                        <a class="text-secondary" href="contact.php"><i class="fa fa-angle-right mr-2"></i>Liên hệ</a>
                    </div>
                </div>
                <div class="col-md-4 mb-5">
                    <h5 class="text-secondary text-uppercase mb-4">My Account</h5>
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
                    <h5 class="text-secondary text-uppercase mb-4">Newsletter</h5>
                    <p>Duo stet tempor ipsum sit amet magna ipsum tempor est</p>
                    <form action="">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Your Email Address">
                            <div class="input-group-append">
                                <button class="btn btn-primary">Sign Up</button>
                            </div>
                        </div>
                    </form>
                    <h6 class="text-secondary text-uppercase mt-4 mb-3">Follow Us</h6>
                    <div class="d-flex">
                        <a class="btn btn-primary btn-square mr-2" href="#"><i class="fab fa-twitter"></i></a>
                        <a class="btn btn-primary btn-square mr-2" href="#"><i class="fab fa-facebook-f"></i></a>
                        <a class="btn btn-primary btn-square mr-2" href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a class="btn btn-primary btn-square" href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row border-top mx-xl-5 py-4" style="border-color: rgba(256,256,256,.1) !important;">
        <div class="col-md-6 px-xl-0">
            <p class="mb-md-0 text-center text-md-left text-secondary">
                &copy; <a class="text-primary" href="#">Domain</a>. All Rights Reserved.
            </p>
        </div>
        <div class="col-md-6 px-xl-0 text-center text-md-right">
            <img class="img-fluid" src="img/payments.png" alt="">
        </div>
    </div>
</div>
<!-- Footer End -->


<a href="#" class="btn btn-primary back-to-top"><i class="fa fa-angle-double-up"></i></a>

<script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
<script src="lib/easing/easing.min.js"></script>
<script src="lib/owlcarousel/owl.carousel.min.js"></script>
<script src="mail/jqBootstrapValidation.min.js"></script>
<script src="mail/contact.js"></script>
<script src="js/main.js"></script>
</body>
</html>
