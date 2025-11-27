<?php
session_start();
require_once 'db.php';

/* ===========================
   AUTH + DEV BYPASS
=========================== */

// DEV: admin.php?dev=1 => auto set admin session
if (isset($_GET['dev']) && $_GET['dev'] == '1') {
    $_SESSION['user_id']  = $_SESSION['user_id'] ?? 1;
    $_SESSION['username'] = $_SESSION['username'] ?? 'dev-admin';
    $_SESSION['vai_tro']  = 'admin';
    $_SESSION['role']     = 'admin';
}

// Nếu chưa login hoặc không phải admin thì chặn
if (empty($_SESSION['user_id'])) {
    header("Location: dangnhap.php");
    exit;
}
$role = $_SESSION['role'] ?? $_SESSION['vai_tro'] ?? '';
if ($role !== 'admin') {
    die("Bạn không có quyền truy cập trang Admin.");
}

/* ===========================
   HELPERS
=========================== */
function e($str){ return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }
function is_post(){ return $_SERVER['REQUEST_METHOD'] === 'POST'; }

function flash($msg, $type='success'){
    $_SESSION['flash_msg']  = $msg;
    $_SESSION['flash_type'] = $type;
}
function get_flash(){
    $msg  = $_SESSION['flash_msg']  ?? '';
    $type = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
    return [$msg, $type];
}

// CSRF
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

/* ===========================
   NỘI DUNG TRANG (JSON FILE)
=========================== */
$contentFile = __DIR__ . '/data_site_content.json';
$siteContent = [
    'home_banner_title'    => 'MobiShop - Hệ sinh thái điện thoại & phụ kiện',
    'home_banner_subtitle' => 'Ưu đãi mỗi ngày, bảo hành chính hãng',
    'announcement'         => 'Miễn phí ship đơn từ 3.000.000đ toàn quốc.',
    'promotion'            => 'Giảm thêm 5% cho khách thanh toán online.'
];

if (file_exists($contentFile)) {
    $json = json_decode(file_get_contents($contentFile), true);
    if (is_array($json)) {
        $siteContent = array_merge($siteContent, $json);
    }
}

