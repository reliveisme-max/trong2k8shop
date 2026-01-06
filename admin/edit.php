<?php
// admin/edit.php - FINAL VERSION: UI MỚI + LOAD DATA CŨ
require_once 'auth.php';
require_once '../includes/config.php';
require_once '../includes/functions.php';

// 1. KIỂM TRA ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}
$id = (int)$_GET['id'];

// 2. LẤY DỮ LIỆU CŨ
$stmt = $conn->prepare("SELECT * FROM products WHERE id = :id");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch();

if (!$product) die("Acc không tồn tại!");

// 3. CHUẨN BỊ DỮ LIỆU HIỂN THỊ
$isSell = ($product['price'] > 0);
$isRent = ($product['price_rent'] > 0);
$gallery = json_decode($product['gallery'], true);
if (!is_array($gallery)) $gallery = [];
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Sửa Acc #<?= $id ?></title>

    <!-- CSS & Libs -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>

    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= time() ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="brand"><i class="ph-fill ph-heart"></i> ADMIN PANEL</div>
        <nav class="d-flex flex-column gap-2">
            <a href="index.php" class="menu-item"><i class="ph-duotone ph-squares-four"></i> Tổng Quan</a>
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

        <div class="d-flex align-items-center mb-4">
            <a href="index.php" class="btn btn-light border rounded-pill me-3 px-3 py-2"><i
                    class="ph-bold ph-arrow-left"></i></a>
            <div>
                <h4 class="m-0 fw-bold text-dark">Sửa Acc #<?= $id ?></h4>
                <small class="text-secondary">Cập nhật thông tin sản phẩm</small>
            </div>
        </div>

        <form action="process.php" method="POST" enctype="multipart/form-data" id="addForm">
            <!-- Input Ẩn ID -->
            <input type="hidden" name="id" value="<?= $id ?>">

            <div class="row g-4 justify-content-center">

                <!-- CỘT TRÁI: ẢNH -->
                <div class="col-12 col-lg-5 order-lg-2">
                    <div class="form-card sticky-top" style="top: 20px; z-index: 1;">
                        <label class="form-label">HÌNH ẢNH SẢN PHẨM</label>
                        <div class="text-secondary small mb-3 fst-italic">
                            <i class="ph-fill ph-info"></i> Ảnh đầu tiên là <b>Ảnh Bìa</b>. Kéo thả để sắp xếp.
                        </div>

                        <!-- Khu vực Upload -->
                        <div class="image-uploader-area" onclick="document.getElementById('fileInput').click()">
                            <i class="ph-duotone ph-cloud-arrow-up text-secondary" style="font-size: 48px;"></i>
                            <div class="fw-bold mt-2 text-dark">Thêm ảnh mới</div>
                        </div>

                        <input type="file" id="fileInput" name="gallery[]" accept="image/*" multiple hidden>
                        <input type="hidden" name="library_images" id="libraryInput">

                        <div class="text-end mt-3">
                            <button type="button" class="btn btn-sm btn-light border fw-bold text-secondary"
                                onclick="openLibrary()">
                                <i class="ph-bold ph-image"></i> Chọn từ Thư viện
                            </button>
                        </div>

                        <!-- LƯỚI ẢNH (Load ảnh cũ vào đây) -->
                        <div id="imageGrid" class="sortable-grid"></div>
                    </div>
                </div>

                <!-- CỘT PHẢI: THÔNG TIN -->
                <div class="col-12 col-lg-7 order-lg-1">
                    <div class="form-card">

                        <!-- Trạng thái (ON/OFF) -->
                        <div
                            class="d-flex justify-content-between align-items-center mb-4 p-3 bg-light rounded-4 border">
                            <label class="fw-bold m-0 text-uppercase text-secondary" style="font-size: 13px;">Trạng thái
                                hiển thị</label>
                            <label class="switch-status">
                                <input type="checkbox" name="status" value="1"
                                    <?= $product['status'] == 1 ? 'checked' : '' ?>>
                                <span class="slider-status"></span>
                            </label>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Tiêu đề / Mã số <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control custom-input"
                                value="<?= htmlspecialchars($product['title']) ?>" required>
                        </div>

                        <!-- Switch Bán -->
                        <div class="mode-switch-group">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-warning bg-opacity-10 p-2 rounded-3 text-warning">
                                    <i class="ph-fill ph-shopping-cart fs-4"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark">Bán Vĩnh Viễn</div>
                                    <small class="text-secondary">Khách mua đứt acc này</small>
                                </div>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="switchSell"
                                    <?= $isSell ? 'checked' : '' ?> onchange="toggleSections()">
                            </div>
                        </div>

                        <!-- Giá Bán -->
                        <div id="sellSection" class="mb-4 ps-4 border-start border-4 border-warning"
                            style="<?= $isSell ? '' : 'display:none' ?>">
                            <label class="form-label text-warning">Giá Bán (VNĐ)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 fw-bold text-success">₫</span>
                                <input type="text" name="price"
                                    class="form-control custom-input price-input-lg border-start-0 ps-0"
                                    value="<?= $product['price'] > 0 ? number_format($product['price']) : '' ?>"
                                    placeholder="0" oninput="formatCurrency(this)">
                            </div>
                        </div>

                        <!-- Switch Thuê -->
                        <div class="mode-switch-group">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-info bg-opacity-10 p-2 rounded-3 text-info">
                                    <i class="ph-fill ph-clock-user fs-4"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark">Cho Thuê</div>
                                    <small class="text-secondary">Khách thuê theo giờ/ngày</small>
                                </div>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="switchRent"
                                    <?= $isRent ? 'checked' : '' ?> onchange="toggleSections()">
                            </div>
                        </div>

                        <!-- Giá Thuê -->
                        <div id="rentSection" class="mb-4 ps-4 border-start border-4 border-info"
                            style="<?= $isRent ? '' : 'display:none' ?>">
                            <label class="form-label text-info">Giá Thuê (VNĐ)</label>
                            <div class="row g-2">
                                <div class="col-8">
                                    <div class="input-group">
                                        <span
                                            class="input-group-text bg-white border-end-0 fw-bold text-success">₫</span>
                                        <input type="text" name="price_rent"
                                            class="form-control custom-input price-input-lg border-start-0 ps-0"
                                            value="<?= $product['price_rent'] > 0 ? number_format($product['price_rent']) : '' ?>"
                                            placeholder="0" oninput="formatCurrency(this)">
                                    </div>
                                </div>
                                <div class="col-4">
                                    <select name="unit" class="form-select h-100 fw-bold">
                                        <option value="1" <?= $product['unit'] == 1 ? 'selected' : '' ?>>/ Giờ</option>
                                        <option value="2" <?= $product['unit'] == 2 ? 'selected' : '' ?>>/ Ngày</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-5">
                            <button type="button" onclick="submitForm()" class="btn-submit">
                                <i class="ph-bold ph-floppy-disk me-2"></i> LƯU THAY ĐỔI
                            </button>
                        </div>

                    </div>
                </div>

            </div>
        </form>
        <div style="height: 80px;"></div>
    </main>

    <!-- LIBRARY MODAL -->
    <div class="modal fade" id="libraryModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4 shadow-lg">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">Thư viện ảnh</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="library-scroll-area" id="scrollArea" style="height: 400px; overflow-y: auto;">
                        <div class="lib-grid p-3" id="libGrid"></div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-warning text-white fw-bold rounded-pill px-4"
                        onclick="confirmLibrarySelection()">
                        <i class="ph-bold ph-check"></i> Chọn ảnh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MOBILE NAV -->
    <div class="bottom-nav">
        <a href="index.php" class="nav-item"><i class="ph-duotone ph-squares-four"></i></a>
        <a href="add.php" class="nav-item active">
            <div class="nav-item-add"><i class="ph-bold ph-plus"></i></div>
        </a>
        <a href="library.php" class="nav-item"><i class="ph-duotone ph-image"></i></a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin-add.js?v=<?= time() ?>"></script>

    <!-- JS LOAD ẢNH CŨ VÀO LƯỚI -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Lấy dữ liệu ảnh từ PHP
        const existingImages = <?= json_encode($gallery) ?>;

        // Loop qua từng ảnh cũ và thêm vào lưới
        // Hàm addToGrid đã được định nghĩa trong admin-add.js
        // Ta dùng type là 'lib' để khi submit nó sẽ gửi tên file đi chứ không upload lại
        existingImages.forEach(filename => {
            const uid = 'old_' + Math.random().toString(36).substr(2, 9);
            addToGrid(uid, `../uploads/${filename}`, 'lib', filename);
        });
    });
    </script>
</body>

</html>