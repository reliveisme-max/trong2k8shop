<?php
// admin/index.php - ĐÃ FIX NÚT BẤM ĐẸP HƠN
require_once 'auth.php'; // Chốt chặn bảo vệ
require_once '../includes/config.php';
require_once '../includes/functions.php';

// LẤY DỮ LIỆU
$stmt = $conn->prepare("SELECT * FROM products ORDER BY id DESC");
$stmt->execute();
$products = $stmt->fetchAll();

// THỐNG KÊ
$totalAcc = count($products);
$soldAcc = 0;
foreach ($products as $p) {
    if ($p['status'] == 0) $soldAcc++;
}
$sellingAcc = $totalAcc - $soldAcc;
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icon -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <!-- Font Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- CSS Tùy chỉnh -->
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?= time() ?>">
</head>

<body>

    <!-- Lớp phủ mờ khi mở menu trên Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleMenu()"></div>

    <!-- 1. SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <i class="ph-fill ph-hexagon text-warning"></i> ADMIN PAGE
        </div>

        <nav class="d-flex flex-column gap-2">
            <a href="index.php" class="menu-item active">
                <i class="ph-bold ph-list-dashes"></i> Danh sách Acc
            </a>
            <a href="add.php" class="menu-item">
                <i class="ph-bold ph-plus"></i> Đăng Acc Mới
            </a>

            <div class="mt-auto">
                <div class="border-top border-secondary opacity-25 mb-3"></div>
                <a href="logout.php" class="menu-item text-danger fw-bold">
                    <i class="ph-bold ph-sign-out"></i> Đăng xuất
                </a>
            </div>
        </nav>
    </aside>

    <!-- 2. MAIN CONTENT -->
    <main class="main-content">

        <!-- Top Header Mobile & Desktop -->
        <div class="top-header">
            <div class="d-flex align-items-center">
                <!-- Nút Hamburger cho Mobile -->
                <button class="btn-menu-toggle" onclick="toggleMenu()">
                    <i class="ph-bold ph-list"></i>
                </button>
                <h4 class="fw-bold m-0 ms-2 ms-lg-0">Tổng Quan</h4>
            </div>

            <a href="add.php" class="btn btn-warning fw-bold text-dark d-flex align-items-center gap-2">
                <i class="ph-bold ph-plus"></i> <span class="d-none d-sm-inline">Đăng Bài</span>
            </a>
        </div>

        <!-- Stats Row -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4">
                <div class="stat-card">
                    <div class="stat-label">Tổng Acc</div>
                    <div class="stat-value"><?= $totalAcc ?></div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="stat-card">
                    <div class="stat-label">Đang bán</div>
                    <div class="stat-value text-success"><?= $sellingAcc ?></div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="stat-card">
                    <div class="stat-label">Đã bán</div>
                    <div class="stat-value text-danger"><?= $soldAcc ?></div>
                </div>
            </div>
        </div>

        <!-- Table Card -->
        <div class="card-table">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th class="ps-4">Hình ảnh</th>
                            <th>Tên Acc</th>
                            <th>Giá bán</th>
                            <th>Trạng thái</th>
                            <th class="text-end pe-4">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                        <tr>
                            <td class="ps-4">
                                <img src="../uploads/<?= $p['thumb'] ?>" class="thumb-img" loading="lazy">
                            </td>
                            <td>
                                <div class="fw-bold text-white mb-1">#<?= $p['id'] ?></div>
                                <div class="text-secondary small text-truncate" style="max-width: 150px;">
                                    <?= $p['title'] ?>
                                </div>
                            </td>
                            <td class="fw-bold text-warning">
                                <?= formatPrice($p['price']) ?>
                            </td>
                            <td>
                                <?php if ($p['status'] == 1): ?>
                                <span class="status-badge status-active">Đang bán</span>
                                <?php else: ?>
                                <span class="status-badge status-sold">Đã bán</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <!-- NÚT XEM (Class mới: btn-action-view) -->
                                <a href="../detail.php?id=<?= $p['id'] ?>" target="_blank"
                                    class="btn-action btn-action-view me-2" title="Xem thử">
                                    <i class="ph-bold ph-eye fs-5"></i>
                                </a>

                                <!-- NÚT SỬA (Class mới: btn-action-edit) -->
                                <a href="edit.php?id=<?= $p['id'] ?>" class="btn-action btn-action-edit me-2"
                                    title="Sửa">
                                    <i class="ph-bold ph-pencil-simple fs-5"></i>
                                </a>

                                <!-- NÚT XÓA (Class mới: btn-action-delete) -->
                                <a href="delete.php?id=<?= $p['id'] ?>" class="btn-action btn-action-delete"
                                    onclick="return confirm('Xóa vĩnh viễn?')">
                                    <i class="ph-bold ph-trash fs-5"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <?php if (count($products) == 0): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-secondary">Chưa có dữ liệu</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- SCRIPT XỬ LÝ MOBILE MENU -->
    <script>
    function toggleMenu() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
    }
    </script>

</body>

</html>