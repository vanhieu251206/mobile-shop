<?php
session_start();
require_once "db.php";

/* ================== HELPERS ================== */
function e($str){ return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }
function is_post(){ return $_SERVER['REQUEST_METHOD'] === 'POST'; }

function flash($msg, $type="success"){
    $_SESSION['flash_msg'] = $msg;
    $_SESSION['flash_type'] = $type;
}
function get_flash(){
    $msg = $_SESSION['flash_msg'] ?? '';
    $type = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
    return [$msg, $type];
}

// CSRF đơn giản
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
function csrf_field(){
    return '<input type="hidden" name="csrf" value="'.e($_SESSION['csrf']).'">';
}
function check_csrf(){
    if (!is_post()) return;
    if (empty($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf']) {
        http_response_code(419);
        die("CSRF token không hợp lệ.");
    }
}

/* ================== DEV BYPASS / AUTH ==================
 *  - staff.php?dev=1 => vào thẳng, auto set admin
 *  - staff.php thường => bắt đăng nhập & vai_tro admin
 */
if (isset($_GET['dev']) && $_GET['dev'] == '1') {
    $_SESSION['user_id']  = $_SESSION['user_id'] ?? 1;
    $_SESSION['username'] = $_SESSION['username'] ?? 'dev-admin';
    $_SESSION['role']     = 'admin';
    $_SESSION['vai_tro']  = 'admin';
} else {
    if (empty($_SESSION['user_id'])) {
        header("Location: dangnhap.php");
        exit;
    }
    $role = $_SESSION['role'] ?? $_SESSION['vai_tro'] ?? '';
    if ($role !== 'admin') {
        die("Bạn không có quyền truy cập trang Staff.");
    }
}

/* ================== UPLOAD IMAGE ================== */
function handle_product_upload($field_name = 'hinh_anh_file'){
    if (empty($_FILES[$field_name]) || $_FILES[$field_name]['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // không upload
    }

    $file = $_FILES[$field_name];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Upload ảnh lỗi (code {$file['error']}).");
    }

    $allowed = ['jpg','jpeg','png','gif','webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        throw new Exception("Ảnh không hợp lệ. Chỉ chấp nhận: jpg, png, gif, webp.");
    }

    if ($file['size'] > 3 * 1024 * 1024) { // 3MB
        throw new Exception("Ảnh quá lớn. Tối đa 3MB.");
    }

    if (!is_dir(__DIR__.'/img')) {
        mkdir(__DIR__.'/img', 0777, true);
    }

    $newName = 'sp_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = __DIR__ . '/img/' . $newName;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new Exception("Không thể lưu ảnh lên server.");
    }

    return $newName;
}

