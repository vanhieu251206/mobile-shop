<?php
require_once "db.php";
session_start();

/* ============================
   Utils
============================= */
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
function qs(array $base, array $override = []) {
    return http_build_query(array_merge($base, $override));
}

/**
 * Chuẩn hoá tên danh mục để UI giống shop điện thoại
 * (chỉ đổi hiển thị, không ảnh hưởng DB)
 */
function normalizeCategoryLabel($name) {
    $n = mb_strtolower(trim($name), 'UTF-8');
    return match(true) {
        in_array($n, ['ốp','op','case','bao'], true) => 'Ốp điện thoại',
        in_array($n, ['tai','tai nghe','earphone','headphone','airpods'], true) => 'Tai nghe',
        in_array($n, ['sạc','sac','charger','adapter'], true) => 'Sạc điện thoại',
        in_array($n, ['cáp','cap','cable','day','dây'], true) => 'Cáp sạc',
        default => $name
    };
}

/**
 * Lấy hãng từ ten_san_pham, nhưng loại bỏ phụ kiện
 */
function brandFromName($name, $notBrands) {
    $token = trim(strtok($name ?? '', ' '));
    if ($token === '') return null;

    $lower = mb_strtolower($token, 'UTF-8');
    if (in_array($lower, $notBrands, true)) return null;

    return $token;
}

/* ============================
   1) Danh mục
============================= */
$danh_mucs = $pdo->query("SELECT * FROM danh_muc ORDER BY id ASC")->fetchAll();

// map id -> label (để dùng lại cho card)
$catMap = [];
foreach ($danh_mucs as $dm) {
    $catMap[(int)$dm['id']] = normalizeCategoryLabel($dm['ten_danh_muc']);
}

/* ============================
   2) Hãng (tạm từ tên_san_pham)
   - lọc mấy từ phụ kiện để không lẫn vào hãng
============================= */
$NOT_BRANDS = [
    'ốp','op','case','bao','tai','tai nghe','airpods',
    'sạc','sac','charger','cáp','cap','cable','dây','day',
    'miếng','kính','loa','pin','adapter','dock','watch',
    'bàn','chuột'
];

