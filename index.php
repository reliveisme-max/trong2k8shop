<?php
// index.php - TRỌNG 2K8 SHOP (THEME PUBG ORANGE)
require_once 'includes/config.php';
require_once 'includes/functions.php';

// GỌI HÀM LẤY DỮ LIỆU
$result = getFilteredProducts($conn, $_GET);
$products = $result['data'];
$filterTitle = $result['title'];
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TRỌNG 2K8 SHOP - Uy Tín Hàng Đầu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <!-- Version mới để clear cache CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <style>
    i,
    .ph,
    .ph-fill,
    .ph-bold {
        vertical-align: middle;
        margin-bottom: 2px;
    }
    </style>
</head>

<body>

    <!-- HEADER ĐEN VIỀN CAM -->
    <header class="main-header">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="logo-text">
                <i class="ph-fill ph-crosshair"></i> TRỌNG 2K8 SHOP
            </div>

            <nav class="d-none d-md-block">
                <a href="index.php" class="nav-link d-inline-block text-white">Trang Chủ</a>
                <a href="#" class="nav-link d-inline-block">Bảo Hành</a>
                <a href="#" class="nav-link d-inline-block">Nạp UC</a>
                <a href="#" class="nav-link d-inline-block highlight">Thu Mua 24/7</a>
            </nav>

            <button class="btn text-white d-md-none"><i class="ph-bold ph-list fs-3"></i></button>
        </div>
    </header>

    <div class="container py-5">

        <!-- INTRO BOX (VIỀN CAM) -->
        <div class="intro-box">
            <h3 class="intro-title">CHUYÊN MUA VÀ BÁN - GIAO LƯU ĐỔI ACC</h3>
            <div class="sub-title">Hỗ Trợ Trả Góp - Cầm Cố Acc Phí Thấp</div>

            <ul class="list-unstyled policy-list">
                <li>
                    <!-- Icon màu cam (#f59e0b) -->
                    <i class="ph-fill ph-check-circle fs-5" style="color: #f59e0b;"></i>
                    Mình chỉ dùng duy nhất 1 Zalo:
                    <span class="zalo-box" onclick="copyToClipboard('0984074897')" title="Bấm để copy">
                        0984.074.897
                    </span>
                </li>
                <li>
                    <i class="ph-fill ph-check-circle fs-5" style="color: #f59e0b;"></i>
                    ACC Order (là acc mình treo bán hộ - giao dịch an toàn tuyệt đối)
                </li>
                <li>
                    <i class="ph-fill ph-shield-check text-success fs-5"></i>
                    Bảo hành hoàn tiền 100% vĩnh viễn nếu xảy ra lỗi back acc!
                </li>
            </ul>
        </div>

        <!-- BỘ LỌC (NÚT CAM) -->
        <div class="filter-section">
            <a href="index.php?min=0&max=5000000" class="filter-pill <?= checkActive(0, 5000000) ?>">Dưới 5m</a>
            <a href="index.php?min=5000000&max=10000000"
                class="filter-pill <?= checkActive(5000000, 10000000) ?>">5m-10m</a>
            <a href="index.php?min=10000000&max=20000000"
                class="filter-pill <?= checkActive(10000000, 20000000) ?>">10m-20m</a>
            <a href="index.php?min=20000000&max=40000000"
                class="filter-pill <?= checkActive(20000000, 40000000) ?>">20m-40m</a>
            <a href="index.php?min=40000000&max=60000000"
                class="filter-pill <?= checkActive(40000000, 60000000) ?>">40m-60m</a>
            <a href="index.php?min=60000000&max=999999999"
                class="filter-pill <?= checkActive(60000000, 999999999) ?>">Trên 60m</a>

            <div class="w-100 d-md-none"></div>

            <a href="index.php"
                class="filter-pill btn-shop <?= (!isset($_GET['status']) && !isset($_GET['min'])) ? 'active' : '' ?>">
                <i class="ph-bold ph-storefront"></i> Acc Đang Bán
            </a>
            <a href="#" class="filter-pill" style="border-color: #ef4444; color: #ef4444;">
                <i class="ph-bold ph-fire"></i> Acc Cho Thuê
            </a>
            <a href="index.php?status=sold"
                class="filter-pill btn-sold <?= (isset($_GET['status']) && $_GET['status'] == 'sold') ? 'active' : '' ?>">
                <i class="ph-bold ph-lock-key"></i> Acc Đã Bán
            </a>
        </div>

        <!-- LIST HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
            <h6 class="fw-bold text-secondary text-uppercase m-0 d-flex align-items-center gap-2">
                <i class="ph-duotone ph-list-dashes fs-4"></i>
                <?= $filterTitle ?> (<?= count($products) ?>)
            </h6>

            <div class="dropdown">
                <button class="btn btn-sm btn-light border dropdown-toggle fw-bold text-secondary" type="button"
                    data-bs-toggle="dropdown">
                    <i class="ph-bold ph-funnel"></i> Sắp xếp
                </button>
                <ul class="dropdown-menu shadow border-0">
                    <li><a class="dropdown-item" href="#">Mới nhất</a></li>
                    <li><a class="dropdown-item" href="#">Giá thấp đến cao</a></li>
                    <li><a class="dropdown-item" href="#">Giá cao đến thấp</a></li>
                </ul>
            </div>
        </div>

        <!-- PRODUCT GRID -->
        <div class="row g-4">
            <?php foreach ($products as $p): ?>
            <div class="col-12 col-md-6 col-lg-3">
                <a href="detail.php?id=<?= $p['id'] ?>" class="d-block h-100 text-decoration-none">
                    <div class="product-card">

                        <div class="product-thumb-container">
                            <span class="card-badge-id">#<?= $p['id'] ?></span>
                            <img src="uploads/<?= $p['thumb'] ?>" class="product-thumb" loading="lazy"
                                alt="<?= $p['title'] ?>">

                            <?php if ($p['status'] == 0): ?>
                            <div class="sold-overlay">ĐÃ BÁN</div>
                            <?php endif; ?>
                        </div>

                        <div class="product-body">
                            <div class="product-title" title="<?= $p['title'] ?>">
                                <?= $p['title'] ?>
                            </div>

                            <div class="product-meta">
                                <div class="price-tag">
                                    <?= formatPrice($p['price']) ?>
                                </div>
                                <div class="btn-detail d-flex align-items-center gap-1">
                                    CHI TIẾT <i class="ph-bold ph-arrow-right"></i>
                                </div>
                            </div>
                        </div>

                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (count($products) == 0): ?>
        <div class="text-center py-5 mt-4 bg-white rounded-4 shadow-sm">
            <i class="ph-duotone ph-magnifying-glass text-secondary opacity-25" style="font-size: 80px;"></i>
            <p class="text-secondary fw-bold mt-3">Không tìm thấy acc nào phù hợp!</p>
            <a href="index.php" class="btn btn-warning text-white px-4 rounded-pill fw-bold">Xem tất cả</a>
        </div>
        <?php endif; ?>

        <div class="mb-5"></div>
    </div>

    <!-- FOOTER -->
    <footer class="text-center py-4 mt-auto border-top bg-white">
        <p class="mb-0 text-secondary fw-bold text-uppercase" style="font-size: 12px; letter-spacing: 1px;">
            &copy; 2024 Trong2k8 Shop - PUBG Mobile Vietnam
        </p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            alert('Đã copy số Zalo: ' + text);
        });
    }
    </script>
</body>

</html>