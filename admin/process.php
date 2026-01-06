<?php
// admin/process.php - FINAL: ĐÃ XÓA MÔ TẢ (DESCRIPTION)
require_once 'auth.php';
require_once '../includes/config.php';
require_once '../includes/functions.php';

// 1. XỬ LÝ THÊM MỚI
if (isset($_POST['btn_submit'])) {

    $title = $_POST['title'];
    // $desc = $_POST['description']; // ĐÃ BỎ
    $type  = (int)$_POST['type'];
    $unit = ($type == 0) ? 0 : (int)$_POST['unit'];

    $priceRaw = str_replace(['.', ','], '', $_POST['price']);
    $price = (int)$priceRaw;

    // Ảnh Bìa
    $thumbName = '';
    if (!empty($_FILES['thumb']['name'])) {
        $uploaded = uploadImageToWebp($_FILES['thumb']);
        if ($uploaded) $thumbName = $uploaded;
        else {
            header("Location: add.php?msg=error&text=" . urlencode("Lỗi upload ảnh bìa!"));
            exit;
        }
    } elseif (!empty($_POST['selected_thumb'])) {
        $thumbName = $_POST['selected_thumb'];
    }

    if (empty($thumbName)) {
        header("Location: add.php?msg=error&text=" . urlencode("Thiếu ảnh bìa!"));
        exit;
    }

    // Album Ảnh
    $galleryNames = [];
    if (!empty($_FILES['gallery']['name'][0])) {
        $fileList = reArrayFiles($_FILES['gallery']);
        foreach ($fileList as $file) {
            $uploaded = uploadImageToWebp($file);
            if ($uploaded) $galleryNames[] = $uploaded;
        }
    }
    if (!empty($_POST['selected_gallery'])) {
        $selectedFromLib = json_decode($_POST['selected_gallery'], true);
        if (is_array($selectedFromLib)) $galleryNames = array_unique(array_merge($galleryNames, $selectedFromLib));
    }
    $galleryJson = json_encode(array_values($galleryNames));

    // INSERT (BỎ CỘT DESCRIPTION)
    try {
        $sql = "INSERT INTO products (title, price, type, unit, thumb, gallery, status, created_at) 
                VALUES (:title, :price, :type, :unit, :thumb, :gallery, 1, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':title'   => $title,
            ':price'   => $price,
            ':type'    => $type,
            ':unit'    => $unit,
            ':thumb'   => $thumbName,
            ':gallery' => $galleryJson
        ]);
        header("Location: index.php?msg=added");
        exit;
    } catch (PDOException $e) {
        header("Location: add.php?msg=error&text=" . urlencode("Lỗi DB: " . $e->getMessage()));
        exit;
    }
}

// 2. XỬ LÝ CẬP NHẬT
elseif (isset($_POST['btn_update'])) {

    $id = (int)$_POST['id'];
    $title = $_POST['title'];
    // $desc = $_POST['description']; // ĐÃ BỎ
    $status = (int)$_POST['status'];
    $type   = (int)$_POST['type'];
    $unit = ($type == 0) ? 0 : (int)$_POST['unit'];

    $priceRaw = str_replace(['.', ','], '', $_POST['price']);
    $price = (int)$priceRaw;

    // Ảnh
    $thumbName = $_POST['old_thumb'];
    if (!empty($_FILES['thumb']['name'])) {
        $uploaded = uploadImageToWebp($_FILES['thumb']);
        if ($uploaded) $thumbName = $uploaded;
    } elseif (!empty($_POST['selected_thumb'])) {
        $thumbName = $_POST['selected_thumb'];
    }

    // Album
    $finalGallery = [];
    if (!empty($_POST['selected_gallery'])) {
        $arr = json_decode($_POST['selected_gallery'], true);
        if (is_array($arr)) $finalGallery = $arr;
    }
    if (!empty($_FILES['gallery']['name'][0])) {
        $fileList = reArrayFiles($_FILES['gallery']);
        foreach ($fileList as $file) {
            $uploaded = uploadImageToWebp($file);
            if ($uploaded) $finalGallery[] = $uploaded;
        }
    }
    $finalGallery = array_unique($finalGallery);
    $galleryJson = json_encode(array_values($finalGallery));

    // UPDATE (BỎ CỘT DESCRIPTION)
    try {
        $sql = "UPDATE products SET 
                title = :title, price = :price, type = :type, unit = :unit,
                thumb = :thumb, gallery = :gallery, status = :status 
                WHERE id = :id";

        $stmt = $conn->prepare($sql);
        $data = [
            ':title'   => $title,
            ':price'   => $price,
            ':type'    => $type,
            ':unit'    => $unit,
            ':thumb'   => $thumbName,
            ':gallery' => $galleryJson,
            ':status'  => $status,
            ':id'      => $id
        ];

        if ($stmt->execute($data)) {
            header("Location: index.php?msg=updated");
            exit;
        } else {
            header("Location: edit.php?id=$id&msg=error&text=" . urlencode("Lỗi update!"));
            exit;
        }
    } catch (PDOException $e) {
        header("Location: edit.php?id=$id&msg=error&text=" . urlencode("Lỗi SQL: " . $e->getMessage()));
        exit;
    }
} else {
    header("Location: index.php");
    exit;
}