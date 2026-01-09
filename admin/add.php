<?php
// admin/add.php - FINAL FIX WIDTHS & REMOVE GLOBAL PRICE
require_once 'auth.php';
require_once '../includes/config.php';

// 1. Cấu hình Prefix
$prefix = 'MS';

// 2. Tính toán số thứ tự tiếp theo
$sql = "SELECT title FROM products WHERE title LIKE :p ORDER BY id DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->execute([':p' => $prefix . '%']);
$lastTitle = $stmt->fetchColumn();

$startNum = 1;
if ($lastTitle && preg_match('/(\d+)$/', $lastTitle, $matches)) {
    $startNum = (int)$matches[1] + 1;
}

// 3. LẤY DANH SÁCH DANH MỤC
$cats = $conn->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();
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
        /* CSS RIÊNG CHO TRANG BULK */
        .bulk-wrapper {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        /* CẤU HÌNH BẢNG - KHÔNG NGẮT DÒNG */
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

        /* Ô ẢNH */
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
            filter: brightness(0.5);
        }

        .img-cell-count {
            position: relative;
            z-index: 2;
            color: #fff;
            font-weight: 800;
            font-size: 16px;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.8);
        }

        /* MODAL GRID */
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
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        .btn-del-img:hover {
            background: #ef4444;
            color: #fff;
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
            <h4 class="m-0 fw-bold text-dark">Đăng Acc Hàng Loạt (Công Nghiệp)</h4>
        </div>

        <!-- CÀI ĐẶT CHUNG (Đã bỏ ô Giá chung) -->
        <div class="form-card mb-4 bg-light border-start border-4 border-warning">
            <h6 class="fw-bold mb-3 text-warning"><i class="ph-fill ph-sliders-horizontal"></i> CÀI ĐẶT CHUNG (Điền
                nhanh)</h6>
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="small fw-bold text-secondary">Ghi chú chung</label>
                    <input type="text" id="globalNote" class="form-control custom-input"
                        placeholder="Ví dụ: Acc trắng thông tin, bao đổi trả...">
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
                        <i class="ph-bold ph-lightning"></i> ÁP DỤNG TẤT CẢ
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
                            <th width="110">MÃ SỐ (Tự sinh)</th>
                            <!-- Đã chỉnh width theo yêu cầu -->
                            <th width="300">DANH MỤC</th>
                            <th width="180">GIÁ BÁN</th>
                            <th width="250">GHI CHÚ</th>
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
                <button id="btnSubmitBulk" class="btn btn-primary fw-bold ms-auto px-5 shadow">
                    <i class="ph-bold ph-floppy-disk"></i> LƯU TẤT CẢ ACC
                </button>
            </div>
        </div>
        <div style="height: 100px;"></div>
    </main>

    <!-- MODAL (GIỮ NGUYÊN) -->
    <div class="modal fade" id="imageModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Quản lý ảnh: <span id="modalRowTitle" class="text-primary"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border border-dashed d-flex align-items-center gap-3 mb-3 cursor-pointer"
                        onclick="document.getElementById('modalFileInput').click()">
                        <div class="bg-primary bg-opacity-10 p-2 rounded-circle text-primary"><i
                                class="ph-bold ph-plus fs-4"></i></div>
                        <div>
                            <div class="fw-bold text-dark">Bấm vào đây để chọn thêm ảnh</div>
                            <div class="small text-secondary">Kéo thả hoặc chọn nhiều ảnh cùng lúc</div>
                        </div>
                    </div>
                    <input type="file" id="modalFileInput" multiple accept="image/*" hidden>
                    <div class="modal-grid" id="modalImgGrid"></div>
                    <div class="text-center mt-3 text-secondary small fst-italic">Ảnh đầu tiên sẽ tự động làm Ảnh Bìa
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-primary w-100 fw-bold py-2" data-bs-dismiss="modal">XONG, ĐÓNG
                        LẠI</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- TRUYỀN DỮ LIỆU SANG JS -->
    <script>
        const BULK_CONFIG = {
            startNum: <?= $startNum ?>,
            prefix: "<?= $prefix ?>",
            categories: <?= json_encode($cats) ?>
        };
    </script>

    <!-- FILE JS LOGIC -->
    <script src="assets/js/pages/bulk-upload.js?v=<?= time() ?>"></script>
</body>

</html>