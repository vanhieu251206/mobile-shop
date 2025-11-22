<?php
session_start();

// Kiểm tra đăng nhập
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Bắt buộc phải đăng nhập
function require_login() {
    if (!is_logged_in()) {
        header("Location: dang_nhap.php");
        exit();
    }
}

// Bắt buộc phải là admin
function require_admin() {
    require_login();
    if ($_SESSION['role'] !== 'admin') {
        die("Bạn không có quyền truy cập trang này.");
    }
}
?>
