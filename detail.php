<?php
require_once "db.php";
session_start();

function e($str){
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// render sao
function render_stars($n){
    $n = (int)$n;
    $html = '';
    for($i=1; $i<=5; $i++){
        $html .= $i <= $n
            ? '<i class="fas fa-star"></i>'
            : '<i class="far fa-star"></i>';
    }
    return $html;
}

// 1) Lấy id sản phẩm
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: shop.php"); exit; }

// 2) Lấy sản phẩm + danh mục
$stm = $pdo->prepare("
    SELECT sp.*, dm.ten_danh_muc
    FROM san_pham sp
    LEFT JOIN danh_muc dm ON dm.id = sp.danh_muc_id
    WHERE sp.id = ?
    LIMIT 1
");
$stm->execute([$id]);
$pro = $stm->fetch();
if(!$pro){ header("Location: shop.php"); exit; }

// 3) Ảnh: hỗ trợ nhiều ảnh "a.jpg,b.jpg"
$imgs = [];
if (!empty($pro['hinh_anh'])) {
    $tmp = array_map('trim', explode(',', $pro['hinh_anh']));
    foreach ($tmp as $img) if ($img !== '') $imgs[] = $img;
}
if (!$imgs) $imgs = ['no-image.png'];

// 4) Vì DB chưa có mo_ta / thong_so → tạo placeholder theo danh mục
$catName = trim($pro['ten_danh_muc'] ?? '');
$mo_ta = '';        // bạn có thể tự gán mô tả sau này nếu thêm cột
$thong_so = [];     // placeholder specs

if ($catName === 'Điện thoại') {
    $mo_ta = "Sản phẩm thuộc dòng điện thoại chính hãng. Hiệu năng ổn định, thiết kế hiện đại, phù hợp nhu cầu học tập – giải trí – làm việc.";
    $thong_so = [
        "Màn hình" => "Đang cập nhật",
        "Chip xử lý" => "Đang cập nhật",
        "RAM" => "Đang cập nhật",
        "Bộ nhớ trong" => "Đang cập nhật",
        "Camera" => "Đang cập nhật",
        "Pin / Sạc" => "Đang cập nhật",
        "Tính năng đặc biệt" => "Đang cập nhật",
        "Bảo hành" => "12 tháng"
    ];
} elseif ($catName === 'Máy tính bảng') {
    $mo_ta = "Máy tính bảng phù hợp học online, làm việc di động và giải trí. Màn hình lớn, pin tốt.";
    $thong_so = [
        "Màn hình" => "Đang cập nhật",
        "Chip xử lý" => "Đang cập nhật",
        "RAM" => "Đang cập nhật",
        "Bộ nhớ trong" => "Đang cập nhật",
        "Pin / Sạc" => "Đang cập nhật",
        "Tương thích bút/phụ kiện" => "Đang cập nhật",
        "Bảo hành" => "12 tháng"
    ];
} else { // Phụ kiện hoặc khác
    $mo_ta = "Phụ kiện chính hãng, bền bỉ, hỗ trợ tốt cho thiết bị của bạn.";
    $thong_so = [
        "Tương thích" => "Đang cập nhật",
        "Chất liệu / Công suất" => "Đang cập nhật",
        "Tính năng" => "Đang cập nhật",
        "Bảo hành" => "3 - 12 tháng (tùy sản phẩm)"
    ];
}

// specs nổi bật 4 cái đầu
$high_specs = [];
foreach ($thong_so as $k=>$v) {
    $high_specs[$k] = $v;
    if (count($high_specs) >= 4) break;
}

// 5) Liên quan cùng danh mục
$related = [];
if (!empty($pro['danh_muc_id'])) {
    $relStm = $pdo->prepare("
        SELECT * FROM san_pham
        WHERE danh_muc_id = ?
          AND id <> ?
        ORDER BY ngay_tao DESC
        LIMIT 10
    ");
    $relStm->execute([(int)$pro['danh_muc_id'], $id]);
    $related = $relStm->fetchAll();
}

$price   = number_format((float)$pro['gia'], 0, ',', '.') . "₫";
$ton_kho = (int)($pro['ton_kho'] ?? 0);
$da_ban  = (int)($pro['da_ban'] ?? 0);
$hang    = trim(strtok($pro['ten_san_pham'], ' ')); // hãng tạm từ chữ đầu

/* ============================
   6) REVIEWS
   - CHỈ hiển thị đánh giá đã DUYỆT
============================= */
$reviews_enabled = true;
$reviews = [];

try {
    $rvStm = $pdo->prepare("
        SELECT dg.*, nd.ten_dang_nhap
        FROM danh_gia dg
        LEFT JOIN nguoi_dung nd ON nd.id = dg.nguoi_dung_id
        WHERE dg.san_pham_id = ?
          AND dg.trang_thai = 'duyet'   -- CHỈ lấy đánh giá đã duyệt
        ORDER BY dg.id DESC
        LIMIT 20
    ");
    $rvStm->execute([$id]);
    $reviews = $rvStm->fetchAll();
} catch (PDOException $ex) {
    // nếu chưa có bảng danh_gia thì tắt chức năng review
    $reviews_enabled = false;
}

// review mẫu khi chưa có review thật hoặc chưa có review duyệt
$sample_reviews = [
    [
        'ten' => 'Nguyễn Minh Anh',
        'so_sao' => 5,
        'ngay' => '2 ngày trước',
        'nhan_xet' => 'Máy rất mượt, pin trâu. Đóng gói cẩn thận, giao nhanh.'
    ],
    [
        'ten' => 'Trần Quốc Bảo',
        'so_sao' => 4,
        'ngay' => '1 tuần trước',
        'nhan_xet' => 'Camera đẹp, màn hình sáng. Giá ổn trong tầm tiền.'
    ],
    [
        'ten' => 'Lê Hoàng Phúc',
        'so_sao' => 5,
        'ngay' => '3 tuần trước',
        'nhan_xet' => 'Chơi game ổn định, không nóng. Rất đáng mua.'
    ],
];

// tính avg rating (chỉ trên đánh giá đã duyệt)
$avg_rating = 0;
if (count($reviews) > 0) {
    $sum = 0;
    foreach($reviews as $rv) $sum += (int)$rv['so_sao'];
    $avg_rating = round($sum / count($reviews), 1);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title><?= e($pro['ten_san_pham']) ?> - MobileShop</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <link href="img/favicon.ico" rel="icon">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">  
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">

    <style>
        body{background:#f7f7f7;}
        .detail-wrap{background:#fff;border-radius:12px;padding:18px;}
        .thumbs{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;}
        .thumbs img{width:64px;height:64px;object-fit:cover;border:2px solid #eee;border-radius:8px;cursor:pointer;transition:.12s;}
        .thumbs img.active{border-color:#ffcc00;transform:translateY(-2px);}
        .badge-pill{border-radius:999px;padding:4px 10px;font-size:12px;font-weight:700;}
        .badge-brand{background:#fff;border:1px solid #eee;color:#333;}
        .badge-sold{background:#ffcc00;color:#000;}
        .badge-stock-in{background:#e8fff0;color:#1a7f37;}
        .badge-stock-out{background:#ffe8e8;color:#a12b2b;}

        .price-box{background:#fff7e0;border:1px dashed #ffcc00;border-radius:10px;padding:12px;}
        .price-main{font-size:26px;font-weight:800;color:#d0021b;}
        .policy-box{background:#f9fafb;border:1px solid #eee;border-radius:10px;padding:12px;font-size:14px;}
        .policy-box i{color:#28a745;margin-right:6px;}

        .spec-highlight{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;}
        .spec-card{background:#f9fafb;border:1px solid #eee;border-radius:10px;padding:10px;}
        .spec-card .k{font-weight:700;font-size:13px;color:#666;}
        .spec-card .v{font-weight:700;color:#111;margin-top:4px;}

        .spec-table li{display:flex;justify-content:space-between;border-bottom:1px dashed #eee;padding:8px 0;}
        .spec-table li span:first-child{font-weight:600;color:#333;max-width:45%;}
        .spec-table li span:last-child{color:#111;text-align:right;max-width:55%;}

        .related-grid .product-item{border-radius:12px;overflow:hidden;transition:.15s;}
        .related-grid .product-item:hover{transform:translateY(-3px);box-shadow:0 6px 20px rgba(0,0,0,.08)}
        .related-grid .product-img img{height:220px;object-fit:contain;}

        .review-card{background:#f9fafb;border:1px solid #eee;border-radius:10px;padding:12px;}
        .review-stars i{margin-right:2px;}

        @media(max-width:991px){
            .spec-highlight{grid-template-columns:1fr;}
        }
    </style>
</head>

<body>

    <!-- Breadcrumb -->
    <div class="container-fluid">
        <div class="row px-xl-5">
            <div class="col-12">
                <nav class="breadcrumb bg-light mb-3 mt-2">
                    <a class="breadcrumb-item text-dark" href="index.php">Trang chủ</a>
                    <a class="breadcrumb-item text-dark" href="shop.php">Cửa hàng</a>
                    <?php if(!empty($pro['ten_danh_muc'])): ?>
                        <a class="breadcrumb-item text-dark" href="shop.php?cat=<?= (int)$pro['danh_muc_id'] ?>">
                            <?= e($pro['ten_danh_muc']) ?>
                        </a>
                    <?php endif; ?>
                    <span class="breadcrumb-item active"><?= e($pro['ten_san_pham']) ?></span>
                </nav>
            </div>
        </div>
    </div>

    <!-- Detail main -->
    <div class="container-fluid pb-4">
        <div class="row px-xl-5">
            <!-- Left: gallery -->
            <div class="col-lg-5 mb-3">
                <div class="detail-wrap">
                    <div id="product-carousel" class="carousel slide" data-ride="carousel">
                        <div class="carousel-inner bg-white">
                            <?php foreach($imgs as $i=>$img): ?>
                                <div class="carousel-item <?= $i==0?'active':'' ?>">
                                    <img class="w-100" style="height:420px;object-fit:contain"
                                         src="img/<?= e($img) ?>" alt="<?= e($pro['ten_san_pham']) ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if(count($imgs)>1): ?>
                        <a class="carousel-control-prev" href="#product-carousel" data-slide="prev">
                            <i class="fa fa-2x fa-angle-left text-dark"></i>
                        </a>
                        <a class="carousel-control-next" href="#product-carousel" data-slide="next">
                            <i class="fa fa-2x fa-angle-right text-dark"></i>
                        </a>
                        <?php endif; ?>
                    </div>

                    <?php if(count($imgs)>1): ?>
                    <div class="thumbs">
                        <?php foreach($imgs as $i=>$img): ?>
                            <img data-slide="<?= $i ?>" class="<?= $i==0?'active':'' ?>"
                                 src="img/<?= e($img) ?>" alt="">
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right: info -->
            <div class="col-lg-7 mb-3">
                <div class="detail-wrap h-100">
                    <div class="d-flex align-items-center flex-wrap mb-2" style="gap:6px;">
                        <span class="badge-pill badge-brand"><?= e($hang) ?></span>
                        <span class="badge-pill badge-sold">Đã bán <?= $da_ban ?></span>
                        <?php if($ton_kho>0): ?>
                            <span class="badge-pill badge-stock-in">Còn hàng</span>
                        <?php else: ?>
                            <span class="badge-pill badge-stock-out">Hết hàng</span>
                        <?php endif; ?>
                    </div>

                    <h3 class="mb-2"><?= e($pro['ten_san_pham']) ?></h3>

                    <div class="d-flex align-items-center mb-3">
                        <div class="text-primary mr-2">
                            <?= render_stars($avg_rating ?: 5) ?>
                        </div>
                        <small class="text-muted">
                            <?= $avg_rating ? "$avg_rating/5" : "Chưa có đánh giá" ?>
                            • <?= count($reviews) ?> đánh giá
                        </small>
                    </div>

                    <div class="price-box mb-3">
                        <div class="price-main"><?= $price ?></div>
                        <div class="text-muted" style="font-size:13px;">
                            Giá đã gồm VAT • Miễn phí giao hàng nội thành
                        </div>
                    </div>

                    <?php if($high_specs): ?>
                        <div class="spec-highlight mb-3">
                            <?php foreach($high_specs as $k=>$v): ?>
                                <div class="spec-card">
                                    <div class="k"><?= e($k) ?></div>
                                    <div class="v"><?= e($v) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <p class="mb-3"><?= nl2br(e($mo_ta)) ?></p>

                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <div class="policy-box">
                                <div class="mb-2"><strong>Ưu đãi khi mua</strong></div>
                                <div><i class="fa fa-check-circle"></i>Giảm 5% khi thanh toán online</div>
                                <div><i class="fa fa-check-circle"></i>Tặng dán cường lực + ốp lưng</div>
                                <div><i class="fa fa-check-circle"></i>Trả góp 0% qua thẻ</div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <div class="policy-box">
                                <div class="mb-2"><strong>Chính sách</strong></div>
                                <div><i class="fa fa-check-circle"></i>Bảo hành chính hãng</div>
                                <div><i class="fa fa-check-circle"></i>1 đổi 1 trong 7 ngày nếu lỗi</div>
                                <div><i class="fa fa-check-circle"></i>Giao nhanh nội thành</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center mt-3" style="gap:10px;">
                        <form action="add_to_cart.php" method="get" class="d-flex align-items-center" style="gap:8px;">
                            <input type="hidden" name="id" value="<?= (int)$pro['id'] ?>">
                            <div class="input-group quantity" style="width: 140px;">
                                <div class="input-group-btn">
                                    <button class="btn btn-primary btn-minus" type="button"><i class="fa fa-minus"></i></button>
                                </div>
                                <input type="text" name="qty" class="form-control bg-secondary border-0 text-center" value="1">
                                <div class="input-group-btn">
                                    <button class="btn btn-primary btn-plus" type="button"><i class="fa fa-plus"></i></button>
                                </div>
                            </div>
                            <button class="btn btn-outline-dark px-3" <?= $ton_kho<=0?'disabled':'' ?>>
                                <i class="fa fa-shopping-cart mr-1"></i> Thêm vào giỏ
                            </button>
                        </form>

                        <a class="btn btn-primary px-4" href="add_to_cart.php?id=<?= (int)$pro['id'] ?>&qty=1"
                           <?= $ton_kho<=0?'style="pointer-events:none;opacity:.6"':'' ?>>
                            Mua ngay
                        </a>
                    </div>

                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="row px-xl-5 mt-2">
            <div class="col">
                <div class="detail-wrap">
                    <div class="nav nav-tabs mb-3">
                        <a class="nav-item nav-link text-dark active" data-toggle="tab" href="#tab-d1">Mô tả</a>
                        <a class="nav-item nav-link text-dark" data-toggle="tab" href="#tab-d2">Thông số kỹ thuật</a>
                        <a class="nav-item nav-link text-dark" data-toggle="tab" href="#tab-d3">Đánh giá</a>
                    </div>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="tab-d1">
                            <h5 class="mb-3">Mô tả chi tiết</h5>
                            <p><?= nl2br(e($mo_ta)) ?></p>
                        </div>

                        <div class="tab-pane fade" id="tab-d2">
                            <h5 class="mb-3">Thông số kỹ thuật</h5>
                            <?php if($thong_so): ?>
                                <ul class="list-unstyled spec-table">
                                    <?php foreach($thong_so as $k=>$v): ?>
                                        <li><span><?= e($k) ?></span><span><?= e($v) ?></span></li>
                                    <?php endforeach; ?>
                                </ul>
                                <small class="text-muted">*Thông số sẽ được cập nhật khi admin bổ sung dữ liệu.</small>
                            <?php else: ?>
                                <p>Thông số kỹ thuật đang cập nhật…</p>
                            <?php endif; ?>
                        </div>

                        <div class="tab-pane fade" id="tab-d3">
                            <div class="row">
                                <div class="col-md-7">
                                    <h5 class="mb-3">
                                        Đánh giá (<?= count($reviews) ? count($reviews) : count($sample_reviews) ?>)
                                    </h5>

                                    <?php if(count($reviews) > 0): ?>
                                        <?php foreach($reviews as $rv): ?>
                                            <div class="review-card mb-3">
                                                <div class="media">
                                                    <img src="img/user.jpg" alt="user" class="img-fluid mr-3 mt-1" style="width:45px;height:45px;">
                                                    <div class="media-body">
                                                        <h6 class="mb-1">
                                                            <?= e($rv['ten_dang_nhap'] ?? ('Khách hàng #'.(int)$rv['nguoi_dung_id'])) ?>
                                                            <small class="text-muted"> - Gần đây</small>
                                                        </h6>
                                                        <div class="text-primary review-stars mb-1">
                                                            <?= render_stars($rv['so_sao']) ?>
                                                        </div>
                                                        <p class="mb-0"><?= e($rv['nhan_xet']) ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <?php foreach($sample_reviews as $rv): ?>
                                            <div class="review-card mb-3">
                                                <div class="media">
                                                    <img src="img/user.jpg" alt="user" class="img-fluid mr-3 mt-1" style="width:45px;height:45px;">
                                                    <div class="media-body">
                                                        <h6 class="mb-1">
                                                            <?= e($rv['ten']) ?>
                                                            <small class="text-muted"> - <?= e($rv['ngay']) ?></small>
                                                        </h6>
                                                        <div class="text-primary review-stars mb-1">
                                                            <?= render_stars($rv['so_sao']) ?>
                                                        </div>
                                                        <p class="mb-0"><?= e($rv['nhan_xet']) ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        <small class="text-muted">*Đánh giá mẫu hiển thị khi chưa có đánh giá được duyệt.</small>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-5">
                                    <h5 class="mb-3">Gửi đánh giá</h5>

                                    <?php if(!$reviews_enabled): ?>
                                        <div class="alert alert-warning">
                                            Chức năng đánh giá chưa bật (DB chưa có bảng <b>danh_gia</b>).
                                        </div>
                                    <?php else: ?>
                                        <?php if(!empty($_SESSION['flash_msg'])): ?>
                                            <div class="alert alert-<?= e($_SESSION['flash_type']) ?>">
                                                <?= e($_SESSION['flash_msg']) ?>
                                            </div>
                                            <?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']); ?>
                                        <?php endif; ?>

                                        <form action="review_add.php" method="post">
                                            <input type="hidden" name="san_pham_id" value="<?= (int)$pro['id'] ?>">

                                            <div class="form-group">
                                                <label>Số sao *</label>
                                                <select name="so_sao" class="form-control" required>
                                                    <option value="5">★★★★★ - Rất tốt</option>
                                                    <option value="4">★★★★☆ - Tốt</option>
                                                    <option value="3">★★★☆☆ - Bình thường</option>
                                                    <option value="2">★★☆☆☆ - Tệ</option>
                                                    <option value="1">★☆☆☆☆ - Rất tệ</option>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label>Nhận xét *</label>
                                                <textarea name="nhan_xet" rows="4" class="form-control" required
                                                          placeholder="Chia sẻ cảm nhận của bạn..."></textarea>
                                            </div>

                                            <button class="btn btn-primary btn-block">
                                                Gửi đánh giá
                                            </button>
                                        </form>

                                        <small class="text-muted d-block mt-2">
                                            *Chỉ đánh giá sau khi mua hàng. Đánh giá sẽ được hiển thị sau khi admin duyệt.
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Related products -->
    <?php if($related): ?>
    <div class="container-fluid py-4">
        <h4 class="section-title position-relative text-uppercase mx-xl-5 mb-3">
            <span class="bg-secondary pr-3">Sản phẩm liên quan</span>
        </h4>
        <div class="row px-xl-5 related-grid">
            <?php foreach($related as $r): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 pb-3">
                    <div class="product-item bg-light">
                        <div class="product-img position-relative overflow-hidden">
                            <img class="img-fluid w-100" src="img/<?= e($r['hinh_anh']) ?>" alt="">
                            <div class="product-action">
                                <a class="btn btn-outline-dark btn-square" href="add_to_cart.php?id=<?= (int)$r['id'] ?>"><i class="fa fa-shopping-cart"></i></a>
                                <a class="btn btn-outline-dark btn-square" href="detail.php?id=<?= (int)$r['id'] ?>"><i class="fa fa-search"></i></a>
                            </div>
                        </div>
                        <div class="text-center py-3 px-2">
                            <a class="h6 text-decoration-none text-truncate d-block" href="detail.php?id=<?= (int)$r['id'] ?>">
                                <?= e($r['ten_san_pham']) ?>
                            </a>
                            <div class="d-flex align-items-center justify-content-center mt-2">
                                <h5><?= number_format((float)$r['gia'], 0, ',', '.') ?>₫</h5>
                            </div>
                            <small class="text-muted">Đã bán <?= (int)($r['da_ban']??0) ?></small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="js/main.js"></script>

    <script>
        // +/- quantity
        $(document).on('click', '.btn-plus', function () {
            var input = $(this).closest('.quantity').find('input');
            input.val(parseInt(input.val()||1) + 1);
        });
        $(document).on('click', '.btn-minus', function () {
            var input = $(this).closest('.quantity').find('input');
            var val = parseInt(input.val()||1) - 1;
            input.val(val < 1 ? 1 : val);
        });

        // thumbnails click -> go to slide
        document.querySelectorAll('.thumbs img').forEach(img=>{
            img.addEventListener('click', ()=>{
                let idx = parseInt(img.dataset.slide);
                $('#product-carousel').carousel(idx);
                document.querySelectorAll('.thumbs img').forEach(i=>i.classList.remove('active'));
                img.classList.add('active');
            });
        });

        // when slide changes, sync active thumb
        $('#product-carousel').on('slid.bs.carousel', function (e) {
            let idx = e.to;
            document.querySelectorAll('.thumbs img').forEach(i=>i.classList.remove('active'));
            let active = document.querySelector('.thumbs img[data-slide="'+idx+'"]');
            if(active) active.classList.add('active');
        });

        // auto open tab by hash (vd redirect về #tab-d3)
        document.addEventListener("DOMContentLoaded", function(){
            if(location.hash){
                const tabLink = document.querySelector('.nav-tabs a[href="'+location.hash+'"]');
                if(tabLink) $(tabLink).tab('show');
            }
        });
    </script>
</body>
</html>
