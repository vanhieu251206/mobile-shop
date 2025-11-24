<?php
// Tránh warning nếu file khác đã session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Lấy vai trò hiện tại:
 * - Ưu tiên 'vai_tro' (đúng theo DB)
 * - Tương thích ngược 'role'
 */
function current_role(): string {
    return $_SESSION['vai_tro'] ?? $_SESSION['role'] ?? 'guest';
}

// Kiểm tra đăng nhập
function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0;
}

// Bắt buộc phải đăng nhập
function require_login(string $redirect = "dangnhap.php"): void {
    if (!is_logged_in()) {
        header("Location: $redirect");
        exit();
    }
}

/**
 * Bắt buộc phải thuộc 1 trong những role cho phép
 * Ví dụ: require_roles(['admin','staff'])
 */
function require_roles(array $roles, string $redirect = "dangnhap.php"): void {
    require_login($redirect);
    $role = current_role();
    if (!in_array($role, $roles, true)) {
        http_response_code(403);
        die("Bạn không có quyền truy cập trang này.");
    }
}

// Bắt buộc phải là admin
function require_admin(string $redirect = "dangnhap.php"): void {
    require_roles(['admin'], $redirect);
}

/**
 * Staff page (hiện DB chưa có staff)
 * => tạm coi staff = admin
 * Sau này nếu thêm staff vào DB chỉ cần:
 * require_roles(['admin','staff'])
 */
function require_staff(string $redirect = "dangnhap.php"): void {
    require_roles(['admin'], $redirect);
}
