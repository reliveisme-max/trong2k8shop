<?php
// admin/api/upload.php
ob_start();

session_start();
// Tắt hiển thị lỗi ra màn hình (để tránh làm hỏng JSON)
ini_set('display_errors', 0);
error_reporting(0);

// --- [QUAN TRỌNG] HÀM BẮT LỖI SẬP SERVER ---
function shutdownHandler()
{
    $error = error_get_last();
    // Nếu có lỗi nghiêm trọng (Fatal Error, Parse Error...)
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_CORE_ERROR)) {
        ob_clean(); // Xóa sạch mọi mã HTML lỗi trước đó
        // Trả về JSON báo lỗi chi tiết
        echo json_encode([
            'status' => 'error',
            'msg' => "Lỗi Server (Dòng {$error['line']}): {$error['message']}"
        ]);
        exit;
    }
}
// Đăng ký hàm này chạy khi script kết thúc hoặc chết
register_shutdown_function('shutdownHandler');

require_once '../auth.php';
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

// Hàm gửi JSON sạch sẽ
function sendJson($data)
{
    ob_end_clean(); // Xóa bộ đệm
    echo json_encode($data);
    exit;
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_id'])) {
    sendJson(['status' => 'error', 'msg' => 'Chưa đăng nhập']);
}

// Kiểm tra xem dữ liệu có bị rỗng do file quá nặng không?
if (empty($_FILES) && empty($_POST) && $_SERVER['CONTENT_LENGTH'] > 0) {
    $maxPost = ini_get('post_max_size');
    $maxUpload = ini_get('upload_max_filesize');
    sendJson([
        'status' => 'error',
        'msg' => "File quá nặng! Server từ chối nhận. (Giới hạn hiện tại: Post $maxPost, Upload $maxUpload). Hãy chỉnh lại php.ini"
    ]);
}

try {
    if (isset($_POST['ajax_upload_mode']) && $_POST['ajax_upload_mode'] == '1') {
        $responseMap = [];
        $uids = isset($_POST['chunk_uids']) ? $_POST['chunk_uids'] : [];

        if (isset($_FILES['chunk_files'])) {
            $files = reArrayFiles($_FILES['chunk_files']);

            // Chuyển về thư mục admin/ để hàm upload tìm đúng đường dẫn
            chdir('../');

            foreach ($files as $index => $file) {
                // Dùng @ để chặn Warning của thư viện ảnh
                $result = @uploadImageToWebp($file);

                if ($result && isset($uids[$index])) {
                    $responseMap[$uids[$index]] = $result;
                }
            }
        }
        sendJson(['status' => 'success', 'data' => $responseMap]);
    }

    sendJson(['status' => 'error', 'msg' => 'Không có dữ liệu gửi lên']);
} catch (Exception $e) {
    sendJson(['status' => 'error', 'msg' => $e->getMessage()]);
}
