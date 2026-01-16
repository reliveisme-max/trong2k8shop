<?php
// admin/menu.php - TRANG MENU CÀI ĐẶT (CHO MOBILE)
require_once 'auth.php';
require_once '../includes/config.php';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Hệ Thống</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= time() ?>">
</head>

<body>
    <!-- 1. MENU TRÁI (ẨN TRÊN MOBILE, HIỆN TRÊN PC) -->
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <h4 class="fw-bold mb-4">Menu Hệ Thống</h4>

        <div class="row g-3">

            <!-- 1. QUẢN LÝ DANH MỤC -->
            <div class="col-12 col-md-6">
                <a href="categories.php"
                    class="d-flex align-items-center p-3 bg-white rounded shadow-sm text-decoration-none text-dark border menu-card-link">
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary me-3">
                        <i class="ph-bold ph-list-dashes fs-4"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold m-0">Danh Mục Game</h6>
                        <small class="text-secondary">Thêm, sửa, xóa loại game</small>
                    </div>
                    <i class="ph-bold ph-caret-right ms-auto text-secondary"></i>
                </a>
            </div>

            <!-- 2. ĐỔI MẬT KHẨU -->
            <div class="col-12 col-md-6">
                <a href="change_pass.php"
                    class="d-flex align-items-center p-3 bg-white rounded shadow-sm text-decoration-none text-dark border menu-card-link">
                    <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning me-3">
                        <i class="ph-bold ph-lock-key fs-4"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold m-0">Đổi Mật Khẩu</h6>
                        <small class="text-secondary">Bảo mật tài khoản Admin</small>
                    </div>
                    <i class="ph-bold ph-caret-right ms-auto text-secondary"></i>
                </a>
            </div>

            <!-- 3. LINK DỰ PHÒNG VÀO TRANG ĐĂNG LÔ -->
            <div class="col-12 col-md-6">
                <a href="add_bulk.php"
                    class="d-flex align-items-center p-3 bg-white rounded shadow-sm text-decoration-none text-dark border menu-card-link">
                    <div class="bg-info bg-opacity-10 p-3 rounded-circle text-info me-3">
                        <i class="ph-bold ph-stack fs-4"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold m-0">Đăng Acc Lô</h6>
                        <small class="text-secondary">Upload nhiều acc cùng lúc</small>
                    </div>
                    <i class="ph-bold ph-caret-right ms-auto text-secondary"></i>
                </a>
            </div>

            <!-- 4. ĐĂNG XUẤT -->
            <div class="col-12">
                <a href="logout.php"
                    class="d-flex align-items-center p-3 bg-danger bg-opacity-10 rounded border border-danger text-decoration-none mt-3">
                    <div class="p-2 rounded-circle bg-white text-danger me-3 d-flex align-items-center justify-content-center"
                        style="width: 40px; height: 40px;">
                        <i class="ph-bold ph-sign-out fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold m-0 text-danger">Đăng Xuất</h6>
                        <small class="text-danger opacity-75">Thoát khỏi hệ thống</small>
                    </div>
                </a>
            </div>

        </div>
    </main>

    <!-- 2. MENU DƯỚI (MOBILE) -->
    <?php include 'includes/bottom_nav.php'; ?>

    <style>
    /* Hiệu ứng bấm vào thẻ */
    .menu-card-link {
        transition: 0.2s;
    }

    .menu-card-link:active {
        transform: scale(0.98);
        background: #f9fafb !important;
    }
    </style>

</body>

</html>