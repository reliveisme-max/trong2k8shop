<?php
// admin/delete.php
require_once '../includes/config.php';
session_start();

// Kiểm tra ID
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // 1. Lấy thông tin ảnh để xóa file trong thư mục uploads (dọn rác)
    $stmt = $conn->prepare("SELECT thumb, gallery FROM products WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $product = $stmt->fetch();

    if ($product) {
        // Xóa ảnh bìa
        $thumbPath = "../uploads/" . $product['thumb'];
        if (file_exists($thumbPath)) unlink($thumbPath);

        // Xóa album ảnh
        $gallery = json_decode($product['gallery'], true);
        if (is_array($gallery)) {
            foreach ($gallery as $img) {
                $imgPath = "../uploads/" . $img;
                if (file_exists($imgPath)) unlink($imgPath);
            }
        }

        // 2. Xóa trong Database
        $delStmt = $conn->prepare("DELETE FROM products WHERE id = :id");
        $delStmt->execute([':id' => $id]);
    }
}

// Quay về trang admin
header("Location: index.php");
exit;
