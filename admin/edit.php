<?php
// admin/edit.php - ĐÃ BẢO MẬT & FULL CHỨC NĂNG
require_once 'auth.php'; // <--- CHỐT CHẶN BẢO VỆ
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

if (!$product) {
    die("Acc không tồn tại!");
}

// Xử lý Gallery cũ
$oldGallery = json_decode($product['gallery'], true);
if (!is_array($oldGallery)) $oldGallery = [];
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa Acc #<?= $id ?></title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icon -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <!-- Font Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- CSS Riêng -->
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= time() ?>">
</head>

<body>

    <div class="main-wrapper">
        <div class="container d-flex align-items-center py-3 mb-2">
            <a href="index.php" class="btn-back">
                <i class="ph-bold ph-arrow-left"></i> Quay lại
            </a>
            <h5 class="m-0 ms-3 fw-bold text-white">Chỉnh sửa Acc #<?= $id ?></h5>
        </div>

        <div class="container pb-5">
            <div class="form-card">

                <form action="process.php" method="POST" enctype="multipart/form-data">

                    <!-- INPUT ẨN: ID và Tên ảnh cũ -->
                    <input type="hidden" name="id" value="<?= $product['id'] ?>">
                    <input type="hidden" name="old_thumb" value="<?= $product['thumb'] ?>">
                    <input type="hidden" name="old_gallery" value='<?= $product['gallery'] ?>'>

                    <!-- Input ẩn cho thư viện (nếu chọn mới) -->
                    <input type="hidden" name="selected_thumb" id="inputSelectedThumb">
                    <input type="hidden" name="selected_gallery" id="inputSelectedGallery">

                    <div class="row g-4">
                        <!-- CỘT TRÁI -->
                        <div class="col-12 col-lg-7">
                            <div class="mb-4">
                                <label class="form-label">Tiêu đề Acc <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control custom-input"
                                    value="<?= htmlspecialchars($product['title']) ?>" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Giá bán (VNĐ) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text custom-addon">₫</span>
                                        <!-- Hiển thị giá có dấu chấm sẵn -->
                                        <input type="text" name="price" class="form-control custom-input"
                                            value="<?= number_format($product['price'], 0, '', '.') ?>" required
                                            oninput="formatCurrency(this)">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Trạng thái</label>
                                    <select name="status" class="form-control custom-input form-select">
                                        <option value="1" <?= $product['status'] == 1 ? 'selected' : '' ?>>Đang bán
                                        </option>
                                        <option value="0" <?= $product['status'] == 0 ? 'selected' : '' ?>>Đã bán
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Mô tả chi tiết</label>
                                <textarea name="description" class="form-control custom-input"
                                    rows="5"><?= $product['description'] ?></textarea>
                            </div>
                        </div>

                        <!-- CỘT PHẢI: ẢNH -->
                        <div class="col-12 col-lg-5">

                            <!-- 1. ẢNH BÌA -->
                            <div class="mb-4">
                                <label class="form-label">Ảnh Bìa (Thumb)</label>
                                <div class="image-upload-wrapper">
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <div class="upload-box"
                                                onclick="document.getElementById('thumbInput').click()">
                                                <i class="ph-duotone ph-upload-simple"></i>
                                                <span>Đổi ảnh khác</span>
                                            </div>
                                            <input type="file" id="thumbInput" name="thumb" accept="image/*" hidden
                                                onchange="previewSingle(this)">
                                        </div>
                                        <div class="col-6">
                                            <div class="upload-box library-box" onclick="openLibrary('thumb')">
                                                <i class="ph-duotone ph-images"></i>
                                                <span>Chọn thư viện</span>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Hiển thị ảnh cũ -->
                                    <div id="preview-thumb" class="mt-2 preview-area">
                                        <img src="../uploads/<?= $product['thumb'] ?>" alt="Ảnh cũ">
                                    </div>
                                </div>
                            </div>

                            <!-- 2. ALBUM ẢNH -->
                            <div class="mb-4">
                                <label class="form-label">Album ảnh chi tiết</label>
                                <div class="image-upload-wrapper">
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <div class="upload-box"
                                                onclick="document.getElementById('galleryInput').click()">
                                                <i class="ph-duotone ph-plus-square"></i>
                                                <span>Đổi album mới</span>
                                            </div>
                                            <input type="file" id="galleryInput" name="gallery[]" accept="image/*"
                                                multiple hidden onchange="previewGallery(this)">
                                        </div>
                                        <div class="col-6">
                                            <div class="upload-box library-box" onclick="openLibrary('gallery')">
                                                <i class="ph-duotone ph-stack"></i>
                                                <span>Chọn thư viện</span>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Hiển thị album cũ -->
                                    <div id="preview-gallery" class="mt-2 preview-grid">
                                        <?php foreach ($oldGallery as $img): ?>
                                        <img src="../uploads/<?= $img ?>">
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="text-secondary small mt-1 fst-italic">
                                        * Lưu ý: Nếu chọn ảnh mới, toàn bộ ảnh cũ trong album sẽ bị thay thế.
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <hr class="border-secondary opacity-25 my-4">

                    <!-- NÚT UPDATE -->
                    <div class="d-flex justify-content-end">
                        <!-- name="btn_update" để phân biệt với btn_submit (thêm mới) -->
                        <button type="submit" name="btn_update" class="btn-submit">
                            <i class="ph-bold ph-floppy-disk"></i> LƯU THAY ĐỔI
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <!-- MODAL THƯ VIỆN (Giữ nguyên như Add) -->
    <div class="modal fade" id="libraryModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content custom-modal">
                <div class="modal-header border-bottom border-secondary border-opacity-25">
                    <h5 class="modal-title fw-bold text-white">Thư viện ảnh</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="library-scroll-area" id="scrollArea">
                        <div class="lib-grid p-3" id="libGrid"></div>
                        <div id="loadingIndicator" class="text-center py-3" style="display: none;">
                            <div class="spinner-border text-warning spinner-border-sm" role="status"></div>
                            <span class="ms-2 text-secondary small">Đang tải...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary border-opacity-25">
                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-warning fw-bold" onclick="confirmSelection()">Sử dụng ảnh đã
                        chọn</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin-add.js?v=<?= time() ?>"></script>

</body>

</html>