<?php
// admin/index.php - GIAO DIỆN DARK MODE DASHBOARD
require_once '../includes/config.php';
require_once '../includes/functions.php';
session_start();

// 1. LẤY DỮ LIỆU SẢN PHẨM
$stmt = $conn->prepare("SELECT * FROM products ORDER BY id DESC");
$stmt->execute();
$products = $stmt->fetchAll();

// 2. TÍNH TOÁN THỐNG KÊ NHANH
$totalAcc = count($products);
$soldAcc = 0;
$revenue = 0;

foreach ($products as $p) {
    if ($p['status'] == 0) { // Đã bán
        $soldAcc++;
        $revenue += $p['price'];
    }
}
$sellingAcc = $totalAcc - $soldAcc;
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Trong2k8 Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">

    <style>
    body {
        background-color: #0f172a;
        /* Nền tối xanh đen */
        color: #e2e8f0;
        font-family: 'Inter', sans-serif;
        overflow-x: hidden;
    }

    /* SIDEBAR */
    .sidebar {
        width: 250px;
        height: 100vh;
        background: #1e293b;
        position: fixed;
        top: 0;
        left: 0;
        padding: 20px;
        border-right: 1px solid #334155;
        display: flex;
        flex-direction: column;
    }

    .brand {
        font-size: 20px;
        font-weight: 800;
        color: #f59e0b;
        margin-bottom: 40px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .menu-item {
        display: block;
        padding: 12px 15px;
        color: #94a3b8;
        text-decoration: none;
        border-radius: 8px;
        margin-bottom: 5px;
        font-weight: 600;
        transition: 0.3s;
    }

    .menu-item:hover,
    .menu-item.active {
        background: #334155;
        color: #fff;
    }

    .menu-item i {
        margin-right: 10px;
        font-size: 18px;
    }

    /* MAIN CONTENT */
    .main-content {
        margin-left: 250px;
        padding: 30px;
    }

    /* STATS CARDS */
    .stat-card {
        background: #1e293b;
        border-radius: 12px;
        padding: 20px;
        border: 1px solid #334155;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .text-label {
        font-size: 13px;
        color: #94a3b8;
        font-weight: 600;
        text-transform: uppercase;
    }

    .text-value {
        font-size: 24px;
        font-weight: 800;
        color: #fff;
        margin-top: 5px;
    }

    /* TABLE STYLE */
    .table-container {
        background: #1e293b;
        border-radius: 12px;
        padding: 20px;
        border: 1px solid #334155;
        margin-top: 30px;
    }

    .custom-table {
        width: 100%;
        border-collapse: collapse;
    }

    .custom-table th {
        text-align: left;
        padding: 15px;
        color: #64748b;
        font-size: 12px;
        text-transform: uppercase;
        border-bottom: 1px solid #334155;
    }

    .custom-table td {
        padding: 15px;
        border-bottom: 1px solid #334155;
        vertical-align: middle;
    }

    .custom-table tr:last-child td {
        border-bottom: none;
    }

    .acc-thumb {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        object-fit: cover;
        border: 1px solid #475569;
    }

    .badge-status {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
    }

    .bg-selling {
        background: rgba(16, 185, 129, 0.2);
        color: #34d399;
    }

    .bg-sold {
        background: rgba(239, 68, 68, 0.2);
        color: #f87171;
    }

    /* Action Buttons */
    .btn-icon {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: 0.2s;
    }

    .btn-edit {
        background: #334155;
        color: #fbbf24;
        margin-right: 5px;
    }

    .btn-edit:hover {
        background: #fbbf24;
        color: #000;
    }

    .btn-del {
        background: #334155;
        color: #f87171;
    }

    .btn-del:hover {
        background: #f87171;
        color: #fff;
    }

    /* Responsive Mobile */
    @media (max-width: 768px) {
        .sidebar {
            width: 70px;
            padding: 20px 10px;
            align-items: center;
        }

        .brand span,
        .menu-item span {
            display: none;
        }

        .menu-item i {
            margin: 0;
            font-size: 24px;
        }

        .main-content {
            margin-left: 70px;
            padding: 15px;
        }

        .hide-mobile {
            display: none;
        }
    }
    </style>
</head>

<body>

    <!-- 1. SIDEBAR -->
    <div class="sidebar">
        <div class="brand">
            <i class="ph-fill ph-game-controller"></i> <span>ADMIN</span>
        </div>

        <a href="index.php" class="menu-item active">
            <i class="ph-bold ph-squares-four"></i> <span>Tổng Quan</span>
        </a>
        <a href="add.php" class="menu-item">
            <i class="ph-bold ph-plus-circle"></i> <span>Đăng Acc Mới</span>
        </a>
        <a href="#" class="menu-item">
            <i class="ph-bold ph-gear"></i> <span>Cài Đặt</span>
        </a>
        <a href="../index.php" class="menu-item mt-auto text-danger">
            <i class="ph-bold ph-sign-out"></i> <span>Thoát</span>
        </a>
    </div>

    <!-- 2. MAIN CONTENT -->
    <div class="main-content">

        <!-- Header Top -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0">Dashboard</h4>
                <div class="text-secondary small">Chào mừng quay trở lại!</div>
            </div>
            <a href="add.php" class="btn btn-primary fw-bold">
                <i class="ph-bold ph-plus"></i> Đăng Bài
            </a>
        </div>

        <!-- 3. STATS CARDS -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="stat-card">
                    <div>
                        <div class="text-label">Tổng Acc</div>
                        <div class="text-value"><?= $totalAcc ?></div>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="ph-fill ph-stack"></i>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="stat-card">
                    <div>
                        <div class="text-label">Đang Bán</div>
                        <div class="text-value text-success"><?= $sellingAcc ?></div>
                    </div>
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="ph-fill ph-tag"></i>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="stat-card">
                    <div>
                        <div class="text-label">Đã Bán (Doanh thu)</div>
                        <div class="text-value text-danger"><?= formatPrice($revenue) ?></div>
                    </div>
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                        <i class="ph-fill ph-money"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. DANH SÁCH ACC -->
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-white mb-0">DANH SÁCH ACC MỚI NHẤT</h6>
            </div>

            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Hình ảnh</th>
                            <th>Thông tin Acc</th>
                            <th>Giá bán</th>
                            <th>Trạng thái</th>
                            <th class="text-end">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                        <tr>
                            <td>
                                <img src="../uploads/<?= $p['thumb'] ?>" class="acc-thumb" alt="Thumb">
                            </td>
                            <td>
                                <div class="fw-bold text-white">#<?= $p['id'] ?> - <?= $p['title'] ?></div>
                                <div class="small text-secondary"><?= date('H:i d/m/Y', strtotime($p['created_at'])) ?>
                                </div>
                            </td>
                            <td class="fw-bold text-warning">
                                <?= formatPrice($p['price']) ?>
                            </td>
                            <td>
                                <?php if ($p['status'] == 1): ?>
                                <span class="badge-status bg-selling">Đang bán</span>
                                <?php else: ?>
                                <span class="badge-status bg-sold">Đã bán</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <!-- Nút Sửa (Sẽ làm sau) -->
                                <a href="#" class="btn-icon btn-edit" title="Sửa">
                                    <i class="ph-bold ph-pencil-simple"></i>
                                </a>
                                <!-- Nút Xóa (Đã link tới file delete.php) -->
                                <a href="delete.php?id=<?= $p['id'] ?>" class="btn-icon btn-del"
                                    onclick="return confirm('Xóa acc này vĩnh viễn?')" title="Xóa">
                                    <i class="ph-bold ph-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <?php if (count($products) == 0): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-secondary">
                                Chưa có dữ liệu. Hãy bấm "Đăng Acc Mới".
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</body>

</html>