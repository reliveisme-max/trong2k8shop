<?php
// admin/api/get_product_info.php
// API LẤY THÔNG TIN CHI TIẾT 1 ACC (DÙNG CHO MODAL SỬA NHANH)

// Khởi động session để kiểm tra quyền Admin
session_start();

require_once '../../includes/config.php';

header('Content-Type: application/json');

// 1. Kiểm tra quyền Admin
// Nếu không phải Admin thì chặn luôn
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Unauthorized']);
    exit;
}

// 2. Kiểm tra ID
if (!isset($_GET['id'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Missing ID']);
    exit;
}

$id = (int)$_GET['id'];

try {
    $stmt = $conn->prepare("SELECT id, title, price, category_id, status, private_note FROM products WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        // Trả về dữ liệu
        echo json_encode(['status' => 'success', 'data' => $product]);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Không tìm thấy Acc']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
