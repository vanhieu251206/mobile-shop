<?php
// ================== KẾT NỐI DB ==================
// Nếu bạn đã có file config/db.php thì dùng:
// require_once __DIR__ . '/config/db.php';  // file này tạo $pdo

// Còn nếu chưa có, dùng tạm đoạn dưới và sửa lại user/pass:
$host = 'localhost';
$db   = 'shop_phone4';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (Exception $e) {
    die("Lỗi kết nối DB: " . $e->getMessage());
}

// ================== HÀM TIỆN ÍCH ==================
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
function money($v) {
    return number_format((float)$v, 0, ',', '.') . ' ₫';
}
function imgUrl($s) {
    if (!$s) return 'img/product-1.jpg';
    $s = trim($s);
    // nếu đã có folder (vd img/a.jpg) thì giữ nguyên
    if (strpos($s, '/') !== false) return $s;
    return 'img/' . ltrim($s, '/');
}

// ================== LẤY ID SẢN PHẨM ==================
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ================== QUERY SẢN PHẨM ==================
$stmt = $pdo->prepare("
    SELECT sp.*, dm.ten_danh_muc
    FROM san_pham sp
    LEFT JOIN danh_muc dm ON sp.danh_muc_id = dm.id
    WHERE sp.id = ?
");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
}

// ================== GALLERY ==================
$gallery = [];
if ($product) {
    if (!empty($product['gallery'])) {
        $gallery = array_filter(array_map('trim', explode(',', $product['gallery'])));
    }
    if (!$gallery) {
        $gallery = [$product['hinh_anh']];
    }
}

