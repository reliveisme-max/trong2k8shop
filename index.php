<?php
// index.php - CLEAN VERSION (Separated Logic)
if (session_status() === PHP_SESSION_NONE) session_start();

require_once 'includes/config.php';
require_once 'includes/functions.php';

$isAdmin = isset($_SESSION['admin_id']);

// 1. NHẬN DỮ LIỆU ĐẦU VÀO
$keyword  = isset($_GET['q']) ? trim($_GET['q']) : '';
$page     = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit    = 12;
$offset   = ($page - 1) * $limit;
$isAjax   = isset($_GET['ajax']) && $_GET['ajax'] == 1;

// 2. XÂY DỰNG CÂU TRUY VẤN
$whereArr = [];
$params = [];

if ($keyword) {
    $whereArr[] = "(p.title LIKE ? OR p.id = ?)";
    $params[] = "%$keyword%";
    $params[] = (int)$keyword;
}

if (isset($_GET['min']) && is_numeric($_GET['min'])) {
    $whereArr[] = "p.price >= ?";
    $params[] = (int)$_GET['min'];
}
if (isset($_GET['max']) && is_numeric($_GET['max'])) {
    $whereArr[] = "p.price <= ?";
    $params[] = (int)$_GET['max'];
}

$whereSql = !empty($whereArr) ? "WHERE " . implode(" AND ", $whereArr) : "";

try {
    if (!$isAjax) {
        $stmtCount = $conn->prepare("SELECT COUNT(*) FROM products p $whereSql");
        $stmtCount->execute($params);
        $totalRecords = $stmtCount->fetchColumn();
        $totalPages = ceil($totalRecords / $limit);
        if ($page > $totalPages && $totalPages > 0) $page = $totalPages;
    }

    $sql = "SELECT p.* 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id
            $whereSql 
            ORDER BY 
                CASE WHEN c.display_order IS NULL THEN 1 ELSE 0 END ASC,
                c.display_order ASC, 
                p.id DESC 
            LIMIT $limit OFFSET $offset";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    // Lấy danh mục để dùng cho Modal Admin
    $categories = $conn->query("SELECT * FROM categories ORDER BY display_order ASC")->fetchAll();
} catch (PDOException $e) {
    if ($isAjax) die();
    die("Lỗi: " . $e->getMessage());
}

// HÀM HIỂN THỊ THẺ SẢN PHẨM
function renderProductCard($p, $isAdmin)
{
    $thumbUrl = 'uploads/' . $p['thumb'];
    if (empty($p['thumb']) || !file_exists($thumbUrl)) $thumbUrl = 'assets/images/no-image.jpg';

    if ((string)$p['title'] !== (string)$p['id']) {
        $priceDisplay = "Liên hệ";
    } else {
        $priceDisplay = formatPrice($p['price']);
    }

    if ($p['status'] == 1) {
        $statusBtn = '<a href="detail.php?id=' . $p['id'] . '" class="btn-detail text-decoration-none fw-bold text-success border-success bg-success bg-opacity-10">CÒN HÀNG</a>';
    } else {
        $statusBtn = '<span class="btn-detail text-decoration-none fw-bold text-danger border-danger bg-danger bg-opacity-10">ĐÃ BÁN</span>';
    }

    // Nút Admin (Gọi hàm JS bên home.js)
    $adminBtns = '';
    if ($isAdmin) {
        $adminBtns = '
        <div class="position-absolute top-0 end-0 p-2 d-flex gap-2" style="z-index: 5;">
            <a href="admin/delete.php?id=' . $p['id'] . '&ref=home" onclick="return confirmDelHome(event, this.href)" class="btn-admin-circle btn-del-home" title="Xóa">
                <i class="ph-bold ph-trash"></i>
            </a>
            <button onclick="openQuickEdit(event, ' . $p['id'] . ')" class="btn-admin-circle btn-edit-home" title="Sửa nhanh">
                <i class="ph-bold ph-pencil-simple"></i>
            </button>
        </div>';
    }
?>
<div class="col-12 col-md-6 col-lg-4 feed-item-scroll">
    <div class="product-card position-relative">
        <a href="detail.php?id=<?= $p['id'] ?>" class="text-decoration-none">
            <div class="product-thumb-box">
                <img src="<?= $thumbUrl ?>" class="product-thumb" loading="lazy"
                    alt="<?= htmlspecialchars($p['title']) ?>">
                <?= $adminBtns ?>
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
                    <?= $priceDisplay ?>
                </div>
                <?= $statusBtn ?>
            </div>
        </div>
    </div>
</div>
<?php
}

if ($isAjax) {
    if (count($products) > 0) {
        foreach ($products as $p) renderProductCard($p, $isAdmin);
    } else {
        echo '<div class="col-12 text-center py-5 text-secondary">Không tìm thấy kết quả nào!</div>';
    }
    exit;
}

$pageTitle = "Danh sách Acc | TRỌNG 2K8 SHOP";
require_once 'includes/header.php';
?>

<style>
.btn-admin-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    transition: 0.2s;
    text-decoration: none !important;
}

.btn-edit-home {
    background: rgba(255, 255, 255, 0.95);
    color: #1877F2;
}

.btn-edit-home:hover {
    background: #1877F2;
    color: #fff;
    transform: scale(1.1);
}

.btn-del-home {
    background: rgba(255, 255, 255, 0.95);
    color: #ef4444;
}

.btn-del-home:hover {
    background: #ef4444;
    color: #fff;
    transform: scale(1.1);
}
</style>

<script>
window.totalPages = <?= $totalPages ?>;
window.currentPage = <?= $page ?>;
</script>

