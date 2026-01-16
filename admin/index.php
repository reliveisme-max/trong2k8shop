<?php
// admin/index.php - FINAL: FILTER NOTE + MOBILE HORIZONTAL VIEW
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
        $stmt = $conn->prepare("SELECT thumb, gallery FROM products WHERE id = :id");
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
$noteFilter = isset($_GET['note']) ? trim($_GET['note']) : ''; // Lọc ghi chú mới
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
if ($noteFilter !== '') {
    $whereArr[] = "p.private_note = :note";
    $params[':note'] = $noteFilter;
}

$whereSql = !empty($whereArr) ? "WHERE " . implode(" AND ", $whereArr) : "";

// Đếm tổng
$stmtCount = $conn->prepare("SELECT COUNT(*) FROM products p $whereSql");
foreach ($params as $key => $val) $stmtCount->bindValue($key, $val);
$stmtCount->execute();
$totalRecords = $stmtCount->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Lấy danh sách Acc
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

// Lấy danh mục
$cats = $conn->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();

// [MỚI] Lấy danh sách Ghi chú duy nhất để làm bộ lọc
$notes = $conn->query("SELECT DISTINCT private_note FROM products WHERE private_note != '' ORDER BY private_note ASC")->fetchAll(PDO::FETCH_COLUMN);

// Thống kê nhanh
$totalAcc = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalSelling = $conn->query("SELECT COUNT(*) FROM products WHERE status = 1")->fetchColumn();
$totalSold = $conn->query("SELECT COUNT(*) FROM products WHERE status = 0")->fetchColumn();

// --- AJAX RENDER ---
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    renderTableBody($products);
    echo "<!--DIVIDER-->";
    renderPagination($page, $totalPages);
    exit;
}

// HÀM RENDER HTML (CÓ 3 CHẤM MOBILE)
function renderTableBody($products)
{
    if (empty($products)) {
        echo '<tr><td colspan="7" class="text-center py-5 text-secondary">Không tìm thấy dữ liệu</td></tr>';
        return;
    }
    foreach ($products as $p) {
        $thumb = !empty($p['thumb']) ? "../uploads/" . $p['thumb'] : "../assets/images/no-image.jpg";
        $catName = $p['cat_name'] ? $p['cat_name'] : 'Chưa phân loại';
        $isSold = ($p['status'] == 0);
        $rowClass = $isSold ? 'opacity-75 bg-light' : '';
        $badgeClass = $isSold ? 'bg-secondary' : 'bg-primary bg-opacity-10 text-primary border border-primary';
?>
        <tr class="<?= $rowClass ?>">
            <!-- 1. CHECKBOX (Ẩn mobile) -->
            <td class="ps-4 d-none d-md-table-cell" width="40">
                <input type="checkbox" name="selected_ids[]" value="<?= $p['id'] ?>" class="form-check-input item-check"
                    onclick="updateDeleteBtn()">
            </td>

            <!-- 2. ẢNH (Chung) -->
            <td class="cell-img">
                <img src="<?= $thumb ?>" class="thumb-img" loading="lazy">
            </td>

            <!-- 3. MOBILE INFO (Chỉ hiện Mobile - Dòng ngang) -->
            <td class="d-md-none cell-info-wrapper">
                <div class="mobile-title">#<?= $p['id'] ?> - <?= htmlspecialchars($p['title']) ?></div>
                <div class="mobile-price"><?= formatPrice($p['price']) ?></div>
                <div class="mobile-meta">
                    <span class="badge bg-light text-dark border px-2 me-2"><?= $catName ?></span>
                    <?php if ($p['private_note']): ?>
                        <span class="text-secondary fst-italic text-truncate" style="max-width: 100px;">
                            <i class="ph-fill ph-note"></i> <?= htmlspecialchars($p['private_note']) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </td>

            <!-- 4. PC INFO (Ẩn mobile) -->
            <td class="d-none d-md-table-cell">
                <div class="fw-bold text-dark">#<?= $p['id'] ?> - <?= htmlspecialchars($p['title']) ?></div>
            </td>
            <td class="d-none d-md-table-cell">
                <div class="fw-bold text-danger"><?= formatPrice($p['price']) ?></div>
            </td>
            <td class="d-none d-md-table-cell">
                <span class="badge <?= $badgeClass ?>"><?= $catName ?></span>
            </td>
            <td class="d-none d-md-table-cell">
                <span class="text-secondary small fst-italic"><?= htmlspecialchars($p['private_note']) ?></span>
            </td>

            <!-- 5. HÀNH ĐỘNG (Chia 2 loại) -->
            <td class="text-end pe-4 cell-action-mobile">

                <!-- PC Buttons -->
                <div class="d-none d-md-flex justify-content-end gap-2">
                    <a href="../detail.php?id=<?= $p['id'] ?>" target="_blank" class="btn btn-light border btn-sm" title="Xem">
                        <i class="ph-bold ph-eye"></i>
                    </a>
                    <a href="add_single.php?id=<?= $p['id'] ?>" class="btn btn-primary btn-sm text-white fw-bold">
                        <i class="ph-bold ph-pencil-simple"></i> Sửa
                    </a>
                    <a href="delete.php?id=<?= $p['id'] ?>" class="btn btn-danger btn-sm text-white fw-bold"
                        onclick="return confirmDelete(event, this.href)">
                        <i class="ph-bold ph-trash"></i>
                    </a>
                </div>

                <!-- MOBILE 3 DOTS (Nút 3 chấm) -->
                <div class="d-md-none dropdown">
                    <button class="btn-3dots" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="ph-bold ph-dots-three-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-mobile shadow">
                        <li><a class="dropdown-item" href="../detail.php?id=<?= $p['id'] ?>" target="_blank"><i
                                    class="ph-bold ph-eye text-primary"></i> Xem chi tiết</a></li>
                        <li><a class="dropdown-item" href="add_single.php?id=<?= $p['id'] ?>"><i
                                    class="ph-bold ph-pencil-simple text-warning"></i> Sửa acc</a></li>
                        <li>
                            <hr class="dropdown-divider my-1">
                        </li>
                        <li><a class="dropdown-item text-danger" href="delete.php?id=<?= $p['id'] ?>"
                                onclick="return confirmDeleteMobile(event, this.href)"><i class="ph-bold ph-trash"></i> Xóa vĩnh
                                viễn</a></li>
                    </ul>
                </div>
            </td>
        </tr>
<?php
    }
}

