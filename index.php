<?php
// index.php - FINAL VERSION: CLEAN SHOP (NO RENT, NO TAGS)
require_once 'includes/config.php';
require_once 'includes/functions.php';

// 1. NHẬN DỮ LIỆU ĐẦU VÀO
$keyword  = isset($_GET['q']) ? trim($_GET['q']) : '';
$page     = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit    = 12; // Số acc mỗi trang
$offset   = ($page - 1) * $limit;
$isAjax   = isset($_GET['ajax']) && $_GET['ajax'] == 1;

// 2. XÂY DỰNG CÂU TRUY VẤN
$whereArr = [];
$params = [];

// Luôn chỉ hiện Acc đang bán (status = 1)
$whereArr[] = "p.status = 1";

// Tìm kiếm từ khóa
if ($keyword) {
    $whereArr[] = "(p.title LIKE ? OR p.id = ?)";
    $params[] = "%$keyword%";
    $params[] = (int)$keyword;
}

// Lọc Giá
if (isset($_GET['min']) && is_numeric($_GET['min'])) {
    $whereArr[] = "p.price >= ?";
    $params[] = (int)$_GET['min'];
}
if (isset($_GET['max']) && is_numeric($_GET['max'])) {
    $whereArr[] = "p.price <= ?";
    $params[] = (int)$_GET['max'];
}

$whereSql = "WHERE " . implode(" AND ", $whereArr);

try {
    // 1. Đếm tổng
    if (!$isAjax) {
        $stmtCount = $conn->prepare("SELECT COUNT(*) FROM products p $whereSql");
        $stmtCount->execute($params);
        $totalRecords = $stmtCount->fetchColumn();
        $totalPages = ceil($totalRecords / $limit);
        if ($page > $totalPages && $totalPages > 0) $page = $totalPages;
    }

    // 2. Lấy dữ liệu (Mới nhất lên đầu)
    $sql = "SELECT p.* FROM products p 
            $whereSql 
            ORDER BY p.id DESC 
            LIMIT $limit OFFSET $offset";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    if ($isAjax) die();
    die("Lỗi: " . $e->getMessage());
}

// HÀM HIỂN THỊ THẺ SẢN PHẨM
function renderProductCard($p)
{
    $thumbUrl = 'uploads/' . $p['thumb'];
    if (empty($p['thumb']) || !file_exists($thumbUrl)) $thumbUrl = 'assets/images/no-image.jpg';
?>
    <div class="col-12 col-md-6 col-lg-4 feed-item-scroll">
        <div class="product-card">
            <a href="detail.php?id=<?= $p['id'] ?>" class="text-decoration-none">
                <div class="product-thumb-box">
                    <img src="<?= $thumbUrl ?>" class="product-thumb" loading="lazy"
                        alt="<?= htmlspecialchars($p['title']) ?>">
                </div>
            </a>
            <div class="product-body">
                <div class="d-flex align-items-center mb-2 gap-2">
                    <a href="detail.php?id=<?= $p['id'] ?>" class="text-decoration-none product-title m-0">
                        Mã: <?= htmlspecialchars($p['title']) ?>
                    </a>
                    <button class="btn-copy-code" onclick="copyCode('<?= htmlspecialchars($p['title']) ?>')">
                        <i class="ph-bold ph-copy"></i> Copy
                    </button>
                </div>

                <div class="d-flex justify-content-between align-items-center small text-secondary">
                    <span><i class="ph-fill ph-clock"></i> <?= date('d/m/y', strtotime($p['created_at'])) ?></span>
                    <span><i class="ph-fill ph-eye"></i> <?= number_format($p['views']) ?></span>
                </div>

                <div class="product-meta">
                    <div class="price-tag">
                        <span class="fw-normal text-secondary" style="font-size: 14px;">Giá: </span>
                        <?= formatPrice($p['price']) ?>
                    </div>
                    <a href="detail.php?id=<?= $p['id'] ?>" class="btn-detail text-decoration-none">CHI TIẾT</a>
                </div>
            </div>
        </div>
    </div>
<?php
}

if ($isAjax) {
    if (count($products) > 0) {
        foreach ($products as $p) renderProductCard($p);
    } else {
        echo '<div class="col-12 text-center py-5 text-secondary">Không tìm thấy kết quả nào!</div>';
    }
    exit;
}

$pageTitle = "Danh sách Acc | TRỌNG 2K8 SHOP";
require_once 'includes/header.php';
?>

<script>
    window.totalPages = <?= $totalPages ?>;
    window.currentPage = <?= $page ?>;
</script>

