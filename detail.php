<?php
// detail.php - TRANG CHI TIẾT SẢN PHẨM
require_once 'includes/config.php';
require_once 'includes/functions.php';

// 1. LẤY ID TỪ URL VÀ KIỂM TRA
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id == 0) {
    header("Location: index.php");
    exit;
}

// 2. TRUY VẤN DỮ LIỆU
$stmt = $conn->prepare("SELECT * FROM products WHERE id = :id");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch();

// Nếu không tìm thấy acc
if (!$product) {
    die("Acc này không tồn tại hoặc đã bị xóa!");
}

// Giải mã JSON album ảnh
$gallery = json_decode($product['gallery'], true);
// Nếu null (do acc cũ chưa có gallery) thì gán mảng rỗng
if (!is_array($gallery)) $gallery = [];
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acc #<?= $product['id'] ?> - <?= $product['title'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">

    <style>
    /* CSS RIÊNG CHO TRANG DETAIL */

    /* Box thông tin chính */
    .detail-box {
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        border: 1px solid #e5e7eb;
    }

    /* Ảnh bìa bên trái */
    .detail-thumb img {
        width: 100%;
        height: auto;
        border-radius: 8px;
        border: 1px solid #eee;
    }

    /* Giá tiền to bự */
    .detail-price {
        font-size: 28px;
        font-weight: 900;
        color: #dc2626;
        margin: 10px 0;
    }

    /* Mô tả (cho phép xuống dòng) */
    .detail-desc {
        background: #f9fafb;
        padding: 15px;
        border-radius: 8px;
        border: 1px dashed #d1d5db;
        color: #374151;
        font-size: 15px;
        line-height: 1.6;
        white-space: pre-line;
        /* Giữ nguyên xuống dòng của người nhập */
    }

    /* Khu vực Album ảnh (Show hàng dọc) */
    .gallery-container {
        margin-top: 30px;
        text-align: center;
    }

    .gallery-title {
        font-weight: 800;
        text-transform: uppercase;
        margin-bottom: 20px;
        position: relative;
        display: inline-block;
        padding-bottom: 10px;
    }

    .gallery-title::after {
        content: '';
        width: 50%;
        height: 3px;
        background: #f59e0b;
        /* Màu cam */
        position: absolute;
        bottom: 0;
        left: 25%;
    }

    .gallery-item {
        margin-bottom: 20px;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }

    .gallery-item img {
        width: 100%;
        display: block;
        transition: transform 0.3s;
    }

    /* Hiệu ứng khi rê chuột vào ảnh album */
    .gallery-item:hover img {
        transform: scale(1.02);
    }

    /* Nút Mua Cố định dưới chân Mobile */
    .sticky-action-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        background: white;
        padding: 12px;
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
        z-index: 999;
        display: flex;
        gap: 10px;
        border-top: 1px solid #eee;
    }
    </style>
</head>

