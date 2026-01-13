<?php
// admin/process.php - FIX: UPDATE CATEGORY ID & AUTO TITLE
require_once 'auth.php';
require_once '../includes/config.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    $title = trim($_POST['title'] ?? '');
    $priceRaw = trim($_POST['price'] ?? '0');
    $note = trim($_POST['private_note'] ?? '');
    $status = isset($_POST['status']) ? 1 : 0;

    // --- 1. NHẬN DANH MỤC TỪ FORM (QUAN TRỌNG) ---
    $catId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;

    // Xử lý giá tiền (Hỗ trợ 5m, 500k...)
    $price = 0;
    $cleanVal = str_replace([',', '.'], '', strtolower($priceRaw));
    if (strpos($cleanVal, 'm') !== false || strpos($cleanVal, 'tr') !== false) {
        $val = (float)preg_replace('/[^0-9.]/', '', $cleanVal);
        $price = $val * 1000000;
    } elseif (strpos($cleanVal, 'k') !== false) {
        $val = (float)preg_replace('/[^0-9.]/', '', $cleanVal);
        $price = $val * 1000;
    } else {
        $price = (int)preg_replace('/[^0-9]/', '', $cleanVal);
    }

    // Xử lý ảnh (Lấy từ JS gửi sang)
    $galleryJson = $_POST['final_gallery_list'] ?? '[]';
    $galleryArr = json_decode($galleryJson, true);
    $thumb = (is_array($galleryArr) && count($galleryArr) > 0) ? $galleryArr[0] : '';

    // --- LOGIC: NẾU TÊN TRỐNG -> TỰ LẤY ID LÀM TÊN ---
    if ($title === '') {
        $title = (string)$id;
    }

    try {
        // --- 2. CẬP NHẬT SQL (ĐÃ THÊM category_id) ---
        $sql = "UPDATE products SET 
                    title = :title, 
                    category_id = :cat, 
                    price = :price, 
                    private_note = :note, 
                    status = :status, 
                    thumb = :thumb, 
                    gallery = :gallery 
                WHERE id = :id";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':title' => $title,
            ':cat' => $catId, // Lưu danh mục vào DB
            ':price' => $price,
            ':note' => $note,
            ':status' => $status,
            ':thumb' => $thumb,
            ':gallery' => $galleryJson,
            ':id' => $id
        ]);

        // Thành công -> Quay về trang danh sách
        header("Location: index.php?msg=updated");
        exit;
    } catch (PDOException $e) {
        die("Lỗi Database: " . $e->getMessage());
    }
} else {
    header("Location: index.php");
}
