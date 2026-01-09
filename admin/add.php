<?php
// admin/add.php - V3: LAYOUT MỚI (ẢNH -> GIÁ -> TÊN)
require_once 'auth.php';
require_once '../includes/config.php';

// Lấy danh sách danh mục (Sắp xếp theo thứ tự ưu tiên display_order)
// Lưu ý: Nếu chưa chạy SQL thêm cột display_order thì nó vẫn chạy được (mặc định 0)
$cats = $conn->query("SELECT * FROM categories ORDER BY display_order ASC, id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Acc Hàng Loạt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= time() ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .bulk-wrapper {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .table-bulk th {
            background: #f9fafb;
            font-size: 12px;
            text-transform: uppercase;
            padding: 12px;
            border-bottom: 2px solid #eee;
            white-space: nowrap;
        }

        .table-bulk td {
            vertical-align: middle;
            padding: 8px;
            border-bottom: 1px solid #f3f4f6;
        }

        .img-cell-box {
            width: 70px;
            height: 70px;
            background: #f3f4f6;
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
        }

        .img-cell-box:hover {
            border-color: #1877F2;
            background: #eff6ff;
        }

        .img-cell-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
            filter: brightness(0.9);
        }

        .img-cell-count {
            position: relative;
            z-index: 2;
            color: #fff;
            font-weight: 800;
            font-size: 16px;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.8);
        }

        /* Modal Grid */
        .modal-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            max-height: 400px;
            overflow-y: auto;
            padding: 5px;
        }

        .modal-item {
            position: relative;
            padding-bottom: 100%;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #eee;
        }

        .modal-item img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .btn-del-img {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 24px;
            height: 24px;
            background: rgba(255, 255, 255, 0.9);
            color: #ef4444;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <aside class="sidebar">
        <div class="brand"><i class="ph-fill ph-crown"></i> ADMIN PANEL</div>
        <nav class="d-flex flex-column gap-2">
            <a href="index.php" class="menu-item"><i class="ph-duotone ph-squares-four"></i> Tổng Quan</a>
            <a href="add.php" class="menu-item active"><i class="ph-duotone ph-stack"></i> Đăng Acc (Bulk)</a>
            <a href="categories.php" class="menu-item"><i class="ph-duotone ph-list-dashes"></i> Danh Mục</a>
            <a href="change_pass.php" class="menu-item"><i class="ph-duotone ph-lock-key"></i> Đổi mật khẩu</a>
            <div class="mt-auto"><a href="logout.php" class="menu-item text-danger fw-bold"><i
                        class="ph-duotone ph-sign-out"></i> Đăng xuất</a></div>
        </nav>
    </aside>

    <main class="main-content">
        <div class="d-flex align-items-center mb-4">
            <h4 class="m-0 fw-bold text-dark">Đăng Acc Nhanh (Layout Mới)</h4>
        </div>

        <!-- CÀI ĐẶT CHUNG -->
        <div class="form-card mb-4 bg-light border-start border-4 border-warning">
            <h6 class="fw-bold mb-3 text-warning"><i class="ph-fill ph-sliders-horizontal"></i> ĐIỀN NHANH (ÁP DỤNG HẾT)
            </h6>
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="small fw-bold text-secondary">Ghi chú chung</label>
                    <input type="text" id="globalNote" class="form-control custom-input"
                        placeholder="Ví dụ: Acc trắng thông tin...">
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-secondary">Danh mục chung</label>
                    <select id="globalCategory" class="form-select custom-input">
                        <option value="">-- Chọn danh mục --</option>
                        <?php foreach ($cats as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="button" id="btnApplyGlobal" class="btn btn-warning text-white fw-bold w-100 shadow-sm"
                        style="height: 46px;">
                        <i class="ph-bold ph-lightning"></i> ÁP DỤNG NGAY
                    </button>
                </div>
            </div>
        </div>

        <!-- BẢNG NHẬP LIỆU -->
        <div class="bulk-wrapper">
            <div class="table-responsive">
                <table class="table table-bordered table-bulk mb-0" id="bulkTable">
                    <thead>
                        <tr>
                            <th width="40" class="text-center">#</th>
                            <th width="90" class="text-center">ẢNH</th>
                            <th width="150">GIÁ BÁN (VNĐ)</th> <!-- Đã chuyển lên trước -->
                            <th width="250">TÊN ACC (Tùy chọn)</th>
                            <th width="200">DANH MỤC</th>
                            <th width="200">GHI CHÚ</th>
                            <th width="40" class="text-center">X</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody"></tbody>
                </table>
            </div>

            <div class="p-3 bg-white border-top d-flex gap-2 sticky-bottom" style="z-index: 5;">
                <button id="btnAddRows" class="btn btn-light border fw-bold text-secondary">
                    <i class="ph-bold ph-plus"></i> Thêm 5 dòng
                </button>
                <!-- Nút type="button" để chống submit form -->
                <button type="button" id="btnSubmitBulk" class="btn btn-primary fw-bold ms-auto px-5 shadow">
                    <i class="ph-bold ph-floppy-disk"></i> LƯU TẤT CẢ
                </button>
            </div>
        </div>
        <div style="height: 100px;"></div>
    </main>

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
        // Truyền danh sách danh mục sang JS
        const BULK_CONFIG = {
            categories: <?= json_encode($cats) ?>
        };
    </script>
    <script src="assets/js/pages/bulk-upload.js?v=<?= time() ?>"></script>
</body>

</html>