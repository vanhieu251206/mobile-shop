<?php
// =====================
// 1) KẾT NỐI DATABASE
// =====================
$host = "localhost";
$db   = "shop_phone4";
$user = "root";
$pass = ""; // XAMPP mặc định trống
$charset = "utf8mb4";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) {
    die("Lỗi kết nối DB: " . $e->getMessage());
}

// =====================
// 2) NHẬN THAM SỐ LỌC GET
// =====================
$q     = trim($_GET['q'] ?? "");
$brand = trim($_GET['brand'] ?? "");
$price = trim($_GET['price'] ?? "");
$chip  = trim($_GET['chip'] ?? "");
$ram   = trim($_GET['ram'] ?? "");
$sort  = trim($_GET['sort'] ?? "latest");

$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 9; // số sản phẩm mỗi trang
$offset = ($page - 1) * $limit;

// =====================
// 3) XỬ LÝ KHOẢNG GIÁ
// =====================
$minPrice = 0;
$maxPrice = PHP_INT_MAX;
if ($price !== "") {
    $tmp = explode("-", $price);
    if (count($tmp) == 2) {
        $minPrice = (int)$tmp[0];
        $maxPrice = (int)$tmp[1];
    }
}

// =====================
// 4) BUILD SQL LỌC
// =====================
$where = [];
$params = [];

if ($q !== "") {
    $where[] = "sp.ten_san_pham LIKE :q";
    $params[':q'] = "%$q%";
}
if ($brand !== "") {
    $where[] = "sp.hang = :brand";
    $params[':brand'] = $brand;
}
$where[] = "sp.gia BETWEEN :minPrice AND :maxPrice";
$params[':minPrice'] = $minPrice;
$params[':maxPrice'] = $maxPrice;

if ($chip !== "") {
    $where[] = "sp.chip = :chip";
    $params[':chip'] = $chip;
}
if ($ram !== "") {
    $where[] = "sp.ram = :ram";
    $params[':ram'] = $ram;
}

$whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

// =====================
// 5) SORT
// =====================
$orderSql = "ORDER BY sp.ngay_tao DESC";
if ($sort === "price-asc")  $orderSql = "ORDER BY sp.gia ASC";
if ($sort === "price-desc") $orderSql = "ORDER BY sp.gia DESC";

// =====================
// 6) LẤY TỔNG SẢN PHẨM (để phân trang)
// =====================
$sqlCount = "SELECT COUNT(*) FROM san_pham sp $whereSql";
$stmtCount = $pdo->prepare($sqlCount);
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();
$totalPages = max(1, ceil($total / $limit));

// =====================
// 7) LẤY DATA SẢN PHẨM
// =====================
$sql = "
    SELECT sp.*
    FROM san_pham sp
    $whereSql
    $orderSql
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
$stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

// =====================
// 8) LẤY DISTINCT HÃNG/CHIP/RAM CHO DROPDOWN
// =====================
$brands = $pdo->query("SELECT DISTINCT hang FROM san_pham WHERE hang IS NOT NULL AND hang<>'' ORDER BY hang")->fetchAll();
$chips  = $pdo->query("SELECT DISTINCT chip FROM san_pham WHERE chip IS NOT NULL AND chip<>'' ORDER BY chip")->fetchAll();
$rams   = $pdo->query("SELECT DISTINCT ram FROM san_pham WHERE ram IS NOT NULL AND ram<>'' ORDER BY ram")->fetchAll();

function money($v) {
    return number_format($v, 0, ",", ".") . " ₫";
}
function h($s) { return htmlspecialchars($s ?? "", ENT_QUOTES, "UTF-8"); }

