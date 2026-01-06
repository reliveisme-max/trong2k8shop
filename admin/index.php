<?php
// admin/index.php - FINAL: BỎ THANH TÌM KIẾM
require_once 'auth.php';
require_once '../includes/config.php';
require_once '../includes/functions.php';

// 1. XỬ LÝ LỌC (Chỉ còn lọc theo Loại)
$typeFilter = isset($_GET['type']) ? $_GET['type'] : '';

$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Xây dựng truy vấn
$whereArr = [];
$params = [];

if ($typeFilter !== '') {
    $whereArr[] = "type = :type";
    $params[':type'] = (int)$typeFilter;
}

$whereSql = !empty($whereArr) ? "WHERE " . implode(" AND ", $whereArr) : "";

// Đếm tổng
$sqlCount = "SELECT COUNT(*) FROM products $whereSql";
$stmtCount = $conn->prepare($sqlCount);
$stmtCount->execute($params);
$totalFiltered = $stmtCount->fetchColumn();
$totalPages = ceil($totalFiltered / $limit);

// Lấy dữ liệu
$sql = "SELECT * FROM products $whereSql ORDER BY id DESC LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

// Thống kê nhanh
$totalAcc = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn();
$countSale = $conn->query("SELECT COUNT(*) FROM products WHERE type = 0")->fetchColumn();
$countRent = $conn->query("SELECT COUNT(*) FROM products WHERE type = 1")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Quản lý Acc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= time() ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="brand"><i class="ph-fill ph-heart"></i> ADMIN PANEL</div>
        <nav class="d-flex flex-column gap-2">
            <a href="index.php" class="menu-item active"><i class="ph-duotone ph-squares-four"></i> Tổng Quan</a>
            <a href="add.php" class="menu-item"><i class="ph-duotone ph-plus-circle"></i> Đăng Acc Mới</a>
            <a href="library.php" class="menu-item"><i class="ph-duotone ph-image"></i> Thư viện ảnh</a>
            <a href="change_pass.php" class="menu-item"><i class="ph-duotone ph-lock-key"></i> Đổi mật khẩu</a>
            <div class="mt-auto">
                <div class="border-top border-secondary opacity-25 mb-3"></div>
                <a href="logout.php" class="menu-item text-danger fw-bold"><i class="ph-duotone ph-sign-out"></i> Đăng
                    xuất</a>
            </div>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="content-container">

            <!-- Header -->
            <div class="top-header">
                <div class="d-flex align-items-center">
                    <h4 class="m-0 text-dark">Xin chào, Admin 👋</h4>
                </div>
            </div>

            <!-- STATS CARDS -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-md-4">
                    <div class="stat-card total">
                        <div class="stat-info">
                            <div class="stat-label">Tổng sản phẩm</div>
                            <div class="stat-value"><?= number_format($totalAcc) ?></div>
                        </div>
                        <div class="stat-icon"><i class="ph-duotone ph-shopping-cart"></i></div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="stat-card sale">
                        <div class="stat-info">
                            <div class="stat-label">Kho Bán</div>
                            <div class="stat-value"><?= number_format($countSale) ?></div>
                        </div>
                        <div class="stat-icon"><i class="ph-duotone ph-tag"></i></div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="stat-card rent">
                        <div class="stat-info">
                            <div class="stat-label">Kho Thuê</div>
                            <div class="stat-value"><?= number_format($countRent) ?></div>
                        </div>
                        <div class="stat-icon"><i class="ph-duotone ph-clock"></i></div>
                    </div>
                </div>
            </div>

            <!-- ACTION TOOLBAR (ĐÃ BỎ TÌM KIẾM) -->
            <div class="action-toolbar">
                <div class="toolbar-left">
                    <div class="desktop-filters">
                        <a href="index.php" class="filter-btn <?= $typeFilter === '' ? 'active' : '' ?>">Tất cả</a>
                        <a href="index.php?type=0" class="filter-btn <?= $typeFilter === '0' ? 'active' : '' ?>">Bán</a>
                        <a href="index.php?type=1"
                            class="filter-btn <?= $typeFilter === '1' ? 'active' : '' ?>">Thuê</a>
                    </div>
                </div>
                <div class="toolbar-right d-none d-lg-block">
                    <a href="add.php" class="btn-submit text-decoration-none d-flex align-items-center gap-2"
                        style="padding: 10px 20px; font-size: 13px;">
                        <i class="ph-bold ph-plus"></i> <span>ĐĂNG ACC</span>
                    </a>
                </div>
            </div>

            <!-- MOBILE FILTERS -->
            <div class="mobile-filters">
                <a href="index.php" class="chip <?= $typeFilter === '' ? 'active' : '' ?>">🔥 Tất cả</a>
                <a href="index.php?type=0" class="chip <?= $typeFilter === '0' ? 'active' : '' ?>">🛒 Bán</a>
                <a href="index.php?type=1" class="chip <?= $typeFilter === '1' ? 'active' : '' ?>">🕒 Thuê</a>
            </div>

            <!-- DESKTOP TABLE -->
            <div class="card-table desktop-table">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th class="ps-4">Ảnh</th>
                                <th>Thông tin Acc</th>
                                <th>Giá tiền</th>
                                <th>Trạng thái</th>
                                <th class="text-end pe-4">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p): ?>
                            <tr>
                                <td class="ps-4" width="80"><img src="../uploads/<?= $p['thumb'] ?>" class="thumb-img"
                                        loading="lazy"></td>
                                <td>
                                    <div class="fw-bold text-dark mb-1">
                                        <span class="text-secondary me-1">#<?= $p['id'] ?></span> <?= $p['title'] ?>
                                    </div>
                                    <div class="text-secondary small">
                                        <?= $p['type'] == 1 ? '<span class="badge bg-light text-primary border border-primary-subtle">THUÊ</span>' : '<span class="badge bg-light text-warning border border-warning-subtle">BÁN</span>' ?>
                                    </div>
                                </td>
                                <td class="fw-bold text-success"><?= formatPrice($p['price']) ?></td>
                                <td>
                                    <?php if ($p['status'] == 1): ?>
                                    <span class="badge-soft badge-soft-success">Đang bán</span>
                                    <?php else: ?>
                                    <span class="badge-soft badge-soft-danger">Đã bán/Ẩn</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="../detail.php?id=<?= $p['id'] ?>" target="_blank"
                                        class="btn-action btn-action-view me-1"><i class="ph-bold ph-eye"></i></a>
                                    <a href="edit.php?id=<?= $p['id'] ?>" class="btn-action btn-action-edit me-1"><i
                                            class="ph-bold ph-pencil-simple"></i></a>
                                    <a href="delete.php?id=<?= $p['id'] ?>" class="btn-action btn-action-delete"
                                        onclick="confirmDelete(event, this.href)"><i class="ph-bold ph-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-secondary">Trống trơn!</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- MOBILE LIST -->
            <div class="mobile-list-view">
                <?php foreach ($products as $p): ?>
                <div class="asset-card">
                    <img src="../uploads/<?= $p['thumb'] ?>" class="asset-thumb" loading="lazy">
                    <div class="asset-info">
                        <div class="d-flex align-items-center gap-2">
                            <span class="asset-id">#<?= $p['id'] ?></span>
                            <?php if ($p['type'] == 1): ?>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle"
                                style="font-size: 9px;">THUÊ</span>
                            <?php endif; ?>
                        </div>
                        <div class="asset-title"><?= $p['title'] ?></div>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <span class="status-dot <?= $p['status'] == 1 ? 'active' : 'sold' ?>"></span>
                            <span
                                style="font-size: 11px; color: #6b7280;"><?= $p['status'] == 1 ? 'Đang bán' : 'Đã bán' ?></span>
                        </div>
                    </div>
                    <div class="asset-actions">
                        <div class="asset-price"><?= number_format($p['price'], 0, ',', '.') ?></div>
                        <div class="dropdown">
                            <button class="btn-more" type="button" data-bs-toggle="dropdown"><i
                                    class="ph-bold ph-dots-three-vertical"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0">
                                <li><a class="dropdown-item" href="../detail.php?id=<?= $p['id'] ?>" target="_blank"><i
                                            class="ph-bold ph-eye me-2"></i> Xem</a></li>
                                <li><a class="dropdown-item" href="edit.php?id=<?= $p['id'] ?>"><i
                                            class="ph-bold ph-pencil-simple me-2"></i> Sửa</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item text-danger" href="delete.php?id=<?= $p['id'] ?>"
                                        onclick="confirmDelete(event, this.href)"><i class="ph-bold ph-trash me-2"></i>
                                        Xóa</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- PAGINATION -->
            <div class="d-flex justify-content-center py-4">
                <?php if ($totalPages > 1): ?>
                <nav>
                    <ul class="pagination mb-0">
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?>&type=<?= $typeFilter ?>"><i
                                    class="ph-bold ph-caret-left"></i></a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&type=<?= $typeFilter ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&type=<?= $typeFilter ?>"><i
                                    class="ph-bold ph-caret-right"></i></a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <!-- MOBILE NAV -->
    <div class="bottom-nav">
        <a href="index.php" class="nav-item active"><i class="ph-duotone ph-squares-four"></i></a>
        <a href="add.php" class="nav-item">
            <div class="nav-item-add"><i class="ph-bold ph-plus"></i></div>
        </a>
        <a href="library.php" class="nav-item"><i class="ph-duotone ph-image"></i></a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');
    if (msg === 'added') Swal.fire({
        icon: 'success',
        title: 'Thành công',
        text: 'Đã thêm mới.'
    });
    if (msg === 'updated') Swal.fire({
        icon: 'success',
        title: 'Thành công',
        text: 'Cập nhật xong.'
    });
    if (msg === 'deleted') Swal.fire({
        icon: 'success',
        title: 'Thành công',
        text: 'Đã xóa.'
    });

    function confirmDelete(event, url) {
        event.preventDefault();
        Swal.fire({
            title: 'Xóa Acc này?',
            text: "Không thể hoàn tác!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#d1d5db',
            confirmButtonText: 'Xóa ngay',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) window.location.href = url;
        })
    }
    </script>
</body>

</html>