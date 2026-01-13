<?php
// admin/index.php - V8: REORDER COLUMNS
require_once 'auth.php';
require_once '../includes/config.php';
require_once '../includes/functions.php';

$current_id = $_SESSION['admin_id'];

// --- 1. XỬ LÝ XÓA NHIỀU ---
if (isset($_POST['btn_delete_multi']) && !empty($_POST['selected_ids'])) {
    $ids = $_POST['selected_ids'];
    $countDeleted = 0;
    foreach ($ids as $id) {
        $id = (int)$id;
        $sqlCheck = "SELECT thumb, gallery FROM products WHERE id = :id";
        $stmt = $conn->prepare($sqlCheck);
        $stmt->execute([':id' => $id]);
        $prod = $stmt->fetch();

        if ($prod) {
            if (!empty($prod['thumb']) && file_exists("../uploads/" . $prod['thumb'])) {
                @unlink("../uploads/" . $prod['thumb']);
            }
            $gallery = json_decode($prod['gallery'], true);
            if (is_array($gallery)) {
                foreach ($gallery as $g) {
                    if (file_exists("../uploads/" . $g)) @unlink("../uploads/" . $g);
                }
            }
            $conn->prepare("DELETE FROM products WHERE id = :id")->execute([':id' => $id]);
            $countDeleted++;
        }
    }
    header("Location: index.php?msg=deleted_multi&count=$countDeleted");
    exit;
}

// --- 2. LẤY DỮ LIỆU & LỌC ---
$keyword  = isset($_GET['q']) ? trim($_GET['q']) : '';
$catId    = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$page     = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit    = 10;
$offset   = ($page - 1) * $limit;

$whereArr = [];
$params = [];

if ($keyword) {
    $whereArr[] = "(p.title LIKE :kw OR p.id = :id)";
    $params[':kw'] = "%$keyword%";
    $params[':id'] = (int)$keyword;
}
if ($catId > 0) {
    $whereArr[] = "p.category_id = :cat";
    $params[':cat'] = $catId;
}

$whereSql = !empty($whereArr) ? "WHERE " . implode(" AND ", $whereArr) : "";

$sqlCount = "SELECT COUNT(*) FROM products p $whereSql";
$stmtCount = $conn->prepare($sqlCount);
$stmtCount->execute($params);
$totalRecords = $stmtCount->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

$sql = "SELECT p.*, c.name as cat_name
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        $whereSql 
        ORDER BY p.id DESC 
        LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($sql);
foreach ($params as $key => $val) $stmt->bindValue($key, $val);
$stmt->execute();
$products = $stmt->fetchAll();

$cats = $conn->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();

// --- THỐNG KÊ NHANH ---
$totalAcc = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalSelling = $conn->query("SELECT COUNT(*) FROM products WHERE status = 1")->fetchColumn();
$totalSold = $conn->query("SELECT COUNT(*) FROM products WHERE status = 0")->fetchColumn();

if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    renderTableBody($products);
    echo "<!--DIVIDER-->";
    renderPagination($page, $totalPages);
    exit;
}

function renderTableBody($products)
{
    if (empty($products)) {
        echo '<tr><td colspan="8" class="text-center py-5 text-secondary">Không tìm thấy dữ liệu</td></tr>';
        return;
    }
    foreach ($products as $p) {
        $thumb = !empty($p['thumb']) ? "../uploads/" . $p['thumb'] : "../assets/images/no-image.jpg";
        $catName = $p['cat_name'] ? $p['cat_name'] : '<span class="text-muted fst-italic">Chưa phân loại</span>';
        $isChecked = ($p['status'] == 1) ? 'checked' : '';
?>
        <tr>
            <!-- 1. CHECKBOX -->
            <td class="ps-4"><input type="checkbox" name="selected_ids[]" value="<?= $p['id'] ?>"
                    class="form-check-input item-check" onclick="updateDeleteBtn()"></td>

            <!-- 2. ẢNH -->
            <td>
                <img src="<?= $thumb ?>" class="thumb-img" loading="lazy">
            </td>

            <!-- 3. THÔNG TIN ACC -->
            <td>
                <div class="fw-bold text-dark text-break">#<?= $p['id'] ?> - <?= htmlspecialchars($p['title']) ?></div>
            </td>

            <!-- 4. GIÁ TIỀN -->
            <td>
                <div class="fw-bold" style="color: var(--primary); font-size: 15px;"><?= formatPrice($p['price']) ?></div>
            </td>

            <!-- 5. DANH MỤC -->
            <td>
                <span class="badge bg-light text-dark border"><?= $catName ?></span>
            </td>

            <!-- 6. GHI CHÚ -->
            <td>
                <span class="text-secondary small fst-italic"><?= htmlspecialchars($p['private_note']) ?></span>
            </td>

            <!-- 7. TRẠNG THÁI -->
            <td>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" style="width: 40px; height: 20px; cursor: pointer;"
                        onchange="toggleStatus(this, <?= $p['id'] ?>)" <?= $isChecked ?>>
                </div>
            </td>

            <!-- 8. THAO TÁC -->
            <td class="text-end pe-4">
                <a href="../detail.php?id=<?= $p['id'] ?>" target="_blank" class="btn btn-light border btn-sm"><i
                        class="ph-bold ph-eye"></i></a>
                <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-light border btn-sm text-primary"><i
                        class="ph-bold ph-pencil-simple"></i></a>
                <a href="delete.php?id=<?= $p['id'] ?>" class="btn btn-light border btn-sm text-danger"
                    onclick="return confirmDelete(event, this.href)"><i class="ph-bold ph-trash"></i></a>
            </td>
        </tr>
<?php
    }
}

