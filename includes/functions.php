<?php
// includes/functions.php

// --- PHẦN 1: CÁC HÀM XỬ LÝ ẢNH & FORMAT ---

function uploadImageToWebp($fileData)
{
    $targetDir = "../uploads/";
    if ($fileData['error'] !== 0) return false;

    $tempPath = $fileData['tmp_name'];
    $ext = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, ['jpg', 'jpeg', 'png'])) return false;

    $image = null;
    if ($ext == 'jpg' || $ext == 'jpeg') $image = imagecreatefromjpeg($tempPath);
    elseif ($ext == 'png') {
        $image = imagecreatefrompng($tempPath);
        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);
    }

    if (!$image) return false;

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

    $newFileName = 'acc_' . uniqid() . '.webp';
    $result = imagewebp($image, $targetDir . $newFileName, 80);
    imagedestroy($image);

    return $result ? $newFileName : false;
}

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

function formatPrice($price)
{
    if ($price <= 0) return "Liên hệ";
    return number_format($price, 0, ',', '.') . ' đ';
}


// --- PHẦN 2: CÁC HÀM LOGIC CHO FRONTEND ---

/**
 * Hàm lấy danh sách Acc (Xử lý Tìm kiếm, Giá, Trạng thái)
 */
function getFilteredProducts($conn, $getRequest)
{
    $whereArr = [];
    $params = [];
    $title = "Tất cả sản phẩm";
    $keyword = '';

    // 1. TÌM KIẾM
    if (isset($getRequest['q']) && !empty($getRequest['q'])) {
        $keyword = $getRequest['q'];
        $whereArr[] = "title LIKE :keyword";
        $params[':keyword'] = "%$keyword%";
        $title = "Kết quả tìm kiếm: \"$keyword\"";
    }

    // 2. LỌC THEO GIÁ
    if (isset($getRequest['min'])) {
        $whereArr[] = "price >= :min";
        $params[':min'] = (int)$getRequest['min'];
    }
    // Nếu có Max thì mới thêm điều kiện <= Max
    if (isset($getRequest['max'])) {
        $whereArr[] = "price <= :max";
        $params[':max'] = (int)$getRequest['max'];
    }

    // 3. LỌC TRẠNG THÁI
    if (isset($getRequest['status']) && $getRequest['status'] == 'sold') {
        $whereArr[] = "status = 0";
        $title = "Acc Đã Bán";
    } else {
        if (empty($keyword)) {
            $whereArr[] = "status = 1";
        }
    }

    // 4. THỰC THI
    $sql = "SELECT * FROM products";
    if (!empty($whereArr)) {
        $sql .= " WHERE " . implode(" AND ", $whereArr);
    }
    $sql .= " ORDER BY id DESC";

    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();
    } catch (PDOException $e) {
        die("Lỗi truy vấn: " . $e->getMessage());
    }

    return [
        'data' => $products,
        'title' => $title,
        'keyword' => $keyword
    ];
}

/**
 * Hàm kiểm tra active bộ lọc (Đã sửa logic cho nút "Trên...")
 */
function checkActive($min, $max)
{
    // Kiểm tra Min trước
    if (isset($_GET['min']) && $_GET['min'] == $min) {
        // Trường hợp 1: Có cả Min và Max (VD: 5m - 10m)
        if ($max !== null && isset($_GET['max']) && $_GET['max'] == $max) {
            return 'active';
        }
        // Trường hợp 2: Chỉ có Min, không có Max trên URL (VD: Trên 60m)
        if ($max === null && !isset($_GET['max'])) {
            return 'active';
        }
    }
    return '';
}