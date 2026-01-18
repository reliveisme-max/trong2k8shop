<?php
// admin/add_bulk.php - ĐĂNG LÔ (BẢNG NGUYÊN BẢN, VUỐT NGANG TRÊN MOBILE)
require_once 'auth.php';
require_once '../includes/config.php';

// 1. Lấy danh sách danh mục (để đưa vào ô chọn)
$cats = $conn->query("SELECT * FROM categories ORDER BY display_order ASC, id ASC")->fetchAll();

// 2. Logic Auto ID
//$conn->query("ALTER TABLE products AUTO_INCREMENT = 1");
$stmt = $conn->query("SELECT MAX(id) FROM products");
$maxId = $stmt->fetchColumn();
$nextId = $maxId ? ($maxId + 1) : 1;
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Acc Lô</title>

    <!-- LIB -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/mobile.css?v=<?= time() ?>">

    <style>
        /* Tinh chỉnh riêng cho bảng Đăng Lô */
        .bulk-wrapper {
            padding: 0;
            overflow: hidden;
        }

        /* Cố định chiều rộng cột để bảng không bị co rúm */
        .col-id {
            width: 60px;
            text-align: center;
        }

        .col-img {
            width: 90px;
            text-align: center;
        }

        .col-price {
            min-width: 140px;
        }

        .col-title {
            min-width: 200px;
        }

        .col-cat {
            min-width: 180px;
        }

        /* Cột danh mục */
        .col-note {
            min-width: 200px;
        }

        .col-del {
            width: 50px;
            text-align: center;
        }

        /* Mobile: Cho phép cuộn ngang mượt mà */
        @media (max-width: 768px) {
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                padding-bottom: 10px;
                /* Để thanh cuộn dễ bấm */
            }
        }
    </style>
</head>

<body>
    <!-- MENU TRÁI (PC) -->
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="d-flex align-items-center mb-4 gap-3">
            <a href="index.php" class="btn btn-light border btn-sm px-3 rounded-pill d-md-none">
                <i class="ph-bold ph-arrow-left"></i>
            </a>
            <h4 class="m-0 fw-bold text-dark">Đăng Acc Lô (20 dòng)</h4>
        </div>

        <!-- CÀI ĐẶT CHUNG (ĐIỀN NHANH) -->
        <div class="form-card mb-4 border-start border-4 border-primary">
            <h6 class="fw-bold mb-3 text-primary"><i class="ph-fill ph-sliders-horizontal"></i> ĐIỀN NHANH (ÁP DỤNG HẾT)
            </h6>
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="small fw-bold text-secondary">Ghi chú chung</label>
                    <input type="text" id="globalNote" class="form-control custom-input"
                        placeholder="Ví dụ: Acc trắng thông tin...">
                </div>
                <div class="col-7 col-md-4">
                    <label class="small fw-bold text-secondary">Danh mục chung</label>
                    <select id="globalCategory" class="form-select custom-input text-primary fw-bold">
                        <!-- JS sẽ tự chọn cái đầu tiên, nhưng ở đây cứ để option để chọn lại -->
                        <?php foreach ($cats as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-5 col-md-3">
                    <button type="button" id="btnApplyGlobal" class="btn btn-primary w-100 shadow-sm fw-bold"
                        style="height: 44px;">
                        <i class="ph-bold ph-lightning"></i> ÁP DỤNG
                    </button>
                </div>
            </div>
        </div>

        <!-- BẢNG NHẬP LIỆU -->
        <div class="bulk-wrapper card">
            <div class="table-responsive">
                <table class="table table-bordered table-bulk mb-0 align-middle" id="bulkTable">
                    <thead class="bg-light">
                        <tr>
                            <th class="col-id">ID</th>
                            <th class="col-img">ẢNH</th>
                            <th class="col-price">GIÁ (VNĐ)</th>
                            <th class="col-title">TÊN ACC</th>
                            <th class="col-cat">DANH MỤC</th>
                            <th class="col-note">GHI CHÚ</th>
                            <th class="col-del">X</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <!-- JS sẽ sinh ra 20 dòng ở đây -->
                    </tbody>
                </table>
            </div>

            <!-- THANH CÔNG CỤ DƯỚI CÙNG -->
            <div class="p-3 bg-white border-top d-flex gap-2 sticky-bottom align-items-center" style="z-index: 5;">
                <button id="btnAddRows" class="btn btn-light border fw-bold text-secondary flex-grow-1 flex-md-grow-0">
                    <i class="ph-bold ph-plus"></i> Thêm 5 dòng
                </button>
                <button type="button" id="btnSubmitBulk" class="btn btn-primary fw-bold ms-auto px-4 px-md-5 shadow">
                    <i class="ph-bold ph-floppy-disk"></i> LƯU TẤT CẢ
                </button>
            </div>
        </div>

        <div style="height: 100px;"></div>
    </main>

    <!-- MENU DƯỚI (MOBILE) -->
    <?php include 'includes/bottom_nav.php'; ?>

    <!-- MODAL ẢNH -->
    <div class="modal fade" id="imageModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Quản lý ảnh</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border border-dashed d-flex align-items-center gap-3 mb-3 cursor-pointer"
                        onclick="document.getElementById('modalFileInput').click()">
                        <div class="bg-primary bg-opacity-10 p-2 rounded-circle text-primary"><i
                                class="ph-bold ph-plus fs-4"></i></div>
                        <div>
                            <div class="fw-bold text-dark">Thêm ảnh vào đây</div>
                            <div class="small text-secondary">Chọn nhiều ảnh cùng lúc</div>
                        </div>
                    </div>
                    <input type="file" id="modalFileInput" multiple accept="image/*" hidden>
                    <div class="modal-grid" id="modalImgGrid"></div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-primary w-100 fw-bold py-2"
                        data-bs-dismiss="modal">XONG</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // CẤU HÌNH DỮ LIỆU ĐỂ JS VẼ BẢNG
        const BULK_CONFIG = {
            categories: <?= json_encode($cats) ?>, // Danh sách danh mục để tạo <select>
            startId: <?= $nextId ?>
        };
    </script>
    <script src="assets/js/pages/bulk-upload.js?v=<?= time() ?>"></script>
</body>

</html>