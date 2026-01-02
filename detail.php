<?php
// detail.php - ĐÃ TÍCH HỢP FANCYBOX (XEM ẢNH POPUP)
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

// 3. XÁC ĐỊNH LOẠI ACC & ĐƠN VỊ TÍNH
$isRent = ($product['type'] == 1); // True nếu là Thuê
$backLink = $isRent ? 'index.php?view=rent' : 'index.php?view=shop'; // Link quay lại đúng tab

// Xác định chữ hiển thị đơn vị
$unitLabel = "";
if ($isRent) {
    $unitLabel = ($product['unit'] == 2) ? "/ ngày" : "/ giờ";
}

// 4. XỬ LÝ ALBUM ẢNH
$gallery = json_decode($product['gallery'], true);
if (!is_array($gallery)) $gallery = [];
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acc #<?= $product['id'] ?> - <?= $product['title'] ?></title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icon -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <!-- Fancybox CSS (Thư viện xem ảnh) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css" />

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
            <a href="<?= $backLink ?>" class="btn btn-outline-dark btn-sm rounded-pill px-3 fw-bold">
                <i class="ph-bold ph-arrow-left"></i> Quay lại
            </a>
        </div>
    </header>

    <div class="container py-4 mb-5">

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?= $backLink ?>" class="text-secondary text-decoration-none">
                        <?= $isRent ? 'Danh sách Acc Thuê' : 'Danh sách Acc Bán' ?>
                    </a>
                </li>
                <li class="breadcrumb-item active fw-bold" aria-current="page">Chi tiết Acc #<?= $product['id'] ?></li>
            </ol>
        </nav>

        <!-- 2. KHỐI THÔNG TIN CHÍNH -->
        <div class="detail-box mb-4">
            <div class="row g-4">

                <!-- Cột Trái: Ảnh Bìa -->
                <div class="col-12 col-md-5">
                    <div class="detail-thumb position-relative">
                        <!-- Thêm data-fancybox để xem ảnh bìa luôn -->
                        <a href="uploads/<?= $product['thumb'] ?>" data-fancybox="gallery"
                            data-caption="Ảnh bìa - Acc #<?= $product['id'] ?>">
                            <img src="uploads/<?= $product['thumb'] ?>" alt="<?= $product['title'] ?>"
                                class="w-100 rounded-3 border">
                        </a>

                        <?php if ($product['status'] == 0): ?>
                        <div class="sold-overlay rounded-3">
                            <?= $isRent ? 'ĐANG CÓ NGƯỜI THUÊ' : 'ĐÃ BÁN' ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Cột Phải: Thông tin -->
                <div class="col-12 col-md-7">
                    <h4 class="fw-bold lh-base mb-2">
                        <?php if ($isRent): ?>
                        <span class="badge bg-info text-dark align-middle" style="font-size: 14px;">THUÊ</span>
                        <?php endif; ?>
                        <?= $product['title'] ?>
                    </h4>

                    <div class="d-flex align-items-center gap-2 mb-3 text-secondary text-sm">
                        <span class="badge bg-light text-dark border">MS: #<?= $product['id'] ?></span>
                        <span><i class="ph-bold ph-clock"></i>
                            <?= date('d/m/Y', strtotime($product['created_at'])) ?></span>
                    </div>

                    <div class="detail-price">
                        <?= formatPrice($product['price']) ?>
                        <!-- HIỂN THỊ ĐƠN VỊ TÍNH -->
                        <?php if ($isRent): ?>
                        <span class="fs-6 text-secondary fw-normal"><?= $unitLabel ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Mô tả -->
                    <div class="detail-desc mb-4">
                        <div class="fw-bold mb-2 text-dark"><i class="ph-fill ph-info"></i> Thông tin chi tiết:</div>
                        <?= $product['description'] ? $product['description'] : "Chưa có mô tả chi tiết cho acc này." ?>
                    </div>

                    <!-- Nút Hành Động (Mua / Thuê) -->
                    <?php if ($product['status'] == 1): ?>

                    <button onclick="buyNow()" class="btn btn-warning w-100 py-3 text-uppercase fw-bold fs-5 shadow-sm">
                        <?php if ($isRent): ?>
                        <i class="ph-bold ph-key"></i> THUÊ NGAY (Qua Zalo)
                        <?php else: ?>
                        <i class="ph-bold ph-shopping-cart"></i> MUA NGAY (Qua Zalo)
                        <?php endif; ?>
                    </button>

                    <div class="text-center mt-2 text-secondary fst-italic" style="font-size: 13px;">
                        * Hỗ trợ giao dịch trung gian, đổi thông tin an toàn 100%
                    </div>

                    <?php else: ?>

                    <button class="btn btn-secondary w-100 py-3 text-uppercase fw-bold fs-5" disabled>
                        <i class="ph-bold ph-lock-key"></i>
                        <?= $isRent ? 'ACC ĐANG CÓ NGƯỜI THUÊ' : 'ACC NÀY ĐÃ BÁN' ?>
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
                    <!-- TÍCH HỢP FANCYBOX: Thêm data-fancybox="gallery" -->
                    <a href="uploads/<?= $img ?>" data-fancybox="gallery"
                        data-caption="Ảnh chi tiết - Acc #<?= $product['id'] ?>">
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

    <!-- FANCYBOX JS -->
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
    <script>
    // Kích hoạt Fancybox
    Fancybox.bind("[data-fancybox]", {
        // Tùy chọn nếu cần (hiện tại để mặc định là đủ đẹp)
        Thumbs: {
            type: "modern"
        }
    });

    function buyNow() {
        var id = "<?= $product['id'] ?>";
        var price = "<?= formatPrice($product['price']) ?>";
        var unitLabel = "<?= $unitLabel ?>";
        var url = window.location.href;

        var actionText = "<?= $isRent ? 'THUÊ' : 'MUA' ?>";

        // Số Zalo của Shop
        var zaloPhone = "0984074897";

        // Nội dung tin nhắn
        var content = `Chào Shop, mình muốn ${actionText} Acc Mã Số #${id} giá ${price}${unitLabel}.\nLink: ${url}`;

        var zaloLink = `https://zalo.me/${zaloPhone}?text=${encodeURIComponent(content)}`;
        window.open(zaloLink, '_blank');
    }
    </script>

</body>

</html>