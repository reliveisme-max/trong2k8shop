<?php
// index.php - ĐÃ NÂNG CẤP KHỐI THÔNG BÁO "NỔI BẬT THẬT SỰ"
require_once 'includes/config.php';
require_once 'includes/functions.php';

// 1. XÁC ĐỊNH CHẾ ĐỘ XEM (SHOP hay THUÊ)
$viewMode = isset($_GET['view']) && $_GET['view'] == 'rent' ? 'rent' : 'shop';
$typeFilter = ($viewMode == 'rent') ? 1 : 0;

// 2. GỘP THAM SỐ ĐỂ LỌC
$filterParams = $_GET;
$filterParams['type'] = $typeFilter;

// GỌI HÀM LẤY DỮ LIỆU
$result = getFilteredProducts($conn, $filterParams);
$products = $result['data'];
$pageTitle = $result['title'];
$keyword = $result['keyword'];
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

    <!-- 1. HEADER -->
    <header class="main-header">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="index.php" class="text-decoration-none">
                <div class="logo-text">
                    <i class="ph-fill ph-crosshair"></i> TRỌNG 2K8 SHOP
                </div>
            </a>

            <div class="d-flex align-items-center gap-3">
                <a href="https://zalo.me/0984074897" target="_blank"
                    class="btn btn-outline-dark btn-sm fw-bold rounded-pill">
                    <i class="ph-bold ph-phone"></i> 0984.074.897
                </a>
            </div>
        </div>
    </header>

    <div class="container py-5">

        <!-- KHỐI THÔNG BÁO VIP PRO (ĐÃ SỬA) -->
        <div class="notice-box p-4 mb-5">
            <div class="text-center mb-4">
                <h3 class="notice-title mb-2">CHUYÊN MUA VÀ BÁN - GIAO LƯU ĐỔI ACC</h3>
                <div class="notice-highlight">
                    HỖ TRỢ TRẢ GÓP - CẦM CỐ ACC PHÍ THẤP
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-12 col-md-10">
                    <!-- Dòng 1: Cảnh báo đỏ -->
                    <div class="alert-item danger">
                        <i class="ph-fill ph-warning-circle"></i>
                        <span>Mình chỉ dùng duy nhất 1 Zalo: <strong class="fs-5">0984.074.897</strong> và <strong>không
                                sử dụng Facebook</strong>.</span>
                    </div>

                    <!-- Dòng 2: Thông tin xanh dương -->
                    <div class="alert-item info">
                        <i class="ph-fill ph-info"></i>
                        <span><strong>ACC Order:</strong> Là acc mình treo bán hộ - mua được và trả góp như acc bình
                            thường.</span>
                    </div>

                    <!-- Dòng 3: Cam kết xanh lá -->
                    <div class="alert-item success">
                        <i class="ph-fill ph-check-circle"></i>
                        <span>Tất cả đều hỗ trợ trả góp - hỗ trợ đổi acc - thu mua acc giá cao - <strong>BẢO HÀNH HOÀN
                                TIỀN 100% NẾU CÓ LỖI!</strong></span>
                    </div>
                </div>
            </div>
        </div>
        <!-- KẾT THÚC KHỐI THÔNG BÁO -->


        <!-- 2. NÚT CHUYỂN ĐỔI (TAB) -->
        <div class="d-flex justify-content-center gap-3 mb-5">
            <a href="index.php?view=shop"
                class="btn rounded-pill px-4 fw-bold <?= $viewMode == 'shop' ? 'btn-dark' : 'btn-light bg-white border shadow-sm' ?>">
                <i class="ph-bold ph-shopping-bag"></i> MUA ACC
            </a>
            <a href="index.php?view=rent"
                class="btn rounded-pill px-4 fw-bold <?= $viewMode == 'rent' ? 'btn-dark' : 'btn-light bg-white border shadow-sm' ?>">
                <i class="ph-bold ph-clock-user"></i> THUÊ ACC
            </a>
        </div>

        <!-- 3. THANH TÌM KIẾM -->
        <div class="search-box">
            <form action="index.php" method="GET">
                <?php if ($viewMode == 'rent'): ?>
                <input type="hidden" name="view" value="rent">
                <?php endif; ?>

                <input type="text" name="q" class="search-input" placeholder="Tìm kiếm tên acc, skin súng..."
                    value="<?= htmlspecialchars($keyword) ?>">
                <button type="submit" class="search-btn">
                    <i class="ph-bold ph-magnifying-glass"></i>
                </button>
            </form>
        </div>

        <!-- 4. BỘ LỌC GIÁ (CHỈ HIỆN KHI MUA) -->
        <div class="filter-section">
            <a href="index.php?view=<?= $viewMode ?>"
                class="filter-pill <?= (!isset($_GET['min']) && !isset($_GET['status']) && empty($keyword)) ? 'active' : '' ?>">
                Tất cả
            </a>

            <?php if ($viewMode == 'shop'): ?>
            <a href="index.php?min=0&max=5000000" class="filter-pill <?= checkActive(0, 5000000) ?>">Dưới 5m</a>
            <a href="index.php?min=5000000&max=10000000" class="filter-pill <?= checkActive(5000000, 10000000) ?>">5m -
                10m</a>
            <a href="index.php?min=10000000&max=20000000" class="filter-pill <?= checkActive(10000000, 20000000) ?>">10m
                - 20m</a>
            <a href="index.php?min=20000000&max=40000000" class="filter-pill <?= checkActive(20000000, 40000000) ?>">20m
                - 40m</a>
            <a href="index.php?min=40000000&max=60000000" class="filter-pill <?= checkActive(40000000, 60000000) ?>">40m
                - 60m</a>
            <a href="index.php?min=60000000" class="filter-pill <?= checkActive(60000000, null) ?>">Trên 60m</a>
            <?php endif; ?>

            <a href="index.php?view=<?= $viewMode ?>&status=sold"
                class="filter-pill <?= (isset($_GET['status']) && $_GET['status'] == 'sold') ? 'active' : '' ?>"
                style="border-color: #ef4444; color: #ef4444;">
                <?= $viewMode == 'rent' ? 'Đang Thuê / Hết' : 'Đã Bán' ?>
            </a>
        </div>

        <!-- 5. TIÊU ĐỀ DANH SÁCH -->
        <div class="d-flex align-items-center gap-2 mb-4">
            <h5 class="fw-bold m-0 text-uppercase">
                <?= $viewMode == 'rent' ? 'Danh sách Acc Thuê' : 'Danh sách Acc Bán' ?>
                <?= !empty($keyword) ? '- Tìm kiếm: ' . htmlspecialchars($keyword) : '' ?>
            </h5>
            <span class="badge bg-secondary rounded-pill"><?= count($products) ?></span>
        </div>

        <!-- 6. DANH SÁCH SẢN PHẨM -->
        <div class="row g-4">
            <?php foreach ($products as $p): ?>
            <div class="col-12 col-md-6 col-lg-3">
                <a href="detail.php?id=<?= $p['id'] ?>" class="text-decoration-none d-block h-100">
                    <div class="product-card">

                        <div class="product-thumb-box">
                            <span class="card-id">#<?= $p['id'] ?></span>

                            <?php if ($p['type'] == 1): ?>
                            <span class="badge bg-info text-dark position-absolute top-0 end-0 m-2 fw-bold"
                                style="z-index: 2">CHO THUÊ</span>
                            <?php endif; ?>

                            <img src="uploads/<?= $p['thumb'] ?>" class="product-thumb" loading="lazy"
                                alt="<?= $p['title'] ?>">

                            <?php if ($p['status'] == 0): ?>
                            <div class="sold-overlay">
                                <?= $p['type'] == 1 ? 'ĐANG THUÊ' : 'ĐÃ BÁN' ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="product-body">
                            <div class="product-title" title="<?= $p['title'] ?>">
                                <?= $p['title'] ?>
                            </div>

                            <div class="product-meta">
                                <div class="price-tag">
                                    <?= formatPrice($p['price']) ?>

                                    <?php if ($p['type'] == 1): ?>
                                    <small class="text-secondary fw-normal" style="font-size: 12px">
                                        <?= ($p['unit'] == 2) ? '/ ngày' : '/ giờ' ?>
                                    </small>
                                    <?php endif; ?>

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
            <p class="text-secondary fw-bold mt-3">Hiện chưa có acc nào trong mục này!</p>
            <a href="index.php?view=<?= $viewMode ?>" class="btn btn-dark rounded-pill px-4">Tải lại</a>
        </div>
        <?php endif; ?>

    </div>

    <footer>
        <div class="container">
            <p class="mb-1 text-uppercase">&copy; 2024 TRỌNG 2K8 SHOP - UY TÍN TẠO NIỀM TIN</p>
            <p class="mb-0">Hỗ trợ Zalo: <span class="text-dark fw-bold">0984.074.897</span></p>
        </div>
    </footer>

</body>

</html>