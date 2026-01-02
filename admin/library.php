<?php
// admin/library.php - ĐÃ SỬA: HIỂN THỊ NÚT TÍCH RÕ RÀNG
require_once 'auth.php';
require_once '../includes/config.php';

// 1. XỬ LÝ XÓA ẢNH KHI SUBMIT FORM
$msg = '';
if (isset($_POST['btn_delete_images'])) {
    if (!empty($_POST['selected_files'])) {
        $count = 0;
        foreach ($_POST['selected_files'] as $filename) {
            $filename = basename($filename);
            $path = "../uploads/" . $filename;
            if (file_exists($path)) {
                unlink($path);
                $count++;
            }
        }
        $msg = "Đã xóa thành công $count ảnh!";
    } else {
        $msg = "Chưa chọn ảnh nào để xóa!";
    }
}

// 2. QUÉT THƯ MỤC LẤY ẢNH
$dir = "../uploads/";
$images = [];
if (is_dir($dir)) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $images[$file] = filemtime($dir . $file);
            }
        }
    }
    arsort($images);
    $images = array_keys($images);
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thư viện ảnh</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= time() ?>">

    <style>
    /* CSS GRID ẢNH */
    .lib-manage-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
        gap: 15px;
    }

    /* THẺ CHỨA ẢNH (LABEL) */
    .lib-card {
        position: relative;
        aspect-ratio: 1/1;
        border-radius: 8px;
        overflow: hidden;
        cursor: pointer;
        transition: 0.2s;
        border: 2px solid transparent;
    }

    /* ẢNH */
    .lib-card img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: 0.2s;
        border-radius: 6px;
    }

    /* CHECKBOX ẨN (Để dùng style tùy chỉnh) */
    .lib-card input[type="checkbox"] {
        position: absolute;
        opacity: 0;
        cursor: pointer;
    }

    /* NÚT TÍCH TRÒN (CUSTOM CHECKBOX) */
    .checkmark {
        position: absolute;
        top: 8px;
        right: 8px;
        height: 24px;
        width: 24px;
        background-color: rgba(0, 0, 0, 0.4);
        /* Màu nền mờ */
        border: 2px solid #fff;
        border-radius: 50%;
        z-index: 2;
        transition: 0.2s;
    }

    /* TRẠNG THÁI KHI ĐƯỢC CHỌN (CHECKED) */
    /* 1. Đổi màu nút tích */
    .lib-card input:checked~.checkmark {
        background-color: #f59e0b;
        /* Cam */
        border-color: #f59e0b;
    }

    /* 2. Hiện dấu V bên trong */
    .checkmark:after {
        content: "";
        position: absolute;
        display: none;
        left: 7px;
        top: 3px;
        width: 6px;
        height: 12px;
        border: solid white;
        border-width: 0 2px 2px 0;
        transform: rotate(45deg);
    }

    .lib-card input:checked~.checkmark:after {
        display: block;
    }

    /* 3. Đổi viền ảnh và làm mờ */
    .lib-card input:checked~img {
        border: 3px solid #f59e0b;
        opacity: 0.7;
        transform: scale(0.95);
    }

    /* HOVER HIỆU ỨNG */
    .lib-card:hover .checkmark {
        background-color: rgba(255, 255, 255, 0.3);
    }
    </style>
</head>

<body>

    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleMenu()"></div>

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <i class="ph-fill ph-hexagon text-warning"></i> ADMIN PAGE
        </div>
        <nav class="d-flex flex-column gap-2">
            <a href="index.php" class="menu-item">
                <i class="ph-bold ph-list-dashes"></i> Danh sách Acc
            </a>
            <a href="add.php" class="menu-item">
                <i class="ph-bold ph-plus"></i> Đăng Acc Mới
            </a>
            <a href="library.php" class="menu-item active">
                <i class="ph-bold ph-images"></i> Quản lý Thư viện
            </a>
            <div class="mt-auto">
                <div class="border-top border-secondary opacity-25 mb-3"></div>
                <a href="logout.php" class="menu-item text-danger fw-bold">
                    <i class="ph-bold ph-sign-out"></i> Đăng xuất
                </a>
            </div>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="top-header">
            <div class="d-flex align-items-center">
                <button class="btn-menu-toggle" onclick="toggleMenu()">
                    <i class="ph-bold ph-list"></i>
                </button>
                <h4 class="fw-bold m-0 ms-2 ms-lg-0">Thư viện ảnh</h4>
            </div>
            <div class="text-secondary fw-bold">
                Tổng: <span class="text-white"><?= count($images) ?></span> ảnh
            </div>
        </div>

        <?php if ($msg): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <?= $msg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <form method="POST" id="libForm">
            <div class="card-table p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="text-secondary small">
                        * Tích vào vòng tròn góc ảnh để chọn.
                    </div>
                    <button type="submit" name="btn_delete_images" class="btn btn-danger fw-bold"
                        onclick="return confirm('Xóa vĩnh viễn các ảnh đã chọn?')">
                        <i class="ph-bold ph-trash"></i> Xóa ảnh đã chọn
                    </button>
                </div>

                <div class="lib-manage-grid">
                    <?php foreach ($images as $img): ?>
                    <label class="lib-card">
                        <!-- Input Checkbox -->
                        <input type="checkbox" name="selected_files[]" value="<?= $img ?>">

                        <!-- Vòng tròn hiển thị tích -->
                        <span class="checkmark"></span>

                        <!-- Ảnh -->
                        <img src="../uploads/<?= $img ?>" loading="lazy">
                    </label>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($images)): ?>
                <div class="text-center py-5 text-secondary">
                    Thư mục trống!
                </div>
                <?php endif; ?>
            </div>
        </form>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function toggleMenu() {
        document.getElementById('sidebar').classList.toggle('show');
        document.getElementById('sidebarOverlay').classList.toggle('show');
    }
    </script>
</body>

</html>