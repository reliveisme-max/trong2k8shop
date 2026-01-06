<?php
// admin/process.php - V8: HYBRID FIX (CHẤP NHẬN MỌI LOẠI DATA)
require_once 'auth.php';
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Tắt hiển thị lỗi PHP mặc định để tránh hỏng JSON/Text trả về
ini_set('display_errors', 0);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $title = trim($_POST['title']);

        // Check trùng
        $checkSql = "SELECT COUNT(*) FROM products WHERE title = :title AND id != :id";
        $stmtCheck = $conn->prepare($checkSql);
        $stmtCheck->execute([':title' => $title, ':id' => $id]);
        if ($stmtCheck->fetchColumn() > 0) {
            die("❌ LỖI: Mã Acc \"$title\" đã tồn tại!");
        }

        // Giá & Loại
        $price = isset($_POST['price']) ? (int)str_replace(['.', ','], '', $_POST['price']) : 0;
        $priceRent = isset($_POST['price_rent']) ? (int)str_replace(['.', ','], '', $_POST['price_rent']) : 0;
        $unit = isset($_POST['unit']) ? (int)$_POST['unit'] : 0;
        $status = isset($_POST['status']) ? 1 : ($id == 0 ? 1 : 0);
        $type = ($priceRent > 0 && $price == 0) ? 1 : 0;

        // --- XỬ LÝ ẢNH (HYBRID LOGIC) ---
        $finalImages = [];
        $orderMap = isset($_POST['order_map']) ? json_decode($_POST['order_map'], true) : [];
        $libImages = isset($_POST['library_images']) ? json_decode($_POST['library_images'], true) : [];

        // [QUAN TRỌNG] Kiểm tra cả 2 tên biến: 'files_to_upload' (Mới) và 'gallery' (Cũ)
        $uploadedFiles = [];
        $keyName = '';

        if (isset($_FILES['files_to_upload']) && !empty($_FILES['files_to_upload']['name'][0])) {
            $keyName = 'files_to_upload';
        } elseif (isset($_FILES['gallery']) && !empty($_FILES['gallery']['name'][0])) {
            $keyName = 'gallery';
        }

        // Nếu tìm thấy file gửi lên
        if ($keyName !== '') {
            $uploadedFiles = reArrayFiles($_FILES[$keyName]);
        }

        $localIndex = 0;
        $libIndex = 0;

        if (is_array($orderMap)) {
            foreach ($orderMap as $sourceType) {
                if ($sourceType === 'local') {
                    if (isset($uploadedFiles[$localIndex])) {
                        // Upload
                        $newFileName = uploadImageToWebp($uploadedFiles[$localIndex]);
                        if ($newFileName) {
                            $finalImages[] = $newFileName;
                        }
                        $localIndex++;
                    }
                } elseif ($sourceType === 'lib') {
                    if (isset($libImages[$libIndex])) {
                        $finalImages[] = $libImages[$libIndex];
                        $libIndex++;
                    }
                }
            }
        }

        // --- DEBUG: NẾU KHÔNG CÓ ẢNH THÌ IN RA THÔNG TIN ĐỂ SOI ---
        if (empty($finalImages)) {
            $debugInfo = print_r($_FILES, true); // Xem Server nhận được cái gì
            $postInfo = print_r($_POST, true);   // Xem dữ liệu POST

            die("❌ LỖI: Không nhận được ảnh nào!\n\n" .
                "🔍 THÔNG TIN DEBUG (Gửi cái này cho Admin):\n" .
                "Key tìm thấy: " . ($keyName ? $keyName : "KHÔNG CÓ") . "\n" .
                "Dữ liệu File nhận được:\n" . $debugInfo . "\n" .
                "Dữ liệu Map:\n" . $postInfo);
        }

        $thumb = $finalImages[0];
        $galleryJson = json_encode($finalImages);

        // SQL
        if ($id == 0) {
            $sql = "INSERT INTO products (title, price, price_rent, type, unit, thumb, gallery, status, created_at, views) 
                    VALUES (:title, :price, :price_rent, :type, :unit, :thumb, :gallery, :status, NOW(), 0)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':title' => $title,
                ':price' => $price,
                ':price_rent' => $priceRent,
                ':type' => $type,
                ':unit' => $unit,
                ':thumb' => $thumb,
                ':gallery' => $galleryJson,
                ':status' => $status
            ]);
            header("Location: index.php?msg=added");
        } else {
            $sql = "UPDATE products SET title=:t, price=:p, price_rent=:pr, type=:ty, unit=:u, thumb=:th, gallery=:g, status=:s WHERE id=:id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':t' => $title,
                ':p' => $price,
                ':pr' => $priceRent,
                ':ty' => $type,
                ':u' => $unit,
                ':th' => $thumb,
                ':g' => $galleryJson,
                ':s' => $status,
                ':id' => $id
            ]);
            header("Location: index.php?msg=updated");
        }
        exit;
    } catch (PDOException $e) {
        die("❌ LỖI SQL: " . $e->getMessage());
    } catch (Exception $e) {
        die("❌ LỖI HỆ THỐNG: " . $e->getMessage());
    }
}