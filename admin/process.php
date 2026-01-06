<?php
// admin/process.php - V3: XỬ LÝ FULL (THÊM + SỬA + ẢNH KÉO THẢ)
require_once 'auth.php';
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Chỉ xử lý POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. LẤY DỮ LIỆU ĐẦU VÀO
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $title = trim($_POST['title']);

    // Xử lý giá
    $priceRaw = isset($_POST['price']) ? str_replace(['.', ','], '', $_POST['price']) : 0;
    $price = (int)$priceRaw;

    $priceRentRaw = isset($_POST['price_rent']) ? str_replace(['.', ','], '', $_POST['price_rent']) : 0;
    $priceRent = (int)$priceRentRaw;

    $unit = isset($_POST['unit']) ? (int)$_POST['unit'] : 0;

    // Trạng thái (Nếu checkbox được tích thì là 1, không thì là 0. Mặc định thêm mới là 1)
    $status = isset($_POST['status']) ? 1 : ($id == 0 ? 1 : 0);

    // Xác định Type (Legacy support)
    $type = ($priceRent > 0 && $price == 0) ? 1 : 0;

    // 2. XỬ LÝ ẢNH (LOGIC PHỨC TẠP NHẤT)
    $finalImages = [];

    // Bản đồ thứ tự ảnh (từ JS gửi lên)
    $orderMap = isset($_POST['order_map']) ? json_decode($_POST['order_map'], true) : [];

    // Danh sách tên ảnh từ thư viện (hoặc ảnh cũ)
    $libImages = isset($_POST['library_images']) ? json_decode($_POST['library_images'], true) : [];

    // Danh sách ảnh mới upload từ máy
    $uploadedFiles = [];
    if (!empty($_FILES['gallery']['name'][0])) {
        $uploadedFiles = reArrayFiles($_FILES['gallery']);
    }

    $localIndex = 0;
    $libIndex = 0;

    // Duyệt qua bản đồ để lắp ghép danh sách ảnh cuối cùng
    if (is_array($orderMap)) {
        foreach ($orderMap as $sourceType) {
            if ($sourceType === 'local') {
                // Upload ảnh mới
                if (isset($uploadedFiles[$localIndex])) {
                    $newFileName = uploadImageToWebp($uploadedFiles[$localIndex]);
                    if ($newFileName) {
                        $finalImages[] = $newFileName;
                    }
                    $localIndex++;
                }
            } elseif ($sourceType === 'lib') {
                // Lấy tên ảnh cũ/thư viện
                if (isset($libImages[$libIndex])) {
                    $finalImages[] = $libImages[$libIndex];
                    $libIndex++;
                }
            }
        }
    }

    // Kiểm tra nếu không có ảnh nào
    if (empty($finalImages)) {
        // Nếu đang sửa mà lỡ tay xóa hết ảnh -> Báo lỗi ngay
        // (Hoặc bạn có thể cho phép không ảnh, nhưng shop game thì nên bắt buộc)
        die("Lỗi: Vui lòng chọn ít nhất 1 ảnh cho sản phẩm!");
    }

    // Tách Thumb (Ảnh đầu tiên) và Gallery (Toàn bộ)
    $thumb = $finalImages[0];
    $galleryJson = json_encode($finalImages);


    // 3. THỰC HIỆN DATABASE (INSERT HOẶC UPDATE)

    try {
        if ($id == 0) {
            // --- THÊM MỚI (INSERT) ---
            $sql = "INSERT INTO products (title, price, price_rent, type, unit, thumb, gallery, status, created_at, views) 
                    VALUES (:title, :price, :price_rent, :type, :unit, :thumb, :gallery, :status, NOW(), 0)";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':title'      => $title,
                ':price'      => $price,
                ':price_rent' => $priceRent,
                ':type'       => $type,
                ':unit'       => $unit,
                ':thumb'      => $thumb,
                ':gallery'    => $galleryJson,
                ':status'     => $status
            ]);

            header("Location: index.php?msg=added");
        } else {
            // --- CẬP NHẬT (UPDATE) ---
            $sql = "UPDATE products SET 
                    title = :title,
                    price = :price,
                    price_rent = :price_rent,
                    type = :type,
                    unit = :unit,
                    thumb = :thumb,
                    gallery = :gallery,
                    status = :status
                    WHERE id = :id";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':title'      => $title,
                ':price'      => $price,
                ':price_rent' => $priceRent,
                ':type'       => $type,
                ':unit'       => $unit,
                ':thumb'      => $thumb,
                ':gallery'    => $galleryJson,
                ':status'     => $status,
                ':id'         => $id
            ]);

            header("Location: index.php?msg=updated");
        }
        exit;
    } catch (PDOException $e) {
        die("Lỗi Database: " . $e->getMessage());
    }
} else {
    // Không phải POST -> Về trang chủ
    header("Location: index.php");
    exit;
}