// lấy token đầu theo danh mục hiện tại (để list hãng gọn hơn)
$categoryTmp = (int)($_GET['cat'] ?? 0);
if ($categoryTmp > 0) {
    $stmHang = $pdo->prepare("
        SELECT DISTINCT TRIM(SUBSTRING_INDEX(ten_san_pham,' ',1)) AS hang
        FROM san_pham
        WHERE danh_muc_id = ?
          AND ten_san_pham IS NOT NULL
          AND ten_san_pham <> ''
        ORDER BY hang ASC
    ");
    $stmHang->execute([$categoryTmp]);
} else {
    $stmHang = $pdo->query("
        SELECT DISTINCT TRIM(SUBSTRING_INDEX(ten_san_pham,' ',1)) AS hang
        FROM san_pham
        WHERE ten_san_pham IS NOT NULL
          AND ten_san_pham <> ''
        ORDER BY hang ASC
    ");
}
$rawHangs = $stmHang->fetchAll(PDO::FETCH_COLUMN);

// lọc bỏ phụ kiện
$hangs = [];
foreach ($rawHangs as $h) {
    $h = trim($h);
    if ($h === '') continue;
    if (in_array(mb_strtolower($h,'UTF-8'), $NOT_BRANDS, true)) continue;
    $hangs[$h] = true;
}
$hangs = array_keys($hangs);
sort($hangs, SORT_LOCALE_STRING);

/* ============================
   3) Nhận tham số lọc/search
============================= */
$category = (int)($_GET['cat'] ?? 0);
$brand    = trim($_GET['brand'] ?? 'all');
$config   = trim($_GET['config'] ?? '');
$kw       = trim($_GET['kw'] ?? '');

// giá theo whitelist để tránh bậy input
$priceRanges = [
    "all" => [0, 0],
    "0-1000000" => [0, 1000000],
    "1000000-5000000" => [1000000, 5000000],
    "5000000-10000000" => [5000000, 10000000],
    "10000000-100000000" => [10000000, 100000000]
];
$priceKey = $_GET['price'] ?? "all";
if (!isset($priceRanges[$priceKey])) $priceKey = "all";
[$min_price, $max_price] = $priceRanges[$priceKey];

/* ============================
   4) Sort
============================= */
$sortKey = $_GET['sort'] ?? "new";
$sortWhitelist = [
    "new"   => "ngay_tao DESC",
    "price_asc"  => "gia ASC",
    "price_desc" => "gia DESC",
    "best"  => "da_ban DESC",
];
$sortSQL = $sortWhitelist[$sortKey] ?? $sortWhitelist["new"];

/* ============================
   5) Build WHERE
============================= */
$conditions = [];
$params = [];

if ($category > 0) {
    $conditions[] = "danh_muc_id = ?";
    $params[] = $category;
}

if ($priceKey !== "all") {
    $conditions[] = "gia BETWEEN ? AND ?";
    $params[] = $min_price;
    $params[] = $max_price;
}

if ($brand !== "all" && $brand !== "") {
    $conditions[] = "ten_san_pham LIKE ?";
    $params[] = $brand . "%";
}

if ($kw !== "") {
    $conditions[] = "ten_san_pham LIKE ?";
    $params[] = "%" . $kw . "%";
}

if ($config !== "") {
    $conditions[] = "ten_san_pham LIKE ?";
    $params[] = "%" . $config . "%";
}

$whereSQL = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

/* ============================
   6) Pagination
============================= */
$limit  = 10;
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// count
$countStm = $pdo->prepare("SELECT COUNT(*) FROM san_pham $whereSQL");
$countStm->execute($params);
$total_products = (int)$countStm->fetchColumn();
$total_pages = max(1, (int)ceil($total_products / $limit));

// fetch list
$listSql = "SELECT * FROM san_pham $whereSQL ORDER BY $sortSQL LIMIT ? OFFSET ?";
$listStm = $pdo->prepare($listSql);
$bindParams = array_merge($params, [$limit, $offset]);

for ($i=0; $i<count($bindParams); $i++) {
    $val = $bindParams[$i];
    $type = is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $listStm->bindValue($i+1, $val, $type);
}
$listStm->execute();
$products = $listStm->fetchAll();

/* baseQuery giữ filter khi sort/paginate */
$baseQuery = [
    'cat' => $category,
    'price' => $priceKey,
    'brand' => $brand,
    'config' => $config,
    'kw' => $kw,
    'sort' => $sortKey
];
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

    <style>
      .filter-wrapper{max-width:260px;font-family:Arial,sans-serif;margin-bottom:20px;}
      .filter-toggle{width:100%;padding:8px 0;background:#ffcc00;border:none;border-radius:6px;font-weight:700;cursor:pointer;}
      .filter-panel{margin-top:10px;border:1px solid #ddd;border-radius:8px;padding:15px;background:#fff;display:none;}
      .filter-group{margin-bottom:15px;}
      .filter-group label{font-weight:600;}
      .filter-options label{display:flex;align-items:center;margin-bottom:6px;cursor:pointer;}
      .filter-options input[type="radio"]{margin-right:8px;}
      .btn-apply{width:100%;padding:8px 0;background:#ffcc00;border:none;border-radius:6px;font-weight:700;cursor:pointer;}
      .btn-apply:hover{background:#ff9900;}

      .product-item {border-radius:10px; overflow:hidden; transition:.15s;}
      .product-item:hover {transform:translateY(-3px); box-shadow:0 6px 20px rgba(0,0,0,.08);}
      .badge-brand {position:absolute; top:8px; left:8px; background:#fff; border-radius:999px; padding:3px 8px; font-size:12px; font-weight:700;}
      .badge-sold {position:absolute; top:8px; right:8px; background:#ffcc00; border-radius:999px; padding:3px 8px; font-size:12px; font-weight:700;}
      .stock-text{font-size:12px;color:#666;}
    </style>
</head>

<body>
    <!-- Topbar -->
    <div class="container-fluid">
        <div class="row bg-secondary py-1 px-xl-5">
            <div class="col-lg-6 d-none d-lg-block">
                <div class="d-inline-flex align-items-center h-100">
                    <a class="text-body mr-3" href="#">Giới thiệu</a>
                    <a class="text-body mr-3" href="#">Liên hệ</a>
                    <a class="text-body mr-3" href="#">Hỗ trợ</a>
                    <a class="text-body mr-3" href="#">FAQs</a>
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

        <!-- Search bar -->
        <div class="row align-items-center bg-light py-3 px-xl-5 d-none d-lg-flex">
            <div class="col-lg-4">
                <a href="index.php" class="text-decoration-none">
                    <span class="h1 text-uppercase text-primary bg-dark px-2">Mobile</span>
                    <span class="h1 text-uppercase text-dark bg-primary px-2 ml-n1">Shop</span>
                </a>
            </div>

            <div class="col-lg-4 col-6 text-left">
                <form action="shop.php" method="get">
                    <input type="hidden" name="cat" value="<?= $category ?>">
                    <input type="hidden" name="price" value="<?= e($priceKey) ?>">
                    <input type="hidden" name="brand" value="<?= e($brand) ?>">
                    <input type="hidden" name="config" value="<?= e($config) ?>">
                    <input type="hidden" name="sort" value="<?= e($sortKey) ?>">

                    <div class="input-group">
                        <input type="text" class="form-control" name="kw" value="<?= e($kw) ?>" placeholder="Tìm điện thoại, phụ kiện...">
                        <div class="input-group-append">
                            <button class="input-group-text bg-transparent text-primary" type="submit">
                                <i class="fa fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-lg-4 col-6 text-right">
                <p class="m-0">Hotline</p>
                <h5 class="m-0">1900 0000</h5>
            </div>
        </div>
    </div>

    <!-- Navbar -->
    <div class="container-fluid bg-dark mb-30">
        <div class="row px-xl-5">
            <div class="col-lg-12">
                <nav class="navbar navbar-expand-lg bg-dark navbar-dark py-3 py-lg-0 px-0">
                    <button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#navbarCollapse">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse justify-content-between" id="navbarCollapse">
                        <div class="navbar-nav mr-auto py-0">
                            <a href="index.php" class="nav-item nav-link">Trang chủ</a>
                            <a href="shop.php" class="nav-item nav-link active">Cửa hàng</a>
                            <a href="contact.php" class="nav-item nav-link">Liên hệ</a>
                        </div>
                        <div class="navbar-nav ml-auto py-0 d-none d-lg-block">
                            <a href="cart.php" class="btn px-0 ml-3">
                                <i class="fas fa-shopping-cart text-primary"></i>
                            </a>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
    </div>

    <!-- Filters + Sort + Products -->
    <div class="container-fluid px-xl-5">
        <div class="row">

            <!-- Filter panel -->
            <div class="col-lg-3 col-md-4">
                <div class="filter-wrapper">
                    <button type="button" class="filter-toggle">Bộ lọc</button>
                    <form method="GET" action="shop.php" class="filter-panel">
                        <!-- Danh mục -->
                        <div class="filter-group">
                            <label>Danh mục:</label>
                            <div class="filter-options">
                                <label>
                                    <input type="radio" name="cat" value="0" <?= $category==0?'checked':'' ?>> Tất cả
                                </label>
                                <?php foreach($danh_mucs as $dm): ?>
                                    <label>
                                        <input type="radio" name="cat" value="<?= (int)$dm['id'] ?>" <?= $category==(int)$dm['id']?'checked':'' ?>>
                                        <?= e(normalizeCategoryLabel($dm['ten_danh_muc'])) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Hãng -->
                        <div class="filter-group">
                            <label>Hãng điện thoại:</label>
                            <div class="filter-options">
                                <label>
                                    <input type="radio" name="brand" value="all" <?= ($brand=='all'||$brand=='')?'checked':'' ?>>
                                    Tất cả hãng
                                </label>
                                <?php foreach($hangs as $h): ?>
                                    <label>
                                        <input type="radio" name="brand" value="<?= e($h) ?>" <?= $brand==$h?'checked':'' ?>>
                                        <?= e($h) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Giá -->
                        <div class="filter-group">
                            <label>Giá:</label>
                            <div class="filter-options">
                                <?php foreach($priceRanges as $k=>$rg): ?>
                                    <label>
                                        <input type="radio" name="price" value="<?= e($k) ?>" <?= $priceKey==$k?'checked':'' ?>>
                                        <?php
                                          echo match($k){
                                            "all" => "Tất cả",
                                            "0-1000000" => "Dưới 1 triệu",
                                            "1000000-5000000" => "1 - 5 triệu",
                                            "5000000-10000000" => "5 - 10 triệu",
                                            default => "Trên 10 triệu"
                                          };
                                        ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Tên -->
                        <div class="filter-group">
                            <label>Tên sản phẩm:</label>
                            <input type="text" class="form-control" name="kw" value="<?= e($kw) ?>" placeholder="VD: iPhone 15...">
                        </div>

                        <!-- Cấu hình keyword -->
                        <div class="filter-group">
                            <label>Cấu hình (keyword):</label>
                            <input type="text" class="form-control" name="config" value="<?= e($config) ?>" placeholder="VD: Pro, Note, 8GB...">
                        </div>

                        <input type="hidden" name="sort" value="<?= e($sortKey) ?>">
                        <button type="submit" class="btn-apply">Áp dụng</button>
                    </form>
                </div>
            </div>

            <!-- Product list -->
            <div class="col-lg-9 col-md-8">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <strong><?= $total_products ?></strong> sản phẩm
                        <?php if($kw || $config): ?>
                            <span class="text-muted">/ Kết quả tìm kiếm</span>
                        <?php endif; ?>
                    </div>

                    <!-- Sort dropdown -->
                    <form method="get">
                        <?php foreach($baseQuery as $k=>$v): if($k!=='sort'): ?>
                            <input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>">
                        <?php endif; endforeach; ?>

                        <select name="sort" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="new" <?= $sortKey=='new'?'selected':'' ?>>Mới nhất</option>
                            <option value="best" <?= $sortKey=='best'?'selected':'' ?>>Bán chạy</option>
                            <option value="price_asc" <?= $sortKey=='price_asc'?'selected':'' ?>>Giá tăng dần</option>
                            <option value="price_desc" <?= $sortKey=='price_desc'?'selected':'' ?>>Giá giảm dần</option>
                        </select>
                    </form>
                </div>

                <div class="row">
                    <?php if ($products): ?>
                        <?php foreach ($products as $pro): 
                            $brandToken = brandFromName($pro['ten_san_pham'], $NOT_BRANDS);
                            $labelForBadge = $brandToken 
                                ? $brandToken 
                                : ($catMap[(int)$pro['danh_muc_id']] ?? 'Phụ kiện');

                            $ton = (int)($pro['ton_kho'] ?? 0);
                            $ban = (int)($pro['da_ban'] ?? 0);
                        ?>
                            <div class="col-lg-4 col-md-6 col-sm-6 pb-4">
                                <div class="product-item bg-light mb-4 position-relative">
                                    <div class="product-img position-relative overflow-hidden">
                                        <span class="badge-brand"><?= e($labelForBadge) ?></span>
                                        <span class="badge-sold">Đã bán <?= $ban ?></span>

                                        <img class="img-fluid w-100"
                                             src="img/<?= e($pro['hinh_anh']) ?>"
                                             alt="<?= e($pro['ten_san_pham']) ?>">

                                        <div class="product-action">
                                            <a class="btn btn-outline-dark btn-square" href="add_to_cart.php?id=<?= (int)$pro['id'] ?>"><i class="fa fa-shopping-cart"></i></a>
                                            <a class="btn btn-outline-dark btn-square" href="detail.php?id=<?= (int)$pro['id'] ?>"><i class="fa fa-search"></i></a>
                                        </div>
                                    </div>

                                    <div class="text-center py-4 px-2">
                                        <a class="h6 text-decoration-none text-truncate d-block"
                                           href="detail.php?id=<?= (int)$pro['id'] ?>">
                                            <?= e($pro['ten_san_pham']) ?>
                                        </a>

                                        <div class="d-flex align-items-center justify-content-center mt-2">
                                            <h5><?= number_format((float)$pro['gia'], 0, ',', '.') ?>₫</h5>
                                        </div>

                                        <div class="stock-text mt-1">
                                            <?= $ton > 0 ? "Còn $ton sản phẩm" : "Hết hàng" ?>
                                        </div>

                                        <!-- rating demo -->
                                        <div class="d-flex align-items-center justify-content-center mb-1 mt-2">
                                            <small class="fa fa-star text-primary mr-1"></small>
                                            <small class="fa fa-star text-primary mr-1"></small>
                                            <small class="fa fa-star text-primary mr-1"></small>
                                            <small class="fa fa-star text-primary mr-1"></small>
                                            <small class="fa fa-star-half-alt text-primary mr-1"></small>
                                            <small>(99)</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 text-center">
                            <h4>Không có sản phẩm phù hợp bộ lọc.</h4>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav>
                        <ul class="pagination justify-content-center">
                            <?php for ($i=1; $i<=$total_pages; $i++): ?>
                                <li class="page-item <?= $i==$page?'active':'' ?>">
                                    <a class="page-link" href="?<?= qs($baseQuery, ['page'=>$i]) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="container-fluid bg-dark text-secondary mt-5 pt-5">
        <div class="row px-xl-5 pt-5">
            <div class="col-lg-12 text-center pb-3">&copy; MobileShop</div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="js/main.js"></script>

    <script>
      document.addEventListener("DOMContentLoaded", function() {
          const toggleBtn = document.querySelector('.filter-toggle');
          const panel = document.querySelector('.filter-panel');
          toggleBtn.addEventListener('click', () => {
              panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
          });
      });
    </script>
</body>
</html>
