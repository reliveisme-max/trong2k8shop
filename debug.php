<?php
// debug.php - KIỂM TRA DỮ LIỆU
require_once 'includes/config.php';

echo "<h1>🛠 CÔNG CỤ DEBUG LỖI</h1>";

try {
    // 1. Kiểm tra cấu trúc bảng
    echo "<h3>1. Kiểm tra cấu trúc bảng 'products':</h3>";
    $stmt = $conn->query("DESCRIBE products");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $hasPriceRent = in_array('price_rent', $columns);

    if ($hasPriceRent) {
        echo "<p style='color:green'>✅ Cột <b>price_rent</b> đã có. (OK)</p>";
    } else {
        echo "<p style='color:red'>❌ THIẾU CỘT <b>price_rent</b>!</p>";
        echo "<p>👉 Bạn cần chạy file <b>admin/update_db_new.php</b> ngay.</p>";
    }

    // 2. Kiểm tra dữ liệu
    echo "<h3>2. Kiểm tra dữ liệu acc mới nhất:</h3>";
    $stmt = $conn->query("SELECT id, title, price, price_rent, status, type FROM products ORDER BY id DESC LIMIT 5");
    $items = $stmt->fetchAll();

    if (count($items) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse'>";
        echo "<tr><th>ID</th><th>Tên</th><th>Giá Bán</th><th>Giá Thuê</th><th>Status</th><th>Type</th><th>Hiện ở đâu?</th></tr>";
        foreach ($items as $row) {
            $showOn = [];
            if ($row['status'] == 1) {
                if ($row['price'] > 0) $showOn[] = "Tab BÁN";
                if (isset($row['price_rent']) && $row['price_rent'] > 0) $showOn[] = "Tab THUÊ";
                if (empty($showOn)) $showOn[] = "⚠️ ẨN (Do cả 2 giá đều = 0)";
            } else {
                $showOn[] = "⚠️ ẨN (Do Status = 0)";
            }

            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['title']}</td>";
            echo "<td>" . number_format($row['price']) . "</td>";
            echo "<td>" . (isset($row['price_rent']) ? number_format($row['price_rent']) : 'N/A') . "</td>";
            echo "<td>{$row['status']}</td>";
            echo "<td>{$row['type']}</td>";
            echo "<td>" . implode(" + ", $showOn) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red'>❌ Database đang trống trơn! (Chưa thêm được dòng nào)</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ LỖI SQL: " . $e->getMessage() . "</p>";
}