<div class="container py-4">

    <!-- BANNER LIÊN HỆ -->
    <a href="https://zalo.me/0984074897" target="_blank" class="text-decoration-none">
        <div class="contact-banner">
            <h3><i class="ph-fill ph-chat-circle-dots"></i> Hỗ trợ giao dịch 24/7 qua Zalo</h3>
            <div class="contact-sub">Uy tín tạo niềm tin - Giao dịch nhanh gọn</div>
        </div>
    </a>

    <!-- THANH TÌM KIẾM -->
    <div class="search-box-modern">
        <form action="" method="GET" class="position-relative">
            <input type="text" name="q" class="search-input-modern" placeholder="Tìm kiếm tên acc, mã số..."
                value="<?= htmlspecialchars($keyword) ?>">
            <?php if (!empty($keyword)): ?>
                <a href="index.php" class="search-btn-modern text-white text-decoration-none"><i
                        class="ph-bold ph-x"></i></a>
            <?php else: ?>
                <button type="submit" class="search-btn-modern"><i class="ph-bold ph-magnifying-glass"></i></button>
            <?php endif; ?>
        </form>
    </div>

    <!-- HEADER DANH SÁCH -->
    <div class="list-header-wrapper align-items-center">
        <div class="d-flex align-items-center gap-2">
            <h4 class="fw-bold m-0" style="color: var(--text-main);">
                <i class="ph-fill ph-squares-four" style="color: var(--accent);"></i> Danh sách Acc
            </h4>
            <span class="badge rounded-pill bg-warning text-dark"><?= $totalRecords ?></span>
        </div>
    </div>

    <!-- BỘ LỌC GIÁ (FILTER) -->
    <div class="filter-section">
        <a href="index.php" class="filter-pill <?= (!isset($_GET['min']) && empty($keyword)) ? 'active' : '' ?>">Tất
            cả</a>
        <a href="?min=0&max=500000" class="filter-pill <?= checkActive(0, 500000) ?>">Dưới 500k</a>
        <a href="?min=500000&max=1000000" class="filter-pill <?= checkActive(500000, 1000000) ?>">500k - 1m</a>
        <a href="?min=1000000&max=5000000" class="filter-pill <?= checkActive(1000000, 5000000) ?>">1m - 5m</a>
        <a href="?min=5000000&max=10000000" class="filter-pill <?= checkActive(5000000, 10000000) ?>">5m - 10m</a>
        <a href="?min=10000000" class="filter-pill <?= checkActive(10000000, null) ?>">Trên 10m</a>
    </div>

    <!-- GRID SẢN PHẨM -->
    <div class="row position-relative" id="productGrid" style="margin: 0 -12px;">
        <?php if (count($products) > 0): ?>
            <?php foreach ($products as $p): renderProductCard($p);
            endforeach; ?>
        <?php else: ?>
            <div class="col-12 empty-state-box">
                <div class="text-center">
                    <i class="ph-duotone ph-magnifying-glass text-secondary opacity-25" style="font-size: 80px;"></i>
                    <p class="text-secondary fw-bold mt-3 mb-4">Không tìm thấy Acc phù hợp!</p>
                    <a href="index.php" class="btn btn-warning text-white rounded-pill px-4 fw-bold shadow-sm">
                        <i class="ph-bold ph-arrow-counter-clockwise me-1"></i> Xem tất cả
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- PHÂN TRANG -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination-container-modern">
            <div class="pagi-nav-btn js-prev-btn <?= ($page <= 1) ? 'disabled' : '' ?>"
                onclick="<?= ($page > 1) ? "goToPage($page - 1)" : "" ?>"><i class="ph-bold ph-caret-left"></i></div>
            <div class="position-relative">
                <div class="pagi-main-btn" id="pagiTrigger" onclick="togglePaginationGrid()">
                    <span>Trang <span id="lblCurrentPage"><?= $page ?></span> / <?= $totalPages ?></span>
                    <i class="ph-bold ph-caret-up"></i>
                </div>
                <div class="pagi-dropdown" id="pagiDropdown">
                    <div class="pagi-grid-wrapper">
                        <?php for ($i = 1; $i <= $totalPages; $i++): $isActive = ($i == $page) ? 'active' : ''; ?>
                            <div class="pagi-num <?= $isActive ?>" onclick="goToPage(<?= $i ?>)" data-page="<?= $i ?>"><?= $i ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            <div class="pagi-nav-btn js-next-btn <?= ($page >= $totalPages) ? 'disabled' : '' ?>"
                onclick="<?= ($page < $totalPages) ? "goToPage($page + 1)" : "" ?>"><i class="ph-bold ph-caret-right"></i>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
    function copyCode(text) {
        navigator.clipboard.writeText(text).then(function() {
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 1500,
                icon: 'success',
                title: 'Đã sao chép!'
            });
        });
    }
</script>

<?php require_once 'includes/footer.php'; ?>