<?php
// admin/edit.php - FINAL VERSION: CLEAN & SIMPLE
require_once 'auth.php';
require_once '../includes/config.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}
$id = (int)$_GET['id'];

// Lấy thông tin Acc
$stmt = $conn->prepare("SELECT * FROM products WHERE id = :id");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch();

if (!$product) die("Acc không tồn tại!");

// Lấy danh sách ảnh
$gallery = json_decode($product['gallery'], true);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa Acc #<?= $id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= time() ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <aside class="sidebar">
        <div class="brand"><i class="ph-fill ph-pencil-simple"></i> EDIT MODE</div>
        <nav class="d-flex flex-column gap-2">
            <a href="index.php" class="menu-item"><i class="ph-duotone ph-squares-four"></i> Tổng Quan</a>
            <a href="add.php" class="menu-item"><i class="ph-duotone ph-plus-circle"></i> Đăng Acc Mới</a>
            <!-- Bỏ menu Tag -->
            <div class="mt-auto"><a href="logout.php" class="menu-item text-danger fw-bold"><i
                        class="ph-duotone ph-sign-out"></i> Đăng xuất</a></div>
        </nav>
    </aside>

    <main class="main-content">
        <div class="d-flex align-items-center mb-4">
            <a href="index.php" class="btn btn-light border rounded-pill me-3 px-3 py-2"><i
                    class="ph-bold ph-arrow-left"></i></a>
            <div>
                <h4 class="m-0 fw-bold text-dark">Sửa Acc #<?= $id ?></h4>
            </div>
        </div>

        <form action="process.php" method="POST" enctype="multipart/form-data" id="addForm">
            <input type="hidden" name="id" value="<?= $id ?>">

            <div class="row g-4">
                <!-- CỘT TRÁI: THÔNG TIN -->
                <div class="col-12 col-lg-8 order-2 order-lg-1">
                    <div class="form-card mb-4">
                        <!-- TRẠNG THÁI HIỂN THỊ -->
                        <div
                            class="d-flex justify-content-between align-items-center mb-4 p-3 bg-light rounded-4 border">
                            <label class="fw-bold m-0 text-secondary">TRẠNG THÁI HIỂN THỊ</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="status" value="1"
                                    <?= $product['status'] == 1 ? 'checked' : '' ?> style="width: 40px; height: 20px;">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Mã Acc / Tiêu đề <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control custom-input"
                                value="<?= htmlspecialchars($product['title']) ?>" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Giá Bán (VNĐ) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 fw-bold text-success">₫</span>
                                <input type="text" name="price"
                                    class="form-control custom-input price-input-lg border-start-0"
                                    value="<?= $product['price'] > 0 ? number_format($product['price'], 0, ',', '.') : '' ?>"
                                    placeholder="0" oninput="formatCurrency(this)">
                            </div>
                            <div class="form-text">Nhập tắt: 5m = 5 triệu, 500k = 500 ngàn.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-primary">Ghi chú nội bộ</label>
                            <textarea name="private_note" class="form-control custom-input"
                                rows="2"><?= htmlspecialchars($product['private_note'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- CỘT PHẢI: ẢNH -->
                <div class="col-12 col-lg-4 order-1 order-lg-2">
                    <div class="form-card mb-4 sticky-top" style="top: 20px; z-index: 2;">
                        <label class="form-label fw-bold text-uppercase text-secondary" style="font-size: 12px;">Ảnh Sản
                            Phẩm</label>
                        <div class="image-uploader-area" onclick="document.getElementById('fileInput').click()">
                            <i class="ph-duotone ph-cloud-arrow-up text-secondary" style="font-size: 32px;"></i>
                            <div class="fw-bold mt-2 text-dark small">Thêm ảnh mới</div>
                        </div>
                        <input type="file" id="fileInput" name="gallery[]" accept="image/*" multiple hidden>
                        <div id="imageGrid" class="sortable-grid"></div>
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <button type="button" onclick="submitForm()" class="btn-submit"><i
                                class="ph-bold ph-floppy-disk me-2"></i> LƯU THAY ĐỔI</button>
                    </div>
                </div>
            </div>
        </form>
        <div style="height: 80px;"></div>
    </main>

    <div class="bottom-nav"><a href="index.php" class="nav-item"><i class="ph-duotone ph-squares-four"></i></a><a
            href="add.php" class="nav-item active">
            <div class="nav-item-add"><i class="ph-bold ph-plus"></i></div>
        </a><a href="#" class="nav-item disabled" style="opacity:0.3"><i class="ph-duotone ph-image"></i></a></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/pages/product-form.js?v=<?= time() ?>"></script>
    <script>
        // Load ảnh cũ lên grid
        document.addEventListener('DOMContentLoaded', function() {
            const existingImages = <?= json_encode($gallery) ?>;
            initExistingImages(existingImages);
        });
    </script>
</body>

</html>