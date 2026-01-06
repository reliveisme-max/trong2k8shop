<?php
// admin/process.php - V2: XỬ LÝ KÉO THẢ ẢNH & ĐA GIÁ
require_once 'auth.php';
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Kiểm tra xem có phải POST request không
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. LẤY DỮ LIỆU CƠ BẢN
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0; // Dùng cho Edit (sau này)
    $title = trim($_POST['title']);

    // Xử lý giá (Xóa dấu chấm, chuyển về số)
    $priceRaw = isset($_POST['price']) ? str_replace(['.', ','], '', $_POST['price']) : 0;
    $price = (int)$priceRaw;

    $priceRentRaw = isset($_POST['price_rent']) ? str_replace(['.', ','], '', $_POST['price_rent']) : 0;
    $priceRent = (int)$priceRentRaw;

    $unit = isset($_POST['unit']) ? (int)$_POST['unit'] : 0;

    // Xác định Type (Để tương thích với bộ lọc cũ)
    // Nếu chỉ có giá thuê -> Type = 1. Các trường hợp còn lại (Bán hoặc Bán+Thuê) -> Type = 0
    $type = ($priceRent > 0 && $price == 0) ? 1 : 0;

    // 2. XỬ LÝ ẢNH (PHẦN QUAN TRỌNG NHẤT)
    $finalImages = []; // Mảng chứa danh sách ảnh cuối cùng theo thứ tự

    // Lấy bản đồ thứ tự từ JS
    $orderMap = isset($_POST['order_map']) ? json_decode($_POST['order_map'], true) : [];

    // Lấy danh sách ảnh thư viện
    $libImages = isset($_POST['library_images']) ? json_decode($_POST['library_images'], true) : [];

    // Chuẩn hóa danh sách file upload (nếu có)
    $uploadedFiles = [];
    if (!empty($_FILES['gallery']['name'][0])) {
        $uploadedFiles = reArrayFiles($_FILES['gallery']);
    }

    $localIndex = 0;
    $libIndex = 0;

    // Duyệt qua bản đồ để sắp xếp
    if (is_array($orderMap)) {
        foreach ($orderMap as $sourceType) {
            if ($sourceType === 'local') {
                // Nếu là ảnh từ máy -> Upload và lấy tên mới
                if (isset($uploadedFiles[$localIndex])) {
                    $newFileName = uploadImageToWebp($uploadedFiles[$localIndex]);
                    if ($newFileName) {
                        $finalImages[] = $newFileName;
                    }
                    $localIndex++;
                }
            } elseif ($sourceType === 'lib') {
                // Nếu là ảnh thư viện -> Lấy tên từ mảng lib
                if (isset($libImages[$libIndex])) {
                    $finalImages[] = $libImages[$libIndex];
                    $libIndex++;
                }
            }
        }
    }

    // Nếu không có ảnh nào (Lỗi)
    if (empty($finalImages)) {
        // Nếu đang update mà không chọn ảnh mới -> Giữ ảnh cũ (Logic cho Edit sau này)
        // Nhưng ở đây là Add New -> Báo lỗi
        if ($id == 0) {
            die("Lỗi: Vui lòng chọn ít nhất 1 ảnh!");
        }
    }

    // Tách Thumb và Gallery
    // Ảnh đầu tiên là Thumb
    $thumb = isset($finalImages[0]) ? $finalImages[0] : '';

    // Toàn bộ danh sách là Gallery (Để hiển thị chi tiết cho đầy đủ)
    $galleryJson = json_encode($finalImages);


    // 3. THỰC HIỆN INSERT (THÊM MỚI)
    // Kiểm tra xem là Thêm mới hay Cập nhật dựa vào ID (hoặc action)
    // Ở file add.php hiện tại chưa gửi ID, nên mặc định là INSERT

    if ($id == 0) {
        // --- ADD NEW ---
        try {
            $sql = "INSERT INTO products (title, price, price_rent, type, unit, thumb, gallery, status, created_at, views) 
                    VALUES (:title, :price, :price_rent, :type, :unit, :thumb, :gallery, 1, NOW(), 0)";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':title'      => $title,
                ':price'      => $price,
                ':price_rent' => $priceRent,
                ':type'       => $type,
                ':unit'       => $unit,
                ':thumb'      => $thumb,
                ':gallery'    => $galleryJson
            ]);

            header("Location: index.php?msg=added");
            exit;
        } catch (PDOException $e) {
            die("Lỗi Database: " . $e->getMessage());
        }
    } else {
        // --- UPDATE (Dành cho edit.php sau này) ---
        // Phần này giữ chỗ để sau bạn nâng cấp file edit.php
    }
} else {
    // Không phải POST -> Về trang chủ
    header("Location: index.php");
    exit;
}