<?php
// admin/get_images.php
// File này trả về danh sách ảnh dưới dạng JSON để Javascript đọc
header('Content-Type: application/json');

// 1. Cấu hình
$dir = "../uploads/";
$limit = 24; // Số lượng ảnh tải mỗi lần (tăng lên nếu muốn load nhiều hơn)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// 2. Quét thư mục và lấy thông tin file
$files = [];
if (is_dir($dir)) {
    $scan = scandir($dir);

    foreach ($scan as $file) {
        // Loại bỏ ký tự đặc biệt và file không phải ảnh
        if ($file === '.' || $file === '..') continue;

        $path = $dir . $file;
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            // Lưu tên file và thời gian sửa đổi
            $files[$file] = filemtime($path);
        }
    }
}

// 3. Sắp xếp: Mới nhất lên đầu
arsort($files);

// 4. Cắt danh sách theo trang (Pagination)
// Lấy ra danh sách tên file sau khi sắp xếp
$allFiles = array_keys($files);
$totalFiles = count($allFiles);

// Cắt mảng lấy đúng số lượng cần thiết
$resultFiles = array_slice($allFiles, $offset, $limit);

// 5. Trả về kết quả JSON
echo json_encode([
    'status' => 'success',
    'page' => $page,
    'total_files' => $totalFiles,
    'data' => $resultFiles,
    'has_more' => ($offset + $limit) < $totalFiles // Kiểm tra xem còn ảnh để load tiếp không
]);