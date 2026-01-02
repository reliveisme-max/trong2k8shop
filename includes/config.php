<?php
// includes/config.php

// 1. Thông tin cấu hình Database
// Nếu bạn dùng XAMPP mặc định thì giữ nguyên, nếu dùng Hosting thì sửa lại theo Hosting cung cấp
define('DB_HOST', 'localhost');
define('DB_NAME', 'trong2k8shop'); // Tên DB bạn vừa tạo
define('DB_USER', 'root');         // User mặc định của XAMPP
define('DB_PASS', '');             // Pass mặc định của XAMPP là rỗng

// 2. Thực hiện kết nối bằng PDO (Chuẩn an toàn hiện nay)
try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);

    // Cấu hình chế độ báo lỗi: Ném ra ngoại lệ (Exception) để dễ debug
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Cấu hình chế độ lấy dữ liệu mặc định: Lấy dạng Mảng kết hợp (Array Key-Value)
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // echo "Kết nối thành công!"; // Bỏ comment dòng này nếu muốn test
} catch (PDOException $e) {
    // Nếu lỗi thì dừng chương trình và báo lỗi
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}