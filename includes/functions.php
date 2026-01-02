<?php
// includes/functions.php - ĐÃ CẬP NHẬT LOGIC PHÂN TRANG

// --- PHẦN 1: CÁC HÀM XỬ LÝ ẢNH & FORMAT (GIỮ NGUYÊN) ---

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


// --- PHẦN 2: CÁC HÀM LOGIC CHO FRONTEND (CÓ PHÂN TRANG) ---

/**
 * Hàm lấy danh sách Acc (Xử lý Tìm kiếm, Giá, Trạng thái, Loại Acc VÀ PHÂN TRANG)
 */
function getFilteredProducts($conn, $getRequest, $limit = 12)
{
    $whereArr = [];
    $params = [];
    $title = "Tất cả sản phẩm";
    $keyword = '';

    // 1. LẤY TRANG HIỆN TẠI
    $page = isset($getRequest['page']) && is_numeric($getRequest['page']) ? (int)$getRequest['page'] : 1;
    if ($page < 1) $page = 1;

    // 2. XÂY DỰNG ĐIỀU KIỆN LỌC

    // Loại Acc (Bán/Thuê)
    $type = isset($getRequest['type']) ? (int)$getRequest['type'] : 0;
    $whereArr[] = "type = :type";
    $params[':type'] = $type;

    if ($type == 1) $title = "Danh sách Acc Thuê";

    // Tìm kiếm
    if (isset($getRequest['q']) && !empty($getRequest['q'])) {
        $keyword = $getRequest['q'];
        $whereArr[] = "title LIKE :keyword";
        $params[':keyword'] = "%$keyword%";
        $title = "Kết quả tìm kiếm: \"$keyword\"";
    }

    // Giá
    if (isset($getRequest['min'])) {
        $whereArr[] = "price >= :min";
        $params[':min'] = (int)$getRequest['min'];
    }
    if (isset($getRequest['max'])) {
        $whereArr[] = "price <= :max";
        $params[':max'] = (int)$getRequest['max'];
    }

    // Trạng thái
    if (isset($getRequest['status']) && $getRequest['status'] == 'sold') {
        $whereArr[] = "status = 0";
        $title = ($type == 1) ? "Acc Đang Thuê / Hết" : "Acc Đã Bán";
    } else {
        if (empty($keyword)) {
            $whereArr[] = "status = 1";
        }
    }

    // 3. ĐẾM TỔNG SỐ ACC (Để tính số trang)
    $whereSql = !empty($whereArr) ? "WHERE " . implode(" AND ", $whereArr) : "";
    $countSql = "SELECT COUNT(*) FROM products $whereSql";

    try {
        $countStmt = $conn->prepare($countSql);
        $countStmt->execute($params);
        $totalRecords = $countStmt->fetchColumn();
    } catch (PDOException $e) {
        die("Lỗi đếm dữ liệu: " . $e->getMessage());
    }

    // Tính toán phân trang
    $totalPages = ceil($totalRecords / $limit);
    // Nếu trang hiện tại lớn hơn tổng trang thì về trang cuối
    if ($page > $totalPages && $totalPages > 0) $page = $totalPages;

    $offset = ($page - 1) * $limit;

    // 4. LẤY DỮ LIỆU CỦA TRANG HIỆN TẠI
    $sql = "SELECT * FROM products $whereSql ORDER BY id DESC LIMIT :limit OFFSET :offset";

    try {
        $stmt = $conn->prepare($sql);

        // Bind các tham số lọc cũ
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val); // Mặc định là String hoặc Int tuỳ PHP đoán
        }

        // Bind tham số phân trang (BẮT BUỘC PHẢI LÀ INT)
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $products = $stmt->fetchAll();
    } catch (PDOException $e) {
        die("Lỗi truy vấn: " . $e->getMessage());
    }

    return [
        'data' => $products,
        'title' => $title,
        'keyword' => $keyword,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords
        ]
    ];
}

/**
 * Hàm kiểm tra active bộ lọc
 */
function checkActive($min, $max)
{
    if (isset($_GET['min']) && $_GET['min'] == $min) {
        if ($max !== null && isset($_GET['max']) && $_GET['max'] == $max) return 'active';
        if ($max === null && !isset($_GET['max'])) return 'active';
    }
    return '';
}