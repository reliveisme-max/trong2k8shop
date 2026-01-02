<?php
// detail.php - TRANG CHI TIẾT SẢN PHẨM (LIGHT MODE)
require_once 'includes/config.php';
require_once 'includes/functions.php';

// 1. LẤY ID TỪ URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id == 0) {
    header("Location: index.php");
    exit;
}

// 2. TRUY VẤN DATABASE
$stmt = $conn->prepare("SELECT * FROM products WHERE id = :id");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch();

// Nếu không tìm thấy acc
if (!$product) {
    die("Acc này không tồn tại hoặc đã bị xóa!");
}

// 3. XỬ LÝ ALBUM ẢNH (JSON -> Array)
$gallery = json_decode($product['gallery'], true);
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
</head>

<body>

    <!-- 1. HEADER -->
    <header class="main-header">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="index.php" class="text-decoration-none">
                <div class="logo-text">
                    <i class="ph-fill ph-crosshair"></i> TRỌNG 2K8 SHOP
                </div>
            </a>
            <a href="index.php" class="btn btn-outline-dark btn-sm rounded-pill px-3 fw-bold">
                <i class="ph-bold ph-arrow-left"></i> Quay lại
            </a>
        </div>
    </header>

    <div class="container py-4 mb-5">

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" class="text-secondary text-decoration-none">Trang
                        chủ</a></li>
                <li class="breadcrumb-item active fw-bold" aria-current="page">Chi tiết Acc #<?= $product['id'] ?></li>
            </ol>
        </nav>

        <!-- 2. KHỐI THÔNG TIN CHÍNH -->
        <div class="detail-box mb-4">
            <div class="row g-4">

                <!-- Cột Trái: Ảnh Bìa -->
                <div class="col-12 col-md-5">
                    <div class="detail-thumb position-relative">
                        <img src="uploads/<?= $product['thumb'] ?>" alt="<?= $product['title'] ?>"
                            class="w-100 rounded-3 border">
                        <?php if ($product['status'] == 0): ?>
                        <div class="sold-overlay rounded-3">ĐÃ BÁN</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Cột Phải: Thông tin -->
                <div class="col-12 col-md-7">
                    <h4 class="fw-bold lh-base mb-2"><?= $product['title'] ?></h4>

                    <div class="d-flex align-items-center gap-2 mb-3 text-secondary text-sm">
                        <span class="badge bg-light text-dark border">MS: #<?= $product['id'] ?></span>
                        <span><i class="ph-bold ph-clock"></i>
                            <?= date('d/m/Y', strtotime($product['created_at'])) ?></span>
                    </div>

                    <div class="detail-price">
                        <?= formatPrice($product['price']) ?>
                    </div>

                    <!-- Mô tả -->
                    <div class="detail-desc mb-4">
                        <div class="fw-bold mb-2 text-dark"><i class="ph-fill ph-info"></i> Thông tin chi tiết:</div>
                        <?= $product['description'] ? $product['description'] : "Chưa có mô tả chi tiết cho acc này." ?>
                    </div>

                    <!-- Nút Mua -->
                    <?php if ($product['status'] == 1): ?>
                    <button onclick="buyNow()" class="btn btn-warning w-100 py-3 text-uppercase fw-bold fs-5 shadow-sm">
                        <i class="ph-bold ph-shopping-cart"></i> Mua Ngay (Qua Zalo)
                    </button>
                    <div class="text-center mt-2 text-secondary fst-italic" style="font-size: 13px;">
                        * Hỗ trợ giao dịch trung gian, đổi thông tin an toàn 100%
                    </div>
                    <?php else: ?>
                    <button class="btn btn-secondary w-100 py-3 text-uppercase fw-bold fs-5" disabled>
                        <i class="ph-bold ph-lock-key"></i> ACC NÀY ĐÃ BÁN
                    </button>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <!-- 3. ALBUM ẢNH CHI TIẾT -->
        <?php if (!empty($gallery)): ?>
        <div class="detail-box">
            <h5 class="fw-bold mb-3 text-uppercase border-bottom pb-2">
                <i class="ph-fill ph-images text-warning"></i> Hình ảnh chi tiết
            </h5>

            <div class="gallery-grid">
                <?php foreach ($gallery as $img): ?>
                <div class="gallery-item">
                    <a href="uploads/<?= $img ?>" target="_blank">
                        <img src="uploads/<?= $img ?>" loading="lazy" alt="Ảnh chi tiết">
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- 4. FOOTER -->
    <footer>
        <div class="container">
            <p class="mb-1 text-uppercase">&copy; 2024 TRỌNG 2K8 SHOP - UY TÍN TẠO NIỀM TIN</p>
            <p class="mb-0">Hỗ trợ Zalo: <span class="text-dark fw-bold">0984.074.897</span></p>
        </div>
    </footer>

    <!-- SCRIPT MUA HÀNG -->
    <script>
    function buyNow() {
        var id = "<?= $product['id'] ?>";
        var price = "<?= formatPrice($product['price']) ?>";
        var url = window.location.href; // Lấy link hiện tại

        // Số Zalo của Shop
        var zaloPhone = "0984074897";

        // Nội dung tin nhắn
        var content = `Chào Shop, mình muốn mua Acc Mã Số #${id} giá ${price}.\nLink: ${url}`;

        // Chuyển hướng
        var zaloLink = `https://zalo.me/${zaloPhone}?text=${encodeURIComponent(content)}`;
        window.open(zaloLink, '_blank');
    }
    </script>

</body>

</html>