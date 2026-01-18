<?php
// admin/add_single.php - ĐĂNG LẺ (AUTO SELECT FIRST CATEGORY)
require_once 'auth.php';
require_once '../includes/config.php';

// 1. Lấy danh sách danh mục (Sắp xếp theo thứ tự hiển thị)
$cats = $conn->query("SELECT * FROM categories ORDER BY display_order ASC, id ASC")->fetchAll();

// 2. Tìm ID của danh mục đầu tiên để mặc định chọn
$firstCatId = 0;
if (!empty($cats)) {
    $firstCatId = $cats[0]['id'];
}

// 3. Dự đoán ID tiếp theo
$conn->query("ALTER TABLE products AUTO_INCREMENT = 1");
$stmt = $conn->query("SELECT MAX(id) FROM products");
$maxId = $stmt->fetchColumn();
$nextId = $maxId ? ($maxId + 1) : 1;
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Acc Lẻ</title>

    <!-- LIB -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/mobile.css?v=<?= time() ?>">
</head>

<!-- Thêm class page-add-single để CSS biết đường ẩn Menu Đáy đi -->

<body class="page-add-single">

    <!-- MENU TRÁI (PC) -->
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <!-- HEADER -->
        <div class="d-flex align-items-center mb-4 gap-3">
            <a href="index.php" class="btn btn-light border btn-sm px-3 rounded-pill d-md-none">
                <i class="ph-bold ph-arrow-left"></i>
            </a>
            <h4 class="m-0 text-dark fw-bold">Đăng Acc Lẻ</h4>
        </div>

        <form action="process.php" method="POST" enctype="multipart/form-data" id="addForm">
            <input type="hidden" name="action" value="add_single">

            <!-- Trạng thái mặc định: 1 (Đang bán) -->
            <input type="checkbox" name="status" value="1" checked style="display: none;">

            <div class="row g-4 flex-column-reverse flex-lg-row">

                <!-- CỘT TRÁI: THÔNG TIN -->
                <div class="col-12 col-lg-8">
                    <div class="form-card">

                        <!-- DANH MỤC (Tự chọn cái đầu tiên) -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">Danh mục</label>
                            <select name="category_id" class="form-select custom-input text-primary fw-bold">
                                <?php foreach ($cats as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ($c['id'] == $firstCatId) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- GIÁ BÁN -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">Giá Bán (VNĐ) <span
                                    class="text-danger">*</span></label>
                            <div class="input-group">
                                <span
                                    class="input-group-text bg-light border-end-0 fw-bold text-success border">₫</span>
                                <input type="text" name="price"
                                    class="form-control custom-input border-start-0 text-success fw-bold"
                                    style="font-size: 18px;" placeholder="Ví dụ: 500k, 1m5..."
                                    onblur="parsePriceShortcut(this)">
                            </div>
                        </div>

                        <!-- TIÊU ĐỀ -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">Tiêu đề / Mã Acc</label>
                            <input type="text" name="title" class="form-control custom-input"
                                placeholder="Để trống sẽ tự lấy Mã số: <?= $nextId ?>">
                        </div>

                        <!-- GHI CHÚ -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark">Ghi chú (Admin xem)</label>
                            <textarea name="private_note" class="form-control custom-input" rows="3"
                                placeholder="Tài khoản, mật khẩu, thông tin..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- CỘT PHẢI: ẢNH -->
                <div class="col-12 col-lg-4">
                    <div class="form-card sticky-top" style="top: 20px; z-index: 2;">
                        <label class="form-label fw-bold text-dark mb-3">ẢNH SẢN PHẨM</label>
                        <!-- Nút gạt nén ảnh -->
                        <div class="form-check form-switch mb-3 bg-light p-2 rounded border">
                            <input class="form-check-input ms-0 me-2" type="checkbox" id="compressToggle">
                            <label class="form-check-label fw-bold small" for="compressToggle">Nén ảnh (Giảm nhẹ còn
                                80%)</label>
                        </div>
                        <div class="image-uploader-area mb-3" onclick="document.getElementById('fileInput').click()">
                            <i class="ph-duotone ph-camera text-primary" style="font-size: 40px;"></i>
                            <div class="fw-bold mt-2 text-dark">Chọn ảnh</div>
                        </div>
                        <input type="file" id="fileInput" name="gallery[]" accept="image/*" multiple hidden>

                        <div id="imageGrid" class="sortable-grid"></div>

                        <!-- NÚT LƯU (PC) -->
                        <button type="button" onclick="submitForm()"
                            class="btn btn-primary w-100 py-3 fw-bold text-uppercase shadow-sm btn-save-pc">
                            <i class="ph-bold ph-floppy-disk me-2"></i> ĐĂNG ACC NGAY
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </main>

    <!-- THANH LƯU ACC DÍNH ĐÁY (MOBILE) -->
    <div class="mobile-sticky-footer">
        <a href="index.php" class="btn btn-light border fw-bold" style="width: 50px;">
            <i class="ph-bold ph-x text-danger" style="font-size: 20px;"></i>
        </a>
        <button type="button" onclick="submitForm()" class="btn btn-primary fw-bold flex-grow-1 shadow-sm">
            <i class="ph-bold ph-floppy-disk me-2"></i> LƯU ACC NGAY
        </button>
    </div>

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin-utils.js?v=<?= time() ?>"></script>
    <script src="assets/js/pages/product-form.js?v=<?= time() ?>"></script>

</body>

</html>