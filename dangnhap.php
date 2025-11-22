<?php
require_once 'db.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ten_dang_nhap = trim($_POST['ten_dang_nhap'] ?? '');
    $mat_khau = $_POST['mat_khau'] ?? '';

    if ($ten_dang_nhap && $mat_khau) {
        $stmt = $pdo->prepare("SELECT * FROM nguoi_dung WHERE ten_dang_nhap=?");
        $stmt->execute([$ten_dang_nhap]);
        $user = $stmt->fetch();

        if ($user && password_verify($mat_khau, $user['mat_khau'])) {
            // Lưu session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['ten_dang_nhap'];
            $_SESSION['role'] = $user['vai_tro'];

            // Chuyển hướng theo vai trò
            if ($user['vai_tro'] === 'admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: index.php");
            }
            exit;
        } else {
            $error = "Tên đăng nhập hoặc mật khẩu không đúng.";
        }
    } else {
        $error = "Vui lòng nhập đầy đủ thông tin.";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Đăng nhập Zalo Style</title>
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
.login-container {
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
input[type="text"], input[type="password"] {
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
.separator {
    margin: 20px 0;
    position: relative;
    text-align: center;
    color: #606770;
    font-size: 14px;
}
.separator::before, .separator::after {
    content: "";
    position: absolute;
    top: 50%;
    width: 40%;
    height: 1px;
    background-color: #ddd;
}
.separator::before { left: 0; }
.separator::after { right: 0; }

/* Button Google/Email style */
.btn-google {
    display: block;
    width: 100%;
    padding: 12px;
    background-color: #009944;
    color: white;
    border-radius: 6px;
    font-weight: bold;
    text-decoration: none;
    margin-bottom: 15px;
    transition: 0.2s;
}
.btn-google:hover {
    background-color: #008033;
}

.error-message {
    color: red;
    margin-bottom: 15px;
}

.register-link {
    margin-top: 10px;
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

    <?php if($error): ?>
        <p class="error-message"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="ten_dang_nhap" placeholder="Tên đăng nhập" required>
        <input type="password" name="mat_khau" placeholder="Mật khẩu" required>
        <button type="submit">Đăng nhập</button>
    </form>

    

    <div class="register-link">
        Chưa có tài khoản? <a href="dangky.php">Đăng ký ngay</a>
    </div>
</div>
</body>
</html>
