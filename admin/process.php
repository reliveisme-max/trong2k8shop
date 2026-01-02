<?php
// admin/process.php

// 1. Gọi các file cấu hình và hàm hỗ trợ
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Kiểm tra xem người dùng có bấm nút "Đăng bán" không
if (isset($_POST['btn_submit'])) {

    // --- BƯỚC 1: NHẬN DỮ LIỆU CHỮ ---
    $title = $_POST['title'];
    $price = (int)$_POST['price']; // Ép kiểu số nguyên cho an toàn
    $desc  = $_POST['description'];

    // --- BƯỚC 2: XỬ LÝ ẢNH BÌA (THUMBNAIL) ---
    // Gọi hàm convert WebP đã viết trong functions.php
    $thumbName = uploadImageToWebp($_FILES['thumb']);

    if (!$thumbName) {
        die("Lỗi: Không upload được ảnh bìa! Vui lòng kiểm tra lại (Chỉ nhận JPG/PNG).");
    }

    // --- BƯỚC 3: XỬ LÝ ALBUM ẢNH (GALLERY) ---
    $galleryNames = []; // Mảng chứa tên các file đã upload thành công

    // Kiểm tra xem có chọn ảnh album không
    if (!empty($_FILES['gallery']['name'][0])) {
        // Sắp xếp lại mảng $_FILES cho dễ lặp (Dùng hàm reArrayFiles đã viết)
        $fileList = reArrayFiles($_FILES['gallery']);

        foreach ($fileList as $file) {
            // Upload từng ảnh một
            $uploaded = uploadImageToWebp($file);
            if ($uploaded) {
                $galleryNames[] = $uploaded; // Thêm tên file vào mảng
            }
        }
    }

    // Chuyển mảng tên ảnh thành chuỗi JSON để lưu vào 1 ô trong Database
    // VD: ["acc_1.webp", "acc_2.webp"]
    $galleryJson = json_encode($galleryNames);

    // --- BƯỚC 4: LƯU VÀO DATABASE ---
    try {
        $sql = "INSERT INTO products (title, price, thumb, gallery, description, created_at) 
                VALUES (:title, :price, :thumb, :gallery, :desc, NOW())";

        $stmt = $conn->prepare($sql);

        $data = [
            ':title'   => $title,
            ':price'   => $price,
            ':thumb'   => $thumbName,
            ':gallery' => $galleryJson,
            ':desc'    => $desc
        ];

        if ($stmt->execute($data)) {
            // Thành công -> Chuyển hướng về trang danh sách (index.php)
            header("Location: index.php?msg=success");
            exit;
        } else {
            echo "Lỗi: Không lưu được vào CSDL.";
        }
    } catch (PDOException $e) {
        echo "Lỗi SQL: " . $e->getMessage();
    }
} else {
    // Nếu chưa bấm submit mà cố tình truy cập file này -> Đá về trang thêm
    header("Location: add.php");
    exit;
}