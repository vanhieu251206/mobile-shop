<?php
require_once 'db.php';

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : "";
$products = [];

if ($keyword !== "") {
    $stmt = $pdo->prepare("SELECT * FROM san_pham WHERE ten_san_pham LIKE ?");
    $stmt->execute(["%$keyword%"]);
    $products = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Kết quả tìm kiếm</title>
    <link href="css/style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
</head>

<body>

<!-- HEADER TÌM KIẾM -->
<div class="container-fluid mb-5">
    <div class="row px-xl-5">
        <div class="col-lg-4 col-6 text-left">
            <form action="search.php" method="GET">
                <div class="input-group">
                    <input type="text" name="keyword" class="form-control"
                           placeholder="Tìm kiếm sản phẩm..."
                           value="<?= htmlspecialchars($keyword) ?>">

                    <div class="input-group-append">
                        <button type="submit" class="input-group-text bg-transparent text-primary">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-lg-8 col-6 text-right">
            <h3 class="text-dark mt-2">Kết quả tìm kiếm</h3>
        </div>
    </div>
</div>


<!-- HIỂN THỊ SẢN PHẨM Y HỆT TRANG INDEX -->
<div class="container-fluid pt-5 pb-3">
    <h2 class="section-title position-relative text-uppercase mx-xl-5 mb-4">
        <span class="bg-secondary pr-3">Kết quả tìm kiếm</span>
    </h2>

    <div class="row px-xl-5">

        <?php if (count($products) === 0): ?>
            <div class="col-12 text-center">
                <h4 class="text-danger">Không tìm thấy sản phẩm nào!</h4>
                <a href="index.php" class="btn btn-primary mt-3">Quay lại trang chủ</a>
            </div>
        <?php endif; ?>

        <?php foreach($products as $product): ?>
        <div class="col-lg-3 col-md-4 col-sm-6 pb-1">
            <div class="product-item bg-light mb-4">
                <div class="product-img position-relative overflow-hidden">

                    <img class="img-fluid w-100"
                         src="img/<?php echo htmlspecialchars($product['hinh_anh']); ?>"
                         alt="<?php echo htmlspecialchars($product['ten_san_pham']); ?>">

                    <div class="product-action">
                        <a class="btn btn-outline-dark btn-square" href=""><i class="fa fa-shopping-cart"></i></a>
                        <a class="btn btn-outline-dark btn-square" href=""><i class="far fa-heart"></i></a>
                        <a class="btn btn-outline-dark btn-square" href=""><i class="fa fa-sync-alt"></i></a>
                        <a class="btn btn-outline-dark btn-square" href=""><i class="fa fa-search"></i></a>
                    </div>
                </div>

                <div class="text-center py-4">
                    <a class="h6 text-decoration-none text-truncate" href="">
                        <?php echo htmlspecialchars($product['ten_san_pham']); ?>
                    </a>

                    <div class="d-flex align-items-center justify-content-center mt-2">
                        <h5><?php echo number_format($product['gia'], 0, ',', '.'); ?>₫</h5>

                        <?php if($product['gia'] < 1000000): ?>
                            <h6 class="text-muted ml-2">
                                <del><?php echo number_format($product['gia']+100000,0,',','.'); ?>₫</del>
                            </h6>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex align-items-center justify-content-center mb-1">
                        <?php for($i=0; $i<5; $i++): ?>
                            <small class="fa fa-star text-primary mr-1"></small>
                        <?php endfor; ?>
                        <small>(<?php echo rand(10,200); ?>)</small>
                    </div>

                </div>
            </div>
        </div>
        <?php endforeach; ?>

    </div>
</div>

</body>
</html>