function renderPagination($page, $totalPages)
{ /* Giữ nguyên hàm này như cũ */
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/admin.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/mobile.css?v=<?= time() ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <!-- HEADER -->
        <div class="mb-4 d-flex justify-content-between align-items-end">
            <div>
                <h4 class="text-dark fw-bold m-0">Tổng quan hệ thống</h4>
                <span class="text-secondary small">Hôm nay: <?= date('d/m/Y') ?></span>
            </div>
            <a href="add_single.php" class="btn btn-primary rounded-pill px-4 shadow-sm d-none d-md-inline-block">
                <i class="ph-bold ph-plus me-1"></i> Đăng Acc
            </a>
        </div>

        <!-- STATS -->
        <div class="row g-3 mb-4">
            <div class="col-4">
                <div class="stat-card p-3">
                    <div class="stat-info">
                        <div class="stat-label small text-secondary">Tổng</div>
                        <div class="stat-value fs-4 fw-bold text-dark"><?= number_format($totalAcc) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="stat-card p-3">
                    <div class="stat-info">
                        <div class="stat-label small text-success">Còn</div>
                        <div class="stat-value fs-4 fw-bold text-success"><?= number_format($totalSelling) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="stat-card p-3">
                    <div class="stat-info">
                        <div class="stat-label small text-secondary">Đã bán</div>
                        <div class="stat-value fs-4 fw-bold text-secondary"><?= number_format($totalSold) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- BỘ LỌC (FILTER) -->
        <div class="form-card mb-4 py-3">
            <div class="row g-2 align-items-center">
                <!-- 1. Danh mục -->
                <div class="col-6 col-md-3">
                    <select id="catFilter" class="form-select border bg-light" onchange="applyFilter()">
                        <option value="0">-- Tất cả mục --</option>
                        <?php foreach ($cats as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $catId == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- 2. Tìm kiếm -->
                <div class="col-6 col-md-5">
                    <div class="position-relative">
                        <input type="text" id="searchInput" class="form-control border bg-light ps-5"
                            placeholder="Tìm kiếm..." value="<?= htmlspecialchars($keyword) ?>"
                            onkeypress="if(event.key === 'Enter') applyFilter();">
                        <i
                            class="ph-bold ph-magnifying-glass position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary"></i>
                    </div>
                </div>

                <!-- [MỚI] 3. LỌC GHI CHÚ (Nằm dưới ở mobile, ngang ở PC nếu đủ chỗ) -->
                <div class="col-12 col-md-4 mt-2 mt-md-0">
                    <select id="noteFilter" class="form-select border bg-light text-primary fw-bold"
                        onchange="applyFilter()">
                        <option value="">-- Lọc theo Ghi chú --</option>
                        <?php foreach ($notes as $note): ?>
                            <option value="<?= htmlspecialchars($note) ?>" <?= $noteFilter === $note ? 'selected' : '' ?>>
                                Note: <?= htmlspecialchars($note) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Nút xóa nhiều -->
                <div class="col-12 text-end mt-2" id="btnDeleteMulti" style="display:none;">
                    <button onclick="submitDelete()" class="btn btn-danger w-100 rounded-pill fw-bold">
                        <i class="ph-bold ph-trash"></i> Xóa (<span id="countSelect">0</span>)
                    </button>
                </div>
            </div>
        </div>

        <!-- DANH SÁCH -->
        <form id="formMultiDelete" method="POST" action=""><input type="hidden" name="btn_delete_multi" value="1">
            <div class="card-table position-relative">
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
                                <th class="text-end pe-4">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody"><?php renderTableBody($products); ?></tbody>
                    </table>
                </div>
            </div>
        </form>

        <div id="paginationContainer" class="d-flex justify-content-center py-4">
            <?php renderPagination($page, $totalPages); ?>
        </div>
        <div class="d-md-none" style="height: 60px;"></div>
    </main>

    <?php include 'includes/bottom_nav.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin-utils.js?v=<?= time() ?>"></script>
    <script src="assets/js/pages/product-list.js?v=<?= time() ?>"></script>
    <script src="assets/js/mobile-app.js?v=<?= time() ?>"></script>
</body>

</html>