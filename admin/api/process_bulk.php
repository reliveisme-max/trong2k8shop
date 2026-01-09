<?php
// admin/api/process_bulk.php - XỬ LÝ ĐĂNG CÔNG NGHIỆP (CÓ DANH MỤC)

// 1. Cấu hình & Auth
require_once '../auth.php';
require_once '../../includes/config.php';

// Tắt báo lỗi HTML
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

// Chỉ nhận POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'msg' => 'Invalid Request']);
    exit;
}

try {
    // Kiểm tra dữ liệu
    if (!isset($_POST['indexes']) || !is_array($_POST['indexes'])) {
        throw new Exception("Không có dữ liệu gửi lên.");
    }

    $userId = $_SESSION['admin_id'];
    $successCount = 0;

    // Đường dẫn upload (Lùi 2 cấp ra root -> uploads/)
    $targetDir = "../../uploads/";

    // Chuẩn bị câu SQL (Đã thêm cột category_id)
    $sql = "INSERT INTO products (
                title, category_id, price, thumb, gallery, 
                status, created_at, views, user_id, private_note
            ) VALUES (
                :title, :cat, :price, :thumb, :gallery, 
                1, NOW(), 0, :uid, :note
            )";
    $stmt = $conn->prepare($sql);

    // --- VÒNG LẶP XỬ LÝ TỪNG ACC ---
    foreach ($_POST['indexes'] as $rowId) {

        // 1. Lấy thông tin cơ bản
        $title = trim($_POST["title_$rowId"] ?? '');
        $priceRaw = $_POST["price_$rowId"] ?? '0';
        $note = trim($_POST["note_$rowId"] ?? '');
        $catId = (int)($_POST["cat_$rowId"] ?? 0); // Lấy ID Danh mục

        // Làm sạch giá
        $price = (int)str_replace(['.', ','], '', $priceRaw);

        // Bỏ qua nếu thiếu dữ liệu quan trọng
        if (empty($title) || $price <= 0) continue;

        // 2. Xử lý Ảnh
        $uploadedImages = [];
        $fileKey = "images_$rowId";

        if (isset($_FILES[$fileKey])) {
            $files = $_FILES[$fileKey];
            $count = count($files['name']);

            for ($i = 0; $i < $count; $i++) {
                if ($files['error'][$i] === 0) {
                    $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));

                    // Validate đuôi ảnh
                    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) continue;

                    // Tạo tên file ngẫu nhiên để tránh trùng
                    $newFileName = 'acc_' . uniqid() . '_' . time() . '_' . $i . '.' . $ext;
                    $targetPath = $targetDir . $newFileName;

                    if (move_uploaded_file($files['tmp_name'][$i], $targetPath)) {
                        $uploadedImages[] = $newFileName;
                    }
                }
            }
        }

        // Nếu không up được ảnh nào thì bỏ qua acc này
        if (empty($uploadedImages)) continue;

        // 3. Chuẩn bị dữ liệu lưu DB
        $thumb = $uploadedImages[0]; // Ảnh đầu tiên làm bìa
        $galleryJson = json_encode($uploadedImages);

        // 4. Thực thi Insert
        $stmt->execute([
            ':title' => $title,
            ':cat'   => $catId,   // Lưu danh mục
            ':price' => $price,
            ':thumb' => $thumb,
            ':gallery' => $galleryJson,
            ':uid' => $userId,
            ':note' => $note
        ]);

        $successCount++;
    }

    // Trả kết quả
    echo json_encode(['status' => 'success', 'count' => $successCount]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
