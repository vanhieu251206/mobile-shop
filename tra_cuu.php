<?php
session_start();
require_once 'db.php';

function e($str){ return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }

// Đơn mới vừa đặt (nếu có) – dùng để highlight
$highlightId = isset($_SESSION['last_order_id']) ? (int)$_SESSION['last_order_id'] : 0;
// Vào trang tra cứu rồi thì tắt báo nháy
unset($_SESSION['last_order_id']);

$userId = $_SESSION['user_id'] ?? 0;
$orders = [];
$error  = '';

// Thông báo sau khi gửi đánh giá
$ratingStatus = $_GET['rv'] ?? ''; // rv=ok từ luu_danh_gia.php

// ====== LẤY ĐƠN HÀNG ======

// Nếu ĐÃ đăng nhập: lấy đơn theo nguoi_dung_id (an toàn vì cột này chắc chắn có)
if ($userId) {
    $ma = trim($_GET['ma'] ?? '');
    if ($ma !== '') {
        $stm = $pdo->prepare("
            SELECT * FROM don_hang
            WHERE id = ? AND nguoi_dung_id = ?
            ORDER BY id DESC
        ");
        $stm->execute([(int)$ma, $userId]);
    } else {
        $stm = $pdo->prepare("
            SELECT * FROM don_hang
            WHERE nguoi_dung_id = ?
            ORDER BY id DESC
        ");
        $stm->execute([$userId]);
    }
    $orders = $stm->fetchAll();
} else {
    // KHÔNG đăng nhập: cho tra cứu nhanh CHỈ BẰNG MÃ ĐƠN (vì DB chưa lưu SĐT)
    if (isset($_GET['guest_lookup'])) {
        $ma   = (int)($_GET['ma'] ?? 0);

        if ($ma <= 0) {
            $error = "Vui lòng nhập Mã đơn hàng.";
        } else {
            $stm = $pdo->prepare("SELECT * FROM don_hang WHERE id = ? LIMIT 1");
            $stm->execute([$ma]);
            $row = $stm->fetch();
            if ($row) {
                $orders[] = $row;
            } else {
                $error = "Không tìm thấy đơn hàng phù hợp.";
            }
        }
    }
}

// Chuẩn bị statement lấy chi tiết sản phẩm
$itemStmt = $pdo->prepare("
    SELECT ctdh.*, sp.ten_san_pham
    FROM chi_tiet_don_hang ctdh
    JOIN san_pham sp ON sp.id = ctdh.san_pham_id
    WHERE ctdh.don_hang_id = ?
");

function status_label($st){
    switch ($st){
        case 'hoan_thanh': return ['Đã hoàn thành', 'success'];
        case 'huy':        return ['Đã hủy', 'danger'];
        default:           return ['Chờ xử lý', 'warning'];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Tra cứu đơn hàng - MobiShop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap & Font Awesome -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <link rel="stylesheet" href="css/style.css">

    <style>
        body{background:#f3f4f6;}
        .page-wrap{max-width:960px;margin:30px auto;}
        .order-card{border-radius:14px;border:none;box-shadow:0 6px 18px rgba(0,0,0,.06);}
        .badge-status{padding:4px 10px;border-radius:999px;font-size:12px;font-weight:600;}
        .badge-status.success{background:#e8fff0;color:#1a7f37;}
        .badge-status.warning{background:#fff7d9;color:#a16207;}
        .badge-status.danger{background:#ffe8e8;color:#a12b2b;}
        .order-highlight{border:2px solid #2563eb;}
        .order-highlight-label{
            font-size:11px;
            font-weight:700;
            color:#2563eb;
            text-transform:uppercase;
            letter-spacing:.5px;
        }
        .order-items td{font-size:14px;}

        /* Rating stars trong popup */
        .rating-stars{
            font-size: 26px;
        }
        .rating-stars i{
            cursor: pointer;
            margin-right: 4px;
        }
        .rating-stars i.active{
            color: #ffc107;
        }
    </style>
</head>
<body>

<div class="page-wrap">
    <div class="mb-4">
        <h3 class="mb-1"><i class="fa fa-search mr-2 text-primary"></i>Tra cứu đơn hàng</h3>
        <p class="text-muted mb-0">
            Xem lại các đơn bạn đã đặt tại MobiShop và theo dõi trạng thái xử lý.
        </p>
        <?php if(!$userId): ?>
            <small class="text-muted">
                (Đăng nhập để có thể đánh giá / viết nhận xét cho sản phẩm bạn đã mua.)
            </small>
        <?php endif; ?>
    </div>

    <?php if ($highlightId): ?>
        <div class="alert alert-success">
            🎉 Bạn vừa đặt thành công đơn hàng <strong>#<?= $highlightId ?></strong>.
            Bạn có thể xem chi tiết bên dưới.
        </div>
    <?php endif; ?>

    <?php if ($ratingStatus === 'ok'): ?>
        <div class="alert alert-success">
            ✅ Gửi đánh giá thành công. Cảm ơn bạn đã phản hồi về sản phẩm!
        </div>
    <?php elseif ($ratingStatus === 'err'): ?>
        <div class="alert alert-danger">
            ❌ Gửi đánh giá thất bại. Vui lòng thử lại sau.
        </div>
    <?php endif; ?>

    <!-- Form tìm kiếm -->
    <div class="card mb-4">
        <div class="card-body">
            <?php if ($userId): ?>
                <h5 class="card-title mb-3">Tài khoản: <?= e($_SESSION['username'] ?? '') ?></h5>
                <form method="get" class="form-inline">
                    <label class="mr-2">Mã đơn hàng (nếu muốn lọc):</label>
                    <input type="number" name="ma" class="form-control mr-2"
                           value="<?= isset($_GET['ma']) ? (int)$_GET['ma'] : '' ?>"
                           placeholder="Ví dụ: 9">
                    <button class="btn btn-primary">Lọc</button>
                    <?php if (!empty($_GET['ma'])): ?>
                        <a href="tra_cuu.php" class="btn btn-link">Xem tất cả</a>
                    <?php endif; ?>
                </form>
            <?php else: ?>
                <h5 class="card-title mb-3">Tra cứu nhanh (không cần đăng nhập)</h5>
                <?php if($error): ?>
                    <div class="alert alert-danger py-2"><?= e($error) ?></div>
                <?php endif; ?>
                <form method="get">
                    <input type="hidden" name="guest_lookup" value="1">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Mã đơn hàng</label>
                            <input type="number" name="ma" class="form-control"
                                   value="<?= isset($_GET['ma']) ? (int)$_GET['ma'] : '' ?>">
                        </div>
                        <div class="form-group col-md-4 d-flex align-items-end">
                            <button class="btn btn-primary btn-block">Tra cứu</button>
                        </div>
                    </div>
                    <small class="text-muted">
                        (Hiện DB chưa lưu số điện thoại, nên chỉ tra cứu được theo mã đơn hàng.)
                    </small>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Danh sách đơn -->
    <?php if (!$orders): ?>
        <div class="alert alert-info">
            Hiện chưa có đơn hàng nào phù hợp với điều kiện tra cứu.
        </div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <?php
                list($stText, $stClass) = status_label($order['trang_thai'] ?? '');
                $isHighlight = ($order['id'] == $highlightId);
                $itemStmt->execute([$order['id']]);
                $items = $itemStmt->fetchAll();

                // DB cũ chưa có đầy đủ cột => fallback
                $tenNhan  = $order['ten_nguoi_nhan']  ?? ($order['ten_khach_hang'] ?? '');
                $sdtNhan  = $order['sdt_nguoi_nhan']  ?? ($order['sdt_khach_hang'] ?? '');
                $diaChi   = $order['dia_chi_giao']    ?? '';
                $ghiChu   = $order['ghi_chu_giao']    ?? ($order['ghi_chu_khach'] ?? '');
                $payLabel = $order['phuong_thuc_thanh_toan'] ?? 'cod';
            ?>
            <div class="card mb-3 order-card <?= $isHighlight ? 'order-highlight' : '' ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <div class="d-flex align-items-center">
                                <h5 class="mb-0 mr-2">Đơn hàng #<?= (int)$order['id'] ?></h5>
                                <span class="badge-status <?= $stClass ?>"><?= $stText ?></span>
                            </div>
                            <?php if ($isHighlight): ?>
                                <div class="order-highlight-label mt-1">
                                    Đơn mới đặt
                                </div>
                            <?php endif; ?>
                            <small class="text-muted">Ngày tạo: <?= e($order['ngay_tao'] ?? '') ?></small>
                        </div>
                        <div class="text-right">
                            <div class="font-weight-bold text-danger" style="font-size:18px;">
                                <?= number_format((float)($order['tong_tien'] ?? 0),0,',','.') ?>₫
                            </div>
                            <small class="text-muted">
                                Phương thức: <?= e(strtoupper($payLabel)) ?>
                            </small>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <h6 class="text-muted text-uppercase" style="font-size:12px;">Thông tin nhận hàng</h6>
                            <?php if($tenNhan || $sdtNhan || $diaChi || $ghiChu): ?>
                                <p class="mb-1"><strong>Người nhận:</strong> <?= e($tenNhan) ?></p>
                                <p class="mb-1"><strong>SĐT:</strong> <?= e($sdtNhan) ?></p>
                                <p class="mb-1"><strong>Địa chỉ:</strong> <?= e($diaChi) ?></p>
                                <?php if($ghiChu !== ''): ?>
                                    <p class="mb-0"><strong>Ghi chú:</strong> <?= e($ghiChu) ?></p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="text-muted mb-0">
                                    (Đơn cũ – hệ thống chưa lưu thông tin người nhận / địa chỉ.)
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-7">
                            <h6 class="text-muted text-uppercase" style="font-size:12px;">Sản phẩm</h6>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0 order-items">
                                    <thead>
                                        <tr>
                                            <th>Sản phẩm</th>
                                            <th class="text-center">SL</th>
                                            <th class="text-right">Thành tiền</th>
                                            <?php if($userId): ?>
                                                <th class="text-center">Đánh giá</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach($items as $it): ?>
                                        <tr>
                                            <td><?= e($it['ten_san_pham']) ?></td>
                                            <td class="text-center"><?= (int)$it['so_luong'] ?></td>
                                            <td class="text-right">
                                                <?= number_format(
                                                    (float)$it['so_luong'] * (float)$it['gia'],
                                                    0,',','.'
                                                ) ?>₫
                                            </td>
                                            <?php if($userId): ?>
                                                <td class="text-center">
                                                    <button type="button"
                                                            class="btn btn-sm btn-outline-primary btn-review"
                                                            data-toggle="modal"
                                                            data-target="#reviewModal"
                                                            data-order="<?= (int)$order['id'] ?>"
                                                            data-product="<?= (int)$it['san_pham_id'] ?>"
                                                            data-name="<?= e($it['ten_san_pham']) ?>">
                                                        Đánh giá
                                                    </button>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="mt-3">
        <a href="index.php" class="btn btn-light"><i class="fa fa-home mr-1"></i> Về trang chủ</a>
        <a href="shop.php" class="btn btn-primary ml-2"><i class="fa fa-shopping-bag mr-1"></i> Tiếp tục mua sắm</a>
    </div>
</div>

<?php if($userId): ?>
<!-- Modal đánh giá -->
<div class="modal fade" id="reviewModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <form method="post" action="luu_danh_gia.php" id="reviewForm">
        <div class="modal-header">
          <h5 class="modal-title">Đánh giá sản phẩm</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="san_pham_id" id="rvProductId">
            <input type="hidden" name="don_hang_id" id="rvOrderId">
            <input type="hidden" name="so_sao" id="rvRating" value="5">

            <p class="mb-1 text-muted">Sản phẩm:</p>
            <p class="font-weight-bold" id="rvProductName"></p>

            <div class="mb-3">
                <label class="mb-1">Chọn số sao:</label>
                <div class="rating-stars" id="rvStars">
                    <i class="fas fa-star rating-star" data-value="1"></i>
                    <i class="fas fa-star rating-star" data-value="2"></i>
                    <i class="fas fa-star rating-star" data-value="3"></i>
                    <i class="fas fa-star rating-star" data-value="4"></i>
                    <i class="fas fa-star rating-star" data-value="5"></i>
                </div>
            </div>

            <div class="form-group mb-0">
                <label>Nhận xét của bạn</label>
                <textarea name="noi_dung" class="form-control" rows="4"
                          placeholder="Chia sẻ trải nghiệm sử dụng sản phẩm..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-primary">
            Gửi đánh giá
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<?php if($userId): ?>
<script>
// Khi mở modal: set thông tin sản phẩm / đơn
$('#reviewModal').on('show.bs.modal', function (event) {
    var button   = $(event.relatedTarget);
    var prodId   = button.data('product');
    var orderId  = button.data('order');
    var prodName = button.data('name');

    $('#rvProductId').val(prodId);
    $('#rvOrderId').val(orderId);
    $('#rvProductName').text(prodName);

    // reset sao về 5 sao
    var $stars = $('#rvStars .rating-star');
    $stars.removeClass('active');
    $stars.each(function(){
        if (parseInt($(this).data('value')) <= 5) {
            $(this).addClass('active');
        }
    });
    $('#rvRating').val(5);
});

// Chọn số sao
$(document).on('click', '.rating-star', function(){
    var value = parseInt($(this).data('value'));
    $('#rvRating').val(value);

    $('#rvStars .rating-star').each(function(){
        var v = parseInt($(this).data('value'));
        if (v <= value) {
            $(this).addClass('active');
        } else {
            $(this).removeClass('active');
        }
    });
});
</script>
<?php endif; ?>

</body>
</html>
