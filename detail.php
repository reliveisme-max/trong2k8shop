<?php
// detail.php - FINAL COMBINED: PINK EDITION + FIX TRÙNG ẢNH
require_once 'includes/config.php';
require_once 'includes/functions.php';

// 1. LẤY ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id == 0) {
    header("Location: index.php");
    exit;
}

// 2. LẤY DỮ LIỆU
$stmt = $conn->prepare("SELECT id, title, price, type, unit, thumb, gallery, status, views, created_at FROM products WHERE id = :id");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch();

if (!$product) die("Acc không tồn tại!");

// 3. TĂNG VIEW
$conn->prepare("UPDATE products SET views = views + 1 WHERE id = :id")->execute([':id' => $id]);
$product['views']++;

// 4. XỬ LÝ HIỂN THỊ
$isRent = ($product['type'] == 1);
$unitLabel = $isRent ? (($product['unit'] == 2) ? "/ ngày" : "/ giờ") : "";
$backLink = $isRent ? '?view=rent' : '?view=shop';

// 5. XỬ LÝ ALBUM ẢNH (Lọc bỏ ảnh bìa ra khỏi Gallery để tránh trùng)
$gallery = json_decode($product['gallery'], true);
if (!is_array($gallery)) $gallery = [];

if (!empty($product['thumb'])) {
    $gallery = array_values(array_filter($gallery, function ($img) use ($product) {
        return $img !== $product['thumb']; // Chỉ giữ lại ảnh KHÁC ảnh bìa
    }));
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mã số: <?= $product['title'] ?> | TRỌNG 2K8 SHOP</title>

    <!-- CSS & FONT -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css" />
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
</head>

<body>

    <!-- HEADER -->
    <header class="main-header">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="./" class="text-decoration-none">
                <div class="logo-text"><i class="ph-fill ph-heart"></i> TRỌNG 2K8</div>
            </a>
            <a href="<?= $backLink ?>" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-bold"
                style="border-color: var(--border); color: var(--text-sub);">
                <i class="ph-bold ph-arrow-left"></i> Quay lại
            </a>
        </div>
    </header>

    <div class="container py-4 detail-container">

        <!-- 1. KHỐI THÔNG TIN CHUNG (HEADER) -->
        <div class="detail-header text-center">
            <h1 class="detail-title">
                <?php if ($isRent): ?>
                    <span class="badge bg-danger bg-opacity-75 align-middle" style="font-size: 14px;">THUÊ</span>
                <?php endif; ?>
                Mã số: <?= $product['title'] ?>
            </h1>

            <div class="text-secondary mb-3" style="font-size: 13px;">
                <i class="ph-fill ph-eye"></i> <?= number_format($product['views']) ?> xem &bull;
                <i class="ph-bold ph-clock"></i> <?= date('d/m/Y', strtotime($product['created_at'])) ?>
            </div>

            <div class="detail-price-lg">
                <span class="text-secondary fw-normal" style="font-size: 20px; vertical-align: middle;">Giá: </span>
                <?= formatPrice($product['price']) ?> <small
                    style="font-size: 16px; font-weight: normal;"><?= $unitLabel ?></small>
            </div>

            <!-- NÚT MUA HÀNG -->
            <?php if ($product['status'] == 1): ?>
                <button onclick="buyNow()" class="btn-buy-lg">
                    <i class="ph-bold ph-heart"></i> <?= $isRent ? 'THUÊ NGAY' : 'MUA NGAY' ?> (QUA ZALO)
                </button>
                <div class="mt-2 text-secondary fst-italic" style="font-size: 12px;">
                    * Giao dịch tự động hoặc trung gian uy tín 100%
                </div>
            <?php else: ?>
                <button class="btn btn-secondary w-100 py-3 rounded-pill fw-bold mt-3" disabled>
                    ĐÃ BÁN / ĐANG CÓ NGƯỜI THUÊ
                </button>
            <?php endif; ?>
        </div>

        <!-- 2. DANH SÁCH ẢNH (FEED 1 CỘT) -->
        <div class="feed-gallery" id="galleryFeed">
            <!-- ẢNH BÌA LUÔN HIỆN ĐẦU TIÊN -->
            <div class="feed-item">
                <a href="uploads/<?= $product['thumb'] ?>" data-fancybox="gallery">
                    <img src="uploads/<?= $product['thumb'] ?>" alt="Ảnh bìa">
                </a>
            </div>

            <!-- JS SẼ CHÈN CÁC ẢNH CÒN LẠI VÀO ĐÂY -->
        </div>

        <!-- 3. LOADING SPINNER -->
        <div class="loading-spinner" id="loadingIcon">
            <div class="spinner-icon"></div>
            <div class="mt-2 text-secondary small">Đang tải thêm ảnh...</div>
        </div>

    </div>

    <footer>
        <div class="container">
            Shop Game Uy Tín &copy; 2024
        </div>
    </footer>

    <!-- SCRIPTS -->
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
    <script>
        Fancybox.bind("[data-fancybox]", {
            Thumbs: {
                type: "modern"
            }
        });

        // --- DỮ LIỆU ẢNH TỪ PHP (ĐÃ ĐƯỢC LỌC BỎ ẢNH BÌA) ---
        const galleryImages = <?= json_encode($gallery) ?>;

        // Cấu hình Infinite Scroll
        let currentIndex = 0;
        const loadBatch = 3; // Mỗi lần tải thêm 3 ảnh
        const feedContainer = document.getElementById('galleryFeed');
        const loadingEl = document.getElementById('loadingIcon');
        let isLoading = false;

        // Hàm render ảnh
        function renderImages(count) {
            const max = Math.min(currentIndex + count, galleryImages.length);

            for (let i = currentIndex; i < max; i++) {
                const imgName = galleryImages[i];
                const div = document.createElement('div');
                div.className = 'feed-item';
                div.innerHTML = `
                    <a href="uploads/${imgName}" data-fancybox="gallery">
                        <img src="uploads/${imgName}" loading="lazy" alt="Ảnh chi tiết">
                    </a>
                `;
                feedContainer.appendChild(div);
            }
            currentIndex = max;

            // Nếu hết ảnh thì ẩn loading vĩnh viễn
            if (currentIndex >= galleryImages.length) {
                loadingEl.remove();
                window.removeEventListener('scroll', handleScroll);
            }
        }

        // Xử lý sự kiện cuộn
        function handleScroll() {
            if (isLoading) return;
            // Kiểm tra xem đã cuộn xuống gần cuối chưa
            if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 600) {
                if (currentIndex < galleryImages.length) {
                    loadMore();
                }
            }
        }

        // Hàm giả lập delay load
        function loadMore() {
            isLoading = true;
            loadingEl.style.display = 'block';

            setTimeout(() => {
                renderImages(loadBatch);
                isLoading = false;
                if (currentIndex < galleryImages.length) {
                    loadingEl.style.display = 'none';
                }
            }, 600);
        }

        // --- KHỞI CHẠY ---
        if (galleryImages.length > 0) {
            renderImages(2); // Load trước 2 ảnh đầu tiên
        } else {
            loadingEl.remove();
        }

        window.addEventListener('scroll', handleScroll);

        // Nút mua hàng Zalo
        function buyNow() {
            var code = "<?= $product['title'] ?>";
            var price = "<?= formatPrice($product['price']) ?>";
            var unitLabel = "<?= $unitLabel ?>";
            var url = window.location.href;
            var actionText = "<?= $isRent ? 'THUÊ' : 'MUA' ?>";
            var zaloPhone = "0984074897";
            var content =
                `Chào Shop, mình muốn ${actionText} Acc Mã Số: ${code} - Giá: ${price}${unitLabel}.\nLink: ${url}`;
            var zaloLink = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent) ?
                `https://zalo.me/${zaloPhone}?text=${encodeURIComponent(content)}` :
                `https://chat.zalo.me/?phone=${zaloPhone}`;
            window.open(zaloLink, '_blank');
        }
    </script>
</body>

</html>