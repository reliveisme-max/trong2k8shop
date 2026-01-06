<?php
// bot.php - V50: FINAL SERVER (STABLE & FULL NOTIFY)

// --- 1. CẤU HÌNH ---
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    exit(0);
}

require_once 'includes/config.php';

// Thay Token của bạn vào đây nếu cần
define('BOT_TOKEN', '8412417564:AAH-WRxefi2sXF0EJYNj6Ib3ke3GszCojck');
define('TEMP_DIR', 'temp_data/');
$allowed_users = ['5914616789', '8343506927']; // ID Admin được phép dùng Bot

if (!file_exists(TEMP_DIR)) mkdir(TEMP_DIR, 0777, true);
if (!file_exists('uploads/')) mkdir('uploads/', 0777, true);

// =================================================================
// PHẦN 1: API GIAO TIẾP VỚI TOOL
// =================================================================

// A. CHECK TRÙNG
if (isset($_POST['check_duplicate'])) {
    $code = $_POST['check_code'];
    $title = cleanTitle($code);
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE title = :t");
        $stmt->execute([':t' => $title]);
        echo ($stmt->fetchColumn() > 0) ? "EXIST" : "OK";
    } catch (Exception $e) {
        echo "ERR";
    }
    exit;
}

// B. NHẬN ẢNH (DIRECT SAVE - TỐC ĐỘ CAO)
if (isset($_POST['upload_zalo'])) {
    $chat_id = $_POST['chat_id'];
    if (!in_array((string)$chat_id, $allowed_users)) {
        http_response_code(403);
        exit;
    }

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $ext = 'webp';
        $name = 'acc_v50_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        $target = "uploads/" . $name;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
            $sessionFile = TEMP_DIR . $chat_id . '.json';
            $currentData = file_exists($sessionFile) ? json_decode(file_get_contents($sessionFile), true) : ['images' => []];
            if (!isset($currentData['images'])) $currentData['images'] = [];

            $currentData['images'][] = $name;
            file_put_contents($sessionFile, json_encode($currentData));
            echo "OK";
        } else {
            http_response_code(500);
            echo "ErrMove";
        }
    } else {
        http_response_code(400);
        echo "ErrFile";
    }
    exit;
}

// C. CHỐT ĐƠN & GỬI THÔNG BÁO (FINISH)
if (isset($_POST['finish_upload'])) {
    $chat_id = $_POST['chat_id'];
    $sessionFile = TEMP_DIR . $chat_id . '.json';

    $images = [];
    if (file_exists($sessionFile)) {
        $data = json_decode(file_get_contents($sessionFile), true);
        $images = $data['images'] ?? [];
    }

    $autoCode = $_POST['auto_code'] ?? '';
    $autoPrice = $_POST['auto_price'] ?? '';
    $type = $_POST['auto_type'] ?? 0;
    $unit = $_POST['auto_unit'] ?? 0;

    if (!empty($autoCode) && !empty($images)) {
        $title = cleanTitle($autoCode);
        $price = parsePriceV2($autoPrice);

        try {
            // 1. Insert Database
            $sql = "INSERT INTO products (title, price, type, unit, thumb, gallery, status, created_at, views) VALUES (:t, :p, :type, :unit, :th, :g, 1, NOW(), 0)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':t' => $title,
                ':p' => $price,
                ':type' => $type,
                ':unit' => $unit,
                ':th' => $images[0],
                ':g' => json_encode($images)
            ]);
            $newId = $conn->lastInsertId();

            // 2. Xóa session
            if (file_exists($sessionFile)) unlink($sessionFile);

            // 3. Gửi Telegram
            $typeLabel = ($type == 1) ? (($unit == 2) ? "📅 Thuê Ngày" : "⏱️ Thuê Giờ") : "🛒 Bán Vĩnh Viễn";
            $count = count($images);
            $link = BASE_URL . "detail.php?id=$newId";

            $msg = "✅ <b>LÊN ĐƠN THÀNH CÔNG</b>\n" .
                "➖➖➖➖➖➖➖➖➖➖\n" .
                "📦 Mã: <b>$title</b>\n" .
                "💰 Giá: <b>" . number_format($price) . " VNĐ</b>\n" .
                "📂 Loại: $typeLabel\n" .
                "🖼 Ảnh: <b>$count file</b>\n" .
                "➖➖➖➖➖➖➖➖➖➖\n" .
                "🔗 <a href='$link'>👉 XEM TRÊN WEB</a>";

            sendTelegram($chat_id, $msg);
            echo "Success";
        } catch (Exception $e) {
            file_put_contents('error_log.txt', $e->getMessage(), FILE_APPEND);
            echo "Err SQL";
        }
    } else {
        echo "EmptyData";
    }
    exit;
}

