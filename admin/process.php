<?php
// admin/process.php - FINAL: XỬ LÝ CẢ THÊM MỚI & CHỈNH SỬA
require_once 'auth.php';
require_once '../includes/config.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. NHẬN DỮ LIỆU CƠ BẢN
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0; // Nếu có ID là Sửa, không có là Thêm
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    $title = trim($_POST['title'] ?? '');
    $priceRaw = trim($_POST['price'] ?? '0');
    $note = trim($_POST['private_note'] ?? '');
    $status = isset($_POST['status']) ? 1 : 0;
    $catId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $userId = $_SESSION['admin_id'];

    // 2. XỬ LÝ GIÁ TIỀN THÔNG MINH (5m -> 5.000.000)
    $price = 0;
    $cleanVal = str_replace([',', '.'], '', strtolower($priceRaw));
    if (strpos($cleanVal, 'm') !== false || strpos($cleanVal, 'tr') !== false) {
        $val = (float)preg_replace('/[^0-9.]/', '', $cleanVal);
        $price = $val * 1000000;
    } elseif (strpos($cleanVal, 'k') !== false) {
        $val = (float)preg_replace('/[^0-9.]/', '', $cleanVal);
        $price = $val * 1000;
    } else {
        $price = (int)preg_replace('/[^0-9]/', '', $cleanVal);
    }

    // 3. XỬ LÝ ẢNH (NHẬN TỪ JS GỬI SANG)
    // JS đã upload ảnh và gửi về 1 chuỗi JSON chứa danh sách tên file
    $galleryJson = $_POST['final_gallery_list'] ?? '[]';
    $galleryArr = json_decode($galleryJson, true);

    // Ảnh đại diện là ảnh đầu tiên trong list
    $thumb = (is_array($galleryArr) && count($galleryArr) > 0) ? $galleryArr[0] : '';

    try {
        // ====================================================
        // TRƯỜNG HỢP 1: THÊM MỚI (INSERT)
        // ====================================================
        if ($id == 0 || $action == 'add_single') {

            $sql = "INSERT INTO products (
                        title, category_id, price, thumb, gallery, 
                        status, created_at, views, user_id, private_note
                    ) VALUES (
                        :title, :cat, :price, :thumb, :gallery, 
                        :status, NOW(), 0, :uid, :note
                    )";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':title' => $title,
                ':cat' => $catId,
                ':price' => $price,
                ':thumb' => $thumb,
                ':gallery' => $galleryJson,
                ':status' => $status,
                ':uid' => $userId,
                ':note' => $note
            ]);

            // --- LOGIC: NẾU TÊN TRỐNG -> TỰ LẤY ID LÀM TÊN ---
            if ($title === '') {
                $newId = $conn->lastInsertId();
                $conn->prepare("UPDATE products SET title = :id WHERE id = :id")
                    ->execute([':id' => $newId]);
            }

            header("Location: index.php?msg=added");
            exit;
        }
        // ====================================================
        // TRƯỜNG HỢP 2: CHỈNH SỬA (UPDATE)
        // ====================================================
        else {

            // Nếu tên trống -> Tự lấy ID làm tên
            if ($title === '') {
                $title = (string)$id;
            }

            $sql = "UPDATE products SET 
                        title = :title, 
                        category_id = :cat, 
                        price = :price, 
                        private_note = :note, 
                        status = :status, 
                        thumb = :thumb, 
                        gallery = :gallery 
                    WHERE id = :id";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':title' => $title,
                ':cat' => $catId,
                ':price' => $price,
                ':note' => $note,
                ':status' => $status,
                ':thumb' => $thumb,
                ':gallery' => $galleryJson,
                ':id' => $id
            ]);

            header("Location: index.php?msg=updated");
            exit;
        }
    } catch (PDOException $e) {
        die("Lỗi Database: " . $e->getMessage());
    }
} else {
    header("Location: index.php");
}