function renderPagination($page, $totalPages)
{
    if ($totalPages <= 1) return;
    $getLink = function ($p) {
        $query = $_GET;
        $query['page'] = $p;
        return '?' . http_build_query($query);
    };
    echo '<nav><ul class="pagination justify-content-center">';
    $prevClass = ($page <= 1) ? 'disabled' : '';
    echo '<li class="page-item ' . $prevClass . '"><a class="page-link" href="' . $getLink($page - 1) . '" onclick="loadPage(' . ($page - 1) . '); return false;"><i class="ph-bold ph-caret-left"></i></a></li>';

    $range = 2;
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i == 1 || $i == $totalPages || ($i >= $page - $range && $i <= $page + $range)) {
            $active = ($i == $page) ? 'active' : '';
            echo '<li class="page-item ' . $active . '"><a class="page-link" href="' . $getLink($i) . '" onclick="loadPage(' . $i . '); return false;">' . $i . '</a></li>';
        } elseif ($i == $page - $range - 1 || $i == $page + $range + 1) {
            echo '<li class="page-item disabled"><span class="page-link border-0 bg-transparent">...</span></li>';
        }
    }

    $nextClass = ($page >= $totalPages) ? 'disabled' : '';
    echo '<li class="page-item ' . $nextClass . '"><a class="page-link" href="' . $getLink($page + 1) . '" onclick="loadPage(' . ($page + 1) . '); return false;"><i class="ph-bold ph-caret-right"></i></a></li>';
    echo '</ul></nav>';
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Shop</title>
    <!-- FONT & CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= time() ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="brand"><i class="ph-fill ph-crown"></i> ADMIN PANEL</div>
        <nav class="d-flex flex-column gap-2">
            <a href="index.php" class="menu-item active"><i class="ph-bold ph-squares-four"></i> Tổng Quan</a>
            <a href="add.php" class="menu-item"><i class="ph-bold ph-plus-circle"></i> Đăng Acc Mới</a>
            <a href="categories.php" class="menu-item"><i class="ph-bold ph-list-dashes"></i> Danh Mục Game</a>
            <a href="change_pass.php" class="menu-item"><i class="ph-bold ph-lock-key"></i> Đổi mật khẩu</a>
            <div class="mt-auto"><a href="logout.php" class="menu-item text-danger fw-bold"><i
                        class="ph-bold ph-sign-out"></i> Đăng xuất</a></div>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- HEADER -->
        <div class="mb-4 d-flex justify-content-between align-items-end">
            <div>
                <h4 class="text-dark m-0">Tổng quan hệ thống</h4>
                <span class="text-secondary" style="font-size: 14px;">Hôm nay: <?= date('d/m/Y') ?></span>
            </div>
        </div>

        <!-- STATS CARDS -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon"><i class="ph-fill ph-game-controller"></i></div>
                    <div class="stat-info text-end">
                        <div class="stat-label">Tổng Acc</div>
                        <div class="stat-value"><?= number_format($totalAcc) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon text-success bg-success bg-opacity-10"><i class="ph-fill ph-check-circle"></i>
                    </div>
                    <div class="stat-info text-end">
                        <div class="stat-label">Đang bán</div>
                        <div class="stat-value text-success"><?= number_format($totalSelling) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon text-secondary bg-secondary bg-opacity-10"><i class="ph-fill ph-bag"></i>
                    </div>
                    <div class="stat-info text-end">
                        <div class="stat-label">Đã bán</div>
                        <div class="stat-value text-secondary"><?= number_format($totalSold) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TOOLBAR & FILTER -->
        <div class="form-card mb-4 py-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-3">
                    <select id="catFilter" class="form-select border-0 bg-light" onchange="applyFilter()">
                        <option value="0">-- Tất cả danh mục --</option>
                        <?php foreach ($cats as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $catId == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <div class="position-relative">
                        <input type="text" id="searchInput" class="form-control border-0 bg-light ps-5"
                            placeholder="Tìm kiếm tên acc, mã số..." value="<?= htmlspecialchars($keyword) ?>"
                            onkeypress="if(event.key === 'Enter') applyFilter();">
                        <i
                            class="ph-bold ph-magnifying-glass position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary"></i>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <button type="button" onclick="openBulkEdit()" id="btnEditMulti"
                        class="btn btn-warning text-white rounded-pill fw-bold px-3 me-2" style="display:none;">
                        <i class="ph-bold ph-pencil-simple"></i> Sửa (<span id="countSelect">0</span>)
                    </button>
                    <button type="button" onclick="submitDelete()" id="btnDeleteMulti"
                        class="btn btn-danger rounded-pill fw-bold px-3 me-2" style="display:none;">
                        <i class="ph-bold ph-trash"></i> Xóa
                    </button>
                    <a href="add.php" class="btn btn-primary rounded-pill px-4 shadow-sm"><i
                            class="ph-bold ph-plus me-1"></i> Đăng Acc</a>
                </div>
            </div>
        </div>

        <!-- DATA TABLE -->
        <form id="formMultiDelete" method="POST" action=""><input type="hidden" name="btn_delete_multi" value="1">
            <div class="card-table desktop-table position-relative">
                <div id="ajaxLoading" class="ajax-loading-overlay d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4" width="40"><input type="checkbox" class="form-check-input"
                                        onclick="toggleAll(this)"></th>
                                <th width="70">Ảnh</th>
                                <th>Thông tin Acc</th>
                                <th>Giá tiền</th>
                                <th>Danh mục</th>
                                <th>Ghi chú</th>
                                <th>Trạng thái</th>
                                <th class="text-end pe-4">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody"><?php renderTableBody($products); ?></tbody>
                    </table>
                </div>
            </div>
        </form>

        <!-- PAGINATION -->
        <div id="paginationContainer" class="d-flex justify-content-center py-4">
            <?php renderPagination($page, $totalPages); ?>
        </div>
    </main>

    <!-- MODAL SỬA NHIỀU -->
    <div class="modal fade" id="bulkEditModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Sửa hàng loạt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-secondary small mb-3">Đang chọn: <b id="lblBulkCount" class="text-primary">0</b> Acc
                    </p>
                    <div class="mb-3">
                        <label class="fw-bold mb-1">Bạn muốn thay đổi gì?</label>
                        <select id="bulkAction" class="form-select custom-input" onchange="toggleBulkInput()">
                            <option value="status">Đổi Trạng Thái</option>
                            <option value="category">Đổi Danh Mục</option>
                            <option value="price">Đổi Giá Tiền</option>
                        </select>
                    </div>
                    <div id="boxStatus" class="bulk-input-box">
                        <select id="valStatus" class="form-select custom-input">
                            <option value="1">Đang Bán (Hiện)</option>
                            <option value="0">Đã Bán (Ẩn/Mờ)</option>
                        </select>
                    </div>
                    <div id="boxCategory" class="bulk-input-box d-none">
                        <select id="valCategory" class="form-select custom-input">
                            <option value="0">-- Bỏ phân loại --</option>
                            <?php foreach ($cats as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="boxPrice" class="bulk-input-box d-none">
                        <input type="text" id="valPrice" class="form-control custom-input"
                            placeholder="Nhập giá mới (VD: 500k)..." onblur="parsePriceShortcut(this)">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary fw-bold px-4" onclick="submitBulkEdit()">LƯU THAY
                        ĐỔI</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin-utils.js?v=<?= time() ?>"></script>
    <script src="assets/js/pages/product-list.js?v=<?= time() ?>"></script>
</body>

</html>