<?php
// admin/api/bulk_edit.php
// XỬ LÝ SỬA HÀNG LOẠT (TRẠNG THÁI, DANH MỤC, GIÁ)

require_once '../auth.php';
require_once '../../includes/config.php';

header('Content-Type: application/json');

// Chỉ nhận POST JSON
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'msg' => 'Invalid Request']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['ids']) || empty($input['action'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Thiếu dữ liệu đầu vào']);
    exit;
}

$ids = $input['ids'];   // Mảng ID (ví dụ: [1, 2, 3])
$action = $input['action']; // 'status', 'category', hoặc 'price'
$value = $input['value'];   // Giá trị mới

// Validate ID: Chắc chắn là số nguyên
$ids = array_map('intval', $ids);
$idsList = implode(',', $ids); // Biến thành chuỗi "1,2,3" để dùng trong SQL IN

try {
    $sql = "";
    $params = [];

    switch ($action) {
        case 'status':
            // Cập nhật Trạng thái (0 hoặc 1)
            $sql = "UPDATE products SET status = :val WHERE id IN ($idsList)";
            $params[':val'] = (int)$value;
            break;

        case 'category':
            // Cập nhật Danh mục
            $sql = "UPDATE products SET category_id = :val WHERE id IN ($idsList)";
            $params[':val'] = (int)$value;
            break;

        case 'price':
            // Cập nhật Giá tiền
            // Xóa dấu chấm, phẩy trước khi lưu (VD: 500.000 -> 500000)
            $cleanPrice = (int)preg_replace('/[^0-9]/', '', $value);
            $sql = "UPDATE products SET price = :val WHERE id IN ($idsList)";
            $params[':val'] = $cleanPrice;
            break;

        default:
            echo json_encode(['status' => 'error', 'msg' => 'Hành động không hợp lệ']);
            exit;
    }

    // Thực thi
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => 'Lỗi Database: ' . $e->getMessage()]);
}
