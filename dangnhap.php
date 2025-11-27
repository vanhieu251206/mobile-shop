<?php
require_once 'db.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['ten_dang_nhap'] ?? ''); // username OR email
    $mat_khau   = $_POST['mat_khau'] ?? '';

    if ($identifier === '' || $mat_khau === '') {
        $error = "Vui lòng nhập đầy đủ thông tin.";
    } else {
        // tìm theo username hoặc email
        $stmt = $pdo->prepare("
            SELECT id, ten_dang_nhap, email, mat_khau, vai_tro
            FROM nguoi_dung
            WHERE ten_dang_nhap = ? OR email = ?
            LIMIT 1
        ");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = "Không tìm thấy tài khoản.";
        } elseif (!password_verify($mat_khau, $user['mat_khau'])) {
            $error = "Tên đăng nhập/email hoặc mật khẩu không đúng.";
        } else {
            session_regenerate_id(true);

            $_SESSION['user_id']  = (int)$user['id'];
            $_SESSION['username'] = $user['ten_dang_nhap'];
            $_SESSION['vai_tro']  = $user['vai_tro'];
            $_SESSION['role']     = $user['vai_tro']; // tương thích code cũ

            header("Location: index.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập</title>
    <style>
        :root {
            --primary-color: #0073e6;
            --btn-hover: #005bb5;
            --font-family: Arial, sans-serif;
        }
        body {
            margin: 0;
            font-family: var(--font-family);
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .login-container {
            background: #fff;
            padding: 30px 25px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 380px;
            text-align: center;
        }
        h2 {
            color: var(--primary-color);
            margin-bottom: 18px;
            font-size: 24px;
        }

        /* tabs */
        .role-switch {
            display: grid;
            grid-template-columns: repeat(3, 1fr); /* 3 cột: Khách / Staff / Admin */
            gap: 6px;
            background: #f3f4f6;
            padding: 6px;
            border-radius: 8px;
            margin-bottom: 14px;
        }
        .role-switch .tab {
            padding: 8px 6px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 13px;
            color: #333;
            text-decoration: none;
            display: block;
            transition: .15s;
            border: none;
            background: transparent;
            cursor: pointer;
        }
        .role-switch .active {
            background: #fff;
            color: var(--primary-color);
            box-shadow: 0 1px 4px rgba(0,0,0,.08);
        }

        input[type="text"], input[type="password"] {
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
        }
        button[type="submit"] {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: 0.2s;
        }
        button[type="submit"]:hover { background-color: var(--btn-hover); }
        .error-message { color: red; margin-bottom: 12px; }

        .note {
            font-size: 13px;
            color: #666;
            background: #f8fafc;
            border: 1px dashed #ddd;
            padding: 8px;
            border-radius: 6px;
            margin-top: 10px;
            text-align: left;
        }

        .register-link {
            margin-top: 12px;
            font-size: 14px;
        }
        .register-link a {
            color: var(--primary-color);
            font-weight: bold;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="login-container">
    <h2>Đăng Nhập</h2>

    <!-- Tabs: Khách hàng / Staff / Admin -->
    <div class="role-switch">
        <div class="tab active">Khách hàng</div>
        <button type="button" class="tab" id="staffTab">Staff</button>
        <button type="button" class="tab" id="adminTab">Admin</button>
    </div>

    <?php if($error): ?>
        <p class="error-message"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <!-- Form login khách hàng -->
    <form method="POST">
        <input type="text" name="ten_dang_nhap" placeholder="Tên đăng nhập hoặc email" required>
        <input type="password" name="mat_khau" placeholder="Mật khẩu" required>
        <button type="submit">Đăng nhập</button>
    </form>

    <div class="note">
        <strong>DEV:</strong> Nếu muốn test nhanh quyền nhân viên hoặc admin,
        hãy bấm tab <b>Staff</b> hoặc <b>Admin</b> phía trên.  
        Hệ thống sẽ tự động vào <code>staff.php?dev=1</code> hoặc
        <code>admin.php?dev=1</code> mà không cần nhập tài khoản.
    </div>

    <div class="register-link">
        Chưa có tài khoản? <a href="dangky.php">Đăng ký ngay</a>
    </div>
</div>

<script>
    // Bấm tab Staff => tự động vào staff.php?dev=1
    document.getElementById('staffTab').addEventListener('click', function () {
        window.location.href = 'staff.php?dev=1';
    });

    // Bấm tab Admin => tự động vào admin.php?dev=1
    document.getElementById('adminTab').addEventListener('click', function () {
        window.location.href = 'admin.php?dev=1';
    });
</script>
</body>
</html>
