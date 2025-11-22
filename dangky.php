<?php
require_once 'db.php';
session_start();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ten_dang_nhap = trim($_POST['ten_dang_nhap'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mat_khau = $_POST['mat_khau'] ?? '';
    $mat_khau2 = $_POST['mat_khau2'] ?? '';

    if (empty($ten_dang_nhap) || empty($email) || empty($mat_khau) || empty($mat_khau2)) {
        $error = "Vui lòng điền đầy đủ thông tin.";
    } elseif ($mat_khau !== $mat_khau2) {
        $error = "Mật khẩu nhập lại không khớp.";
    } else {
        // Kiểm tra username/email đã tồn tại chưa
        $stmt = $pdo->prepare("SELECT id FROM nguoi_dung WHERE ten_dang_nhap=? OR email=?");
        $stmt->execute([$ten_dang_nhap, $email]);
        if ($stmt->fetch()) {
            $error = "Tên đăng nhập hoặc email đã tồn tại.";
        } else {
            $hashed = password_hash($mat_khau, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO nguoi_dung (ten_dang_nhap, email, mat_khau, vai_tro) VALUES (?, ?, ?, 'user')");
            $stmt->execute([$ten_dang_nhap, $email, $hashed]);
            $success = "Đăng ký thành công. Bạn có thể đăng nhập ngay.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Đăng ký Zalo Style</title>
<style>
:root {
    --primary-color: #0073e6; /* xanh Zalo */
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
.register-container {
    background: #fff;
    padding: 30px 25px;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    width: 100%;
    max-width: 360px;
    text-align: center;
}
h2 {
    color: var(--primary-color);
    margin-bottom: 25px;
    font-size: 24px;
}
input[type="text"], input[type="email"], input[type="password"] {
    width: calc(100% - 20px);
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 16px;
}
button {
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
button:hover {
    background-color: var(--btn-hover);
}
.error-message {
    color: red;
    margin-bottom: 15px;
}
.success-message {
    color: green;
    margin-bottom: 15px;
}
.login-link {
    margin-top: 10px;
    font-size: 14px;
}
.login-link a {
    color: var(--primary-color);
    font-weight: bold;
    text-decoration: none;
}
</style>
</head>
<body>
<div class="register-container">
    <h2>Đăng Ký</h2>

    <?php if($error): ?>
        <p class="error-message"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if($success): ?>
        <p class="success-message"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="ten_dang_nhap" placeholder="Tên đăng nhập" required value="<?= htmlspecialchars($_POST['ten_dang_nhap'] ?? '') ?>">
        <input type="email" name="email" placeholder="Email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        <input type="password" name="mat_khau" placeholder="Mật khẩu" required>
        <input type="password" name="mat_khau2" placeholder="Nhập lại mật khẩu" required>
        <button type="submit">Đăng ký</button>
    </form>

    <div class="login-link">
        Bạn đã có tài khoản? <a href="dangnhap.php">Đăng nhập</a>
    </div>
</div>
</body>
</html>
