<?php
// admin/process.php - XỬ LÝ CẢ THÊM MỚI (ADD) VÀ CẬP NHẬT (EDIT) - CÓ LOẠI ACC
require_once 'auth.php';
require_once '../includes/config.php';
require_once '../includes/functions.php';

// 1. XỬ LÝ KHI BẤM NÚT "ĐĂNG BÁN NGAY" (THÊM MỚI)
if (isset($_POST['btn_submit'])) {

    $title = $_POST['title'];
    $desc  = $_POST['description'];
    $type  = (int)$_POST['type']; // 0: Bán, 1: Thuê

    // Xử lý giá tiền
    $priceRaw = str_replace(['.', ','], '', $_POST['price']);
    $price = (int)$priceRaw;

    // Xử lý Ảnh Bìa
    $thumbName = '';
    if (!empty($_FILES['thumb']['name'])) {
        $uploaded = uploadImageToWebp($_FILES['thumb']);
        if ($uploaded) $thumbName = $uploaded;
    } elseif (!empty($_POST['selected_thumb'])) {
        $thumbName = $_POST['selected_thumb'];
    }

    if (empty($thumbName)) die("Lỗi: Chưa chọn ảnh bìa!");

    // Xử lý Album
    $galleryNames = [];
    if (!empty($_FILES['gallery']['name'][0])) {
        $fileList = reArrayFiles($_FILES['gallery']);
        foreach ($fileList as $file) {
            $uploaded = uploadImageToWebp($file);
            if ($uploaded) $galleryNames[] = $uploaded;
        }
    } elseif (!empty($_POST['selected_gallery'])) {
        $selectedFromLib = json_decode($_POST['selected_gallery'], true);
        if (is_array($selectedFromLib)) $galleryNames = $selectedFromLib;
    }
    $galleryJson = json_encode($galleryNames);

    // Lưu vào DB
    try {
        $sql = "INSERT INTO products (title, price, type, thumb, gallery, description, status, created_at) 
                VALUES (:title, :price, :type, :thumb, :gallery, :desc, 1, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':title' => $title,
            ':price' => $price,
            ':type' => $type,
            ':thumb' => $thumbName,
            ':gallery' => $galleryJson,
            ':desc' => $desc
        ]);
        header("Location: index.php?msg=added");
        exit;
    } catch (PDOException $e) {
        echo "Lỗi SQL: " . $e->getMessage();
    }
}

// 2. XỬ LÝ KHI BẤM NÚT "LƯU THAY ĐỔI" (CẬP NHẬT)
elseif (isset($_POST['btn_update'])) {

    $id = (int)$_POST['id'];
    $title = $_POST['title'];
    $desc  = $_POST['description'];
    $status = (int)$_POST['status'];
    $type   = (int)$_POST['type']; // Cập nhật loại

    // Xử lý giá tiền
    $priceRaw = str_replace(['.', ','], '', $_POST['price']);
    $price = (int)$priceRaw;

    // Xử lý Ảnh
    $thumbName = $_POST['old_thumb'];
    if (!empty($_FILES['thumb']['name'])) {
        $uploaded = uploadImageToWebp($_FILES['thumb']);
        if ($uploaded) $thumbName = $uploaded;
    } elseif (!empty($_POST['selected_thumb'])) {
        $thumbName = $_POST['selected_thumb'];
    }

    // Xử lý Album
    $galleryJson = $_POST['old_gallery'];
    $hasNewGallery = false;
    $newGalleryNames = [];

    if (!empty($_FILES['gallery']['name'][0])) {
        $fileList = reArrayFiles($_FILES['gallery']);
        foreach ($fileList as $file) {
            $uploaded = uploadImageToWebp($file);
            if ($uploaded) $newGalleryNames[] = $uploaded;
        }
        $hasNewGallery = true;
    } elseif (!empty($_POST['selected_gallery'])) {
        $selectedFromLib = json_decode($_POST['selected_gallery'], true);
        if (is_array($selectedFromLib)) {
            $newGalleryNames = $selectedFromLib;
            $hasNewGallery = true;
        }
    }

    if ($hasNewGallery) {
        $galleryJson = json_encode($newGalleryNames);
    }

    // Cập nhật Database
    try {
        $sql = "UPDATE products SET 
                title = :title, price = :price, type = :type,
                thumb = :thumb, gallery = :gallery, 
                description = :desc, status = :status 
                WHERE id = :id";

        $stmt = $conn->prepare($sql);
        $data = [
            ':title'   => $title,
            ':price' => $price,
            ':type' => $type,
            ':thumb'   => $thumbName,
            ':gallery' => $galleryJson,
            ':desc'    => $desc,
            ':status'  => $status,
            ':id' => $id
        ];

        if ($stmt->execute($data)) {
            header("Location: index.php?msg=updated");
            exit;
        } else {
            echo "Lỗi: Không cập nhật được.";
        }
    } catch (PDOException $e) {
        echo "Lỗi SQL: " . $e->getMessage();
    }
} else {
    header("Location: index.php");
    exit;
}