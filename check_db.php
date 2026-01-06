<?php
// check_db.php - Kiểm tra kết nối
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Đang thử kết nối Database...</h2>";

// Thông tin lấy từ config cũ của bạn
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'trong2k8shop'; // <--- Kiểm tra kỹ cái tên này

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<h1 style='color:green'>✅ KẾT NỐI THÀNH CÔNG!</h1>";
    echo "Database '$dbname' đang hoạt động tốt.";
} catch (PDOException $e) {
    echo "<h1 style='color:red'>❌ KẾT NỐI THẤT BẠI!</h1>";
    echo "<h3>Lỗi cụ thể: " . $e->getMessage() . "</h3>";

    if (strpos($e->getMessage(), 'Unknown database') !== false) {
        echo "<p>👉 Nguyên nhân: Bạn chưa tạo Database tên là <b>$dbname</b> trong phpMyAdmin.</p>";
    }
}