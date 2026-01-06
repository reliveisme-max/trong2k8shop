<?php
// admin/add.php - FINAL: ĐÃ XÓA NÚT "TÌNH TRẠNG KHO"
require_once 'auth.php';
require_once '../includes/config.php';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Đăng Acc Mới</title>
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
        <div class="brand"><i class="ph-fill ph-heart"></i> ADMIN PANEL</div>
        <nav class="d-flex flex-column gap-2">
            <a href="index.php" class="menu-item"><i class="ph-duotone ph-squares-four"></i> Tổng Quan</a>
            <a href="add.php" class="menu-item active"><i class="ph-duotone ph-plus-circle"></i> Đăng Acc Mới</a>
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
            <a href="index.php" class="btn-back me-3"><i class="ph-bold ph-arrow-left"></i></a>
            <h4 class="m-0 fw-bold text-dark">Đăng Acc Mới</h4>
        </div>

        <form action="process.php" method="POST" enctype="multipart/form-data">
            <div class="row g-4 justify-content-center">

                <!-- CỘT TRÁI: ẢNH -->
                <div class="col-12 col-lg-5 order-lg-2">
                    <div class="form-card sticky-top" style="top: 20px; z-index: 1;">
                        <label class="form-label">1. ẢNH BÌA (THUMB)</label>
                        <div class="upload-box mb-3" id="thumbBox"
                            onclick="document.getElementById('thumbInput').click()">
                            <input type="file" id="thumbInput" name="thumb" accept="image/*" hidden
                                onchange="previewSingle(this)">
                            <input type="hidden" name="selected_thumb" id="inputSelectedThumb">
                            <div id="preview-thumb" class="w-100 h-100 position-relative">
                                <div class="text-center position-absolute top-50 start-50 translate-middle"
                                    id="thumbDefault">
                                    <div class="upload-icon mx-auto"><i class="ph-bold ph-cloud-arrow-up"></i></div>
                                    <div class="fw-bold text-dark">Tải ảnh lên</div>
                                </div>
                            </div>
                        </div>
                        <div class="text-end mb-4">
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                onclick="openLibrary('thumb')">
                                <i class="ph-bold ph-image"></i> Thư viện
                            </button>
                        </div>

                        <label class="form-label">2. ẢNH CHI TIẾT</label>
                        <div class="upload-box" style="height: 100px; border-style: dashed;"
                            onclick="document.getElementById('galleryInput').click()">
                            <div class="d-flex align-items-center gap-2">
                                <i class="ph-bold ph-plus-circle fs-3 text-warning"></i>
                                <span class="text-secondary fw-bold">Thêm nhiều ảnh</span>
                            </div>
                            <input type="file" id="galleryInput" name="gallery[]" accept="image/*" multiple hidden
                                onchange="previewGallery(this)">
                            <input type="hidden" name="selected_gallery" id="inputSelectedGallery">
                        </div>
                        <div class="text-end mt-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                onclick="openLibrary('gallery')">
                                <i class="ph-bold ph-stack"></i> Thư viện
                            </button>
                        </div>
                        <div id="preview-gallery" class="preview-grid"></div>
                    </div>
                </div>

                <!-- CỘT PHẢI: THÔNG TIN -->
                <div class="col-12 col-lg-7 order-lg-1">
                    <div class="form-card">

                        <div class="mb-4">
                            <label class="form-label">Mã Acc / Tiêu đề <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control custom-input" placeholder="Ví dụ: 3029"
                                required>
                        </div>

                        <!-- LOẠI SẢN PHẨM -->
                        <div class="mb-4">
                            <label class="form-label">Loại sản phẩm</label>
                            <div class="radio-group">
                                <input type="radio" class="btn-check" name="type" id="typeSell" value="0" checked
                                    onchange="toggleSettings()">
                                <label class="radio-label" for="typeSell">
                                    <i class="ph-duotone ph-shopping-cart"></i> <span>BÁN ACC</span>
                                </label>

                                <input type="radio" class="btn-check" name="type" id="typeRent" value="1"
                                    onchange="toggleSettings()">
                                <label class="radio-label" for="typeRent">
                                    <i class="ph-duotone ph-clock"></i> <span>CHO THUÊ</span>
                                </label>
                            </div>
                        </div>

                        <!-- ĐƠN VỊ TÍNH (HIỆN KHI CHỌN THUÊ) -->
                        <div class="mb-4" id="unitContainer" style="display: none;">
                            <label class="form-label">Đơn vị tính</label>
                            <div class="radio-group">
                                <input type="radio" class="btn-check" name="unit" id="unitHour" value="1" checked
                                    onchange="updatePriceLabel()">
                                <label class="radio-label" for="unitHour"><span>/ GIỜ</span></label>

                                <input type="radio" class="btn-check" name="unit" id="unitDay" value="2"
                                    onchange="updatePriceLabel()">
                                <label class="radio-label" for="unitDay"><span>/ NGÀY</span></label>
                            </div>
                        </div>

                        <!-- GIÁ TIỀN -->
                        <div class="mb-4">
                            <label class="form-label" id="priceLabel">Giá bán (VNĐ)</label>
                            <div class="input-group">
                                <span class="input-group-text custom-addon border-0" style="font-size: 20px;">₫</span>
                                <input type="text" name="price" class="form-control custom-input price-input-lg"
                                    placeholder="0" required oninput="formatCurrency(this)">
                            </div>
                        </div>

                        <!-- ĐÃ XÓA TOGGLE TRẠNG THÁI -->

                        <div class="d-grid gap-2 mt-5">
                            <button type="submit" name="btn_submit" class="btn-submit">
                                <i class="ph-bold ph-check-circle me-2"></i> LƯU SẢN PHẨM NGAY
                            </button>
                        </div>

                    </div>
                </div>

            </div>
        </form>
        <div style="height: 80px;"></div>
    </main>

    <!-- MOBILE NAV -->
    <div class="bottom-nav">
        <a href="index.php" class="nav-item"><i class="ph-duotone ph-squares-four"></i></a>
        <a href="add.php" class="nav-item active">
            <div class="nav-item-add"><i class="ph-bold ph-plus"></i></div>
        </a>
        <a href="library.php" class="nav-item"><i class="ph-duotone ph-image"></i></a>
    </div>

    <!-- LIBRARY MODAL -->
    <div class="modal fade" id="libraryModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content custom-modal">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">Thư viện ảnh</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="library-scroll-area" id="scrollArea">
                        <div class="lib-grid p-3" id="libGrid"></div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-warning text-white fw-bold" onclick="confirmSelection()">Chọn
                        ảnh</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin-add.js?v=<?= time() ?>"></script>
    <script>
    function toggleSettings() {
        const isRent = document.getElementById('typeRent').checked;
        document.getElementById('unitContainer').style.display = isRent ? 'block' : 'none';
        updatePriceLabel();
    }

    function updatePriceLabel() {
        const isRent = document.getElementById('typeRent').checked;
        const isDay = document.getElementById('unitDay').checked;
        const label = document.getElementById('priceLabel');
        if (!isRent) label.innerHTML = 'GIÁ BÁN (VNĐ)';
        else label.innerHTML = isDay ? 'GIÁ THUÊ / NGÀY (VNĐ)' : 'GIÁ THUÊ / GIỜ (VNĐ)';
    }
    document.addEventListener('DOMContentLoaded', function() {
        toggleSettings();
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('msg') === 'error') {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi',
                text: decodeURIComponent(urlParams.get('text'))
            });
        }
    });
    </script>
</body>

</html>