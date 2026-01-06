<?php
// includes/config.php - FINAL V3: AUTO DETECT LOCALHOST & HOSTING

// 1. TỰ ĐỘNG PHÁT HIỆN MÔI TRƯỜNG
// Lấy tên miền hiện tại
$currentHost = $_SERVER['HTTP_HOST'];

// Nếu là Localhost HOẶC là đường dẫn Ngrok
if ($currentHost == 'localhost' || $currentHost == '127.0.0.1' || strpos($currentHost, 'ngrok') !== false) {

    // === CẤU HÌNH Ở MÁY TÍNH (LOCAL / XAMPP) ===
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');      // Mặc định Xampp là root
    define('DB_PASS', '');          // Mặc định Xampp không có pass
    define('DB_NAME', 'trong2k8shop'); // Tên database ở máy bạn

    // Đường dẫn gốc tự động
    define('BASE_URL', 'http://' . $currentHost . '/trong2k8shop/');
} else {

    // === CẤU HÌNH TRÊN HOSTING (ONLINE) ===
    define('DB_HOST', 'localhost');
    define('DB_USER', 'gtciqvsk_trong2k8shop');
    define('DB_PASS', 'Relive174@');
    define('DB_NAME', 'gtciqvsk_trong2k8shop');

    define('BASE_URL', 'https://trong2k8.xdhoaphat.com/');
}

// 2. KẾT NỐI DATABASE
try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Ghi lỗi ra file để debug nếu Bot bị im lặng
    file_put_contents('../bot_db_error.txt', $e->getMessage());
    // Hiển thị thông báo lỗi thân thiện hơn
    die("<h1>Lỗi kết nối Database!</h1><p>Vui lòng kiểm tra lại thông tin cấu hình trong includes/config.php</p><br>Chi tiết: " . $e->getMessage());
}