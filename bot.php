<?php
// bot.php - V60: SEARCH & DELETE ONLY (CLEAN MODE)

// --- 1. CẤU HÌNH ---
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

require_once 'includes/config.php';

// Thay Token của bạn vào đây
define('BOT_TOKEN', '8412417564:AAH-WRxefi2sXF0EJYNj6Ib3ke3GszCojck');
define('TEMP_DIR', 'temp_data/');

// Danh sách ID Admin được phép dùng Bot (Nhớ thêm ID của bạn vào đây)
$allowed_users = ['5914616789', '8343506927'];

if (!file_exists(TEMP_DIR)) mkdir(TEMP_DIR, 0777, true);

// =================================================================
// PHẦN 1: XỬ LÝ WEBHOOK TELEGRAM
// =================================================================
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if ($update && isset($update['message'])) {
    $message = $update['message'];
    $chat_id = $message['chat']['id'];
    $text = $message['text'] ?? '';

    // 1. Chặn người lạ
    if (!in_array((string)$chat_id, $allowed_users)) {
        // Có thể mở dòng dưới nếu muốn báo cho người lạ biết họ không có quyền
        // sendTelegram($chat_id, "⛔ Bạn không có quyền truy cập!");
        exit;
    }

    // 2. File lưu trạng thái (đang ở chế độ Tra cứu hay Xóa)
    $sessionFile = TEMP_DIR . $chat_id . '.json';
    $sessionData = file_exists($sessionFile) ? json_decode(file_get_contents($sessionFile), true) : ['mode' => 'normal'];

    // 3. Xử lý Lệnh từ bàn phím
    if ($text === '/start' || $text === '🔍 TRA CỨU') {
        file_put_contents($sessionFile, json_encode(['mode' => 'normal']));
        sendTelegram($chat_id, "🔍 <b>CHẾ ĐỘ TRA CỨU</b>\n\n👉 Nhập <b>Mã Acc</b> hoặc <b>ID</b> để xem thông tin.");
        exit;
    }

    if ($text === '❌ XÓA ACC') {
        file_put_contents($sessionFile, json_encode(['mode' => 'delete']));
        sendTelegram($chat_id, "🗑️ <b>CHẾ ĐỘ XÓA ACC</b>\n\n⚠️ <b>CẢNH BÁO:</b> Nhập Mã Acc nào là xóa NGAY Acc đó (kèm ảnh). Cẩn thận!\n\n👉 Nhập Mã Acc cần xóa:");
        exit;
    }

    // 4. Xử lý tin nhắn văn bản (Logic chính)
    if (!empty($text)) {
        if ($sessionData['mode'] === 'delete') {
            // Đang ở chế độ xóa
            deleteProductFinal($text, $chat_id, $conn);
        } else {
            // Mặc định là tra cứu
            searchProductFinal($text, $chat_id, $conn);
        }
    }
}

// =================================================================
// PHẦN 2: CÁC HÀM XỬ LÝ (FUNCTIONS)
// =================================================================

function sendTelegram($cid, $txt)
{
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    // Bàn phím rút gọn chỉ còn Tra cứu và Xóa
    $keyboard = [
        'keyboard' => [
            [['text' => '🔍 TRA CỨU'], ['text' => '❌ XÓA ACC']]
        ],
        'resize_keyboard' => true,
        'is_persistent' => true
    ];

    $postData = [
        'chat_id' => $cid,
        'text' => $txt,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => false,
        'reply_markup' => json_encode($keyboard)
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 5
    ]);
    curl_exec($ch);
    curl_close($ch);
}

// Hàm Xóa Acc + Xóa Ảnh (Quan trọng)
function deleteProductFinal($input, $cid, $conn)
{
    $input = trim($input);
    // Tìm Acc trước khi xóa
    $stmt = $conn->prepare("SELECT id, title, thumb, gallery FROM products WHERE title = :i OR id = :i LIMIT 1");
    $stmt->execute([':i' => $input]);
    $p = $stmt->fetch();

    if ($p) {
        $countImg = 0;

        // 1. Xóa ảnh bìa (Thumb)
        if (!empty($p['thumb'])) {
            $thumbPath = "uploads/" . $p['thumb'];
            if (file_exists($thumbPath)) {
                @unlink($thumbPath);
                $countImg++;
            }
        }

        // 2. Xóa album ảnh (Gallery)
        $gallery = json_decode($p['gallery'], true);
        if (is_array($gallery)) {
            foreach ($gallery as $imgName) {
                $imgPath = "uploads/" . $imgName;
                if (file_exists($imgPath)) {
                    @unlink($imgPath);
                    $countImg++;
                }
            }
        }

        // 3. Xóa khỏi Database
        $del = $conn->prepare("DELETE FROM products WHERE id = :id");
        $del->execute([':id' => $p['id']]);

        sendTelegram($cid, "✅ <b>ĐÃ XÓA THÀNH CÔNG</b>\n\n🆔 Acc: <b>{$p['title']}</b>\n🗑️ Đã dọn dẹp: <b>$countImg</b> file ảnh.");
    } else {
        sendTelegram($cid, "❌ Không tìm thấy Acc nào có mã: <b>$input</b>");
    }
}

// Hàm Tra cứu Acc
function searchProductFinal($input, $cid, $conn)
{
    $input = trim($input);
    $stmt = $conn->prepare("SELECT * FROM products WHERE title = :k OR id = :k LIMIT 1");
    $stmt->execute([':k' => $input]);
    $p = $stmt->fetch();

    if ($p) {
        $status = ($p['status'] == 1) ? "🟢 Đang bán" : "🔴 Đã bán/Ẩn";
        $type = ($p['price_rent'] > 0) ? "Thuê" : "Bán";
        $price = ($p['price_rent'] > 0)
            ? number_format($p['price_rent']) . "đ / " . ($p['unit'] == 2 ? "Ngày" : "Giờ")
            : number_format($p['price']) . "đ";

        $link = BASE_URL . "detail.php?id=" . $p['id'];

        $msg = "🔎 <b>KẾT QUẢ TRA CỨU:</b>\n" .
            "➖➖➖➖➖➖➖➖\n" .
            "🆔 Mã: <b>{$p['title']}</b> (ID: {$p['id']})\n" .
            "💰 Giá: <b>$price</b>\n" .
            "📂 Loại: $type\n" .
            "info: $status\n" .
            "👀 View: " . number_format($p['views']) . "\n" .
            "➖➖➖➖➖➖➖➖\n" .
            "🔗 <a href='$link'>👉 Xem trên Web</a>";

        sendTelegram($cid, $msg);
    } else {
        sendTelegram($cid, "❓ Không tìm thấy kết quả nào cho: <b>$input</b>");
    }
}