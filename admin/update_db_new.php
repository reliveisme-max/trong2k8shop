<?php
// admin/update_db_new.php
// CHẠY FILE NÀY 1 LẦN DUY NHẤT ĐỂ NÂNG CẤP DATABASE

require_once '../includes/config.php';

echo "<h2>Đang cập nhật Database...</h2>";

try {
    // 1. Thêm cột price_rent (Giá thuê) nếu chưa có
    // Mặc định giá trị là 0
    $sql = "ALTER TABLE products ADD COLUMN price_rent INT DEFAULT 0 AFTER price";
    $conn->exec($sql);
    echo "<p style='color:green'>✅ Đã thêm cột <b>price_rent</b> thành công.</p>";
} catch (PDOException $e) {
    // Nếu lỗi code 42S21 (Duplicate column) nghĩa là đã có rồi
    if ($e->getCode() == '42S21') {
        echo "<p style='color:orange'>⚠️ Cột <b>price_rent</b> đã tồn tại (Không cần thêm lại).</p>";
    } else {
        echo "<p style='color:red'>❌ Lỗi: " . $e->getMessage() . "</p>";
    }
}

echo "<h3>🎉 Xong! Bây giờ bạn có thể xóa file này và quay lại trang Admin.</h3>";
echo "<a href='index.php'>Về trang chủ Admin</a>";