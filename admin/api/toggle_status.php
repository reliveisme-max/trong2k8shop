<?php
// admin/api/toggle_status.php
// FILE NÀY DÙNG ĐỂ BẬT/TẮT TRẠNG THÁI ACC NGAY TẠI DANH SÁCH

require_once '../auth.php';
require_once '../../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $id = isset($input['id']) ? (int)$input['id'] : 0;
    $status = isset($input['status']) ? (int)$input['status'] : 0;

    if ($id > 0) {
        try {
            // Cập nhật trạng thái
            $stmt = $conn->prepare("UPDATE products SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $status, ':id' => $id]);

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'msg' => 'Invalid ID']);
    }
}