// D. API NOTIFY (Báo Start)
if (isset($_POST['notify_tele'])) {
    $chat_id = $_POST['chat_id'];
    $msg = $_POST['msg'];
    if (!empty($msg)) sendTelegram($chat_id, $msg);
    exit;
}

// =================================================================
// PHẦN 2: WEBHOOK TELEGRAM
// =================================================================
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if ($update && isset($update['message'])) {
    $message = $update['message'];
    $chat_id = $message['chat']['id'];
    $text = $message['text'] ?? '';

    if (!in_array((string)$chat_id, $allowed_users)) exit;

    if ($text === '/start' || $text === '📝 ĐĂNG ACC') {
        sendTelegram($chat_id, "⚙️ <b>HỆ THỐNG AUTO V50 ĐÃ SẴN SÀNG</b>");
        exit;
    }
    if ($text === '❌ XÓA ACC') {
        sendTelegram($chat_id, "🗑️ Nhập MÃ ACC để xóa:");
        $sessionFile = TEMP_DIR . $chat_id . '.json';
        file_put_contents($sessionFile, json_encode(['mode' => 'delete']));
        exit;
    }

    $sessionFile = TEMP_DIR . $chat_id . '.json';
    $sessionData = file_exists($sessionFile) ? json_decode(file_get_contents($sessionFile), true) : [];

    if (isset($sessionData['mode']) && $sessionData['mode'] === 'delete') {
        deleteProductV2($text, $chat_id, $conn);
        file_put_contents($sessionFile, json_encode(['mode' => 'normal']));
    } else {
        if (!empty($text)) searchProduct($text, $chat_id, $conn);
    }
}

// =================================================================
// PHẦN 3: HELPER FUNCTIONS
// =================================================================
function sendTelegram($cid, $txt)
{
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    $buttons = json_encode(['keyboard' => [[['text' => '📝 ĐĂNG ACC'], ['text' => '❌ XÓA ACC']]], 'resize_keyboard' => true, 'is_persistent' => true]);
    $postData = ['chat_id' => $cid, 'text' => $txt, 'parse_mode' => 'HTML', 'disable_web_page_preview' => false, 'reply_markup' => $buttons];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_TIMEOUT => 10
    ]);
    $res = curl_exec($ch);
    if (curl_errno($ch)) {
        file_put_contents('tele_error.log', date('Y-m-d H:i:s') . ' - ' . curl_error($ch) . "\n", FILE_APPEND);
    }
    curl_close($ch);
}

function cleanTitle($s)
{
    foreach (['Mã:', 'Mã', 'Tên:', 'Tên', 'Acc:', 'Acc '] as $p) if (mb_stripos($s, $p) === 0) $s = mb_substr($s, mb_strlen($p));
    return trim($s);
}
function parsePriceV2($s)
{
    $s = mb_strtolower($s, 'UTF-8');
    $m = 1;
    if (strpos($s, 'm') !== false) {
        $m = 1000000;
        $s = str_replace('m', '.', $s);
    } elseif (strpos($s, 'k') !== false) {
        $m = 1000;
        $s = str_replace('k', '', $s);
    }
    $s = preg_replace('/[^0-9.,]/', '', $s);
    $s = str_replace(',', '.', $s);
    return (int)((float)$s * $m);
}
function deleteProductV2($in, $cid, $conn)
{
    $in = trim($in);
    $s = $conn->prepare("SELECT id, title, thumb, gallery FROM products WHERE title = :i OR id = :i LIMIT 1");
    $s->execute([':i' => $in]);
    $p = $s->fetch();
    if ($p) {
        if ($p['thumb']) @unlink("uploads/" . $p['thumb']);
        $g = json_decode($p['gallery'], true);
        if (is_array($g)) foreach ($g as $gi) @unlink("uploads/" . $gi);
        $conn->prepare("DELETE FROM products WHERE id = :id")->execute([':id' => $p['id']]);
        sendTelegram($cid, "🗑️ Đã xóa: <b>{$p['title']}</b>");
    } else sendTelegram($cid, "❌ Không tìm thấy: <b>$in</b>");
}
function searchProduct($k, $cid, $conn)
{
    $k = trim($k);
    $s = $conn->prepare("SELECT * FROM products WHERE title = :k OR id = :k LIMIT 1");
    $s->execute([':k' => $k]);
    $p = $s->fetch();
    if ($p) {
        $lk = BASE_URL . "detail.php?id=" . $p['id'];
        sendTelegram($cid, "🔎 <b>TRA CỨU:</b>\n🆔 <b>{$p['title']}</b>\n💰 <b>" . number_format($p['price']) . "</b>\n🔗 <a href='$lk'>Xem trên Web</a>");
    } else sendTelegram($cid, "❓ Không tìm thấy: <b>$k</b>");
}