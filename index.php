<?php
// index.php - V3: HIỂN THỊ ĐÚNG GIÁ BÁN / GIÁ THUÊ
require_once 'includes/config.php';
require_once 'includes/functions.php';

// 1. CHẾ ĐỘ XEM (Mặc định là shop)
$viewMode = isset($_GET['view']) && $_GET['view'] == 'rent' ? 'rent' : 'shop';

// 2. LẤY DỮ LIỆU (Hàm này đã được sửa ở functions.php)
// Truyền viewMode vào để nó tự lọc theo price hoặc price_rent
$filterParams = $_GET;
$filterParams['view'] = $viewMode;

$result = getFilteredProducts($conn, $filterParams, 12);
$products = $result['data'];
$pageTitle = $result['title'];
$keyword = $result['keyword'];

$pagination = $result['pagination'];
$currentPage = $pagination['current_page'];
$totalPages = $pagination['total_pages'];
$totalRecords = $pagination['total_records'];

function createPageLink($page)
{
    $params = $_GET;
    $params['page'] = $page;
    return '?' . http_build_query($params);
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TRỌNG 2K8 SHOP - Uy Tín Hàng Đầu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
</head>

<body>

    <!-- HEADER -->
    <header class="main-header">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="./" class="text-decoration-none">
                <div class="logo-text"><i class="ph-fill ph-heart"></i> TRỌNG 2K8 SHOP</div>
            </a>
            <div class="d-flex align-items-center gap-3">
                <a href="https://zalo.me/0984074897" target="_blank"
                    class="btn btn-outline-danger btn-sm fw-bold rounded-pill"
                    style="border-color: var(--border); color: var(--text-sub);">
                    <i class="ph-bold ph-phone"></i> 0984.074.897
                </a>
            </div>
        </div>
    </header>

    <div class="container py-5">

        <!-- BANNER LIÊN HỆ -->
        <a href="https://zalo.me/0984074897" target="_blank" class="text-decoration-none">
            <div class="contact-banner mb-5">
                <h3><i class="ph-fill ph-chat-circle-dots"></i> Hỗ trợ giao dịch 24/7 qua Zalo: 0984.074.897</h3>
                <div class="contact-sub">(Bấm vào đây để nhắn tin ngay - Uy tín tạo niềm tin)</div>
            </div>
        </a>

        <!-- SEARCH -->
        <div class="search-box-modern">
            <form action="" method="GET" class="position-relative">
                <?php if ($viewMode == 'rent'): ?><input type="hidden" name="view" value="rent"><?php endif; ?>
                <input type="text" name="q" class="search-input-modern" placeholder="Tìm kiếm tên acc, mã số..."
                    value="<?= htmlspecialchars($keyword) ?>">
                <?php if (!empty($keyword)): ?>
                <a href="?view=<?= $viewMode ?>" class="search-btn-modern"
                    style="display:flex;align-items:center;justify-content:center;text-decoration:none;"><i
                        class="ph-bold ph-x"></i></a>
                <?php else: ?>
                <button type="submit" class="search-btn-modern"><i class="ph-bold ph-magnifying-glass"></i></button>
                <?php endif; ?>
            </form>
        </div>

        <!-- HEADER LIST -->
        <div
            class="list-header-wrapper d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <h5 class="fw-bold m-0 text-uppercase"
                    style="color: var(--text-main); display: flex; align-items: center;">
                    <i class="ph-fill ph-squares-four me-2" style="font-size: 24px; color: var(--accent);"></i>
                    <?= htmlspecialchars($pageTitle) ?>
                </h5>
                <span class="badge rounded-pill bg-danger bg-opacity-10 text-danger"><?= $totalRecords ?></span>
            </div>

            <div class="toggle-group align-self-start align-self-md-auto">
                <a href="?view=shop" class="toggle-btn <?= $viewMode == 'shop' ? 'active' : '' ?>">
                    <i class="ph-bold ph-shopping-cart"></i> MUA ACC
                </a>
                <a href="?view=rent" class="toggle-btn <?= $viewMode == 'rent' ? 'active' : '' ?>">
                    <i class="ph-bold ph-clock-user"></i> THUÊ ACC
                </a>
            </div>
        </div>

        <!-- FILTERS -->
        <div class="filter-section">
            <a href="?view=<?= $viewMode ?>"
                class="filter-pill <?= (!isset($_GET['min']) && empty($keyword)) ? 'active' : '' ?>">Tất cả</a>
            <?php if ($viewMode == 'shop'): ?>
            <a href="?min=0&max=5000000" class="filter-pill <?= checkActive(0, 5000000) ?>">Dưới 5m</a>
            <a href="?min=5000000&max=10000000" class="filter-pill <?= checkActive(5000000, 10000000) ?>">5m - 10m</a>
            <a href="?min=10000000&max=20000000" class="filter-pill <?= checkActive(10000000, 20000000) ?>">10m -
                20m</a>
            <a href="?min=20000000&max=40000000" class="filter-pill <?= checkActive(20000000, 40000000) ?>">20m -
                40m</a>
            <a href="?min=60000000" class="filter-pill <?= checkActive(60000000, null) ?>">Trên 60m</a>
            <?php endif; ?>
        </div>

        <!-- PRODUCT LIST -->
        <div class="row g-4">
            <?php foreach ($products as $p): ?>
            <?php
                // XÁC ĐỊNH GIÁ HIỂN THỊ
                // Nếu đang xem tab Rent -> Lấy giá thuê
                // Nếu đang xem tab Shop -> Lấy giá bán
                $displayPrice = ($viewMode == 'rent') ? $p['price_rent'] : $p['price'];

                // Xác định nhãn đơn vị (chỉ cho thuê)
                $unitLabel = '';
                if ($viewMode == 'rent') {
                    $unitLabel = ($p['unit'] == 2) ? '/ ngày' : '/ giờ';
                }
                ?>
            <div class="col-12 col-md-6 col-lg-3">
                <a href="detail.php?id=<?= $p['id'] ?>" class="text-decoration-none d-block h-100">
                    <div class="product-card">
                        <div class="product-thumb-box">
                            <?php if ($viewMode == 'rent'): ?>
                            <span class="badge bg-danger bg-opacity-75 position-absolute top-0 end-0 m-2 fw-bold"
                                style="z-index: 2">THUÊ</span>
                            <?php endif; ?>
                            <img src="uploads/<?= $p['thumb'] ?>" class="product-thumb" loading="lazy"
                                alt="<?= $p['title'] ?>">
                        </div>
                        <div class="product-body">
                            <div class="product-title" title="<?= $p['title'] ?>"><?= $p['title'] ?></div>
                            <div class="d-flex justify-content-between align-items-center mb-2"
                                style="font-size: 12px; color: var(--text-sub);">
                                <span><i class="ph-fill ph-clock"></i>
                                    <?= date('d/m/Y', strtotime($p['created_at'])) ?></span>
                                <span><i class="ph-fill ph-eye"></i> <?= number_format($p['views'] ?? 0) ?> xem</span>
                            </div>
                            <div class="product-meta">
                                <div class="price-tag">
                                    <span class="text-secondary fw-normal" style="font-size: 14px;">Giá: </span>
                                    <?= formatPrice($displayPrice) ?>
                                    <small class="fw-normal"
                                        style="font-size: 12px; color: var(--text-sub);"><?= $unitLabel ?></small>
                                </div>
                                <div class="btn-detail">CHI TIẾT</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (count($products) == 0): ?>
        <div class="text-center py-5">
            <i class="ph-duotone ph-magnifying-glass text-secondary opacity-25" style="font-size: 80px;"></i>
            <p class="text-secondary fw-bold mt-3">Không tìm thấy Acc nào!</p>
            <a href="?view=<?= $viewMode ?>" class="btn btn-outline-danger rounded-pill px-4 mt-3">Xem tất cả</a>
        </div>
        <?php endif; ?>

        <!-- PAGINATION -->
        <?php if ($totalPages > 1): ?>
        <div class="d-flex justify-content-center mt-5">
            <nav>
                <ul class="pagination">
                    <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= createPageLink($currentPage - 1) ?>"><i
                                class="ph-bold ph-caret-left"></i></a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($i == $currentPage) ? 'active' : '' ?>">
                        <a class="page-link" href="<?= createPageLink($i) ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= createPageLink($currentPage + 1) ?>"><i
                                class="ph-bold ph-caret-right"></i></a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>

    </div>

    <footer>
        <div class="container">
            <p class="mb-1 text-uppercase">&copy; 2024 TRỌNG 2K8 SHOP - UY TÍN TẠO NIỀM TIN</p>
            <p class="mb-0">Hỗ trợ Zalo: <span class="fw-bold" style="color: var(--text-main);">0984.074.897</span></p>
        </div>
    </footer>

</body>

</html>