<div class="container py-4">
    <!-- --- KHỐI PROFILE HEADER (Đã tích hợp CSS để tránh lỗi Cache) --- -->
    <style>
    .profile-section {
        background: #fff;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        border: 1px solid #f0f2f5;
        overflow: hidden;
    }

    .profile-cover {
        width: 100%;
        height: 220px;
        /* Chiều cao cố định trên mobile */
        background-color: #333;
        background-position: center 30%;
        /* Căn chỉnh để thấy mặt người trong ảnh */
        background-size: cover;
        background-repeat: no-repeat;
    }

    .profile-avatar-container {
        position: relative;
        margin-top: -75px;
        /* Đẩy avatar lên đè vào ảnh bìa */
        text-align: center;
        margin-bottom: 10px;
        z-index: 2;
    }

    .profile-avatar {
        width: 140px;
        height: 140px;
        border-radius: 50%;
        border: 5px solid #ffffff;
        /* Viền trắng dày */
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        object-fit: cover;
        background: #fff;
    }

    .btn-contact {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 20px;
        border-radius: 50px;
        font-weight: 700;
        font-size: 14px;
        text-decoration: none;
        transition: 0.2s;
    }

    .btn-zalo-pf {
        background: #0068ff;
        color: #fff;
        border: 1px solid #0068ff;
    }

    .btn-fb-pf {
        background: #1877f2;
        color: #fff;
        border: 1px solid #1877f2;
    }

    .btn-contact:hover {
        opacity: 0.9;
        color: #fff;
        transform: translateY(-2px);
    }

    @media (min-width: 768px) {
        .profile-cover {
            height: 350px;
            background-position: center 25%;
        }

        /* PC cao hơn */
        .profile-avatar {
            width: 160px;
            height: 160px;
            margin-top: -85px;
        }
    }
    </style>

    <div class="profile-section rounded-4 mb-4">
        <!-- 1. ẢNH BÌA -->
        <div class="profile-cover"
            style="background-image: url('https://truongtranshop.com/903d93f4-6500-49b4-9395-31d5a8de2851.jpg');"></div>

        <!-- 2. AVATAR & THÔNG TIN -->
        <div class="position-relative pb-4 px-3">
            <div class="profile-avatar-container">
                <img src="https://truongtranshop.com/assets/Screen-Shot-2023-06-14-at-23.33.57-808x800.png" alt="Avatar"
                    class="profile-avatar">
            </div>

            <div class="text-center">
                <h2 class="fw-bold mb-1 d-flex align-items-center justify-content-center gap-2 text-dark"
                    style="font-size: 24px;">
                    TRỌNG 2K8 SHOP
                    <i class="ph-fill ph-check-circle text-primary" title="Uy tín"></i>
                </h2>

                <div class="text-secondary mb-3" style="font-size: 15px; line-height: 1.6;">
                    <p class="mb-0">✅ Chuyên Mua Bán - Trao Đổi Acc Game Uy Tín - Giá Rẻ</p>
                    <p class="mb-0">✅ Hỗ trợ Trả Góp - Thu mua acc giá cao</p>
                    <p class="mb-0 fw-bold text-danger">👉 Bảo hành 100% các acc bán ra - Sai hoàn tiền!</p>
                </div>

                <div class="d-flex justify-content-center gap-2">
                    <a href="https://zalo.me/0984074897" target="_blank" class="btn-contact btn-zalo-pf shadow-sm">
                        <i class="ph-bold ph-chat-circle-dots"></i> Nhắn Zalo
                    </a>
                    <a href="#" class="btn-contact btn-fb-pf shadow-sm">
                        <i class="ph-bold ph-facebook-logo"></i> Facebook
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- SEARCH -->
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

    <!-- HEADER -->
    <div class="list-header-wrapper align-items-center">
        <div class="d-flex align-items-center gap-2">
            <h4 class="fw-bold m-0" style="color: var(--text-main);">
                <i class="ph-fill ph-squares-four" style="color: var(--accent);"></i> Danh sách Acc
            </h4>
            <span class="badge rounded-pill bg-warning text-dark"><?= $totalRecords ?></span>
        </div>
    </div>

    <!-- FILTER -->
    <div class="filter-section">
        <a href="index.php" class="filter-pill <?= (!isset($_GET['min']) && empty($keyword)) ? 'active' : '' ?>">Tất
            cả</a>
        <a href="?min=0&max=500000" class="filter-pill <?= checkActive(0, 500000) ?>">Dưới 500k</a>
        <a href="?min=500000&max=1000000" class="filter-pill <?= checkActive(500000, 1000000) ?>">500k - 1m</a>
        <a href="?min=1000000&max=5000000" class="filter-pill <?= checkActive(1000000, 5000000) ?>">1m - 5m</a>
        <a href="?min=5000000&max=10000000" class="filter-pill <?= checkActive(5000000, 10000000) ?>">5m - 10m</a>
        <a href="?min=10000000" class="filter-pill <?= checkActive(10000000, null) ?>">Trên 10m</a>
    </div>

    <!-- GRID -->
    <div class="row position-relative" id="productGrid" style="margin: 0 -12px;">
        <?php if (count($products) > 0): ?>
        <?php foreach ($products as $p): renderProductCard($p, $isAdmin);
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

    <!-- PAGINATION -->
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

<!-- INCLUDE MODAL ADMIN (Chỉ khi là Admin mới load file này) -->
<?php if ($isAdmin): ?>
<?php include 'includes/modals/admin-quick-edit.php'; ?>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>