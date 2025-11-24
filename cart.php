<?php
// ============================
// PHẦN XỬ LÝ BACKEND (SAU NÀY)
// ============================

// Kết nối database (tùy bạn sửa thông tin)
$host = "localhost";
$user = "root";
$pass = "";  
$dbname = "multishop";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Lỗi kết nối database: " . $conn->connect_error);
}

// Giỏ hàng mẫu (có thể thay bằng dữ liệu trong DB)
$cart = [
    [
        "name" => "iPhone 17 Pro Max",
        "img" => "img/17.jpg",
        "price" => 40000000,
        "qty" => 1
    ],
    [
        "name" => "iPhone 16 Pro Max",
        "img" => "img/16.jpg",
        "price" => 30000000,
        "qty" => 1
    ],
    [
        "name" => "iPhone 15 Pro Max",
        "img" => "img/15.webp",
        "price" => 25000000,
        "qty" => 1
    ],
    [
        "name" => "iPhone 14 Pro Max",
        "img" => "img/14.jpg",
        "price" => 20500000,
        "qty" => 1
    ],
    [
        "name" => "iPhone 13 Pro Max",
        "img" => "img/13.jpg",
        "price" => 15800000,
        "qty" => 1
    ],
];

// Tính tổng bằng PHP (khi mới load trang)
$subtotal = 0;
foreach ($cart as $c) {
    $subtotal += $c["price"] * $c["qty"];
}

$shipping = 200000;
$total = $subtotal + $shipping;

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>MultiShop - Website Bán Hàng Online</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Mẫu HTML miễn phí" name="keywords">
    <meta content="Mẫu HTML miễn phí" name="description">

    <link href="img/favicon.ico" rel="icon">

    <!-- CSS -->
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">  
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>

<body>

<!-- ===================== -->
<!-- GIỮ NGUYÊN GIAO DIỆN -->
<!-- ===================== -->

<!-- Topbar Start -->
<div class="container-fluid">
    <div class="row bg-secondary py-1 px-xl-5">
        <!-- ... giữ nguyên toàn bộ phần topbar ... -->
    </div>

    <!-- ... giữ nguyên navbar ... -->
</div>

<!-- Breadcrumb Start -->
<div class="container-fluid">
    <div class="row px-xl-5">
        <div class="col-12">
            <nav class="breadcrumb bg-light mb-30">
                <a class="breadcrumb-item text-dark" href="#">Trang chủ</a>
                <a class="breadcrumb-item text-dark" href="#">Cửa hàng</a>
                <span class="breadcrumb-item active">Giỏ hàng</span>
            </nav>
        </div>
    </div>
</div>

<!-- Cart Start -->
<div class="container-fluid">
    <div class="row px-xl-5">
        <div class="col-lg-8 table-responsive mb-5">
            <table class="table table-light table-borderless table-hover text-center mb-0">
                <thead class="thead-dark">
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Giá</th>
                        <th>Số lượng</th>
                        <th>Tổng</th>
                        <th>Xóa</th>
                    </tr>
                </thead>
                <tbody class="align-middle">

                <?php foreach ($cart as $item): ?>
                <tr>
                    <td class="align-middle">
                        <img src="<?= $item['img'] ?>" style="width:50px;">
                        <?= $item['name'] ?>
                    </td>
                    <td class="align-middle"><?= number_format($item['price']) ?>₫</td>
                    <td class="align-middle">
                        <div class="input-group quantity mx-auto" style="width: 100px;">
                            <div class="input-group-btn">
                                <button class="btn btn-sm btn-primary btn-minus"><i class="fa fa-minus"></i></button>
                            </div>
                            <input type="text"
                                   class="form-control form-control-sm bg-secondary border-0 text-center"
                                   value="<?= $item['qty'] ?>">
                            <div class="input-group-btn">
                                <button class="btn btn-sm btn-primary btn-plus"><i class="fa fa-plus"></i></button>
                            </div>
                        </div>
                    </td>
                    <td class="align-middle"><?= number_format($item['price'] * $item['qty']) ?>₫</td>
                    <td class="align-middle"><button class="btn btn-sm btn-danger"><i class="fa fa-times"></i></button></td>
                </tr>
                <?php endforeach; ?>

                </tbody>
            </table>
        </div>

        <div class="col-lg-4">
            <form class="mb-30">
                <div class="input-group">
                    <input type="text" class="form-control border-0 p-4" placeholder="Mã giảm giá">
                    <div class="input-group-append">
                        <button class="btn btn-primary">Áp dụng</button>
                    </div>
                </div>
            </form>

            <h5 class="section-title position-relative text-uppercase mb-3">
                <span class="bg-secondary pr-3">Tóm tắt giỏ hàng</span>
            </h5>

            <div class="bg-light p-30 mb-5">
                <div class="border-bottom pb-2">
                    <div class="d-flex justify-content-between mb-3">
                        <h6>Tạm tính</h6>
                        <h6><?= number_format($subtotal) ?>₫</h6>
                    </div>
                    <div class="d-flex justify-content-between">
                        <h6 class="font-weight-medium">Vận chuyển</h6>
                        <h6 class="font-weight-medium"><?= number_format($shipping) ?>₫</h6>
                    </div>
                </div>
                <div class="pt-2">
                    <div class="d-flex justify-content-between mt-2">
                        <h5>Tổng</h5>
                        <h5><?= number_format($total) ?>₫</h5>
                    </div>
                    <button class="btn btn-block btn-primary font-weight-bold my-3 py-3">
                        Tiến hành thanh toán
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Footer giữ nguyên -->
<!-- Back to top giữ nguyên -->

<!-- JS giữ nguyên -->
<script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
<script src="lib/easing/easing.min.js"></script>
<script src="lib/owlcarousel/owl.carousel.min.js"></script>

<script src="mail/jqBootstrapValidation.min.js"></script>
<script src="mail/contact.js"></script>
<script src="js/main.js"></script>

<script>
/* Giữ nguyên toàn bộ đoạn jQuery update giỏ hàng */
$(document).ready(function () {
    $(".btn-plus, .btn-minus").off("click");

    function updateCartTotal() {
        let total = 0;
        $("tbody tr").each(function () {
            const priceText = $(this).find("td:nth-child(2)").text().replace(/[^\d]/g, "");
            const price = parseFloat(priceText);
            const quantity = parseInt($(this).find("input").val()) || 1;
            const subtotal = price * quantity;
            $(this).find("td:nth-child(4)").text(subtotal.toLocaleString('vi-VN') + "₫");
            total += subtotal;
        });

        const shipping = 200000;
        $(".bg-light h6:contains('Tạm tính')").next().text(total.toLocaleString('vi-VN') + "₫");
        $(".bg-light h5:contains('Tổng')").next().text((total + shipping).toLocaleString('vi-VN') + "₫");
    }

    $(".btn-plus").on("click", function () {
        let input = $(this).closest(".input-group").find("input");
        let val = parseInt(input.val()) || 0;
        input.val(val + 1);
        updateCartTotal();
    });

    $(".btn-minus").on("click", function () {
        let input = $(this).closest(".input-group").find("input");
        let val = parseInt(input.val()) || 0;
        if (val > 1) input.val(val - 1);
        updateCartTotal();
    });

    $("input").on("input", function () {
        let val = parseInt($(this).val());
        if (isNaN(val) || val < 1) $(this).val(1);
        updateCartTotal();
    });

    $(".btn-danger").on("click", function () {
        $(this).closest("tr").remove();
        updateCartTotal();
    });

    updateCartTotal();
});
</script>

</body>
</html>
