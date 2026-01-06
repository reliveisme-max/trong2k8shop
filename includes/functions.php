<?php
// includes/functions.php - FINAL VERSION: GIÁ HIỂN THỊ RÚT GỌN (13m5, 500k)

// --- PHẦN 1: CÁC HÀM XỬ LÝ ẢNH ---

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

// --- PHẦN 2: HÀM FORMAT GIÁ MỚI (HIỆN m, k) ---

function formatPrice($price)
{
    if ($price <= 0) return "Liên hệ";

    // Xử lý Hàng Triệu (>= 1.000.000)
    // Ví dụ: 13.500.000 -> 13m5 | 13.000.000 -> 13m
    if ($price >= 1000000) {
        $val = $price / 1000000;
        $str = (string)$val; // Ép kiểu về chuỗi để kiểm tra dấu chấm

        if (strpos($str, '.') !== false) {
            // Nếu là số lẻ (13.5) -> Thay dấu chấm bằng m -> 13m5
            return str_replace('.', 'm', $str);
        }
        // Nếu là số chẵn (13) -> Thêm m -> 13m
        return $str . 'm';
    }

    // Xử lý Hàng Nghìn (>= 1.000)
    // Ví dụ: 500.000 -> 500k | 1.500 -> 1k5
    if ($price >= 1000) {
        $val = $price / 1000;
        $str = (string)$val;

        if (strpos($str, '.') !== false) {
            return str_replace('.', 'k', $str);
        }
        return $str . 'k';
    }

    // Nhỏ quá thì hiện đầy đủ
    return number_format($price, 0, ',', '.') . ' đ';
}


// --- PHẦN 3: CÁC HÀM LOGIC LỌC SẢN PHẨM ---

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
    $type = isset($getRequest['type']) ? (int)$getRequest['type'] : 0;
    $whereArr[] = "type = :type";
    $params[':type'] = $type;

    if ($type == 1) $title = "Danh sách Acc Thuê";

    // Tìm kiếm
    if (isset($getRequest['q']) && !empty($getRequest['q'])) {
        $keywordRaw = trim($getRequest['q']);
        $keywordEscaped = str_replace(['%', '_'], ['\%', '\_'], $keywordRaw);

        if (is_numeric($keywordRaw)) {
            $whereArr[] = "(id = :id_exact OR title LIKE :keyword)";
            $params[':id_exact'] = (int)$keywordRaw;
            $params[':keyword'] = "%$keywordEscaped%";
        } else {
            $whereArr[] = "title LIKE :keyword";
            $params[':keyword'] = "%$keywordEscaped%";
        }

        $keyword = $keywordRaw;
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

    // 3. ĐẾM TỔNG SỐ
    $whereSql = !empty($whereArr) ? "WHERE " . implode(" AND ", $whereArr) : "";
    $countSql = "SELECT COUNT(*) FROM products $whereSql";

    try {
        $countStmt = $conn->prepare($countSql);
        $countStmt->execute($params);
        $totalRecords = $countStmt->fetchColumn();
    } catch (PDOException $e) {
        die("Lỗi đếm dữ liệu: " . $e->getMessage());
    }

    $totalPages = ceil($totalRecords / $limit);
    if ($page > $totalPages && $totalPages > 0) $page = $totalPages;

    $offset = ($page - 1) * $limit;

    // 4. LẤY DỮ LIỆU
    $sql = "SELECT * FROM products $whereSql ORDER BY id DESC LIMIT :limit OFFSET :offset";

    try {
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
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

function checkActive($min, $max)
{
    if (isset($_GET['min']) && $_GET['min'] == $min) {
        if ($max !== null && isset($_GET['max']) && $_GET['max'] == $max) return 'active';
        if ($max === null && !isset($_GET['max'])) return 'active';
    }
    return '';
}