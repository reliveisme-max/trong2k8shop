<?php
// admin/api/process_bulk.php - V6: AUTO TITLE = ID IF EMPTY

require_once '../auth.php';
require_once '../../includes/config.php';

// Tắt hiển thị lỗi ra màn hình (tránh làm hỏng JSON trả về cho JS)
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'msg' => 'Invalid Request']);
    exit;
}

try {
    if (!isset($_POST['indexes']) || !is_array($_POST['indexes'])) {
        throw new Exception("Không có dữ liệu gửi lên.");
    }

    $userId = $_SESSION['admin_id'];
    $successCount = 0;
    $targetDir = "../../uploads/";

    // 1. Chuẩn bị câu lệnh INSERT
    $sql = "INSERT INTO products (
                title, category_id, price, thumb, gallery, 
                status, created_at, views, user_id, private_note
            ) VALUES (
                :title, :cat, :price, :thumb, :gallery, 
                1, NOW(), 0, :uid, :note
            )";
    $stmt = $conn->prepare($sql);

    // 2. Chuẩn bị câu lệnh UPDATE (Dùng để sửa Tên = ID nếu bị bỏ trống)
    $sqlUpdate = $conn->prepare("UPDATE products SET title = :newTitle WHERE id = :id");

    foreach ($_POST['indexes'] as $rowId) {
        $titleRaw = trim($_POST["title_$rowId"] ?? '');
        $note = trim($_POST["note_$rowId"] ?? '');
        $catId = (int)($_POST["cat_$rowId"] ?? 0);
        $priceRaw = strtolower(trim($_POST["price_$rowId"] ?? '0'));

        // --- XỬ LÝ GIÁ THÔNG MINH ---
        $price = 0;
        $cleanVal = str_replace([',', '.'], '', $priceRaw);

        if (strpos($priceRaw, 'm') !== false || strpos($priceRaw, 'tr') !== false) {
            $val = (float)preg_replace('/[^0-9.]/', '', $priceRaw);
            $price = $val * 1000000;
        } elseif (strpos($priceRaw, 'k') !== false) {
            $val = (float)preg_replace('/[^0-9.]/', '', $priceRaw);
            $price = $val * 1000;
        } else {
            $price = (int)preg_replace('/[^0-9]/', '', $priceRaw);
        }

        if ($price <= 0) continue;

        // --- XỬ LÝ ẢNH ---
        $finalImages = [];
        // Ưu tiên ảnh JS đã upload (Chunk upload)
        if (isset($_POST["uploaded_images_$rowId"])) {
            $rawList = $_POST["uploaded_images_$rowId"];
            $finalImages = is_array($rawList) ? $rawList : json_decode($rawList, true);
        }

        // Dự phòng upload thường (Input file truyền thống)
        $fileKey = "images_$rowId";
        if (empty($finalImages) && isset($_FILES[$fileKey])) {
            $files = $_FILES[$fileKey];
            $count = count($files['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($files['error'][$i] === 0) {
                    $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'jfif'])) continue;
                    $newFileName = 'acc_' . uniqid() . '_' . time() . '_' . $i . '.' . $ext;
                    if (move_uploaded_file($files['tmp_name'][$i], $targetDir . $newFileName)) {
                        $finalImages[] = $newFileName;
                    }
                }
            }
        }

        if (empty($finalImages)) continue;

        $thumb = $finalImages[0];
        $galleryJson = json_encode($finalImages);

        // --- THỰC HIỆN INSERT ---
        $stmt->execute([
            ':title' => $titleRaw, // Có thể là chuỗi rỗng
            ':cat'   => $catId,
            ':price' => $price,
            ':thumb' => $thumb,
            ':gallery' => $galleryJson,
            ':uid' => $userId,
            ':note' => $note
        ]);

        // --- LOGIC MỚI: TỰ CẬP NHẬT TÊN = ID NẾU TRỐNG ---
        if ($titleRaw === '') {
            $newId = $conn->lastInsertId(); // Lấy ID vừa tạo (Chính xác tuyệt đối)
            $sqlUpdate->execute([
                ':newTitle' => $newId, // Gán Tên = ID (Ví dụ: 483)
                ':id' => $newId
            ]);
        }

        $successCount++;
    }

    echo json_encode(['status' => 'success', 'count' => $successCount]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
