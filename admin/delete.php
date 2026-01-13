<?php
// admin/delete.php - FIX: REDIRECT OPTION
require_once 'auth.php';
require_once '../includes/config.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // 1. Lấy thông tin ảnh để dọn rác
    $stmt = $conn->prepare("SELECT thumb, gallery FROM products WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $product = $stmt->fetch();

    if ($product) {
        // Xóa ảnh bìa
        if (!empty($product['thumb'])) {
            $path = "../uploads/" . $product['thumb'];
            if (file_exists($path)) @unlink($path);
        }

        // Xóa album ảnh
        $gallery = json_decode($product['gallery'], true);
        if (is_array($gallery)) {
            foreach ($gallery as $img) {
                $path = "../uploads/" . $img;
                if (file_exists($path)) @unlink($path);
            }
        }

        // 2. Xóa trong Database
        $delStmt = $conn->prepare("DELETE FROM products WHERE id = :id");
        $delStmt->execute([':id' => $id]);
    }
}

// --- LOGIC ĐIỀU HƯỚNG MỚI ---
// Kiểm tra: Nếu trên URL có ?ref=home thì quay về trang chủ
if (isset($_GET['ref']) && $_GET['ref'] === 'home') {
    header("Location: ../index.php");
} else {
    // Mặc định: Quay về trang quản lý Admin
    header("Location: index.php?msg=deleted");
}
exit;
