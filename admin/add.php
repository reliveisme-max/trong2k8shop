<?php
// admin/add.php - V2: KÉO THẢ ẢNH + ĐA CHẾ ĐỘ BÁN/THUÊ
require_once 'auth.php';
require_once '../includes/config.php';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Đăng Acc Mới (V2)</title>

    <!-- CSS & Libs -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <!-- Thư viện Kéo Thả Ảnh -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>

    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= time() ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
    /* CSS Riêng cho phần Kéo thả & Switch */
    .image-uploader-area {
        border: 2px dashed #d1d5db;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        background: #f9fafb;
        cursor: pointer;
        transition: 0.2s;
    }

    .image-uploader-area:hover {
        border-color: #f59e0b;
        background: #fffbeb;
    }

    .sortable-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        margin-top: 20px;
    }

    .sortable-item {
        aspect-ratio: 1/1;
        border-radius: 8px;
        overflow: hidden;
        position: relative;
        cursor: grab;
        border: 2px solid #e5e7eb;
        background: #fff;
    }

    .sortable-item:active {
        cursor: grabbing;
    }

    .sortable-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        pointer-events: none;
    }

    /* Đánh dấu ảnh đầu tiên là Ảnh Bìa */
    .sortable-item:first-child {
        border-color: #f59e0b;
        border-width: 3px;
    }

    .sortable-item:first-child::before {
        content: 'ẢNH BÌA';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: #f59e0b;
        color: #fff;
        font-size: 10px;
        font-weight: bold;
        text-align: center;
        padding: 2px 0;
        z-index: 2;
    }

    .btn-remove-img {
        position: absolute;
        top: 2px;
        right: 2px;
        width: 20px;
        height: 20px;
        background: rgba(239, 68, 68, 0.9);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        cursor: pointer;
        z-index: 5;
    }

    /* Switch Toggle To Đẹp */
    .mode-switch-group {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .form-check-input:checked {
        background-color: #f59e0b;
        border-color: #f59e0b;
    }
    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="brand"><i class="ph-fill ph-heart"></i> ADMIN V2</div>
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
            <h4 class="m-0 fw-bold text-dark">Đăng Acc (Chế độ Pro)</h4>
        </div>

        <form action="process.php" method="POST" enctype="multipart/form-data" id="addForm">
            <div class="row g-4 justify-content-center">

                <!-- CỘT TRÁI: ẢNH (GỘP CHUNG) -->
                <div class="col-12 col-lg-5 order-lg-2">
                    <div class="form-card sticky-top" style="top: 20px; z-index: 1;">
                        <label class="form-label">HÌNH ẢNH SẢN PHẨM</label>
                        <div class="text-secondary small mb-2 fst-italic">* Ảnh đầu tiên sẽ tự động làm Ảnh Bìa. Kéo thả
                            để sắp xếp.</div>

                        <!-- KHU VỰC UPLOAD -->
                        <div class="image-uploader-area" onclick="document.getElementById('fileInput').click()">
                            <i class="ph-bold ph-images text-warning" style="font-size: 32px;"></i>
                            <div class="fw-bold mt-2">Bấm để chọn nhiều ảnh</div>
                            <div class="text-muted small">(Hoặc chọn từ thư viện)</div>
                        </div>

                        <!-- INPUT ẨN -->
                        <input type="file" id="fileInput" name="gallery[]" accept="image/*" multiple hidden>
                        <input type="hidden" name="library_images" id="libraryInput"> <!-- Chứa ảnh chọn từ thư viện -->

                        <!-- NÚT THƯ VIỆN -->
                        <div class="text-end mt-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="openLibrary()">
                                <i class="ph-bold ph-image"></i> Mở Thư viện
                            </button>
                        </div>

                        <!-- LƯỚI ẢNH (SORTABLE) -->
                        <div id="imageGrid" class="sortable-grid"></div>
                    </div>
                </div>

                <!-- CỘT PHẢI: THÔNG TIN -->
                <div class="col-12 col-lg-7 order-lg-1">
                    <div class="form-card">

                        <div class="mb-4">
                            <label class="form-label">Tiêu đề / Mã số <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control custom-input"
                                placeholder="Ví dụ: Acc VIP 123" required>
                        </div>

                        <!-- CHỌN CHẾ ĐỘ (SWITCH) -->
                        <label class="form-label mb-2">LOẠI SẢN PHẨM (CHỌN CẢ 2 ĐỀU ĐƯỢC)</label>

                        <!-- Switch Bán -->
                        <div class="mode-switch-group">
                            <div class="d-flex align-items-center gap-3">
                                <i class="ph-fill ph-shopping-cart text-warning fs-4"></i>
                                <div>
                                    <div class="fw-bold">Bán Vĩnh Viễn</div>
                                    <small class="text-secondary">Khách mua đứt acc này</small>
                                </div>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="switchSell" checked
                                    onchange="toggleSections()">
                            </div>
                        </div>

                        <!-- Khu vực nhập giá Bán -->
                        <div id="sellSection" class="mb-4 ps-3 border-start border-3 border-warning">
                            <label class="form-label">Giá Bán (VNĐ)</label>
                            <input type="text" name="price" class="form-control custom-input price-input-lg"
                                placeholder="0" oninput="formatCurrency(this)">
                        </div>

                        <!-- Switch Thuê -->
                        <div class="mode-switch-group">
                            <div class="d-flex align-items-center gap-3">
                                <i class="ph-fill ph-clock-user text-info fs-4"></i>
                                <div>
                                    <div class="fw-bold">Cho Thuê</div>
                                    <small class="text-secondary">Khách thuê theo giờ/ngày</small>
                                </div>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="switchRent"
                                    onchange="toggleSections()">
                            </div>
                        </div>

                        <!-- Khu vực nhập giá Thuê -->
                        <div id="rentSection" class="mb-4 ps-3 border-start border-3 border-info"
                            style="display: none;">
                            <label class="form-label">Giá Thuê (VNĐ)</label>
                            <div class="row g-2">
                                <div class="col-7">
                                    <input type="text" name="price_rent"
                                        class="form-control custom-input price-input-lg" placeholder="0"
                                        oninput="formatCurrency(this)">
                                </div>
                                <div class="col-5">
                                    <select name="unit" class="form-select h-100">
                                        <option value="1">/ Giờ</option>
                                        <option value="2">/ Ngày</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-5">
                            <button type="button" onclick="submitForm()" class="btn-submit">
                                <i class="ph-bold ph-check-circle me-2"></i> ĐĂNG SẢN PHẨM
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
                    <button type="button" class="btn btn-warning text-white fw-bold"
                        onclick="confirmLibrarySelection()">Chọn ảnh</button>
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
    <!-- FILE JS LOGIC SẼ ĐƯỢC GỬI Ở BƯỚC TIẾP THEO -->
    <script src="assets/js/admin-add.js?v=<?= time() ?>"></script>
</body>

</html>