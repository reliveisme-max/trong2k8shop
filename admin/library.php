<?php
// admin/library.php - NFT STYLE COLLECTION
require_once 'auth.php';
require_once '../includes/config.php';

$msg = '';

// 1. XỬ LÝ XÓA ẢNH (LOGIC MẠNH)
if (isset($_POST['action_delete']) && $_POST['action_delete'] == '1' && !empty($_POST['selected_files'])) {

    $filesToDelete = array_map('basename', $_POST['selected_files']);
    $countPhysical = 0;
    $countDB = 0;

    foreach ($filesToDelete as $filename) {
        $path = "../uploads/" . $filename;
        if (file_exists($path)) {
            unlink($path);
            $countPhysical++;
        }
        // Xóa Thumb
        $stmtThumb = $conn->prepare("UPDATE products SET thumb = '' WHERE thumb = :img");
        $stmtThumb->execute([':img' => $filename]);
    }

    // Xóa trong Gallery
    $stmtAll = $conn->prepare("SELECT id, gallery FROM products");
    $stmtAll->execute();
    $allProducts = $stmtAll->fetchAll();

    foreach ($allProducts as $p) {
        $gallery = json_decode($p['gallery'], true);
        if (is_array($gallery) && !empty($gallery)) {
            $newGallery = [];
            $isChanged = false;
            foreach ($gallery as $img) {
                if (!in_array($img, $filesToDelete)) $newGallery[] = $img;
                else $isChanged = true;
            }
            if ($isChanged) {
                $newJson = json_encode(array_values($newGallery));
                $stmtUpdate = $conn->prepare("UPDATE products SET gallery = :gallery WHERE id = :id");
                $stmtUpdate->execute([':gallery' => $newJson, ':id' => $p['id']]);
                $countDB++;
            }
        }
    }
    $msg = "Đã xóa $countPhysical ảnh và làm sạch $countDB Acc liên quan!";
}

// 2. QUÉT ẢNH
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Thư viện NFT</title>
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
        <div class="brand"><i class="ph-fill ph-hexagon"></i> ADMIN PANEL</div>
        <nav class="d-flex flex-column gap-2">
            <a href="index.php" class="menu-item"><i class="ph-duotone ph-squares-four"></i> Tổng Quan</a>
            <a href="add.php" class="menu-item"><i class="ph-duotone ph-plus-circle"></i> Đăng Acc Mới</a>
            <a href="library.php" class="menu-item active"><i class="ph-duotone ph-image"></i> Thư viện ảnh</a>
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

        <!-- HEADER -->
        <div class="top-header mb-4">
            <div class="d-flex align-items-center justify-content-between w-100">
                <div>
                    <h4 class="m-0 fw-bold text-dark">Thư viện Assets</h4>
                    <small class="text-secondary">Tổng cộng: <?= count($images) ?> files</small>
                </div>

                <!-- Nút tải ảnh nhanh -->
                <a href="add.php"
                    class="btn btn-light border d-flex align-items-center gap-2 rounded-pill px-3 fw-bold">
                    <i class="ph-bold ph-upload-simple"></i> <span class="d-none d-sm-inline">Tải lên</span>
                </a>
            </div>
        </div>

        <form method="POST" id="libForm">
            <input type="hidden" name="action_delete" value="1">

            <!-- LƯỚI ẢNH (NFT GRID) -->
            <div class="nft-grid">
                <?php foreach ($images as $img): ?>
                <label class="nft-card">
                    <input type="checkbox" name="selected_files[]" value="<?= $img ?>" onchange="updateToolbar()">
                    <img src="../uploads/<?= $img ?>" loading="lazy">

                    <!-- Dấu tích V khi chọn -->
                    <div class="nft-check-icon">
                        <i class="ph-bold ph-check"></i>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>

            <?php if (empty($images)): ?>
            <div class="text-center py-5 text-secondary">
                <i class="ph-duotone ph-image" style="font-size: 60px; opacity: 0.3;"></i>
                <p class="mt-3">Chưa có ảnh nào.</p>
            </div>
            <?php endif; ?>

            <!-- THANH CÔNG CỤ NỔI (FLOATING TOOLBAR) -->
            <div class="floating-toolbar" id="floatingBar">
                <div class="floating-count" id="countText">Đã chọn 0</div>

                <button type="button" class="floating-btn-delete" onclick="confirmDelete()">
                    <i class="ph-bold ph-trash"></i> Xóa ngay
                </button>

                <button type="button" class="floating-btn-close" onclick="deselectAll()">
                    <i class="ph-bold ph-x"></i>
                </button>
            </div>

        </form>
    </main>

    <!-- MOBILE NAV -->
    <div class="bottom-nav">
        <a href="index.php" class="nav-item">
            <i class="ph-duotone ph-squares-four"></i> <span>Home</span>
        </a>
        <a href="index.php?type=0" class="nav-item">
            <i class="ph-duotone ph-tag"></i> <span>Kho</span>
        </a>
        <a href="add.php" class="nav-item">
            <div class="nav-item-add"><i class="ph-bold ph-plus"></i></div>
        </a>
        <a href="library.php" class="nav-item active">
            <i class="ph-duotone ph-image"></i> <span>Ảnh</span>
        </a>
        <div class="dropup">
            <div class="nav-item" data-bs-toggle="dropdown"><i class="ph-duotone ph-user-circle"></i> <span>Menu</span>
            </div>
            <ul class="dropdown-menu mb-3 shadow-lg border-0">
                <li><a class="dropdown-item py-2" href="change_pass.php">Đổi mật khẩu</a></li>
                <li><a class="dropdown-item py-2 text-danger" href="logout.php">Đăng xuất</a></li>
            </ul>
        </div>
    </div>

    <!-- SCRIPT -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    <?php if ($msg): ?>
    Swal.fire({
        icon: 'success',
        title: 'Thành công!',
        text: '<?= $msg ?>',
        confirmButtonColor: '#f59e0b',
        background: '#fff',
        color: '#000'
    });
    <?php endif; ?>

    // Xử lý thanh công cụ nổi
    function updateToolbar() {
        const checkboxes = document.querySelectorAll('input[name="selected_files[]"]:checked');
        const bar = document.getElementById('floatingBar');
        const countText = document.getElementById('countText');

        if (checkboxes.length > 0) {
            bar.classList.add('active');
            countText.innerText = `Đã chọn ${checkboxes.length}`;
        } else {
            bar.classList.remove('active');
        }
    }

    // Hủy chọn tất cả
    function deselectAll() {
        const checkboxes = document.querySelectorAll('input[name="selected_files[]"]');
        checkboxes.forEach(cb => cb.checked = false);
        updateToolbar();
    }

    // Xác nhận xóa
    function confirmDelete() {
        const checkboxes = document.querySelectorAll('input[name="selected_files[]"]:checked');

        Swal.fire({
            title: `Xóa ${checkboxes.length} ảnh?`,
            text: "Cảnh báo: Ảnh trong các bài viết liên quan cũng sẽ bị gỡ bỏ!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#9ca3af',
            confirmButtonText: 'Xóa vĩnh viễn',
            cancelButtonText: 'Hủy',
            background: '#fff',
            color: '#000'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('libForm').submit();
            }
        });
    }
    </script>
</body>

</html>