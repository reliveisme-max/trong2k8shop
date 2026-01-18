<?php
// index.php - FINAL LOGIC: SORT BY CAT DEFAULT / SORT BY TIME ON CLICK
if (session_status() === PHP_SESSION_NONE) session_start();

require_once 'includes/config.php';
require_once 'includes/functions.php';

$isAdmin = isset($_SESSION['admin_id']);

// 1. NHẬN DỮ LIỆU ĐẦU VÀO
$keyword  = isset($_GET['q']) ? trim($_GET['q']) : '';
$sortType = isset($_GET['sort']) ? $_GET['sort'] : ''; // Nhận biến sort
$page     = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit    = 12;
$offset   = ($page - 1) * $limit;
$isAjax   = isset($_GET['ajax']) && $_GET['ajax'] == 1;

// 2. XÂY DỰNG BỘ LỌC (WHERE)
$whereArr = [];
$params = [];

if ($keyword) {
    $whereArr[] = "(p.title LIKE ? OR p.id = ?)";
    $params[] = "%$keyword%";
    $params[] = (int)$keyword;
}

if (isset($_GET['cat']) && is_numeric($_GET['cat'])) {
    $whereArr[] = "p.category_id = ?";
    $params[] = (int)$_GET['cat'];
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

    // --- 3. LOGIC SẮP XẾP (ORDER BY) ---

    if ($sortType === 'new') {
        // TRƯỜNG HỢP 1: Bấm vào "Acc mới nhất" (?sort=new)
        // -> Sắp xếp thuần túy theo thời gian (Mới đăng lên đầu), trộn lẫn tất cả danh mục
        $orderBy = "ORDER BY p.id DESC";
    } else {
        // TRƯỜNG HỢP 2: Mặc định (Trang chủ)
        // -> Sắp xếp ưu tiên theo THỨ TỰ DANH MỤC (1. Mới nhất -> 2. Order -> 3. Đã bán)
        // Sau đó trong cùng 1 danh mục mới xếp theo ID
        $orderBy = "ORDER BY 
                    CASE WHEN c.display_order IS NULL THEN 1 ELSE 0 END ASC,
                    c.display_order ASC, 
                    p.id DESC";
    }

    $sql = "SELECT p.* 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id
            $whereSql 
            $orderBy 
            LIMIT $limit OFFSET $offset";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    // Lấy danh mục để dùng cho việc hiển thị nút
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
        $statusBtn = '<a href="acc/' . $p['id'] . '" class="btn-detail text-decoration-none fw-bold text-success border-success bg-success bg-opacity-10">CÒN HÀNG</a>';
    } else {
        $statusBtn = '<span class="btn-detail text-decoration-none fw-bold text-danger border-danger bg-danger bg-opacity-10">ĐÃ BÁN</span>';
    }

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
            <a href="acc/<?= $p['id'] ?>" class="text-decoration-none">
                <div class="product-thumb-box">
                    <img src="<?= $thumbUrl ?>" class="product-thumb" loading="lazy"
                        alt="<?= htmlspecialchars($p['title']) ?>">
                    <?= $adminBtns ?>
                </div>
            </a>
            <div class="product-body">
                <div class="d-flex align-items-center mb-2 gap-2">
                    <a href="acc/<?= $p['id'] ?>" class="text-decoration-none product-title m-0">
                        <?php
                        $displayTitle = ($p['title'] != $p['id']) ? $p['id'] . ' - ' . $p['title'] : $p['id'];
                        ?>
                        Mã: <?= htmlspecialchars($displayTitle) ?>
                    </a>
                    <button class="btn-copy-code" onclick="copyCode('<?= htmlspecialchars($displayTitle) ?>')">
                        <i class="ph-bold ph-copy"></i> Copy
                    </button>
                </div>
                <div class="d-flex align-items-center small text-secondary mt-1">
                    <span>
                        <i class="ph-fill ph-eye me-1"></i> <?= number_format($p['views']) ?> lượt xem
                    </span>
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

$pageTitle = "Danh sách Acc | TRƯỜNG TRẦN SHOP";
require_once 'includes/header.php';
?>

<script>
    window.totalPages = <?= $totalPages ?>;
    window.currentPage = <?= $page ?>;
</script>

