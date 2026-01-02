<?php
// index.php - TRANG CHỦ (LIGHT MODE & SEARCH LOGIC)
require_once 'includes/config.php';
require_once 'includes/functions.php';

// --- XỬ LÝ LỌC TÌM KIẾM & GIÁ ---
$where = [];
$params = [];

// 1. Tìm kiếm từ khóa
$keyword = '';
if (isset($_GET['q']) && !empty($_GET['q'])) {
    $keyword = $_GET['q'];
    $where[] = "title LIKE :keyword";
    $params[':keyword'] = "%$keyword%";
}

// 2. Lọc theo giá
if (isset($_GET['min'])) {
    $where[] = "price >= :min";
    $params[':min'] = (int)$_GET['min'];
}
if (isset($_GET['max'])) {
    $where[] = "price <= :max";
    $params[':max'] = (int)$_GET['max'];
}

// 3. Lọc trạng thái (Mặc định hiện Đang bán, nếu chọn 'sold' thì hiện Đã bán)
$statusTitle = "Acc Đang Bán";
if (isset($_GET['status']) && $_GET['status'] == 'sold') {
    $where[] = "status = 0";
    $statusTitle = "Acc Đã Bán";
} else {
    // Mặc định không hiện acc đã bán trừ khi tìm kiếm
    if (empty($keyword)) {
        $where[] = "status = 1";
    }
}

// Tạo câu SQL
$sql = "SELECT * FROM products";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY id DESC";

// Truy vấn
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
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

        <!-- 2. THANH TÌM KIẾM (SEARCH) -->
        <div class="search-box">
            <form action="index.php" method="GET">
                <input type="text" name="q" class="search-input" placeholder="Tìm kiếm tên acc, skin súng..."
                    value="<?= htmlspecialchars($keyword) ?>">
                <button type="submit" class="search-btn">
                    <i class="ph-bold ph-magnifying-glass"></i>
                </button>
            </form>
        </div>

        <!-- 3. BỘ LỌC GIÁ -->
        <div class="filter-section">
            <a href="index.php"
                class="filter-pill <?= (!isset($_GET['min']) && !isset($_GET['status'])) ? 'active' : '' ?>">Tất cả</a>
            <a href="index.php?min=0&max=1000000" class="filter-pill">Dưới 1 triệu</a>
            <a href="index.php?min=1000000&max=5000000" class="filter-pill">1m - 5m</a>
            <a href="index.php?min=5000000&max=10000000" class="filter-pill">5m - 10m</a>
            <a href="index.php?min=10000000&max=999999999" class="filter-pill">Trên 10m</a>
            <a href="index.php?status=sold" class="filter-pill" style="border-color: #ef4444; color: #ef4444;">Đã
                Bán</a>
        </div>

        <!-- 4. TIÊU ĐỀ DANH SÁCH -->
        <div class="d-flex align-items-center gap-2 mb-4">
            <h5 class="fw-bold m-0 text-uppercase">
                <?php if ($keyword): ?>
                Kết quả tìm kiếm: "<?= htmlspecialchars($keyword) ?>"
                <?php else: ?>
                <?= $statusTitle ?>
                <?php endif; ?>
            </h5>
            <span class="badge bg-secondary rounded-pill"><?= count($products) ?></span>
        </div>

        <!-- 5. DANH SÁCH SẢN PHẨM -->
        <div class="row g-4">
            <?php foreach ($products as $p): ?>
            <div class="col-12 col-md-6 col-lg-3">
                <a href="detail.php?id=<?= $p['id'] ?>" class="text-decoration-none d-block h-100">
                    <div class="product-card">

                        <div class="product-thumb-box">
                            <span class="card-id">#<?= $p['id'] ?></span>
                            <img src="uploads/<?= $p['thumb'] ?>" class="product-thumb" loading="lazy"
                                alt="<?= $p['title'] ?>">

                            <!-- Nếu status = 0 thì hiện overlay ĐÃ BÁN -->
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
                                <div class="btn-detail">CHI TIẾT</div>
                            </div>
                        </div>

                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- TRẠNG THÁI KHÔNG TÌM THẤY -->
        <?php if (count($products) == 0): ?>
        <div class="text-center py-5">
            <i class="ph-duotone ph-magnifying-glass text-secondary opacity-25" style="font-size: 80px;"></i>
            <p class="text-secondary fw-bold mt-3">Không tìm thấy acc nào phù hợp!</p>
            <a href="index.php" class="btn btn-dark rounded-pill px-4">Xem tất cả</a>
        </div>
        <?php endif; ?>

    </div>

    <!-- FOOTER -->
    <footer>
        <div class="container">
            <p class="mb-1 text-uppercase">&copy; 2024 TRỌNG 2K8 SHOP - UY TÍN TẠO NIỀM TIN</p>
            <p class="mb-0">Hỗ trợ Zalo: <span class="text-dark fw-bold">0984.074.897</span></p>
        </div>
    </footer>

</body>

</html>