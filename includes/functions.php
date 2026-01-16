<?php
// includes/functions.php - FINAL STANDARDIZED VERSION

// 1. XỬ LÝ UPLOAD ẢNH (Auto convert to WebP)
// includes/functions.php

function uploadImageToWebp($fileData)
{
    $targetDir = "../uploads/";

    // 1. Kiểm tra lỗi
    if ($fileData['error'] !== 0) return false;

    $tempPath = $fileData['tmp_name'];
    $ext = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));

    // 2. Cho phép các đuôi file (Bao gồm jfif)
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'jfif'])) return false;

    // 3. Nếu là WebP sẵn thì move luôn
    if ($ext == 'webp') {
        $newFileName = 'acc_' . uniqid() . '.webp';
        if (move_uploaded_file($tempPath, $targetDir . $newFileName)) {
            return $newFileName;
        }
        return false;
    }

    // 4. Tạo ảnh từ file gốc
    $image = null;
    if ($ext == 'jpg' || $ext == 'jpeg' || $ext == 'jfif') {
        $image = @imagecreatefromjpeg($tempPath);
    } elseif ($ext == 'png') {
        $image = @imagecreatefrompng($tempPath);
        if ($image) {
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
        }
    }

    if (!$image) return false;

    // 5. [QUAN TRỌNG] RESIZE VỀ 2K (2560px)
    // Nếu ảnh nhỏ hơn 2560px thì giữ nguyên, lớn hơn thì thu về 2560px
    $maxWidth = 2560;
    $origWidth = imagesx($image);

    if ($origWidth > $maxWidth) {
        $newHeight = floor(imagesy($image) * ($maxWidth / $origWidth));

        $newImage = imagecreatetruecolor($maxWidth, $newHeight);

        if ($ext == 'png') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
        }

        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $maxWidth, $newHeight, $origWidth, imagesy($image));
        imagedestroy($image);
        $image = $newImage;
    }

    // 6. [QUAN TRỌNG] XUẤT WEBP CHẤT LƯỢNG 95 (Cực nét)
    $newFileName = 'acc_' . uniqid() . '.webp';
    $result = imagewebp($image, $targetDir . $newFileName, 95);

    imagedestroy($image);

    return $result ? $newFileName : false;
}

// 2. SẮP XẾP MẢNG FILE (Dùng cho upload nhiều ảnh)
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

// 3. FORMAT GIÁ TIỀN (Hiển thị đẹp: 1m, 500k...)
function formatPrice($price)
{
    if ($price <= 0) return "Liên hệ";

    // Xử lý hàng Triệu (m)
    if ($price >= 1000000) {
        $val = $price / 1000000;
        // Chuyển sang chuỗi, nếu tròn (10.0) thì bỏ .0 thành 10
        $str = (string)$val;
        if (strpos($str, '.') !== false) {
            $str = rtrim(rtrim($str, '0'), '.');
        }
        return str_replace('.', ',', $str) . 'm';
    }

    // Xử lý hàng Nghìn (k)
    if ($price >= 1000) {
        $val = $price / 1000;
        $str = (string)$val;
        if (strpos($str, '.') !== false) {
            $str = rtrim(rtrim($str, '0'), '.');
        }
        return str_replace('.', ',', $str) . 'k';
    }

    // Số nhỏ quá thì để nguyên đ
    return number_format($price, 0, ',', '.') . 'đ';
}

// 4. CHECK ACTIVE BỘ LỌC (Dùng cho index.php)
// Hàm này kiểm tra xem URL hiện tại có trùng với min/max không để thêm class 'active'
function checkActive($min, $max)
{
    if (isset($_GET['min']) && $_GET['min'] == $min) {
        // Trường hợp max là null (Ví dụ: Trên 60m)
        if ($max === null && !isset($_GET['max'])) return 'active';

        // Trường hợp có cả min và max
        if (isset($_GET['max']) && $_GET['max'] == $max) return 'active';
    }
    return '';
}
