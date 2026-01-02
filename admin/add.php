<?php
// admin/add.php - ĐÃ CẬP NHẬT CHỌN ĐƠN VỊ TÍNH (GIỜ/NGÀY)
require_once 'auth.php';
require_once '../includes/config.php';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Acc Mới</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icon -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <!-- Font Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- CSS RIÊNG -->
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= time() ?>">
</head>

<body>

    <div class="main-wrapper">
        <!-- Header nhỏ -->
        <div class="container d-flex align-items-center py-3 mb-2">
            <a href="index.php" class="btn-back">
                <i class="ph-bold ph-arrow-left"></i> Quay lại
            </a>
            <h5 class="m-0 ms-3 fw-bold text-white">Tạo sản phẩm mới</h5>
        </div>

        <div class="container pb-5">
            <div class="form-card">

                <form action="process.php" method="POST" enctype="multipart/form-data">

                    <!-- INPUT ẨN -->
                    <input type="hidden" name="selected_thumb" id="inputSelectedThumb">
                    <input type="hidden" name="selected_gallery" id="inputSelectedGallery">

                    <div class="row g-4">
                        <!-- CỘT TRÁI: THÔNG TIN CƠ BẢN -->
                        <div class="col-12 col-lg-7">

                            <!-- HÀNG 1: LOẠI ACC + ĐƠN VỊ TÍNH -->
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Loại sản phẩm</label>
                                    <select name="type" id="typeSelect" class="form-control custom-input form-select"
                                        onchange="toggleSettings()">
                                        <option value="0">Bán Acc (Vĩnh viễn)</option>
                                        <option value="1">Cho Thuê Acc</option>
                                    </select>
                                </div>

                                <!-- Ô NÀY SẼ ẨN KHI CHỌN BÁN, HIỆN KHI CHỌN THUÊ -->
                                <div class="col-md-6 mb-4" id="unitContainer" style="display: none;">
                                    <label class="form-label">Đơn vị tính</label>
                                    <select name="unit" id="unitSelect" class="form-control custom-input form-select"
                                        onchange="toggleSettings()">
                                        <option value="1">Theo Giờ</option>
                                        <option value="2">Theo Ngày</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Tiêu đề Acc <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control custom-input"
                                    placeholder="Ví dụ: Acc PUBG M416 Băng..." required>
                            </div>

                            <div class="mb-4">
                                <!-- ID priceLabel để JS đổi chữ -->
                                <label class="form-label" id="priceLabel">Giá bán (VNĐ) <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text custom-addon">₫</span>
                                    <input type="text" name="price" class="form-control custom-input"
                                        placeholder="Nhập số tiền..." required oninput="formatCurrency(this)">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Mô tả chi tiết</label>
                                <textarea name="description" class="form-control custom-input" rows="5"
                                    placeholder="Mô tả skin, rank, thông tin acc..."></textarea>
                            </div>
                        </div>

                        <!-- CỘT PHẢI: HÌNH ẢNH -->
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
                                                <span>Tải ảnh lên</span>
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
                                    <div id="preview-thumb" class="mt-2 preview-area"></div>
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
                                                <span>Thêm nhiều ảnh</span>
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
                                    <div id="preview-gallery" class="mt-2 preview-grid"></div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <hr class="border-secondary opacity-25 my-4">

                    <!-- NÚT SUBMIT -->
                    <div class="d-flex justify-content-end">
                        <button type="submit" name="btn_submit" class="btn-submit">
                            <i class="ph-bold ph-check-circle"></i> ĐĂNG BÁN NGAY
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <!-- MODAL THƯ VIỆN ẢNH (GIỮ NGUYÊN) -->
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

    <!-- SCRIPT -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin-add.js?v=<?= time() ?>"></script>

    <!-- SCRIPT XỬ LÝ LOGIC HIỂN THỊ GIỜ/NGÀY -->
    <script>
    function toggleSettings() {
        const type = document.getElementById('typeSelect').value;
        const unit = document.getElementById('unitSelect').value;

        const unitContainer = document.getElementById('unitContainer');
        const priceLabel = document.getElementById('priceLabel');

        if (type == 1) {
            // Nếu là Thuê
            unitContainer.style.display = 'block'; // Hiện ô chọn đơn vị

            // Đổi nhãn giá dựa theo đơn vị
            if (unit == 1) {
                priceLabel.innerHTML = 'Giá thuê / Giờ (VNĐ) <span class="text-danger">*</span>';
            } else {
                priceLabel.innerHTML = 'Giá thuê / Ngày (VNĐ) <span class="text-danger">*</span>';
            }
        } else {
            // Nếu là Bán
            unitContainer.style.display = 'none'; // Ẩn ô chọn đơn vị
            priceLabel.innerHTML = 'Giá bán (VNĐ) <span class="text-danger">*</span>';
        }
    }
    </script>

</body>

</html>