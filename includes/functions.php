<?php
// includes/functions.php

// --- PHẦN 1: CÁC HÀM XỬ LÝ ẢNH & FORMAT ---

/**
 * Upload và convert ảnh sang WebP
 */
function uploadImageToWebp($fileData)
{
    $targetDir = "../uploads/";
    if ($fileData['error'] !== 0) return false;

    $tempPath = $fileData['tmp_name'];
    $ext = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));

    // Chỉ cho phép ảnh
    if (!in_array($ext, ['jpg', 'jpeg', 'png'])) return false;

    // Load ảnh vào RAM
    $image = null;
    if ($ext == 'jpg' || $ext == 'jpeg') $image = imagecreatefromjpeg($tempPath);
    elseif ($ext == 'png') {
        $image = imagecreatefrompng($tempPath);
        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);
    }

    if (!$image) return false;

    // Resize nếu ảnh quá to (>1200px)
    $maxWidth = 1200;
    $origWidth = imagesx($image);
    if ($origWidth > $maxWidth) {
        $newHeight = floor(imagesy($image) * ($maxWidth / $origWidth));
        $newImage = imagecreatetruecolor($maxWidth, $newHeight);
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $maxWidth, $newHeight, $origWidth, imagesy($image));
        imagedestroy($image);
        $image = $newImage;
    }

    // Lưu file WebP
    $newFileName = 'acc_' . uniqid() . '.webp';
    $result = imagewebp($image, $targetDir . $newFileName, 80);
    imagedestroy($image);

    return $result ? $newFileName : false;
}

/**
 * Sắp xếp lại mảng file khi upload nhiều ảnh
 */
function reArrayFiles(&$file_post)
{
    $file_ary = array();
    $file_count = count($file_post['name']);
    $file_keys = array_keys($file_post);
    for ($i = 0; $i < $file_count; $i++) {
        foreach ($file_keys as $key) {
            $file_ary[$i][$key] = $file_post[$key][$i];
        }
    }
    return $file_ary;
}

/**
 * Format giá tiền
 */
function formatPrice($price)
{
    if ($price <= 0) return "Liên hệ";
    return number_format($price, 0, ',', '.') . ' đ';
}


// --- PHẦN 2: CÁC HÀM LOGIC CHO FRONTEND (MỚI THÊM) ---

/**
 * Hàm lấy danh sách Acc có lọc theo điều kiện $_GET
 * @param PDO $conn Kết nối Database
 * @param array $getRequest Mảng $_GET từ URL
 * @return array [danh_sach_acc, tieu_de_hien_thi]
 */
function getFilteredProducts($conn, $getRequest)
{
    $whereArr = [];
    $params = [];
    $title = "Tất cả sản phẩm";

    // 1. Lọc theo trạng thái (Mặc định chỉ hiện acc đang bán)
    if (isset($getRequest['status']) && $getRequest['status'] == 'sold') {
        $whereArr[] = "status = 0";
        $title = "Acc Đã Bán";
    } else {
        $whereArr[] = "status = 1";
    }

    // 2. Lọc theo Giá (Min - Max)
    if (isset($getRequest['min'])) {
        $whereArr[] = "price >= :min";
        $params[':min'] = $getRequest['min'];
    }
    if (isset($getRequest['max'])) {
        $whereArr[] = "price <= :max";
        $params[':max'] = $getRequest['max'];
    }

    // 3. Thực thi SQL
    $whereClause = implode(' AND ', $whereArr);
    $sql = "SELECT * FROM products WHERE $whereClause ORDER BY id DESC";

    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();
    } catch (PDOException $e) {
        die("Lỗi truy vấn: " . $e->getMessage());
    }

    return [
        'data' => $products,
        'title' => $title
    ];
}

/**
 * Hàm kiểm tra nút lọc nào đang active để đổi màu
 */
function checkActive($min, $max)
{
    if (isset($_GET['min']) && $_GET['min'] == $min && isset($_GET['max']) && $_GET['max'] == $max) {
        return 'active';
    }
    return '';
}