<body>

    <!-- 1. HEADER (Copy từ index.php) -->
    <header class="main-header">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="index.php" class="text-decoration-none">
                <div class="logo-text">
                    <i class="ph-fill ph-crosshair"></i> TRỌNG 2K8 SHOP
                </div>
            </a>
            <a href="index.php" class="btn btn-outline-light btn-sm">
                <i class="ph-bold ph-arrow-u-up-left"></i> Quay lại
            </a>
        </div>
    </header>

    <div class="container py-4 mb-5">

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" class="text-secondary text-decoration-none">Trang
                        chủ</a></li>
                <li class="breadcrumb-item active fw-bold" aria-current="page">Acc Mã Số #<?= $product['id'] ?></li>
            </ol>
        </nav>

        <!-- 2. KHỐI THÔNG TIN CHUNG -->
        <div class="detail-box p-3 p-md-4">
            <div class="row g-4">

                <!-- Cột Trái: Ảnh Bìa -->
                <div class="col-12 col-md-5">
                    <div class="detail-thumb">
                        <img src="uploads/<?= $product['thumb'] ?>" alt="<?= $product['title'] ?>">
                    </div>
                </div>

                <!-- Cột Phải: Thông tin & Nút Mua -->
                <div class="col-12 col-md-7">
                    <h4 class="fw-bold text-uppercase mb-2 lh-base"><?= $product['title'] ?></h4>

                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-dark">MS: #<?= $product['id'] ?></span>
                        <span class="badge bg-secondary"><?= date('d/m/Y', strtotime($product['created_at'])) ?></span>
                        <?php if ($product['status'] == 1): ?>
                        <span class="badge bg-success">Đang Bán</span>
                        <?php else: ?>
                        <span class="badge bg-danger">Đã Bán</span>
                        <?php endif; ?>
                    </div>

                    <div class="detail-price">
                        <?= formatPrice($product['price']) ?>
                    </div>

                    <div class="detail-desc mb-4">
                        <b>Mô tả:</b><br>
                        <?= nl2br($product['description']) ?>
                    </div>

                    <!-- Nút Mua Hàng (Desktop) -->
                    <div class="d-none d-md-block">
                        <?php if ($product['status'] == 1): ?>
                        <button onclick="buyNow()" class="btn btn-primary btn-lg w-100 fw-bold py-3 text-uppercase fs-5"
                            style="background: linear-gradient(to right, #f59e0b, #d97706); border:none;">
                            <i class="ph-fill ph-shopping-cart"></i> Mua Ngay (Zalo)
                        </button>
                        <div class="text-center mt-2 text-muted fst-italic text-sm">
                            <small>Hỗ trợ giao dịch trung gian, đổi thông tin an toàn 100%</small>
                        </div>
                        <?php else: ?>
                        <button class="btn btn-secondary btn-lg w-100 fw-bold py-3" disabled>
                            ĐÃ BÁN
                        </button>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>

        <!-- 3. KHỐI HÌNH ẢNH CHI TIẾT (SHOW DỌC - KHÔNG SLIDER) -->
        <?php if (!empty($gallery)): ?>
        <div class="gallery-container">
            <h4 class="gallery-title">Hình Ảnh Chi Tiết</h4>
            <div class="row justify-content-center">
                <div class="col-12 col-md-10">

                    <!-- Vòng lặp in ảnh ra hết -->
                    <?php foreach ($gallery as $img): ?>
                    <div class="gallery-item">
                        <img src="uploads/<?= $img ?>" loading="lazy" alt="Ảnh chi tiết">
                    </div>
                    <?php endforeach; ?>

                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- 4. FOOTER MOBILE STICKY (Nút mua dính dưới đáy điện thoại) -->
    <div class="sticky-action-bar d-md-none">
        <div class="d-flex flex-column justify-content-center">
            <span class="text-muted" style="font-size: 11px;">Giá bán:</span>
            <span class="fw-bold text-danger fs-5"><?= formatPrice($product['price']) ?></span>
        </div>

        <?php if ($product['status'] == 1): ?>
        <button onclick="buyNow()" class="btn btn-primary flex-grow-1 fw-bold"
            style="background: #f59e0b; border:none;">
            MUA NGAY
        </button>
        <?php else: ?>
        <button class="btn btn-secondary flex-grow-1 fw-bold" disabled>ĐÃ BÁN</button>
        <?php endif; ?>
    </div>

    <!-- SCRIPT XỬ LÝ -->
    <script>
    // Hàm chuyển hướng sang Zalo với nội dung soạn sẵn
    function buyNow() {
        var id = "<?= $product['id'] ?>";
        var price = "<?= formatPrice($product['price']) ?>";
        var url = window.location.href; // Lấy link hiện tại

        // Số Zalo của Admin
        var zaloPhone = "0984074897";

        // Nội dung tin nhắn
        var content = `Chào Shop, mình muốn mua Acc Mã Số #${id} giá ${price}.\nLink: ${url}`;

        // Mã hóa URL để gửi qua web
        var zaloLink = `https://zalo.me/${zaloPhone}?text=${encodeURIComponent(content)}`;

        // Mở tab mới
        window.open(zaloLink, '_blank');
    }
    </script>

    <!-- Footer chung -->
    <footer class="text-center py-4 mt-auto border-top bg-white pb-5 pb-md-3">
        <p class="mb-0 text-secondary fw-bold text-uppercase" style="font-size: 12px; letter-spacing: 1px;">
            &copy; 2024 Trong2k8 Shop - PUBG Mobile Vietnam
        </p>
    </footer>

</body>

</html>