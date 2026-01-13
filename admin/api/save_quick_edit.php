<?php
// admin/api/save_quick_edit.php
// API LƯU THÔNG TIN SỬA NHANH TỪ TRANG CHỦ

session_start();
require_once '../../includes/config.php';

header('Content-Type: application/json');

// 1. Check Quyền Admin (Bắt buộc)
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Bạn không có quyền này!']);
    exit;
}

// 2. Nhận dữ liệu JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['id'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Dữ liệu không hợp lệ']);
    exit;
}

$id = (int)$input['id'];
$title = trim($input['title'] ?? '');
$catId = (int)($input['category_id'] ?? 0);
$status = (int)($input['status'] ?? 1);
$note = trim($input['private_note'] ?? '');
$priceRaw = strtolower(trim($input['price'] ?? '0'));

// 3. Xử lý giá tiền (Logic 5m -> 5.000.000)
$price = 0;
$cleanVal = str_replace([',', '.'], '', $priceRaw);

if (strpos($cleanVal, 'm') !== false || strpos($cleanVal, 'tr') !== false) {
    $val = (float)preg_replace('/[^0-9.]/', '', $cleanVal);
    $price = $val * 1000000;
} elseif (strpos($cleanVal, 'k') !== false) {
    $val = (float)preg_replace('/[^0-9.]/', '', $cleanVal);
    $price = $val * 1000;
} else {
    $price = (int)preg_replace('/[^0-9]/', '', $cleanVal);
}

// 4. Xử lý Tên Acc (Nếu để trống -> Lấy ID làm tên)
if ($title === '') {
    $title = (string)$id;
}

try {
    $sql = "UPDATE products SET 
                title = :title, 
                category_id = :cat, 
                price = :price, 
                status = :status, 
                private_note = :note 
            WHERE id = :id";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':title' => $title,
        ':cat' => $catId,
        ':price' => $price,
        ':status' => $status,
        ':note' => $note,
        ':id' => $id
    ]);

    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => 'Lỗi DB: ' . $e->getMessage()]);
}
