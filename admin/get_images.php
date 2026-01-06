<?php
// admin/get_images.php - API LẤY ẢNH TỪ THƯ MỤC UPLOADS
require_once 'auth.php'; // Bảo mật: Chỉ admin mới lấy được danh sách ảnh

header('Content-Type: application/json');

// 1. Cấu hình
$dir = "../uploads/";
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20; // Số ảnh lấy mỗi lần
$offset = ($page - 1) * $limit;

// 2. Kiểm tra thư mục
if (!is_dir($dir)) {
    echo json_encode(['status' => 'error', 'message' => 'Thư mục uploads không tồn tại']);
    exit;
}

// 3. Quét tất cả file trong thư mục
$files = scandir($dir);
$images = [];

// Lọc chỉ lấy file ảnh
foreach ($files as $file) {
    if ($file !== '.' && $file !== '..') {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            // Lấy thêm thời gian sửa đổi để sắp xếp ảnh mới nhất lên đầu
            $images[$file] = filemtime($dir . $file);
        }
    }
}

// 4. Sắp xếp ảnh mới nhất lên đầu
arsort($images);
$sortedImages = array_keys($images);

// 5. Cắt mảng theo phân trang (Pagination)
$totalImages = count($sortedImages);
$slicedImages = array_slice($sortedImages, $offset, $limit);

// 6. Kiểm tra xem còn ảnh để load tiếp không
$hasMore = ($offset + $limit) < $totalImages;

// 7. Trả về JSON
echo json_encode([
    'status'   => 'success',
    'data'     => $slicedImages,
    'has_more' => $hasMore
]);