// ================== SẢN PHẨM LIÊN QUAN ==================
$related = [];
if ($product) {
    $stmt = $pdo->prepare("
        SELECT id, ten_san_pham, gia, hinh_anh, gallery, hang, danh_muc_id
        FROM san_pham
        WHERE id <> ?
          AND (danh_muc_id = ? OR hang = ?)
        ORDER BY ngay_tao DESC
        LIMIT 6
    ");
    $stmt->execute([$product['id'], $product['danh_muc_id'], $product['hang']]);
    $related = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>MobileShop - Chi tiết sản phẩm</title>
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <link href="img/favicon.ico" rel="icon">

  <!-- Fonts & Icons -->
  <link rel="preconnect" href="https://fonts.gstatic.com">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">

  <!-- Libraries -->
  <link href="lib/animate/animate.min.css" rel="stylesheet">
  <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

  <!-- Theme -->
  <link href="css/style.css" rel="stylesheet">
</head>
<body>

<?php if (!$product): ?>
  <div class="container py-5">
    <h3 class="text-danger">Không tìm thấy sản phẩm!</h3>
    <a href="shop.php" class="btn btn-primary mt-3">Quay lại cửa hàng</a>
  </div>
<?php else: ?>

  <!-- Topbar Start (giữ nguyên template) -->
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
            <button type="button" class="btn btn-sm btn-light dropdown-toggle" data-toggle="dropdown">Tài khoản</button>
            <div class="dropdown-menu dropdown-menu-right">
              <button class="dropdown-item" type="button">Đăng nhập</button>
              <button class="dropdown-item" type="button">Đăng ký</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row align-items-center bg-light py-3 px-xl-5 d-none d-lg-flex">
      <div class="col-lg-4">
        <a href="index.html" class="text-decoration-none">
          <span class="h1 text-uppercase text-primary bg-dark px-2">Mobile</span>
          <span class="h1 text-uppercase text-dark bg-primary px-2 ml-n1">Shop</span>
        </a>
      </div>
      <div class="col-lg-4 col-6 text-left">
        <form action="shop.php" method="get">
          <div class="input-group">
            <input type="text" name="kw" class="form-control" placeholder="Tìm kiếm sản phẩm">
            <div class="input-group-append">
              <button class="input-group-text bg-transparent text-primary">
                <i class="fa fa-search"></i>
              </button>
            </div>
          </div>
        </form>
      </div>
      <div class="col-lg-4 col-6 text-right">
        <p class="m-0">Chăm sóc khách hàng</p>
        <h5 class="m-0">1900 0000</h5>
      </div>
    </div>
  </div>
  <!-- Topbar End -->

  <!-- Navbar Start -->
  <div class="container-fluid bg-dark mb-30">
    <div class="row px-xl-5">
      <div class="col-lg-9">
        <nav class="navbar navbar-expand-lg bg-dark navbar-dark py-3 py-lg-0 px-0">
          <button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#navbarCollapse">
            <span class="navbar-toggler-icon"></span>
          </button>
          <div class="collapse navbar-collapse justify-content-between" id="navbarCollapse">
            <div class="navbar-nav mr-auto py-0">
              <a href="index.html" class="nav-item nav-link">Trang chủ</a>
              <a href="shop.php" class="nav-item nav-link">Cửa hàng</a>
              <a href="detail.php?id=<?= (int)$product['id'] ?>" class="nav-item nav-link active">Chi tiết</a>
              <a href="contact.html" class="nav-item nav-link">Liên hệ</a>
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
          <a class="breadcrumb-item text-dark" href="index.html">Trang chủ</a>
          <a class="breadcrumb-item text-dark" href="shop.php">Cửa hàng</a>
          <span class="breadcrumb-item active"><?= e($product['ten_san_pham']) ?></span>
        </nav>
      </div>
    </div>
  </div>
  <!-- Breadcrumb End -->

  <!-- Detail Start -->
  <div class="container-fluid pb-5">
    <div class="row px-xl-5">
      <!-- Carousel ảnh -->
      <div class="col-lg-5 mb-30">
        <div id="product-carousel" class="carousel slide" data-ride="carousel">
          <div class="carousel-inner bg-light">
            <?php foreach ($gallery as $i => $src): ?>
              <div class="carousel-item <?= $i===0 ? 'active' : '' ?>">
                <img class="w-100 h-100" src="<?= e(imgUrl($src)) ?>" alt="<?= e($product['ten_san_pham']) ?>">
              </div>
            <?php endforeach; ?>
          </div>
          <a class="carousel-control-prev" href="#product-carousel" data-slide="prev">
            <i class="fa fa-2x fa-angle-left text-dark"></i>
          </a>
          <a class="carousel-control-next" href="#product-carousel" data-slide="next">
            <i class="fa fa-2x fa-angle-right text-dark"></i>
          </a>
        </div>
      </div>

      <!-- Thông tin -->
      <div class="col-lg-7 h-auto mb-30">
        <div class="h-100 bg-light p-30">
          <h3><?= e($product['ten_san_pham']) ?></h3>

          <h3 class="font-weight-semi-bold mb-4"><?= money($product['gia']) ?></h3>
          <p class="mb-4"><?= e($product['mo_ta']) ?></p>

          <!-- Thông số kỹ thuật -->
          <div class="mb-4">
            <h5 class="text-uppercase mb-3">Thông số kỹ thuật</h5>
            <div class="row">
              <div class="col-sm-6">
                <p class="mb-2">Hãng:</p>
                <p class="mb-2">Chip xử lý:</p>
                <p class="mb-2">RAM:</p>
                <p class="mb-2">Bộ nhớ:</p>
              </div>
              <div class="col-sm-6">
                <p class="mb-2"><?= e($product['hang']) ?></p>
                <p class="mb-2"><?= e($product['chip']) ?></p>
                <p class="mb-2"><?= e($product['ram']) ?></p>
                <p class="mb-2"><?= e($product['bo_nho']) ?></p>
              </div>
              <div class="col-sm-6">
                <p class="mb-2">Màn hình:</p>
                <p class="mb-2">Pin:</p>
                <p class="mb-2">Camera:</p>
              </div>
              <div class="col-sm-6">
                <p class="mb-2"><?= e($product['man_hinh']) ?></p>
                <p class="mb-2"><?= e($product['pin']) ?></p>
                <p class="mb-2"><?= e($product['camera']) ?></p>
              </div>
            </div>
          </div>

          <div class="d-flex align-items-center mb-4 pt-2">
            <div class="input-group quantity mr-3" style="width:130px;">
              <div class="input-group-btn">
                <button class="btn btn-primary btn-minus"><i class="fa fa-minus"></i></button>
              </div>
              <input id="qty" type="text" class="form-control bg-secondary border-0 text-center" value="1">
              <div class="input-group-btn">
                <button class="btn btn-primary btn-plus"><i class="fa fa-plus"></i></button>
              </div>
            </div>
            <button class="btn btn-primary px-3"><i class="fa fa-shopping-cart mr-1"></i> Thêm vào giỏ</button>
          </div>

        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="row px-xl-5">
      <div class="col">
        <div class="bg-light p-30">
          <div class="nav nav-tabs mb-4">
            <a class="nav-item nav-link text-dark active" data-toggle="tab" href="#tab-pane-1">Mô tả</a>
            <a class="nav-item nav-link text-dark" data-toggle="tab" href="#tab-pane-2">Thông tin thêm</a>
          </div>
          <div class="tab-content">
            <div class="tab-pane fade show active" id="tab-pane-1">
              <h4 class="mb-3">Mô tả sản phẩm</h4>
              <p><?= nl2br(e($product['mo_ta'])) ?></p>
            </div>
            <div class="tab-pane fade" id="tab-pane-2">
              <h4 class="mb-3">Thông tin bổ sung</h4>
              <p>Hỗ trợ đổi trả trong 7 ngày nếu lỗi nhà sản xuất. Giao hàng nhanh nội thành.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Detail End -->

  <!-- Gợi ý sản phẩm -->
  <div class="container-fluid py-5">
    <h2 class="section-title position-relative text-uppercase mx-xl-5 mb-4">
      <span class="bg-secondary pr-3">Có thể bạn sẽ thích</span>
    </h2>
    <div class="row px-xl-5">
      <div class="col">
        <div class="owl-carousel related-carousel">
          <?php foreach ($related as $r):
              $thumb = $r['hinh_anh'];
              if (!empty($r['gallery'])) {
                  $tmp = array_filter(array_map('trim', explode(',', $r['gallery'])));
                  if (!empty($tmp)) $thumb = $tmp[0];
              }
          ?>
            <div class="product-item bg-light">
              <div class="product-img position-relative overflow-hidden">
                <img class="img-fluid w-100" src="<?= e(imgUrl($thumb)) ?>" alt="<?= e($r['ten_san_pham']) ?>">
                <div class="product-action">
                  <a class="btn btn-outline-dark btn-square" href="#"><i class="fa fa-shopping-cart"></i></a>
                  <a class="btn btn-outline-dark btn-square" href="detail.php?id=<?= (int)$r['id'] ?>"><i class="fa fa-search"></i></a>
                </div>
              </div>
              <div class="text-center py-4">
                <a class="h6 text-decoration-none text-truncate" href="detail.php?id=<?= (int)$r['id'] ?>">
                  <?= e($r['ten_san_pham']) ?>
                </a>
                <div class="d-flex align-items-center justify-content-center mt-2">
                  <h5><?= money($r['gia']) ?></h5>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer giữ nguyên template (bạn có thể paste lại footer cũ nếu muốn) -->
  <div class="container-fluid bg-dark text-secondary mt-5 pt-5">
    <div class="row px-xl-5 pt-5">
      <div class="col-lg-12 text-center pb-3">
        &copy; MobileShop
      </div>
    </div>
  </div>

<?php endif; ?>

  <!-- JS libs -->
  <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
  <script src="lib/easing/easing.min.js"></script>
  <script src="lib/owlcarousel/owl.carousel.min.js"></script>
  <script src="js/main.js"></script>

  <script>
    // Init related carousel
    $('.related-carousel').owlCarousel({
      autoplay: true,
      smartSpeed: 1000,
      margin: 25,
      dots: false,
      loop: true,
      nav : true,
      navText : [
          '<i class="fa fa-angle-left"></i>',
          '<i class="fa fa-angle-right"></i>'
      ],
      responsive: {0:{items:1},576:{items:2},768:{items:3},992:{items:4}}
    });

    // Nút +/- số lượng
    const qty = document.getElementById('qty');
    document.querySelector('.btn-plus')?.addEventListener('click', () => {
      qty.value = Math.max(1, (+qty.value||1)+1);
    });
    document.querySelector('.btn-minus')?.addEventListener('click', () => {
      qty.value = Math.max(1, (+qty.value||1)-1);
    });
  </script>
</body>
</html>