// Dùng lại query string khi phân trang
function buildQuery($extra = []) {
    $q = array_merge($_GET, $extra);
    return http_build_query($q);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>MobileShop - Cửa hàng điện thoại</title>
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <link href="img/favicon.ico" rel="icon">
  <link rel="preconnect" href="https://fonts.gstatic.com">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
  <link href="lib/animate/animate.min.css" rel="stylesheet">
  <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
  <link href="css/style.css" rel="stylesheet">
</head>

<body>
  <!-- Topbar Start -->
  <div class="container-fluid">
    <div class="row bg-secondary py-1 px-xl-5">
      <div class="col-lg-6 d-none d-lg-block">
        <div class="d-inline-flex align-items-center h-100">
          <a class="text-body mr-3" href="#">Giới thiệu</a>
          <a class="text-body mr-3" href="contact.html">Liên hệ</a>
          <a class="text-body mr-3" href="#">Trợ giúp</a>
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
        <div class="d-inline-flex align-items-center d-block d-lg-none">
          <a href="#" class="btn px-0 ml-2">
            <i class="fas fa-heart text-dark"></i>
            <span class="badge text-dark border border-dark rounded-circle" style="padding-bottom:2px;">0</span>
          </a>
          <a href="cart.html" class="btn px-0 ml-2">
            <i class="fas fa-shopping-cart text-dark"></i>
            <span class="badge text-dark border border-dark rounded-circle" style="padding-bottom:2px;">0</span>
          </a>
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

      <!-- SEARCH FORM (GET) -->
      <div class="col-lg-4 col-6 text-left">
        <form method="get">
          <div class="input-group">
            <input name="q" value="<?=h($q)?>" type="text" class="form-control" placeholder="Tìm theo tên điện thoại...">
            <div class="input-group-append">
              <button class="input-group-text bg-transparent text-primary">
                <i class="fa fa-search"></i>
              </button>
            </div>
          </div>
        </form>
      </div>

      <div class="col-lg-4 col-6 text-right">
        <p class="m-0">Hỗ trợ khách hàng</p>
        <h5 class="m-0">(+84) 0123 456 789</h5>
      </div>
    </div>
  </div>
  <!-- Topbar End -->

  <!-- Navbar Start -->
  <div class="container-fluid bg-dark mb-30">
    <div class="row px-xl-5">
      <div class="col-lg-3 d-none d-lg-block">
        <a class="btn d-flex align-items-center justify-content-between bg-primary w-100"
           data-toggle="collapse" href="#navbar-vertical" style="height:65px;padding:0 30px;">
          <h6 class="text-dark m-0"><i class="fa fa-bars mr-2"></i>Danh mục</h6>
          <i class="fa fa-angle-down text-dark"></i>
        </a>
        <nav class="collapse position-absolute navbar navbar-vertical navbar-light align-items-start p-0 bg-light"
             id="navbar-vertical" style="width:calc(100% - 30px); z-index:999;">
          <div class="navbar-nav w-100">
            <a href="#" class="nav-item nav-link">iPhone</a>
            <a href="#" class="nav-item nav-link">Samsung</a>
            <a href="#" class="nav-item nav-link">Xiaomi</a>
            <a href="#" class="nav-item nav-link">OPPO</a>
            <a href="#" class="nav-item nav-link">Vivo</a>
            <a href="#" class="nav-item nav-link">Phụ kiện</a>
          </div>
        </nav>
      </div>
      <div class="col-lg-9">
        <nav class="navbar navbar-expand-lg bg-dark navbar-dark py-3 py-lg-0 px-0">
          <a href="index.html" class="text-decoration-none d-block d-lg-none">
            <span class="h1 text-uppercase text-dark bg-light px-2">Mobile</span>
            <span class="h1 text-uppercase text-light bg-primary px-2 ml-n1">Shop</span>
          </a>
          <button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#navbarCollapse">
            <span class="navbar-toggler-icon"></span>
          </button>
          <div class="collapse navbar-collapse justify-content-between" id="navbarCollapse">
            <div class="navbar-nav mr-auto py-0">
              <a href="index.html" class="nav-item nav-link">Trang chủ</a>
              <a href="shop.php" class="nav-item nav-link active">Cửa hàng</a>
              <a href="detail.php" class="nav-item nav-link">Chi tiết</a>
              <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">Trang khác <i class="fa fa-angle-down mt-1"></i></a>
                <div class="dropdown-menu bg-primary rounded-0 border-0 m-0">
                  <a href="cart.html" class="dropdown-item">Giỏ hàng</a>
                  <a href="checkout.html" class="dropdown-item">Thanh toán</a>
                </div>
              </div>
              <a href="contact.html" class="nav-item nav-link">Liên hệ</a>
            </div>
            <div class="navbar-nav ml-auto py-0 d-none d-lg-block">
              <a href="#" class="btn px-0">
                <i class="fas fa-heart text-primary"></i>
                <span class="badge text-secondary border border-secondary rounded-circle" style="padding-bottom:2px;">0</span>
              </a>
              <a href="cart.html" class="btn px-0 ml-3">
                <i class="fas fa-shopping-cart text-primary"></i>
                <span class="badge text-secondary border border-secondary rounded-circle" style="padding-bottom:2px;">0</span>
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
          <a class="breadcrumb-item text-dark" href="index.html">Trang chủ</a>
          <a class="breadcrumb-item text-dark" href="shop.php">Cửa hàng</a>
          <span class="breadcrumb-item active">Danh sách sản phẩm</span>
        </nav>
      </div>
    </div>
  </div>
  <!-- Breadcrumb End -->

  <!-- Shop Start -->
  <div class="container-fluid">
    <div class="row px-xl-5">

      <!-- Sidebar Start -->
      <div class="col-lg-3 col-md-4">
        <form method="get" class="mb-3">

          <!-- Lọc hãng -->
          <h5 class="section-title position-relative text-uppercase mb-3">
            <span class="bg-secondary pr-3">Lọc theo hãng</span>
          </h5>
          <div class="bg-light p-4 mb-30">
            <select name="brand" class="custom-select">
              <option value="">Tất cả hãng</option>
              <?php foreach ($brands as $b): ?>
                <option value="<?=h($b['hang'])?>" <?=($brand===$b['hang'])?'selected':''?>>
                  <?=h($b['hang'])?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Lọc giá -->
          <h5 class="section-title position-relative text-uppercase mb-3">
            <span class="bg-secondary pr-3">Lọc theo giá</span>
          </h5>
          <div class="bg-light p-4 mb-30">
            <select name="price" class="custom-select">
              <option value="">Tất cả mức giá</option>
              <option value="0-5000000"        <?=$price==="0-5000000"?"selected":""?>>Dưới 5 triệu</option>
              <option value="5000000-10000000" <?=$price==="5000000-10000000"?"selected":""?>>5–10 triệu</option>
              <option value="10000000-20000000"<?=$price==="10000000-20000000"?"selected":""?>>10–20 triệu</option>
              <option value="20000000-100000000"<?=$price==="20000000-100000000"?"selected":""?>>Trên 20 triệu</option>
            </select>
          </div>

          <!-- Lọc cấu hình -->
          <h5 class="section-title position-relative text-uppercase mb-3">
            <span class="bg-secondary pr-3">Lọc theo cấu hình</span>
          </h5>
          <div class="bg-light p-4 mb-30">
            <div class="form-group mb-3">
              <label class="mb-1">Chip xử lý</label>
              <select name="chip" class="custom-select">
                <option value="">Tất cả</option>
                <?php foreach ($chips as $c): ?>
                  <option value="<?=h($c['chip'])?>" <?=($chip===$c['chip'])?'selected':''?>>
                    <?=h($c['chip'])?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group mb-0">
              <label class="mb-1">RAM</label>
              <select name="ram" class="custom-select">
                <option value="">Tất cả</option>
                <?php foreach ($rams as $r): ?>
                  <option value="<?=h($r['ram'])?>" <?=($ram===$r['ram'])?'selected':''?>>
                    <?=h($r['ram'])?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <!-- Giữ keyword khi lọc -->
          <input type="hidden" name="q" value="<?=h($q)?>">
          <input type="hidden" name="sort" value="<?=h($sort)?>">

          <div class="bg-light p-4 mb-30">
            <button class="btn btn-primary btn-block mb-2" type="submit">Áp dụng bộ lọc</button>
            <a href="shop.php" class="btn btn-outline-secondary btn-block">Xoá bộ lọc</a>
          </div>
        </form>
      </div>
      <!-- Sidebar End -->

      <!-- Product List Start -->
      <div class="col-lg-9 col-md-8">
        <div class="row pb-3">
          <div class="col-12 pb-1">
            <div class="d-flex align-items-center justify-content-between mb-4">
              <div class="text-muted small">
                <span>Đang hiển thị <?=count($products)?> / <?=$total?> sản phẩm</span>
              </div>

              <!-- SORT -->
              <div class="ml-2">
                <div class="btn-group">
                  <button type="button" class="btn btn-sm btn-light dropdown-toggle" data-toggle="dropdown">Sắp xếp</button>
                  <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="shop.php?<?=buildQuery(['sort'=>'latest','page'=>1])?>">Mới nhất</a>
                    <a class="dropdown-item" href="shop.php?<?=buildQuery(['sort'=>'price-asc','page'=>1])?>">Giá tăng dần</a>
                    <a class="dropdown-item" href="shop.php?<?=buildQuery(['sort'=>'price-desc','page'=>1])?>">Giá giảm dần</a>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Render sản phẩm -->
          <?php if (!$products): ?>
            <div class="col-12">
              <div class="alert alert-warning">Không tìm thấy sản phẩm phù hợp.</div>
            </div>
          <?php endif; ?>

          <?php foreach ($products as $p): ?>
            <div class="col-lg-4 col-md-6 col-sm-6 pb-1">
              <div class="product-item bg-light mb-4">
                <div class="product-img position-relative overflow-hidden">
                  <img class="img-fluid w-100" 
                       src="img/<?=h($p['hinh_anh'] ?: 'product-1.jpg')?>"
                       alt="<?=h($p['ten_san_pham'])?>">
                  <div class="product-action">
                    <a class="btn btn-outline-dark btn-square" href="#"><i class="fa fa-shopping-cart"></i></a>
                    <a class="btn btn-outline-dark btn-square" href="#"><i class="far fa-heart"></i></a>
                    <a class="btn btn-outline-dark btn-square" href="#"><i class="fa fa-sync-alt"></i></a>
                    <a class="btn btn-outline-dark btn-square" href="detail.php?id=<?=$p['id']?>"><i class="fa fa-search"></i></a>
                  </div>
                </div>
                <div class="text-center py-4">
                  <a class="h6 text-decoration-none text-truncate" href="detail.php?id=<?=$p['id']?>">
                    <?=h($p['ten_san_pham'])?>
                  </a>
                  <div class="d-flex align-items-center justify-content-center mt-2">
                    <h5><?=money($p['gia'])?></h5>
                  </div>
                  <div class="d-flex align-items-center justify-content-center mb-1">
                    <small class="text-muted mr-2"><?=h($p['hang'] ?? '')?></small>
                    <?php if (!empty($p['chip'])): ?>
                      <small class="text-muted mr-2">• <?=h($p['chip'])?></small>
                    <?php endif; ?>
                    <?php if (!empty($p['ram'])): ?>
                      <small class="text-muted">• <?=h($p['ram'])?></small>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>

          <!-- Pagination -->
          <?php if ($totalPages > 1): ?>
          <div class="col-12 mt-3">
            <nav>
              <ul class="pagination justify-content-center">
                <li class="page-item <?=($page<=1?'disabled':'')?>">
                  <a class="page-link" href="shop.php?<?=buildQuery(['page'=>$page-1])?>">Trước</a>
                </li>

                <?php for($i=1;$i<=$totalPages;$i++): ?>
                  <li class="page-item <?=($i==$page?'active':'')?>"><a class="page-link" href="shop.php?<?=buildQuery(['page'=>$i])?>"><?=$i?></a></li>
                <?php endfor; ?>

                <li class="page-item <?=($page>=$totalPages?'disabled':'')?>">
                  <a class="page-link" href="shop.php?<?=buildQuery(['page'=>$page+1])?>">Sau</a>
                </li>
              </ul>
            </nav>
          </div>
          <?php endif; ?>

        </div>
      </div>
      <!-- Product List End -->

    </div>
  </div>
  <!-- Shop End -->

  <!-- Footer, BackToTop, JS libs giữ nguyên -->
  <div class="container-fluid bg-dark text-secondary mt-5 pt-5">
    <div class="row px-xl-5 pt-5">
      <div class="col-lg-4 col-md-12 mb-5 pr-3 pr-xl-5">
        <h5 class="text-secondary text-uppercase mb-4">Kết nối</h5>
        <p class="mb-4">MobileShop - Mua sắm điện thoại chính hãng, giá tốt.</p>
        <p class="mb-2"><i class="fa fa-map-marker-alt text-primary mr-3"></i>123 Đường ABC, Q.1, TP.HCM</p>
        <p class="mb-2"><i class="fa fa-envelope text-primary mr-3"></i>support@mobileshop.vn</p>
        <p class="mb-0"><i class="fa fa-phone-alt text-primary mr-3"></i>(+84) 0123 456 789</p>
      </div>
      <div class="col-lg-8 col-md-12">
        <div class="row">
          <div class="col-md-4 mb-5">
            <h5 class="text-secondary text-uppercase mb-4">Liên kết nhanh</h5>
            <div class="d-flex flex-column justify-content-start">
              <a class="text-secondary mb-2" href="index.html"><i class="fa fa-angle-right mr-2"></i>Trang chủ</a>
              <a class="text-secondary mb-2" href="shop.php"><i class="fa fa-angle-right mr-2"></i>Cửa hàng</a>
              <a class="text-secondary mb-2" href="detail.php"><i class="fa fa-angle-right mr-2"></i>Chi tiết</a>
              <a class="text-secondary mb-2" href="cart.html"><i class="fa fa-angle-right mr-2"></i>Giỏ hàng</a>
              <a class="text-secondary mb-2" href="checkout.html"><i class="fa fa-angle-right mr-2"></i>Thanh toán</a>
              <a class="text-secondary" href="contact.html"><i class="fa fa-angle-right mr-2"></i>Liên hệ</a>
            </div>
          </div>
          <div class="col-md-4 mb-5">
            <h5 class="text-secondary text-uppercase mb-4">Tài khoản</h5>
            <div class="d-flex flex-column justify-content-start">
              <a class="text-secondary mb-2" href="#"><i class="fa fa-angle-right mr-2"></i>Đăng nhập</a>
              <a class="text-secondary mb-2" href="#"><i class="fa fa-angle-right mr-2"></i>Đăng ký</a>
              <a class="text-secondary mb-2" href="#"><i class="fa fa-angle-right mr-2"></i>Theo dõi đơn hàng</a>
              <a class="text-secondary" href="#"><i class="fa fa-angle-right mr-2"></i>Hỗ trợ</a>
            </div>
          </div>
          <div class="col-md-4 mb-5">
            <h5 class="text-secondary text-uppercase mb-4">Bản tin</h5>
            <p>Nhận thông tin khuyến mãi mới nhất mỗi tuần.</p>
            <form onsubmit="return false;">
              <div class="input-group">
                <input type="text" class="form-control" placeholder="Email của bạn">
                <div class="input-group-append">
                  <button class="btn btn-primary">Đăng ký</button>
                </div>
              </div>
            </form>
            <h6 class="text-secondary text-uppercase mt-4 mb-3">Theo dõi chúng tôi</h6>
            <div class="d-flex">
              <a class="btn btn-primary btn-square mr-2" href="#"><i class="fab fa-facebook-f"></i></a>
              <a class="btn btn-primary btn-square mr-2" href="#"><i class="fab fa-instagram"></i></a>
              <a class="btn btn-primary btn-square" href="#"><i class="fab fa-youtube"></i></a>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="row border-top mx-xl-5 py-4" style="border-color: rgba(256,256,256,.1)!important;">
      <div class="col-md-6 px-xl-0">
        <p class="mb-md-0 text-center text-md-left text-secondary">
          &copy; <a class="text-primary" href="#">MobileShop</a>. Thiết kế bởi HTML Codex.
        </p>
      </div>
      <div class="col-md-6 px-xl-0 text-center text-md-right">
        <img class="img-fluid" src="img/payments.png" alt="">
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
</body>
</html>
