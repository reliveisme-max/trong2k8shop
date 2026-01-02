<?php
// admin/index.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
session_start();

// Lấy danh sách sản phẩm từ DB (Mới nhất lên đầu)
$stmt = $conn->prepare("SELECT * FROM products ORDER BY id DESC");
$stmt->execute();
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Shop Acc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
    body {
        background-color: #f0f2f5;
        padding-bottom: 80px;
    }

    /* Acc Item - Thiết kế dạng thẻ ngang cho dễ nhìn trên mobile */
    .acc-item {
        background: white;
        border-radius: 10px;
        padding: 10px;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        transition: 0.2s;
    }

    .acc-item:active {
        transform: scale(0.98);
    }

    .acc-thumb {
        width: 80px;
        height: 80px;
        border-radius: 8px;
        object-fit: cover;
        flex-shrink: 0;
    }

    .acc-info {
        margin-left: 15px;
        flex-grow: 1;
    }

    .acc-title {
        font-weight: bold;
        font-size: 15px;
        margin-bottom: 2px;
    }

    .acc-price {
        color: #d32f2f;
        font-weight: bold;
        font-size: 14px;
    }

    .acc-date {
        font-size: 11px;
        color: #888;
    }

    /* Nút trạng thái */
    .badge-status {
        font-size: 10px;
        padding: 4px 8px;
        border-radius: 4px;
    }

    .bg-selling {
        background: #e3f2fd;
        color: #1976d2;
    }

    .bg-sold {
        background: #ffebee;
        color: #c62828;
    }

    /* Nút chức năng nhỏ */
    .btn-action {
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #f5f5f5;
        color: #555;
        text-decoration: none;
        margin-left: 5px;
    }

    /* Nút thêm mới trôi nổi (FAB) */
    .fab-btn {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 60px;
        height: 60px;
        background-color: #0d6efd;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
        box-shadow: 0 4px 12px rgba(13, 110, 253, 0.4);
        text-decoration: none;
        z-index: 1000;
    }

    .fab-btn:hover {
        color: white;
        transform: scale(1.1);
    }
    </style>
</head>

<body>

    <div class="container mt-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold">DANH SÁCH ACC (<?= count($products) ?>)</h5>
            <!-- Tạm thời để nút Logout đơn giản -->
            <a href="#" class="text-secondary"><i class="fa-solid fa-right-from-bracket"></i></a>
        </div>

        <!-- Thông báo nếu vừa đăng xong -->
        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
        <div class="alert alert-success py-2 fs-6">
            <i class="fa-solid fa-check-circle"></i> Đã đăng acc thành công!
        </div>
        <?php endif; ?>

        <!-- Danh sách sản phẩm -->
        <div class="list-acc">
            <?php foreach ($products as $p): ?>
            <div class="acc-item">
                <!-- Ảnh đại diện WebP -->
                <img src="../uploads/<?= $p['thumb'] ?>" class="acc-thumb" alt="Acc">

                <div class="acc-info">
                    <div class="acc-title text-truncate" style="max-width: 180px;">
                        #<?= $p['id'] ?> - <?= $p['title'] ?>
                    </div>
                    <div class="acc-price"><?= formatPrice($p['price']) ?></div>

                    <div class="d-flex align-items-center mt-1">
                        <!-- Trạng thái -->
                        <?php if ($p['status'] == 1): ?>
                        <span class="badge-status bg-selling">Đang bán</span>
                        <?php else: ?>
                        <span class="badge-status bg-sold">Đã bán</span>
                        <?php endif; ?>
                        <span class="acc-date ms-2"><?= date('d/m', strtotime($p['created_at'])) ?></span>
                    </div>
                </div>

                <!-- Các nút hành động -->
                <div>
                    <!-- Nút Xóa (Tạm thời để link #, sẽ làm sau) -->
                    <a href="#" class="btn-action" onclick="return confirm('Bạn có chắc muốn xóa?')">
                        <i class="fa-solid fa-trash text-danger"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if (count($products) == 0): ?>
            <div class="text-center text-secondary mt-5">
                <p>Chưa có acc nào.</p>
                <p>Bấm nút <b>+</b> bên dưới để đăng ngay!</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Nút Tròn Thêm Mới -->
    <a href="add.php" class="fab-btn">
        <i class="fa-solid fa-plus"></i>
    </a>

</body>

</html>