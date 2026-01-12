<?php
// admin/process.php - XỬ LÝ SỬA ACC ĐƠN (EDIT)
require_once 'auth.php';
require_once '../includes/config.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    $title = trim($_POST['title'] ?? '');
    $priceRaw = trim($_POST['price'] ?? '0');
    $note = trim($_POST['private_note'] ?? '');
    $status = isset($_POST['status']) ? 1 : 0;

    // Xử lý giá
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

    // Xử lý ảnh
    $galleryJson = $_POST['final_gallery_list'] ?? '[]';
    $galleryArr = json_decode($galleryJson, true);
    $thumb = (is_array($galleryArr) && count($galleryArr) > 0) ? $galleryArr[0] : '';

    try {
        $sql = "UPDATE products SET title = :title, price = :price, private_note = :note, status = :status, thumb = :thumb, gallery = :gallery WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':title' => $title, ':price' => $price, ':note' => $note, ':status' => $status, ':thumb' => $thumb, ':gallery' => $galleryJson, ':id' => $id]);
        echo "<script>alert('Cập nhật thành công!'); window.location.href='index.php?msg=updated';</script>";
    } catch (PDOException $e) {
        die("Lỗi: " . $e->getMessage());
    }
} else {
    header("Location: index.php");
}