<div class="container py-4">

    <!-- PROFILE -->
    <div class="profile-tactical">
        <!-- 1. ẢNH BÌA (bia.jpg) -->
        <div class="pt-cover" style="background-image: url('assets/images/bia.jpg');"></div>

        <div class="pt-body">
            <!-- AVATAR (avt.jpg) -->
            <div class="pt-avatar-box">
                <img src="assets/images/avt.jpg" alt="Avatar" class="pt-avatar">
            </div>

            <div class="pt-info">
                <h2 class="pt-name">
                    TRƯỜNG TRẦN
                    <i class="ph-fill ph-seal-check pt-rank-badge" title="Verified Shop"></i>
                </h2>
                <div class="pt-bio">
                    <p class="mb-1"><i class="ph-bold ph-target text-secondary"></i> <b>Chuyên:</b> Mua Bán - Trao Đổi -
                        Cầm Cố Acc Game</p>
                    <p class="mb-1"><i class="ph-bold ph-credit-card text-secondary"></i> <b>Hỗ trợ:</b> Trả Góp Phí
                        Thấp - Thu Mua Giá Cao</p>
                    <p class="mb-0 text-danger fw-bold"><i class="ph-fill ph-fire"></i> Hotline/Zalo: 0901.999.222</p>
                </div>
                <div class="pt-actions">
                    <a href="https://zalo.me/0901999222" target="_blank" class="btn-tactical btn-zalo-tac">
                        <i class="ph-bold ph-chat-circle-dots" style="font-size: 18px;"></i> NHẮN ZALO
                    </a>
                    <a href="https://www.facebook.com/truong.ttv.1999" target="_blank" class="btn-tactical btn-fb-tac">
                        <i class="ph-bold ph-facebook-logo" style="font-size: 18px;"></i> FACEBOOK
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- HEADER LIST -->
    <div class="list-header-wrapper align-items-center">
        <div class="d-flex align-items-center gap-2">
            <h4 class="fw-bold m-0" style="color: var(--text-main);">
                <i class="ph-fill ph-squares-four" style="color: var(--accent);"></i> Danh sách Acc
            </h4>
            <span class="badge rounded-pill bg-warning text-dark"><?= $totalRecords ?></span>
        </div>
    </div>

    <!-- TÌM KIẾM -->
    <form action="" method="GET" class="search-bar-unified">
        <input type="text" name="q" class="inp-search-unified" placeholder="Nhập mã số, tên acc..."
            value="<?= htmlspecialchars($keyword) ?>">
        <button type="submit" class="btn-search-unified">TÌM KIẾM</button>
    </form>

    <!-- DANH MỤC -->
    <div class="grid-category">
        <?php
        $catList = $conn->query("SELECT * FROM categories ORDER BY display_order ASC")->fetchAll();

        foreach ($catList as $cat):
            $nameLower = mb_strtolower($cat['name']);

            // --- ẨN ACC ORDER ---
            if (strpos($nameLower, 'order') !== false) continue;

            $colorClass = 'solid-dark';
            $iconClass = 'ph-tag';
            $linkUrl = "?cat=" . $cat['id'];
            $isActive = (isset($_GET['cat']) && $_GET['cat'] == $cat['id']) ? 'active' : '';

            // --- NÚT ACC MỚI NHẤT -> Chuyển sang chế độ xem thời gian (?sort=new) ---
            if (strpos($nameLower, 'mới') !== false) {
                $colorClass = 'solid-red';
                $iconClass = 'ph-fire';
                $linkUrl = "?sort=new";
                $isActive = (isset($_GET['sort']) && $_GET['sort'] == 'new') ? 'active' : '';
            } elseif (strpos($nameLower, 'đã bán') !== false) {
                $colorClass = 'solid-dark';
                $iconClass = 'ph-lock-key';
            }
        ?>
            <a href="<?= $linkUrl ?>" class="btn-solid <?= $colorClass ?> <?= $isActive ?>">
                <i class="ph-light <?= $iconClass ?>"></i> <?= htmlspecialchars($cat['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- GIÁ TIỀN -->
    <div class="grid-price">
        <a href="?min=1000000&max=5000000" class="btn-price-flat <?= checkActive(1000000, 5000000) ?>">
            <i class="ph-light ph-coins"></i> 1m - 5m
        </a>
        <a href="?min=5000000&max=10000000" class="btn-price-flat <?= checkActive(5000000, 10000000) ?>">
            <i class="ph-light ph-coins"></i> 5m - 10m
        </a>
        <a href="?min=10000000&max=15000000" class="btn-price-flat <?= checkActive(10000000, 15000000) ?>">
            <i class="ph-light ph-coins"></i> 10m - 15m
        </a>
        <a href="?min=15000000&max=20000000" class="btn-price-flat <?= checkActive(15000000, 20000000) ?>">
            <i class="ph-light ph-coins"></i> 15m - 20m
        </a>
        <a href="?min=20000000&max=30000000" class="btn-price-flat <?= checkActive(20000000, 30000000) ?>">
            <i class="ph-light ph-coins"></i> 20m - 30m
        </a>
        <a href="?min=30000000&max=50000000" class="btn-price-flat <?= checkActive(30000000, 50000000) ?>">
            <i class="ph-light ph-coins"></i> 30m - 50m
        </a>
        <a href="?min=50000000&max=70000000" class="btn-price-flat <?= checkActive(50000000, 70000000) ?>">
            <i class="ph-light ph-coins"></i> 50m - 70m
        </a>
        <a href="?min=70000000" class="btn-price-flat <?= checkActive(70000000, null) ?>">
            <i class="ph-bold ph-crown"></i> Trên 70m
        </a>
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
                    <a href="./" class="btn btn-warning text-white rounded-pill px-4 fw-bold shadow-sm">
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

<!-- MODAL ADMIN -->
<?php if ($isAdmin): ?>
    <?php include 'includes/modals/admin-quick-edit.php'; ?>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>