<?php
// admin/process.php - XỬ LÝ CẢ UPLOAD MỚI VÀ ẢNH CŨ
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (isset($_POST['btn_submit'])) {

    // 1. NHẬN DỮ LIỆU CƠ BẢN
    $title = $_POST['title'];
    $price = (int)$_POST['price'];
    $desc  = $_POST['description'];

    // 2. XỬ LÝ ẢNH BÌA (THUMB)
    $thumbName = '';

    // Trường hợp A: Có upload ảnh mới
    if (!empty($_FILES['thumb']['name'])) {
        $uploaded = uploadImageToWebp($_FILES['thumb']);
        if ($uploaded) {
            $thumbName = $uploaded;
        }
    }
    // Trường hợp B: Chọn từ thư viện (input hidden có giá trị)
    elseif (!empty($_POST['selected_thumb'])) {
        $thumbName = $_POST['selected_thumb'];
    }

    // Nếu cả 2 đều không có -> Lỗi
    if (empty($thumbName)) {
        die("Lỗi: Bạn chưa chọn ảnh bìa (Upload mới hoặc chọn từ thư viện)!");
    }

    // 3. XỬ LÝ ALBUM ẢNH (GALLERY)
    $galleryNames = [];

    // Trường hợp A: Có upload album mới
    if (!empty($_FILES['gallery']['name'][0])) {
        $fileList = reArrayFiles($_FILES['gallery']);
        foreach ($fileList as $file) {
            $uploaded = uploadImageToWebp($file);
            if ($uploaded) {
                $galleryNames[] = $uploaded;
            }
        }
    }
    // Trường hợp B: Chọn từ thư viện (Input hidden trả về chuỗi JSON)
    elseif (!empty($_POST['selected_gallery'])) {
        // Decode chuỗi JSON từ input hidden
        $selectedFromLib = json_decode($_POST['selected_gallery'], true);
        if (is_array($selectedFromLib)) {
            $galleryNames = $selectedFromLib;
        }
    }

    // Encode lại thành JSON để lưu DB
    $galleryJson = json_encode($galleryNames);

    // 4. LƯU VÀO DATABASE
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
            header("Location: index.php?msg=success");
            exit;
        } else {
            echo "Lỗi: Không lưu được vào CSDL.";
        }
    } catch (PDOException $e) {
        echo "Lỗi SQL: " . $e->getMessage();
    }
} else {
    header("Location: add.php");
    exit;
}
