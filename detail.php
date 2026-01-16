<?php
// detail.php - FINAL VERSION: CLEAN (NO RENT)
require_once 'includes/config.php';
require_once 'includes/functions.php';

// 1. LẤY ID & KIỂM TRA
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id == 0) {
    header("Location: index.php");
    exit;
}

// 2. LẤY DỮ LIỆU TỪ DB
$stmt = $conn->prepare("SELECT * FROM products WHERE id = :id");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch();

if (!$product) die("Acc không tồn tại!");

// 3. TĂNG VIEW
$conn->prepare("UPDATE products SET views = views + 1 WHERE id = :id")->execute([':id' => $id]);
$product['views']++;

// 4. XỬ LÝ GALLERY ẢNH
$gallery = json_decode($product['gallery'], true);
if (!is_array($gallery)) $gallery = [];

// Lọc bỏ ảnh bìa khỏi danh sách gallery (để tránh lặp lại nếu muốn, hoặc giữ nguyên tùy ý)
// Ở đây giữ logic cũ: bỏ thumb ra khỏi list gallery để render sau
if (!empty($product['thumb'])) {
    $gallery = array_values(array_filter($gallery, function ($img) use ($product) {
        return $img !== $product['thumb'];
    }));
}

// --- CẤU HÌNH HEADER ---
$pageTitle = "Mã số: " . $product['title'] . " | TRỌNG 2K8 SHOP";
$isDetailPage = true;
$backUrl = 'index.php'; // Luôn quay về trang chủ

require_once 'includes/header.php';
?>

<div class="container py-4 detail-container">

    <!-- 1. KHỐI THÔNG TIN SẢN PHẨM -->
    <div class="detail-header">

        <!-- TIÊU ĐỀ: MÃ SỐ [ID] - [TÊN] -->
        <h1 class="detail-title">
            <?php
            // Logic: Nếu tên khác ID thì hiện nối chuỗi, ngược lại chỉ hiện ID
            $displayTitle = ($product['title'] != $product['id']) ? $product['id'] . ' - ' . $product['title'] : $product['id'];
            ?>
            Mã số: <?= htmlspecialchars($displayTitle) ?>
        </h1>

        <!-- LƯỢT XEM (ĐÃ BỎ NGÀY THÁNG) -->
        <div class="detail-views">
            <i class="ph-fill ph-eye"></i> <?= number_format($product['views']) ?> lượt xem
        </div>

        <!-- GIÁ BÁN -->
        <div class="mb-4">
            <div class="detail-price-lg">
                <span class="text-secondary fw-normal fs-6 me-1">Giá: </span>
                <?= formatPrice($product['price']) ?>
            </div>
        </div>

        <!-- GHI CHÚ NỘI BỘ (CHỈ ADMIN MỚI THẤY) -->
        <?php if (isset($_SESSION['admin_id']) && !empty($product['private_note'])): ?>
            <div class="alert alert-warning border-0 bg-warning bg-opacity-10 text-dark text-start mb-4"
                style="font-size: 14px;">
                <i class="ph-bold ph-lock-key me-1"></i> <b>Admin Note:</b>
                <?= nl2br(htmlspecialchars($product['private_note'])) ?>
            </div>
        <?php endif; ?>

        <!-- NÚT MUA -->
        <?php if ($product['status'] == 1): ?>
            <button onclick="openZalo()" class="btn-buy-lg">
                <i class="ph-bold ph-shopping-cart me-2"></i> MÚC NGAY
            </button>
            <div class="mt-3 text-secondary small" style="opacity: 0.8;">
                <i class="ph-fill ph-shield-check text-success"></i> Giao dịch tự động hoặc trung gian uy tín 100%
            </div>
        <?php else: ?>
            <button class="btn btn-secondary w-100 py-3 rounded-3 fw-bold mt-3" disabled
                style="background: #333; border: none;">
                ĐÃ BÁN
            </button>
        <?php endif; ?>
    </div>

    <!-- 2. DANH SÁCH ẢNH (GALLERY) -->
    <div class="feed-gallery" id="galleryFeed">
        <!-- Luôn hiện ảnh bìa đầu tiên -->
        <div class="feed-item">
            <a href="uploads/<?= $product['thumb'] ?>" data-fancybox="gallery">
                <img src="uploads/<?= $product['thumb'] ?>" alt="Ảnh bìa">
            </a>
        </div>
    </div>

    <!-- 3. HIỆU ỨNG LOADING SPINNER -->
    <div class="loading-spinner" id="loadingIcon">
        <div class="spinner-icon"></div>
        <div class="mt-2 text-secondary small">Đang tải thêm ảnh...</div>
    </div>

</div>

<!-- SCRIPTS RIÊNG CHO TRANG CHI TIẾT -->
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
<script>
    // Cấu hình Fancybox (Xem ảnh phóng to)
    Fancybox.bind("[data-fancybox]", {
        Thumbs: {
            type: "modern"
        }
    });

    // Logic Lazy Load ảnh (Tải dần khi cuộn trang để web nhẹ)
    const galleryImages = <?= json_encode($gallery) ?>;
    let currentIndex = 0;
    const loadBatch = 3; // Mỗi lần tải thêm 3 ảnh
    const feedContainer = document.getElementById('galleryFeed');
    const loadingEl = document.getElementById('loadingIcon');
    let isLoading = false;

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
        if (currentIndex >= galleryImages.length) {
            loadingEl.remove(); // Hết ảnh thì xóa loading
            window.removeEventListener('scroll', handleScroll);
        }
    }

    function handleScroll() {
        if (isLoading) return;
        if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 600) {
            if (currentIndex < galleryImages.length) {
                loadMore();
            }
        }
    }

    function loadMore() {
        isLoading = true;
        loadingEl.style.display = 'block';
        setTimeout(() => {
            renderImages(loadBatch);
            isLoading = false;
            if (currentIndex < galleryImages.length) loadingEl.style.display = 'none';
        }, 300);
    }

    // Khởi chạy: Tải trước 2 ảnh đầu tiên (ngoài ảnh bìa)
    if (galleryImages.length > 0) {
        renderImages(2);
    } else {
        loadingEl.remove();
    }
    window.addEventListener('scroll', handleScroll);

    // Logic mở Zalo
    function openZalo() {
        var zaloPhone = "0984074897"; // Số Zalo của bạn
        var isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
        var zaloLink = isMobile ?
            `https://zalo.me/${zaloPhone}` :
            `https://chat.zalo.me/?phone=${zaloPhone}`;
        window.open(zaloLink, '_blank');
    }
</script>

<?php require_once 'includes/footer.php'; ?>