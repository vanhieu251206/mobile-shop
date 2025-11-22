<?php
// Tên file: db.php (Tại thư mục gốc MOBILE-SHOP/)

// THÔNG SỐ KẾT NỐI CSDL
$host = 'localhost';
$db   = 'shop_phone4'; // Tên CSDL của bạn
$user = 'root'; 
$pass = ''; 
$charset = 'utf8mb4';
$port = 3309; // Sử dụng port 3309

$dsn = "mysql:host=$host;dbname=$db;charset=$charset;port=$port"; // Thêm port vào DSN
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Báo lỗi dưới dạng Exception
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,     // Trả về mảng kết hợp
    PDO::ATTR_EMULATE_PREPARES   => false,                // Vô hiệu hóa giả lập Prepared Statements
];

try {
     // Khởi tạo biến $pdo
     $pdo = new PDO($dsn, $user, $pass, $options);
     // echo "Kết nối thành công!"; // Bỏ dòng này trong môi trường production
} catch (\PDOException $e) {
     // Dừng và báo lỗi nếu kết nối thất bại
     die("Kết nối thất bại: " . $e->getMessage());
}
?>