/* ================== HANDLE POST ACTIONS ================== */
if (is_post()) {
    check_csrf();
    $action = $_POST['action'] ?? '';

    try {
        /* ---------- ADD PRODUCT ---------- */
        if ($action === 'add_product') {
            $ten = trim($_POST['ten_san_pham'] ?? '');
            $dm  = (int)($_POST['danh_muc_id'] ?? 0);
            $gia = (float)($_POST['gia'] ?? 0);
            $ton = (int)($_POST['ton_kho'] ?? 0);
            $imgText = trim($_POST['hinh_anh'] ?? '');

            if ($ten === '' || $gia <= 0) {
                throw new Exception("Thiếu tên hoặc giá không hợp lệ.");
            }

            // upload ảnh nếu có
            $uploaded = handle_product_upload('hinh_anh_file');
            if ($uploaded) {
                // nếu có text ảnh khác -> ghép thêm
                $imgText = $imgText ? ($uploaded . ',' . $imgText) : $uploaded;
            }

            $stm = $pdo->prepare("
                INSERT INTO san_pham(ten_san_pham,danh_muc_id,gia,ton_kho,hinh_anh)
                VALUES(?,?,?,?,?)
            ");
            $stm->execute([$ten, $dm ?: null, $gia, $ton, $imgText]);

            flash("Đã thêm sản phẩm mới!");
            header("Location: staff.php?tab=products");
            exit;
        }

        /* ---------- UPDATE PRODUCT ---------- */
        if ($action === 'update_product') {
            $id  = (int)($_POST['id'] ?? 0);
            $ten = trim($_POST['ten_san_pham'] ?? '');
            $dm  = (int)($_POST['danh_muc_id'] ?? 0);
            $gia = (float)($_POST['gia'] ?? 0);
            $ton = (int)($_POST['ton_kho'] ?? 0);
            $imgText = trim($_POST['hinh_anh'] ?? '');

            if ($id <= 0 || $ten === '' || $gia <= 0) {
                throw new Exception("Dữ liệu sửa không hợp lệ.");
            }

            // upload ảnh mới nếu có
            $uploaded = handle_product_upload('hinh_anh_file');
            if ($uploaded) {
                $imgText = $imgText ? ($uploaded . ',' . $imgText) : $uploaded;
            }

            $stm = $pdo->prepare("
                UPDATE san_pham
                SET ten_san_pham=?, danh_muc_id=?, gia=?, ton_kho=?, hinh_anh=?
                WHERE id=?
            ");
            $stm->execute([$ten, $dm ?: null, $gia, $ton, $imgText, $id]);

            flash("Đã cập nhật sản phẩm #$id!");
            header("Location: staff.php?tab=products");
            exit;
        }

        /* ---------- DELETE PRODUCT ---------- */
        if ($action === 'delete_product') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new Exception("ID sản phẩm không hợp lệ.");

            $stm = $pdo->prepare("DELETE FROM san_pham WHERE id=?");
            $stm->execute([$id]);

            flash("Đã xoá sản phẩm #$id!");
            header("Location: staff.php?tab=products");
            exit;
        }

        /* ---------- UPDATE ORDER STATUS ---------- */
        if ($action === 'update_order_status') {
            $order_id = (int)($_POST['don_hang_id'] ?? 0);
            $status   = $_POST['trang_thai'] ?? 'cho_xu_ly';
            if ($order_id<=0) throw new Exception("ID đơn không hợp lệ.");

            $pdo->beginTransaction();

            $old = $pdo->prepare("SELECT * FROM don_hang WHERE id=? LIMIT 1");
            $old->execute([$order_id]);
            $oldOrder = $old->fetch();
            if (!$oldOrder) throw new Exception("Không tìm thấy đơn.");

            $up = $pdo->prepare("UPDATE don_hang SET trang_thai=? WHERE id=?");
            $up->execute([$status, $order_id]);

            if ($status === 'hoan_thanh' && $oldOrder['trang_thai'] !== 'hoan_thanh') {
                $chk = $pdo->prepare("SELECT COUNT(*) FROM lich_su_ban_hang WHERE don_hang_id=?");
                $chk->execute([$order_id]);
                $hasHistory = (int)$chk->fetchColumn();

                if (!$hasHistory) {
                    $ct = $pdo->prepare("SELECT * FROM chi_tiet_don_hang WHERE don_hang_id=?");
                    $ct->execute([$order_id]);
                    $items = $ct->fetchAll();

                    foreach($items as $it){
                        $spid = (int)$it['san_pham_id'];
                        $qty  = (int)$it['so_luong'];
                        $gia  = (float)$it['gia'];
                        $tt   = $qty * $gia;

                        $ins = $pdo->prepare("
                            INSERT INTO lich_su_ban_hang(don_hang_id, nguoi_dung_id, san_pham_id, so_luong, thanh_tien)
                            VALUES(?,?,?,?,?)
                        ");
                        $ins->execute([$order_id, $oldOrder['nguoi_dung_id'], $spid, $qty, $tt]);

                        $updSp = $pdo->prepare("
                            UPDATE san_pham
                            SET ton_kho = GREATEST(ton_kho - ?, 0),
                                da_ban  = da_ban + ?
                            WHERE id=?
                        ");
                        $updSp->execute([$qty, $qty, $spid]);
                    }
                }
            }

            $pdo->commit();

            flash("Cập nhật trạng thái đơn #$order_id thành công!");
            header("Location: staff.php?tab=orders");
            exit;
        }

    } catch (Exception $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash("Lỗi: ".$ex->getMessage(), "danger");
        header("Location: staff.php?tab=".urlencode($_GET['tab'] ?? 'dashboard'));
        exit;
    }
}

/* ================== FETCH DATA ================== */
$tab = $_GET['tab'] ?? 'dashboard';

$cats = $pdo->query("SELECT * FROM danh_muc ORDER BY id ASC")->fetchAll();

$products = $pdo->query("
    SELECT sp.*, dm.ten_danh_muc
    FROM san_pham sp
    LEFT JOIN danh_muc dm ON dm.id=sp.danh_muc_id
    ORDER BY sp.id DESC
")->fetchAll();

$orders = $pdo->query("
    SELECT dh.*, nd.ten_dang_nhap, nd.email
    FROM don_hang dh
    JOIN nguoi_dung nd ON nd.id = dh.nguoi_dung_id
    ORDER BY dh.id DESC
")->fetchAll();

$customers = $pdo->query("
    SELECT * FROM nguoi_dung
    WHERE vai_tro='user'
    ORDER BY id DESC
")->fetchAll();

$total_revenue = (float)$pdo->query("SELECT IFNULL(SUM(thanh_tien),0) FROM lich_su_ban_hang")->fetchColumn();
$total_done_orders = (int)$pdo->query("SELECT COUNT(*) FROM don_hang WHERE trang_thai='hoan_thanh'")->fetchColumn();

$top_products = $pdo->query("
    SELECT sp.id, sp.ten_san_pham, SUM(ls.so_luong) as sl, SUM(ls.thanh_tien) as doanh_thu
    FROM lich_su_ban_hang ls
    JOIN san_pham sp ON sp.id=ls.san_pham_id
    GROUP BY sp.id
    ORDER BY doanh_thu DESC
    LIMIT 5
")->fetchAll();

$count_products = (int)$pdo->query("SELECT COUNT(*) FROM san_pham")->fetchColumn();
$count_users    = (int)$pdo->query("SELECT COUNT(*) FROM nguoi_dung WHERE vai_tro='user'")->fetchColumn();
$count_pending  = (int)$pdo->query("SELECT COUNT(*) FROM don_hang WHERE trang_thai='cho_xu_ly'")->fetchColumn();

$edit_id = (int)($_GET['edit_id'] ?? 0);
$edit_product = null;
if ($edit_id) {
    $stm = $pdo->prepare("SELECT * FROM san_pham WHERE id=?");
    $stm->execute([$edit_id]);
    $edit_product = $stm->fetch();
}

list($flash_msg, $flash_type) = get_flash();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Staff Panel - MobileShop</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <!-- Base -->
    <link href="img/favicon.ico" rel="icon">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">

    <!-- Optional: bootstrap 4.6 (nếu style.css chưa có) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <link href="css/style.css" rel="stylesheet">

    <style>
        body{font-family:Roboto,Arial;background:#f6f7fb;}
        .staff-wrap{display:flex;min-height:100vh;}
        .sidebar{
            width:250px;background:#0f172a;color:#fff;padding:18px 12px;position:sticky;top:0;height:100vh;
        }
        .sidebar .brand{
            font-size:20px;font-weight:800;margin-bottom:14px;color:#facc15;text-align:center;letter-spacing:.5px;
        }
        .sidebar a{
            display:flex;align-items:center;gap:10px;color:#cbd5e1;padding:10px 12px;border-radius:10px;text-decoration:none;
            transition:.15s;
        }
        .sidebar a.active, .sidebar a:hover{background:#1e293b;color:#fff;}
        .content{flex:1;padding:22px;}
        .cardx{
            background:#fff;border-radius:14px;padding:16px;box-shadow:0 6px 18px rgba(15,23,42,.06);
        }
        .stat{display:flex;gap:12px;flex-wrap:wrap;}
        .stat .item{flex:1;min-width:180px;}
        .badge-status{padding:4px 9px;border-radius:999px;font-size:12px;font-weight:700;display:inline-block;}
        .st-pending{background:#fff7d9;color:#a16207;}
        .st-done{background:#e8fff0;color:#1a7f37;}
        .st-cancel{background:#ffe8e8;color:#a12b2b;}
        .table td, .table th{vertical-align:middle;}
        .form-control{border-radius:10px;}
        .btn{border-radius:10px;}
        .page-title{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;}
        .muted{color:#64748b;}
        .img-preview{width:70px;height:70px;border-radius:10px;object-fit:cover;border:1px solid #eee;}
    </style>
</head>
<body>

<div class="staff-wrap">
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="brand">MobiShop Staff</div>
        <a class="<?= $tab==='dashboard'?'active':'' ?>" href="?tab=dashboard"><i class="fa fa-chart-pie"></i> Trang chủ Staff</a>
        <a class="<?= $tab==='products'?'active':'' ?>" href="?tab=products"><i class="fa fa-box"></i> Quản lý sản phẩm</a>
        <a class="<?= $tab==='orders'?'active':'' ?>" href="?tab=orders"><i class="fa fa-receipt"></i> Quản lý đơn hàng</a>
        <a class="<?= $tab==='customers'?'active':'' ?>" href="?tab=customers"><i class="fa fa-users"></i> Khách hàng</a>
        <a class="<?= $tab==='reports'?'active':'' ?>" href="?tab=reports"><i class="fa fa-file-invoice-dollar"></i> Báo cáo bán hàng</a>
        <hr style="border-color:#334155">
        <a href="index.php"><i class="fa fa-home"></i> Về trang khách</a>
        <a href="dangxuat.php"><i class="fa fa-sign-out-alt"></i> Đăng xuất</a>
    </div>

    <!-- MAIN CONTENT -->
    <div class="content">

        <?php if($flash_msg): ?>
            <div class="alert alert-<?= e($flash_type) ?>"><?= e($flash_msg) ?></div>
        <?php endif; ?>

        <!-- DASHBOARD -->
        <?php if($tab==='dashboard'): ?>
            <div class="page-title">
                <h4 class="mb-0">Trang chủ Staff</h4>
                <div class="muted">Xin chào, <?= e($_SESSION['username'] ?? 'staff') ?></div>
            </div>

            <div class="stat mb-3">
                <div class="cardx item">
                    <div class="muted">Tổng sản phẩm</div>
                    <div style="font-size:28px;font-weight:800;"><?= $count_products ?></div>
                </div>
                <div class="cardx item">
                    <div class="muted">Khách hàng</div>
                    <div style="font-size:28px;font-weight:800;"><?= $count_users ?></div>
                </div>
                <div class="cardx item">
                    <div class="muted">Đơn chờ xử lý</div>
                    <div style="font-size:28px;font-weight:800;"><?= $count_pending ?></div>
                </div>
                <div class="cardx item">
                    <div class="muted">Doanh thu</div>
                    <div style="font-size:28px;font-weight:800;color:#d0021b;">
                        <?= number_format($total_revenue,0,',','.') ?>₫
                    </div>
                </div>
            </div>

            <div class="cardx">
                <h5 class="mb-3">Top 5 sản phẩm bán chạy</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th><th>Tên</th><th>Số lượng bán</th><th>Doanh thu</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($top_products as $i=>$tp): ?>
                            <tr>
                                <td><?= $i+1 ?></td>
                                <td><?= e($tp['ten_san_pham']) ?></td>
                                <td><?= (int)$tp['sl'] ?></td>
                                <td><?= number_format((float)$tp['doanh_thu'],0,',','.') ?>₫</td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(!$top_products): ?>
                            <tr><td colspan="4" class="text-center muted">Chưa có dữ liệu bán hàng.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- PRODUCTS -->
        <?php if($tab==='products'): ?>
            <div class="page-title">
                <h4 class="mb-0">Quản lý sản phẩm</h4>
                <?php if($edit_product): ?>
                    <a class="btn btn-secondary btn-sm" href="staff.php?tab=products">Thoát chế độ sửa</a>
                <?php endif; ?>
            </div>

            <!-- ADD / EDIT FORM -->
            <div class="cardx mb-3">
                <h5 class="mb-3"><?= $edit_product ? "Sửa sản phẩm #".$edit_product['id'] : "Thêm sản phẩm mới" ?></h5>

                <form method="POST" enctype="multipart/form-data">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="action" value="<?= $edit_product ? "update_product" : "add_product" ?>">
                    <?php if($edit_product): ?>
                        <input type="hidden" name="id" value="<?= (int)$edit_product['id'] ?>">
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label>Tên sản phẩm *</label>
                            <input class="form-control" name="ten_san_pham" required
                                   value="<?= e($edit_product['ten_san_pham'] ?? '') ?>">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label>Danh mục</label>
                            <select class="form-control" name="danh_muc_id">
                                <option value="">--Chọn--</option>
                                <?php foreach($cats as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>"
                                        <?= isset($edit_product['danh_muc_id']) && $edit_product['danh_muc_id']==$c['id']?'selected':'' ?>>
                                        <?= e($c['ten_danh_muc']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label>Giá *</label>
                            <input type="number" min="0" class="form-control" name="gia" required
                                   value="<?= e($edit_product['gia'] ?? '') ?>">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label>Tồn kho</label>
                            <input type="number" min="0" class="form-control" name="ton_kho"
                                   value="<?= e($edit_product['ton_kho'] ?? 0) ?>">
                        </div>

                        <!-- Choose file upload -->
                        <div class="col-md-4 mb-2">
                            <label>Upload ảnh chính</label>
                            <input type="file" class="form-control-file" name="hinh_anh_file" accept="image/*">
                            <small class="muted">Ảnh sẽ tự lưu vào thư mục <b>img/</b></small>
                        </div>

                        <div class="col-md-8 mb-2">
                            <label>Ảnh phụ / tên file (vd: s23.jpg hoặc a.jpg,b.jpg)</label>
                            <input class="form-control" name="hinh_anh"
                                   value="<?= e($edit_product['hinh_anh'] ?? '') ?>">
                        </div>

                        <?php if($edit_product): ?>
                            <?php $oldimg = explode(',', $edit_product['hinh_anh'] ?? '')[0] ?? ''; ?>
                            <?php if($oldimg): ?>
                                <div class="col-md-12 mt-2">
                                    <label>Ảnh hiện tại:</label><br>
                                    <img class="img-preview" src="img/<?= e(trim($oldimg)) ?>" alt="">
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <div class="mt-3">
                        <button class="btn btn-primary">
                            <?= $edit_product ? "Cập nhật" : "Thêm mới" ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- PRODUCT LIST -->
            <div class="cardx">
                <h5 class="mb-3">Danh sách sản phẩm</h5>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID</th><th>Ảnh</th><th>Tên</th><th>Danh mục</th>
                                <th>Giá</th><th>Tồn</th><th>Đã bán</th><th width="160">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($products as $p): ?>
                            <?php $img = explode(',', $p['hinh_anh'] ?? '')[0] ?? 'no-image.png'; ?>
                            <tr>
                                <td><?= (int)$p['id'] ?></td>
                                <td style="width:90px">
                                    <img class="img-preview" src="img/<?= e(trim($img)) ?>">
                                </td>
                                <td><?= e($p['ten_san_pham']) ?></td>
                                <td><?= e($p['ten_danh_muc'] ?? '—') ?></td>
                                <td><?= number_format((float)$p['gia'],0,',','.') ?>₫</td>
                                <td><?= (int)$p['ton_kho'] ?></td>
                                <td><?= (int)$p['da_ban'] ?></td>
                                <td>
                                    <a class="btn btn-sm btn-warning"
                                       href="staff.php?tab=products&edit_id=<?= (int)$p['id'] ?>">
                                        Sửa
                                    </a>

                                    <form method="POST" style="display:inline" onsubmit="return confirm('Xoá sản phẩm này?')">
                                        <?= csrf_field(); ?>
                                        <input type="hidden" name="action" value="delete_product">
                                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                        <button class="btn btn-sm btn-danger">Xoá</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(!$products): ?>
                            <tr><td colspan="8" class="text-center muted">Chưa có sản phẩm.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- ORDERS -->
        <?php if($tab==='orders'): ?>
            <div class="page-title">
                <h4 class="mb-0">Quản lý đơn hàng</h4>
            </div>

            <div class="cardx">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID</th><th>Khách hàng</th><th>Email</th>
                                <th>Tổng tiền</th><th>Trạng thái</th><th>Ngày tạo</th><th width="210">Cập nhật</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($orders as $o): ?>
                            <?php
                                $stClass = $o['trang_thai']==='hoan_thanh' ? 'st-done' :
                                           ($o['trang_thai']==='huy' ? 'st-cancel' : 'st-pending');
                            ?>
                            <tr>
                                <td>#<?= (int)$o['id'] ?></td>
                                <td><?= e($o['ten_dang_nhap']) ?></td>
                                <td><?= e($o['email']) ?></td>
                                <td><?= number_format((float)$o['tong_tien'],0,',','.') ?>₫</td>
                                <td><span class="badge-status <?= $stClass ?>"><?= e($o['trang_thai']) ?></span></td>
                                <td><?= e($o['ngay_tao']) ?></td>
                                <td>
                                    <form method="POST" class="d-flex" style="gap:6px;align-items:center;">
                                        <?= csrf_field(); ?>
                                        <input type="hidden" name="action" value="update_order_status">
                                        <input type="hidden" name="don_hang_id" value="<?= (int)$o['id'] ?>">
                                        <select name="trang_thai" class="form-control form-control-sm" style="width:130px;">
                                            <option value="cho_xu_ly" <?= $o['trang_thai']=='cho_xu_ly'?'selected':'' ?>>chờ xử lý</option>
                                            <option value="hoan_thanh" <?= $o['trang_thai']=='hoan_thanh'?'selected':'' ?>>hoàn thành</option>
                                            <option value="huy" <?= $o['trang_thai']=='huy'?'selected':'' ?>>huỷ</option>
                                        </select>
                                        <button class="btn btn-sm btn-primary">Lưu</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(!$orders): ?>
                            <tr><td colspan="7" class="text-center muted">Chưa có đơn hàng.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- CUSTOMERS -->
        <?php if($tab==='customers'): ?>
            <div class="page-title">
                <h4 class="mb-0">Thông tin khách hàng</h4>
            </div>

            <div class="cardx">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID</th><th>Tên đăng nhập</th><th>Email</th><th>Ngày tạo</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($customers as $c): ?>
                            <tr>
                                <td><?= (int)$c['id'] ?></td>
                                <td><?= e($c['ten_dang_nhap']) ?></td>
                                <td><?= e($c['email']) ?></td>
                                <td><?= e($c['ngay_tao']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(!$customers): ?>
                            <tr><td colspan="4" class="text-center muted">Chưa có khách hàng.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- REPORTS -->
        <?php if($tab==='reports'): ?>
            <div class="page-title">
                <h4 class="mb-0">Báo cáo bán hàng</h4>
            </div>

            <div class="stat mb-3">
                <div class="cardx item">
                    <div class="muted">Tổng doanh thu</div>
                    <div style="font-size:26px;font-weight:800;color:#d0021b;">
                        <?= number_format($total_revenue,0,',','.') ?>₫
                    </div>
                </div>
                <div class="cardx item">
                    <div class="muted">Đơn hoàn thành</div>
                    <div style="font-size:26px;font-weight:800;"><?= $total_done_orders ?></div>
                </div>
            </div>

            <div class="cardx mb-3">
                <h5 class="mb-3">Top 5 sản phẩm doanh thu cao</h5>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th><th>Sản phẩm</th><th>Số lượng bán</th><th>Doanh thu</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($top_products as $i=>$tp): ?>
                            <tr>
                                <td><?= $i+1 ?></td>
                                <td><?= e($tp['ten_san_pham']) ?></td>
                                <td><?= (int)$tp['sl'] ?></td>
                                <td><?= number_format((float)$tp['doanh_thu'],0,',','.') ?>₫</td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(!$top_products): ?>
                            <tr><td colspan="4" class="text-center muted">Chưa có dữ liệu.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="cardx">
                <h5 class="mb-3">Lịch sử bán hàng</h5>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID</th><th>Đơn hàng</th><th>Sản phẩm</th><th>SL</th><th>Thành tiền</th><th>Ngày bán</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                            $history = $pdo->query("
                                SELECT ls.*, sp.ten_san_pham
                                FROM lich_su_ban_hang ls
                                JOIN san_pham sp ON sp.id=ls.san_pham_id
                                ORDER BY ls.id DESC
                                LIMIT 200
                            ")->fetchAll();
                        ?>
                        <?php foreach($history as $h): ?>
                            <tr>
                                <td><?= (int)$h['id'] ?></td>
                                <td>#<?= (int)$h['don_hang_id'] ?></td>
                                <td><?= e($h['ten_san_pham']) ?></td>
                                <td><?= (int)$h['so_luong'] ?></td>
                                <td><?= number_format((float)$h['thanh_tien'],0,',','.') ?>₫</td>
                                <td><?= e($h['ngay_ban']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(!$history): ?>
                            <tr><td colspan="6" class="text-center muted">Chưa có lịch sử.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- JS bootstrap (nếu cần) -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