/* ===========================
   HANDLE POST ACTIONS
=========================== */
if (is_post()) {
    check_csrf();
    $action = $_POST['action'] ?? '';

    try {
        /* ------ USER CRUD (STAFF + CUSTOMER) ------ */
        if ($action === 'add_user') {
            $u = trim($_POST['ten_dang_nhap'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $vai_tro = $_POST['vai_tro'] ?? 'user';
            $pass = $_POST['mat_khau'] ?? '';

            if ($u === '' || $email === '' || $pass === '') {
                throw new Exception("Vui lòng nhập đủ tên, email và mật khẩu.");
            }

            $hash = password_hash($pass, PASSWORD_BCRYPT);

            $stm = $pdo->prepare("
                INSERT INTO nguoi_dung(ten_dang_nhap, email, mat_khau, vai_tro)
                VALUES(?,?,?,?)
            ");
            $stm->execute([$u, $email, $hash, $vai_tro]);

            flash("Đã thêm tài khoản mới!");
            header("Location: admin.php?tab=users");
            exit;
        }

        if ($action === 'update_user') {
            $id  = (int)($_POST['id'] ?? 0);
            $u   = trim($_POST['ten_dang_nhap'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $vai_tro = $_POST['vai_tro'] ?? 'user';
            $newpass = $_POST['new_password'] ?? '';

            if ($id <= 0 || $u === '' || $email === '') {
                throw new Exception("Dữ liệu tài khoản không hợp lệ.");
            }

            if ($newpass !== '') {
                $hash = password_hash($newpass, PASSWORD_BCRYPT);
                $stm = $pdo->prepare("
                    UPDATE nguoi_dung
                    SET ten_dang_nhap=?, email=?, vai_tro=?, mat_khau=?
                    WHERE id=?
                ");
                $stm->execute([$u, $email, $vai_tro, $hash, $id]);
            } else {
                $stm = $pdo->prepare("
                    UPDATE nguoi_dung
                    SET ten_dang_nhap=?, email=?, vai_tro=?
                    WHERE id=?
                ");
                $stm->execute([$u, $email, $vai_tro, $id]);
            }

            flash("Đã cập nhật tài khoản #$id!");
            header("Location: admin.php?tab=users");
            exit;
        }

        if ($action === 'delete_user') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new Exception("ID không hợp lệ.");
            if ($id == $_SESSION['user_id']) {
                throw new Exception("Không thể tự xoá chính mình.");
            }

            $stm = $pdo->prepare("DELETE FROM nguoi_dung WHERE id = ?");
            $stm->execute([$id]);

            flash("Đã xoá tài khoản #$id!");
            header("Location: admin.php?tab=users");
            exit;
        }

        /* ------ PERMISSIONS: ĐỔI VAI TRÒ ------ */
        if ($action === 'change_role') {
            $id  = (int)($_POST['id'] ?? 0);
            $vai = $_POST['vai_tro'] ?? 'user';
            if ($id <= 0) throw new Exception("ID không hợp lệ.");
            if (!in_array($vai, ['user','staff','admin'], true)) {
                throw new Exception("Vai trò không hợp lệ.");
            }

            $stm = $pdo->prepare("UPDATE nguoi_dung SET vai_tro=? WHERE id=?");
            $stm->execute([$vai, $id]);

            flash("Đã cập nhật quyền truy cập cho user #$id!");
            header("Location: admin.php?tab=permissions");
            exit;
        }

        /* ------ LƯU NỘI DUNG TRANG ------ */
        if ($action === 'save_content') {
            $siteContent['home_banner_title']    = trim($_POST['home_banner_title'] ?? '');
            $siteContent['home_banner_subtitle'] = trim($_POST['home_banner_subtitle'] ?? '');
            $siteContent['announcement']         = trim($_POST['announcement'] ?? '');
            $siteContent['promotion']            = trim($_POST['promotion'] ?? '');

            file_put_contents($contentFile, json_encode($siteContent, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            flash("Đã lưu nội dung trang chủ!");
            header("Location: admin.php?tab=content");
            exit;
        }

        /* ------ QUẢN LÝ ĐÁNH GIÁ SẢN PHẨM ------ */
        // Từ tab Đánh giá, admin gửi form đổi trạng thái (duyệt / ẩn / chờ duyệt)
        if ($action === 'update_review_status') {
            $id   = (int)($_POST['id'] ?? 0);
            $stat = $_POST['trang_thai'] ?? 'cho_duyet';

            if ($id <= 0) {
                throw new Exception("ID đánh giá không hợp lệ.");
            }
            if (!in_array($stat, ['cho_duyet','duyet','an'], true)) {
                throw new Exception("Trạng thái đánh giá không hợp lệ.");
            }

            $stm = $pdo->prepare("UPDATE danh_gia SET trang_thai = ? WHERE id = ?");
            $stm->execute([$stat, $id]);

            flash("Đã cập nhật trạng thái đánh giá #$id!");
            header("Location: admin.php?tab=reviews");
            exit;
        }

    } catch (Exception $ex) {
        flash("Lỗi: ".$ex->getMessage(), "danger");
        header("Location: admin.php?tab=".urlencode($_GET['tab'] ?? 'dashboard'));
        exit;
    }
}

/* ===========================
   FETCH DATA CHUNG
=========================== */
$tab = $_GET['tab'] ?? 'dashboard';

$users = $pdo->query("SELECT * FROM nguoi_dung ORDER BY id DESC")->fetchAll();

$orders = $pdo->query("
    SELECT dh.*, nd.ten_dang_nhap, nd.email
    FROM don_hang dh
    JOIN nguoi_dung nd ON nd.id = dh.nguoi_dung_id
    ORDER BY dh.id DESC
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
$count_users    = (int)$pdo->query("SELECT COUNT(*) FROM nguoi_dung")->fetchColumn();
$count_staff    = (int)$pdo->query("SELECT COUNT(*) FROM nguoi_dung WHERE vai_tro='staff'")->fetchColumn();
$count_pending  = (int)$pdo->query("SELECT COUNT(*) FROM don_hang WHERE trang_thai='cho_xu_ly'")->fetchColumn();

$edit_user_id = (int)($_GET['edit_user_id'] ?? 0);
$edit_user = null;
if ($edit_user_id) {
    foreach ($users as $u) {
        if ((int)$u['id'] === $edit_user_id) { $edit_user = $u; break; }
    }
}

/* ------ Lấy danh sách đánh giá (chỉ khi vào tab reviews) ------ */
$reviews = [];
if ($tab === 'reviews') {
    $reviews = $pdo->query("
        SELECT dg.*, nd.ten_dang_nhap, sp.ten_san_pham
        FROM danh_gia dg
        JOIN nguoi_dung nd ON nd.id = dg.nguoi_dung_id
        LEFT JOIN san_pham sp ON sp.id = dg.san_pham_id
        ORDER BY dg.id DESC
    ")->fetchAll();
}

list($flash_msg, $flash_type) = get_flash();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Admin Panel - MobiShop</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <link href="img/favicon.ico" rel="icon">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <style>
        body{font-family:Roboto,Arial;background:#f3f4f6;}
        .admin-wrap{display:flex;min-height:100vh;}
        .sidebar{
            width:260px;background:#020617;color:#e5e7eb;padding:18px 12px;
            position:sticky;top:0;height:100vh;
        }
        .sidebar .brand{
            font-size:20px;font-weight:800;margin-bottom:16px;color:#facc15;text-align:center;
        }
        .sidebar .desc{font-size:12px;color:#9ca3af;margin-bottom:12px;text-align:center;}
        .sidebar a{
            display:flex;align-items:center;gap:10px;color:#cbd5e1;padding:9px 12px;border-radius:10px;
            text-decoration:none;font-size:14px;transition:.15s;margin-bottom:2px;
        }
        .sidebar a i{width:18px;text-align:center;}
        .sidebar a.active,.sidebar a:hover{background:#0f172a;color:#fff;}
        .content{flex:1;padding:22px;}
        .page-title{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;}
        .cardx{
            background:#fff;border-radius:14px;padding:16px;box-shadow:0 8px 24px rgba(15,23,42,.06);
        }
        .stat{display:flex;gap:14px;flex-wrap:wrap;}
        .stat .item{flex:1;min-width:180px;}
        .muted{color:#6b7280;font-size:13px;}
        .badge-role{
            padding:3px 8px;border-radius:999px;font-size:11px;font-weight:700;display:inline-block;
        }
        .role-admin{background:#fee2e2;color:#b91c1c;}
        .role-staff{background:#e0f2fe;color:#0369a1;}
        .role-user{background:#e5e7eb;color:#374151;}
        .badge-status{padding:4px 9px;border-radius:999px;font-size:12px;font-weight:700;display:inline-block;}
        .st-pending{background:#fff7d9;color:#a16207;}
        .st-done{background:#e8fff0;color:#1a7f37;}
        .st-cancel{background:#ffe8e8;color:#a12b2b;}
        .table td,.table th{vertical-align:middle;}
        .form-control,.btn{border-radius:10px;}
        .textarea-sm{min-height:70px;}
    </style>
</head>
<body>

<div class="admin-wrap">
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="brand">MobiShop Admin</div>
        <div class="desc">Xin chào, <?= e($_SESSION['username'] ?? 'admin') ?></div>

        <a href="admin.php?tab=dashboard" class="<?= $tab==='dashboard'?'active':'' ?>">
            <i class="fa fa-chart-line"></i> Tổng quan
        </a>
        <a href="admin.php?tab=users" class="<?= $tab==='users'?'active':'' ?>">
            <i class="fa fa-user-cog"></i> Tài khoản (Staff, Customer)
        </a>
        <a href="admin.php?tab=permissions" class="<?= $tab==='permissions'?'active':'' ?>">
            <i class="fa fa-key"></i> Phân quyền truy cập
        </a>
        <a href="admin.php?tab=products" class="<?= $tab==='products'?'active':'' ?>">
            <i class="fa fa-box"></i> Quản lý sản phẩm
        </a>
        <a href="admin.php?tab=orders" class="<?= $tab==='orders'?'active':'' ?>">
            <i class="fa fa-receipt"></i> Quản lý đơn hàng
        </a>
        <!-- TAB MỚI: ĐÁNH GIÁ SẢN PHẨM -->
        <a href="admin.php?tab=reviews" class="<?= $tab==='reviews'?'active':'' ?>">
            <i class="fa fa-star"></i> Đánh giá sản phẩm
        </a>
        <a href="admin.php?tab=reports" class="<?= $tab==='reports'?'active':'' ?>">
            <i class="fa fa-file-invoice-dollar"></i> Thống kê & báo cáo
        </a>
        <a href="admin.php?tab=content" class="<?= $tab==='content'?'active':'' ?>">
            <i class="fa fa-bullhorn"></i> Quản lý nội dung
        </a>

        <hr style="border-color:#1f2937">

        <a href="staff.php">
            <i class="fa fa-users-cog"></i> Mở Staff Panel
        </a>
        <a href="index.php">
            <i class="fa fa-home"></i> Về trang khách
        </a>
        <a href="dangxuat.php">
            <i class="fa fa-sign-out-alt"></i> Đăng xuất
        </a>
    </div>

    <!-- MAIN CONTENT -->
    <div class="content">

        <?php if($flash_msg): ?>
            <div class="alert alert-<?= e($flash_type) ?>"><?= e($flash_msg) ?></div>
        <?php endif; ?>

        <!-- DASHBOARD -->
        <?php if($tab==='dashboard'): ?>
            <div class="page-title">
                <h4 class="mb-0">Tổng quan hệ thống</h4>
                <a href="staff.php" class="btn btn-outline-primary btn-sm">
                    <i class="fa fa-external-link-alt mr-1"></i> Mở Staff Panel
                </a>
            </div>

            <div class="stat mb-3">
                <div class="cardx item">
                    <div class="muted">Tổng tài khoản</div>
                    <div style="font-size:28px;font-weight:800;"><?= $count_users ?></div>
                </div>
                <div class="cardx item">
                    <div class="muted">Nhân viên (Staff)</div>
                    <div style="font-size:28px;font-weight:800;"><?= $count_staff ?></div>
                </div>
                <div class="cardx item">
                    <div class="muted">Sản phẩm</div>
                    <div style="font-size:28px;font-weight:800;"><?= $count_products ?></div>
                </div>
                <div class="cardx item">
                    <div class="muted">Đơn chờ xử lý</div>
                    <div style="font-size:28px;font-weight:800;"><?= $count_pending ?></div>
                </div>
                <div class="cardx item">
                    <div class="muted">Tổng doanh thu</div>
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
                                <th>#</th><th>Sản phẩm</th><th>Số lượng</th><th>Doanh thu</th>
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
        <?php endif; ?>

        <!-- USERS MANAGEMENT -->
        <?php if($tab==='users'): ?>
            <div class="page-title">
                <h4 class="mb-0">Quản lý tài khoản (Staff, Customer)</h4>
            </div>

            <div class="cardx mb-3">
                <h5 class="mb-3"><?= $edit_user ? "Sửa tài khoản #".$edit_user['id'] : "Thêm tài khoản mới" ?></h5>

                <form method="POST">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="action" value="<?= $edit_user ? 'update_user' : 'add_user' ?>">
                    <?php if($edit_user): ?>
                        <input type="hidden" name="id" value="<?= (int)$edit_user['id'] ?>">
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <label>Tên đăng nhập *</label>
                            <input class="form-control" name="ten_dang_nhap" required
                                   value="<?= e($edit_user['ten_dang_nhap'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Email *</label>
                            <input type="email" class="form-control" name="email" required
                                   value="<?= e($edit_user['email'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Vai trò</label>
                            <select class="form-control" name="vai_tro">
                                <?php
                                  $curRole = $edit_user['vai_tro'] ?? 'user';
                                ?>
                                <option value="user"  <?= $curRole==='user'?'selected':'' ?>>Customer</option>
                                <option value="staff" <?= $curRole==='staff'?'selected':'' ?>>Staff</option>
                                <option value="admin" <?= $curRole==='admin'?'selected':'' ?>>Admin</option>
                            </select>
                        </div>

                        <?php if(!$edit_user): ?>
                            <div class="col-md-4 mb-2">
                                <label>Mật khẩu *</label>
                                <input type="password" class="form-control" name="mat_khau" required>
                            </div>
                        <?php else: ?>
                            <div class="col-md-4 mb-2">
                                <label>Mật khẩu mới (bỏ trống nếu giữ nguyên)</label>
                                <input type="password" class="form-control" name="new_password">
                            </div>
                        <?php endif; ?>
                    </div>

                    <button class="btn btn-primary mt-2">
                        <?= $edit_user ? "Cập nhật tài khoản" : "Thêm tài khoản" ?>
                    </button>
                    <?php if($edit_user): ?>
                        <a href="admin.php?tab=users" class="btn btn-secondary mt-2 ml-1">Huỷ</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="cardx">
                <h5 class="mb-3">Danh sách tài khoản</h5>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID</th><th>Tên đăng nhập</th><th>Email</th>
                                <th>Vai trò</th><th>Ngày tạo</th><th width="170">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($users as $u): ?>
                            <?php
                              $r = $u['vai_tro'];
                              $rc = $r==='admin'?'role-admin':($r==='staff'?'role-staff':'role-user');
                            ?>
                            <tr>
                                <td><?= (int)$u['id'] ?></td>
                                <td><?= e($u['ten_dang_nhap']) ?></td>
                                <td><?= e($u['email']) ?></td>
                                <td><span class="badge-role <?= $rc ?>"><?= e($r) ?></span></td>
                                <td><?= e($u['ngay_tao'] ?? '') ?></td>
                                <td>
                                    <a href="admin.php?tab=users&edit_user_id=<?= (int)$u['id'] ?>"
                                       class="btn btn-sm btn-warning">Sửa</a>

                                    <?php if($u['id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" style="display:inline"
                                              onsubmit="return confirm('Xoá tài khoản này?')">
                                            <?= csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                            <button class="btn btn-sm btn-danger">Xoá</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(!$users): ?>
                            <tr><td colspan="6" class="text-center muted">Chưa có tài khoản.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- PERMISSIONS -->
        <?php if($tab==='permissions'): ?>
            <div class="page-title">
                <h4 class="mb-0">Phân quyền truy cập cho Staff</h4>
            </div>

            <div class="cardx">
                <p class="muted mb-3">
                    Admin có thể cấp / thu quyền staff hoặc admin cho từng tài khoản.  
                    (Lưu ý: không nên tự hạ quyền tài khoản của bạn xuống user để tránh bị kẹt.)
                </p>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID</th><th>Tên đăng nhập</th><th>Email</th><th>Vai trò hiện tại</th><th>Cập nhật</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($users as $u): ?>
                            <tr>
                                <td><?= (int)$u['id'] ?></td>
                                <td><?= e($u['ten_dang_nhap']) ?></td>
                                <td><?= e($u['email']) ?></td>
                                <td><?= e($u['vai_tro']) ?></td>
                                <td>
                                    <form method="POST" class="form-inline">
                                        <?= csrf_field(); ?>
                                        <input type="hidden" name="action" value="change_role">
                                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                        <select name="vai_tro" class="form-control form-control-sm mr-2">
                                            <option value="user"  <?= $u['vai_tro']==='user'?'selected':'' ?>>User</option>
                                            <option value="staff" <?= $u['vai_tro']==='staff'?'selected':'' ?>>Staff</option>
                                            <option value="admin" <?= $u['vai_tro']==='admin'?'selected':'' ?>>Admin</option>
                                        </select>
                                        <button class="btn btn-sm btn-primary">Lưu</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(!$users): ?>
                            <tr><td colspan="5" class="text-center muted">Chưa có tài khoản.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- PRODUCTS: chỉ xem + link sang staff -->
        <?php if($tab==='products'): ?>
            <?php
              $products = $pdo->query("
                    SELECT sp.*, dm.ten_danh_muc
                    FROM san_pham sp
                    LEFT JOIN danh_muc dm ON dm.id=sp.danh_muc_id
                    ORDER BY sp.id DESC
              ")->fetchAll();
            ?>
            <div class="page-title">
                <h4 class="mb-0">Quản lý sản phẩm (Admin)</h4>
                <a href="staff.php?tab=products" class="btn btn-outline-primary btn-sm">
                    <i class="fa fa-external-link-alt mr-1"></i> Mở quản lý sản phẩm (Staff)
                </a>
            </div>

            <div class="cardx">
                <p class="muted mb-3">
                    Admin có thể duyệt & kiểm soát sản phẩm do Staff thêm.  
                    Để chỉnh sửa chi tiết, hãy mở tab sản phẩm trong <b>Staff Panel</b>.
                </p>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID</th><th>Tên</th><th>Danh mục</th><th>Giá</th>
                                <th>Tồn</th><th>Đã bán</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($products as $p): ?>
                            <tr>
                                <td><?= (int)$p['id'] ?></td>
                                <td><?= e($p['ten_san_pham']) ?></td>
                                <td><?= e($p['ten_danh_muc'] ?? '—') ?></td>
                                <td><?= number_format((float)$p['gia'],0,',','.') ?>₫</td>
                                <td><?= (int)$p['ton_kho'] ?></td>
                                <td><?= (int)$p['da_ban'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(!$products): ?>
                            <tr><td colspan="6" class="text-center muted">Chưa có sản phẩm.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- ORDERS: xem + link staff -->
        <?php if($tab==='orders'): ?>
            <div class="page-title">
                <h4 class="mb-0">Quản lý đơn hàng (Admin)</h4>
                <a href="staff.php?tab=orders" class="btn btn-outline-primary btn-sm">
                    <i class="fa fa-external-link-alt mr-1"></i> Mở quản lý đơn hàng (Staff)
                </a>
            </div>

            <div class="cardx">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID</th><th>Khách hàng</th><th>Email</th>
                                <th>Tổng tiền</th><th>Trạng thái</th><th>Ngày tạo</th>
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
                            </tr>
                        <?php endforeach; ?>
                        <?php if(!$orders): ?>
                            <tr><td colspan="6" class="text-center muted">Chưa có đơn hàng.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- REVIEWS: QUẢN LÝ ĐÁNH GIÁ SẢN PHẨM -->
        <?php if($tab==='reviews'): ?>
            <div class="page-title">
                <h4 class="mb-0">Quản lý đánh giá sản phẩm</h4>
            </div>

            <div class="cardx">
                <p class="muted mb-3">
                    Dữ liệu được gửi từ trang <b>tra_cuu.php → luu_danh_gia.php</b>.  
                    Tại đây Admin có thể xem nội dung và đổi trạng thái:
                    <b>chờ_duyệt</b> / <b>duyệt</b> / <b>ẩn</b>.
                </p>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID</th>
                                <th>Sản phẩm</th>
                                <th>Người dùng</th>
                                <th>Đơn hàng #</th>
                                <th>Số sao</th>
                                <th>Nhận xét</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($reviews as $rv): ?>
                            <?php
                                $st = $rv['trang_thai'];
                                $stClass = $st==='duyet' ? 'st-done' : ($st==='an' ? 'st-cancel' : 'st-pending');
                                $stLabel = $st==='duyet' ? 'Hiển thị' : ($st==='an' ? 'Ẩn' : 'Chờ duyệt');
                            ?>
                            <tr>
                                <td><?= (int)$rv['id'] ?></td>
                                <td><?= e($rv['ten_san_pham'] ?? ('#'.$rv['san_pham_id'])) ?></td>
                                <td><?= e($rv['ten_dang_nhap']) ?></td>
                                <td>#<?= (int)$rv['don_hang_id'] ?></td>
                                <td>
                                    <?php for($i=1;$i<=5;$i++): ?>
                                        <i class="fa fa-star <?= $i <= (int)$rv['so_sao'] ? 'text-warning' : 'text-muted' ?>"></i>
                                    <?php endfor; ?>
                                </td>
                                <td style="max-width:260px;white-space:pre-wrap;">
                                    <?= nl2br(e($rv['nhan_xet'])) ?>
                                </td>
                                <td>
                                    <span class="badge-status <?= $stClass ?>"><?= e($stLabel) ?></span>
                                </td>
                                <td><?= e($rv['ngay_tao']) ?></td>
                                <td>
                                    <form method="POST" class="form-inline">
                                        <?= csrf_field(); ?>
                                        <input type="hidden" name="action" value="update_review_status">
                                        <input type="hidden" name="id" value="<?= (int)$rv['id'] ?>">
                                        <select name="trang_thai" class="form-control form-control-sm mr-2">
                                            <option value="cho_duyet" <?= $st==='cho_duyet'?'selected':'' ?>>Chờ duyệt</option>
                                            <option value="duyet"     <?= $st==='duyet'?'selected':'' ?>>Duyệt & hiển thị</option>
                                            <option value="an"        <?= $st==='an'?'selected':'' ?>>Ẩn</option>
                                        </select>
                                        <button class="btn btn-sm btn-primary">Lưu</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(!$reviews): ?>
                            <tr><td colspan="9" class="text-center muted">Chưa có đánh giá nào.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- REPORTS -->
        <?php if($tab==='reports'): ?>
            <div class="page-title">
                <h4 class="mb-0">Thống kê & báo cáo (Admin)</h4>
            </div>

            <div class="stat mb-3">
                <div class="cardx item">
                    <div class="muted">Tổng doanh thu</div>
                    <div style="font-size:26px;font-weight:800;color:#d0021b;">
                        <?= number_format($total_revenue,0,',','.') ?>₫
                    </div>
                </div>
                <div class="cardx item">
                    <div class="muted">Đơn đã hoàn thành</div>
                    <div style="font-size:26px;font-weight:800;"><?= $total_done_orders ?></div>
                </div>
            </div>

            <div class="cardx mb-3">
                <h5 class="mb-3">Top 5 sản phẩm doanh thu cao</h5>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th><th>Sản phẩm</th><th>Số lượng</th><th>Doanh thu</th>
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
        <?php endif; ?>

        <!-- CONTENT MANAGEMENT -->
        <?php if($tab==='content'): ?>
            <div class="page-title">
                <h4 class="mb-0">Quản lý nội dung trang (banner, thông báo, khuyến mãi)</h4>
            </div>

            <div class="cardx">
                <form method="POST">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="action" value="save_content">

                    <div class="form-group">
                        <label>Tiêu đề banner trang chủ</label>
                        <input class="form-control" name="home_banner_title"
                               value="<?= e($siteContent['home_banner_title']) ?>">
                    </div>

                    <div class="form-group">
                        <label>Mô tả dưới banner</label>
                        <textarea class="form-control textarea-sm"
                                  name="home_banner_subtitle"><?= e($siteContent['home_banner_subtitle']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Thông báo (ví dụ: lịch làm việc, bảo hành...)</label>
                        <textarea class="form-control textarea-sm"
                                  name="announcement"><?= e($siteContent['announcement']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Chương trình khuyến mãi nổi bật</label>
                        <textarea class="form-control textarea-sm"
                                  name="promotion"><?= e($siteContent['promotion']) ?></textarea>
                    </div>

                    <button class="btn btn-primary">Lưu nội dung</button>
                </form>

                <hr>
                <p class="muted mb-0">
                    Dữ liệu nội dung được lưu trong file <code>data_site_content.json</code> cùng thư mục với
                    <code>admin.php</code>.  
                    Bạn có thể đọc file này từ <code>index.php</code> để hiển thị banner / thông báo / khuyến mãi.
                </p>
            </div>
        <?php endif; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
