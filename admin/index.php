<?php
// admin/index.php - FINAL VERSION: UI MỚI + LOGIC ĐA GIÁ + XÓA NHIỀU
require_once 'auth.php';
require_once '../includes/config.php';
require_once '../includes/functions.php';

// --- 1. XỬ LÝ XÓA NHIỀU (BULK DELETE) ---
if (isset($_POST['btn_delete_multi']) && !empty($_POST['selected_ids'])) {
    $ids = $_POST['selected_ids']; // Mảng các ID được chọn
    $countDeleted = 0;

    foreach ($ids as $id) {
        $id = (int)$id;
        // Lấy thông tin ảnh để xóa file
        $stmt = $conn->prepare("SELECT thumb, gallery FROM products WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $prod = $stmt->fetch();

        if ($prod) {
            // Xóa ảnh
            if (!empty($prod['thumb']) && file_exists("../uploads/" . $prod['thumb'])) {
                @unlink("../uploads/" . $prod['thumb']);
            }
            $gallery = json_decode($prod['gallery'], true);
            if (is_array($gallery)) {
                foreach ($gallery as $g) {
                    if (file_exists("../uploads/" . $g)) @unlink("../uploads/" . $g);
                }
            }
            // Xóa DB
            $conn->prepare("DELETE FROM products WHERE id = :id")->execute([':id' => $id]);
            $countDeleted++;
        }
    }
    header("Location: index.php?msg=deleted_multi&count=$countDeleted");
    exit;
}

// --- 2. XỬ LÝ LỌC & TÌM KIẾM ---
$viewType = isset($_GET['type']) ? $_GET['type'] : ''; // ''=All, 'sell', 'rent'
$keyword  = isset($_GET['q']) ? trim($_GET['q']) : '';
$page     = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit    = 10;
$offset   = ($page - 1) * $limit;

// Xây dựng câu lệnh SQL
$whereArr = [];
$params = [];

// Lọc theo loại (Dựa vào giá)
if ($viewType === 'sell') {
    $whereArr[] = "price > 0";
} elseif ($viewType === 'rent') {
    $whereArr[] = "price_rent > 0";
}

// Tìm kiếm
if ($keyword) {
    $whereArr[] = "(title LIKE :kw OR id = :id)";
    $params[':kw'] = "%$keyword%";
    $params[':id'] = (int)$keyword;
}

$whereSql = !empty($whereArr) ? "WHERE " . implode(" AND ", $whereArr) : "";

// Đếm tổng
$stmtCount = $conn->prepare("SELECT COUNT(*) FROM products $whereSql");
$stmtCount->execute($params);
$totalRecords = $stmtCount->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Lấy dữ liệu (Fix lỗi bindValue bằng cách đưa limit/offset thẳng vào chuỗi)
$sql = "SELECT * FROM products $whereSql ORDER BY id DESC LIMIT $limit OFFSET $offset";
$stmt = $conn->prepare($sql);
foreach ($params as $key => $val) $stmt->bindValue($key, $val);
$stmt->execute();
$products = $stmt->fetchAll();

// Thống kê nhanh
$totalAcc = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn();
$countSale = $conn->query("SELECT COUNT(*) FROM products WHERE price > 0")->fetchColumn();
$countRent = $conn->query("SELECT COUNT(*) FROM products WHERE price_rent > 0")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Acc</title>
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
            <a href="index.php" class="menu-item active"><i class="ph-duotone ph-squares-four"></i> Tổng Quan</a>
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
        <div class="content-container">

            <div class="top-header">
                <h4 class="m-0 text-dark">Quản lý sản phẩm</h4>
            </div>

            <!-- THỐNG KÊ -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-md-4">
                    <div class="stat-card total">
                        <div class="stat-info">
                            <div class="stat-label">Tổng Acc</div>
                            <div class="stat-value"><?= number_format($totalAcc) ?></div>
                        </div>
                        <div class="stat-icon"><i class="ph-duotone ph-shopping-cart"></i></div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="stat-card sale">
                        <div class="stat-info">
                            <div class="stat-label">Acc Bán</div>
                            <div class="stat-value"><?= number_format($countSale) ?></div>
                        </div>
                        <div class="stat-icon"><i class="ph-duotone ph-tag"></i></div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="stat-card rent">
                        <div class="stat-info">
                            <div class="stat-label">Acc Thuê</div>
                            <div class="stat-value"><?= number_format($countRent) ?></div>
                        </div>
                        <div class="stat-icon"><i class="ph-duotone ph-clock"></i></div>
                    </div>
                </div>
            </div>

            <!-- CÔNG CỤ: LỌC + TÌM KIẾM + THÊM -->
            <div
                class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 p-3 bg-white border rounded-4 shadow-sm">
                <!-- Nhóm bên trái: Bộ lọc + Tìm kiếm -->
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <a href="index.php"
                        class="btn btn-sm rounded-pill fw-bold <?= $viewType == '' ? 'btn-dark' : 'btn-light border' ?>">Tất
                        cả</a>
                    <a href="index.php?type=sell"
                        class="btn btn-sm rounded-pill fw-bold <?= $viewType == 'sell' ? 'btn-warning text-white' : 'btn-light border' ?>">Bán</a>
                    <a href="index.php?type=rent"
                        class="btn btn-sm rounded-pill fw-bold <?= $viewType == 'rent' ? 'btn-info text-white' : 'btn-light border' ?>">Thuê</a>

                    <!-- Form Tìm kiếm -->
                    <form action="" method="GET" class="d-flex align-items-center ms-2">
                        <?php if ($viewType): ?><input type="hidden" name="type"
                            value="<?= $viewType ?>"><?php endif; ?>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0"><i
                                    class="ph-bold ph-magnifying-glass"></i></span>
                            <input type="text" name="q" class="form-control border-start-0"
                                placeholder="Tìm tên, mã số..." value="<?= htmlspecialchars($keyword) ?>">
                        </div>
                    </form>
                </div>

                <!-- Nhóm bên phải: Nút Đăng + Nút Xóa -->
                <div class="d-flex align-items-center gap-2">
                    <button type="button" onclick="submitDelete()" id="btnDeleteMulti"
                        class="btn btn-danger btn-sm rounded-pill fw-bold" style="display:none;">
                        <i class="ph-bold ph-trash"></i> Xóa (<span id="countSelect">0</span>)
                    </button>
                    <a href="add.php" class="btn btn-warning text-white btn-sm rounded-pill fw-bold px-3">
                        <i class="ph-bold ph-plus"></i> Đăng Acc
                    </a>
                </div>
            </div>

            <!-- TABLE -->
            <form id="formMultiDelete" method="POST" action="">
                <input type="hidden" name="btn_delete_multi" value="1">
                <div class="card-table desktop-table">
                    <div class="table-responsive">
                        <table class="table align-middle table-hover">
                            <thead>
                                <tr>
                                    <th class="ps-4" width="40">
                                        <input type="checkbox" class="form-check-input" onclick="toggleAll(this)">
                                    </th>
                                    <th width="80">Ảnh</th>
                                    <th>Thông tin Acc</th>
                                    <th>Giá tiền</th>
                                    <th>Trạng thái</th>
                                    <th class="text-end pe-4">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $p): ?>
                                <tr>
                                    <td class="ps-4">
                                        <input type="checkbox" name="selected_ids[]" value="<?= $p['id'] ?>"
                                            class="form-check-input item-check" onclick="updateDeleteBtn()">
                                    </td>
                                    <td><img src="../uploads/<?= $p['thumb'] ?>" class="thumb-img" loading="lazy"></td>
                                    <td>
                                        <div class="fw-bold text-dark">#<?= $p['id'] ?> - <?= $p['title'] ?></div>
                                        <div class="d-flex gap-1 mt-1">
                                            <?php if ($p['price'] > 0): ?>
                                            <span class="badge badge-soft-success" style="font-size:10px">BÁN</span>
                                            <?php endif; ?>
                                            <?php if ($p['price_rent'] > 0): ?>
                                            <span class="badge badge-soft-success"
                                                style="font-size:10px; background:#eff6ff; color:#3b82f6">THUÊ</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($p['price'] > 0): ?>
                                        <div class="text-success fw-bold"><?= formatPrice($p['price']) ?></div>
                                        <?php endif; ?>
                                        <?php if ($p['price_rent'] > 0): ?>
                                        <div class="text-primary small">
                                            <?= formatPrice($p['price_rent']) ?>/<?= $p['unit'] == 2 ? 'ngày' : 'giờ' ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $p['status'] == 1
                                                ? '<span class="badge-soft badge-soft-success">Đang bán</span>'
                                                : '<span class="badge-soft badge-soft-danger">Đã bán/Ẩn</span>' ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="../detail.php?id=<?= $p['id'] ?>" target="_blank"
                                            class="btn-action btn-action-view me-1"><i class="ph-bold ph-eye"></i></a>
                                        <a href="edit.php?id=<?= $p['id'] ?>" class="btn-action btn-action-edit me-1"><i
                                                class="ph-bold ph-pencil-simple"></i></a>
                                        <a href="delete.php?id=<?= $p['id'] ?>" class="btn-action btn-action-delete"
                                            onclick="return confirmDelete(event, this.href)"><i
                                                class="ph-bold ph-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-secondary">Không tìm thấy dữ liệu</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>

            <!-- PAGINATION -->
            <?php if ($totalPages > 1): ?>
            <div class="d-flex justify-content-center py-4">
                <nav>
                    <ul class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                            <a class="page-link"
                                href="?page=<?= $i ?>&type=<?= $viewType ?>&q=<?= $keyword ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>

        </div>
    </main>

    <!-- MOBILE NAV -->
    <div class="bottom-nav">
        <a href="index.php" class="nav-item active"><i class="ph-duotone ph-squares-four"></i></a>
        <a href="add.php" class="nav-item">
            <div class="nav-item-add"><i class="ph-bold ph-plus"></i></div>
        </a>
        <a href="library.php" class="nav-item"><i class="ph-duotone ph-image"></i></a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Thông báo SweetAlert
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('msg') === 'deleted_multi') {
        Swal.fire('Thành công', `Đã xóa ${urlParams.get('count')} Acc`, 'success');
        window.history.replaceState({}, document.title, "index.php");
    }

    // Checkbox Logic
    function toggleAll(source) {
        document.querySelectorAll('.item-check').forEach(c => c.checked = source.checked);
        updateDeleteBtn();
    }

    function updateDeleteBtn() {
        const count = document.querySelectorAll('.item-check:checked').length;
        const btn = document.getElementById('btnDeleteMulti');
        document.getElementById('countSelect').innerText = count;
        btn.style.display = count > 0 ? 'inline-block' : 'none';
    }

    function submitDelete() {
        Swal.fire({
            title: 'Xác nhận xóa?',
            text: "Các Acc đã chọn sẽ bị xóa vĩnh viễn!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Xóa ngay',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('formMultiDelete').submit();
            }
        })
    }

    function confirmDelete(e, url) {
        e.preventDefault();
        Swal.fire({
            title: 'Xóa Acc này?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Xóa'
        }).then((res) => {
            if (res.isConfirmed) window.location.href = url;
        });
    }
    </script>
</body>

</html>