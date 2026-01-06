<?php
// admin/setup.php
require_once '../includes/config.php';

try {
    // 1. Tạo bảng admins
    $sql = "CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL
    )";
    $conn->exec($sql);

    // 2. Kiểm tra xem có admin nào chưa
    $stmt = $conn->prepare("SELECT COUNT(*) FROM admins");
    $stmt->execute();

    if ($stmt->fetchColumn() == 0) {
        // 3. Tạo tài khoản mặc định: admin / 123456
        $user = 'admin';
        $pass = '123456';
        $hash = password_hash($pass, PASSWORD_DEFAULT);

        $insert = $conn->prepare("INSERT INTO admins (username, password) VALUES (:u, :p)");
        $insert->execute([':u' => $user, ':p' => $hash]);

        echo "<h1 style='color:green'>Đã tạo bảng 'admins' thành công!</h1>";
        echo "Tài khoản mặc định: <b>admin</b><br>";
        echo "Mật khẩu mặc định: <b>123456</b><br><br>";
        echo "<a href='login.php'>Bấm vào đây để Đăng nhập</a>";
    } else {
        echo "<h3>Bảng 'admins' đã tồn tại rồi!</h3>";
        echo "<a href='login.php'>Quay lại đăng nhập</a>";
    }
} catch (PDOException $e) {
    die("Lỗi: " . $e->getMessage());
}