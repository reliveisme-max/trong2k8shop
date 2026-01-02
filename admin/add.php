<?php
// admin/add.php - PHIÊN BẢN GỌN (TÁCH FILE CSS/JS)
require_once '../includes/config.php';
session_start();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Acc Mới - Admin</title>
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">

    <!-- CSS TỰ VIẾT -->
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?= time() ?>">
</head>

<body>

    <div class="container mt-4">
        <a href="index.php" class="text-decoration-none text-secondary fw-bold mb-3 d-inline-block">
            <i class="ph-bold ph-arrow-left"></i> Quay lại
        </a>

        <div class="form-container">
            <h4 class="text-center text-warning fw-bold mb-4">ĐĂNG ACC MỚI</h4>

            <form action="process.php" method="POST" enctype="multipart/form-data">

                <!-- INPUT ẨN -->
                <input type="hidden" name="selected_thumb" id="inputSelectedThumb">
                <input type="hidden" name="selected_gallery" id="inputSelectedGallery">

                <!-- 1. Tiêu đề -->
                <div class="mb-3">
                    <label class="form-label">Tiêu đề Acc</label>
                    <input type="text" name="title" class="form-control" placeholder="VD: M416 Băng giá..." required>
                </div>

                <!-- 2. Giá tiền -->
                <div class="mb-3">
                    <label class="form-label">Giá bán</label>
                    <input type="number" name="price" class="form-control" placeholder="VNĐ" required>
                </div>

                <!-- 3. Ảnh Bìa -->
                <div class="mb-3">
                    <label class="form-label">Ảnh Bìa</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="upload-area" onclick="document.getElementById('thumbInput').click()">
                                <i class="ph-fill ph-upload-simple fs-3 text-secondary"></i>
                                <div class="small mt-1">Tải ảnh mới</div>
                            </div>
                            <input type="file" id="thumbInput" name="thumb" accept="image/*" hidden
                                onchange="previewSingle(this)">
                        </div>
                        <div class="col-6">
                            <div class="btn btn-library h-100 d-flex flex-column align-items-center justify-content-center"
                                onclick="openLibrary('thumb')">
                                <i class="ph-fill ph-images-square fs-3"></i>
                                <div class="small mt-1">Chọn thư viện</div>
                            </div>
                        </div>
                    </div>
                    <div id="preview-thumb"></div>
                </div>

                <!-- 4. Album Ảnh -->
                <div class="mb-3">
                    <label class="form-label">Album Chi Tiết</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="upload-area" onclick="document.getElementById('galleryInput').click()">
                                <i class="ph-fill ph-upload-simple fs-3 text-secondary"></i>
                                <div class="small mt-1">Tải nhiều ảnh</div>
                            </div>
                            <input type="file" id="galleryInput" name="gallery[]" accept="image/*" multiple hidden
                                onchange="previewGallery(this)">
                        </div>
                        <div class="col-6">
                            <div class="btn btn-library h-100 d-flex flex-column align-items-center justify-content-center"
                                onclick="openLibrary('gallery')">
                                <i class="ph-fill ph-stack fs-3"></i>
                                <div class="small mt-1">Chọn thư viện</div>
                            </div>
                        </div>
                    </div>
                    <div id="preview-gallery"></div>
                </div>

                <!-- 5. Mô tả -->
                <div class="mb-3">
                    <label class="form-label">Mô tả</label>
                    <textarea name="description" class="form-control" rows="4"></textarea>
                </div>

                <div class="sticky-footer">
                    <button type="submit" name="btn_submit" class="btn btn-submit w-100 mw-100">
                        ĐĂNG BÁN NGAY
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- MODAL THƯ VIỆN INFINITE SCROLL -->
    <div class="modal fade" id="libraryModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-warning">Chọn ảnh từ thư viện</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <!-- Khu vực cuộn -->
                <div class="library-scroll-area" id="scrollArea">
                    <div class="lib-grid" id="libGrid"></div>

                    <div id="loadingIndicator" class="loading-zone" style="display: none;">
                        <div class="spinner-border text-warning spinner-border-sm" role="status"></div>
                        <span class="ms-2">Đang tải thêm ảnh...</span>
                    </div>

                    <div id="endOfData" class="loading-zone text-muted" style="display: none;">
                        Đã hiển thị hết ảnh trong kho!
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary fw-bold" onclick="confirmSelection()">Xác nhận
                        chọn</button>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPT -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin-add.js?v=<?= time() ?>"></script>

</body>

</html>