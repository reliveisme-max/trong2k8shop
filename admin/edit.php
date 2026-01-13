<?php
// admin/edit.php - RITO STYLE: REFACTORED (CLEAN CODE)
require_once 'auth.php';
require_once '../includes/config.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}
$id = (int)$_GET['id'];

// 1. Lấy thông tin Acc
$stmt = $conn->prepare("SELECT * FROM products WHERE id = :id");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch();

if (!$product) die("Acc không tồn tại!");

// 2. Lấy danh sách ảnh cũ
$gallery = json_decode($product['gallery'], true);

// 3. Lấy Danh mục
$cats = $conn->query("SELECT * FROM categories ORDER BY display_order ASC, id ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa Acc #<?= $id ?></title>

    <!-- FONT & CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <!-- SortableJS (Kéo thả ảnh) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>

    <!-- CSS ADMIN MỚI -->
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= time() ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="brand"><i class="ph-fill ph-polygon"></i> TRỌNG ADMIN</div>
        <nav class="d-flex flex-column gap-2">
            <a href="index.php" class="menu-item"><i class="ph-bold ph-squares-four"></i> Tổng Quan</a>
            <a href="add.php" class="menu-item"><i class="ph-bold ph-plus-circle"></i> Đăng Acc Mới</a>
            <a href="categories.php" class="menu-item"><i class="ph-bold ph-list-dashes"></i> Danh Mục Game</a>
            <a href="change_pass.php" class="menu-item"><i class="ph-bold ph-lock-key"></i> Đổi mật khẩu</a>
            <div class="mt-auto"><a href="logout.php" class="menu-item text-danger fw-bold"><i
                        class="ph-bold ph-sign-out"></i> Đăng xuất</a></div>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- HEADER -->
        <div class="d-flex align-items-center mb-4 gap-3">
            <a href="index.php" class="btn btn-light border btn-sm px-3 rounded-pill">
                <i class="ph-bold ph-arrow-left"></i> Quay lại
            </a>
            <h4 class="m-0 text-dark">Chỉnh sửa Acc #<?= $id ?></h4>
        </div>

        <form action="process.php" method="POST" enctype="multipart/form-data" id="addForm">
            <input type="hidden" name="id" value="<?= $id ?>">

            <div class="row g-4">
                <!-- CỘT TRÁI: THÔNG TIN (70%) -->
                <div class="col-12 col-lg-8">
                    <div class="form-card">

                        <!-- TRẠNG THÁI -->
                        <div
                            class="d-flex justify-content-between align-items-center mb-4 p-3 bg-light rounded-3 border">
                            <div>
                                <label class="fw-bold m-0 text-dark">TRẠNG THÁI HIỂN THỊ</label>
                                <div class="small text-secondary">Gạt tắt để ẩn acc khỏi trang chủ</div>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="status" value="1"
                                    <?= $product['status'] == 1 ? 'checked' : '' ?>
                                    style="width: 50px; height: 26px; cursor: pointer;">
                            </div>
                        </div>

                        <!-- DANH MỤC -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">Danh mục Game <span
                                    class="text-danger">*</span></label>
                            <select name="category_id" class="form-select custom-input">
                                <option value="0">-- Chưa phân loại --</option>
                                <?php foreach ($cats as $c): ?>
                                <option value="<?= $c['id'] ?>"
                                    <?= $c['id'] == $product['category_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- MÃ ACC -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">Mã Acc / Tiêu đề <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control custom-input"
                                value="<?= htmlspecialchars($product['title']) ?>"
                                placeholder="Để trống sẽ tự lấy ID làm tên">
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
                                    style="font-size: 18px;"
                                    value="<?= $product['price'] > 0 ? number_format($product['price'], 0, ',', '.') : '' ?>"
                                    placeholder="0" onblur="parsePriceShortcut(this)"> <!-- Hàm từ admin-utils.js -->
                            </div>
                            <div class="form-text mt-2"><i class="ph-fill ph-info"></i> Nhập tắt: <b>5m</b> = 5 triệu,
                                <b>500k</b> = 500 ngàn.
                            </div>
                        </div>

                        <!-- GHI CHÚ -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark">Ghi chú nội bộ</label>
                            <textarea name="private_note" class="form-control custom-input" rows="3"
                                placeholder="Thông tin đăng nhập, pass mail..."><?= htmlspecialchars($product['private_note'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- CỘT PHẢI: ẢNH (30%) -->
                <div class="col-12 col-lg-4">
                    <div class="form-card sticky-top" style="top: 20px; z-index: 2;">
                        <label class="form-label fw-bold text-dark mb-3">ẢNH SẢN PHẨM</label>

                        <!-- VÙNG UPLOAD -->
                        <div class="image-uploader-area mb-3" onclick="document.getElementById('fileInput').click()">
                            <i class="ph-duotone ph-cloud-arrow-up text-primary" style="font-size: 40px;"></i>
                            <div class="fw-bold mt-2 text-dark">Thêm ảnh mới</div>
                            <div class="small text-secondary">Hỗ trợ JPG, PNG, WEBP</div>
                        </div>
                        <input type="file" id="fileInput" name="gallery[]" accept="image/*" multiple hidden>

                        <!-- LƯỚI ẢNH (GRID) -->
                        <div id="imageGrid" class="sortable-grid"></div>

                        <hr class="my-4 border-secondary opacity-25">

                        <button type="button" onclick="submitForm()"
                            class="btn btn-primary w-100 py-3 fw-bold text-uppercase shadow-sm">
                            <i class="ph-bold ph-floppy-disk me-2"></i> LƯU THAY ĐỔI
                        </button>
                    </div>
                </div>
            </div>
        </form>
        <div style="height: 50px;"></div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- 1. CÁC HÀM TIỆN ÍCH CHUNG (Giá, Nén ảnh...) -->
    <script src="assets/js/admin-utils.js?v=<?= time() ?>"></script>

    <!-- 2. LOGIC XỬ LÝ FORM (Kéo thả, Upload...) -->
    <script src="assets/js/pages/product-form.js?v=<?= time() ?>"></script>

    <!-- 3. KHỞI TẠO DỮ LIỆU BAN ĐẦU -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Truyền danh sách ảnh từ PHP sang JS để hiển thị
        const existingImages = <?= json_encode($gallery) ?>;
        if (typeof initExistingImages === 'function') {
            initExistingImages(existingImages);
        }
    });
    </script>
</body>

</html>