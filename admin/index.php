<?php
// admin/index.php - UPDATE: SHOW CATEGORIES & FILTER
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
$catId    = isset($_GET['cat']) ? (int)$_GET['cat'] : 0; // Lọc theo danh mục
$page     = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit    = 10;
$offset   = ($page - 1) * $limit;

$whereArr = [];
$params = [];

// Tìm kiếm
if ($keyword) {
    $whereArr[] = "(p.title LIKE :kw OR p.id = :id)";
    $params[':kw'] = "%$keyword%";
    $params[':id'] = (int)$keyword;
}

// Lọc Danh Mục
if ($catId > 0) {
    $whereArr[] = "p.category_id = :cat";
    $params[':cat'] = $catId;
}

$whereSql = !empty($whereArr) ? "WHERE " . implode(" AND ", $whereArr) : "";

// Đếm tổng
$sqlCount = "SELECT COUNT(*) FROM products p $whereSql";
$stmtCount = $conn->prepare($sqlCount);
$stmtCount->execute($params);
$totalRecords = $stmtCount->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// [QUERY] JOIN VỚI BẢNG CATEGORIES ĐỂ LẤY TÊN
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

// Lấy danh sách danh mục để hiện vào Select box
$cats = $conn->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();

// Thống kê nhanh
$totalAcc = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn();

// Xử lý AJAX
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    renderTableBody($products);
    echo "<!--DIVIDER-->";
    renderPagination($page, $totalPages);
    exit;
}

function renderTableBody($products)
{
    if (empty($products)) {
        echo '<tr><td colspan="7" class="text-center py-5 text-secondary">Không tìm thấy dữ liệu</td></tr>';
        return;
    }
    foreach ($products as $p) {
        $thumb = !empty($p['thumb']) ? "../uploads/" . $p['thumb'] : "../assets/images/no-image.jpg";
        $catName = $p['cat_name'] ? $p['cat_name'] : '<span class="text-muted fst-italic">Chưa phân loại</span>';
?>
        <tr>
            <td class="ps-4"><input type="checkbox" name="selected_ids[]" value="<?= $p['id'] ?>"
                    class="form-check-input item-check" onclick="updateDeleteBtn()"></td>
            <td>
                <img src="<?= $thumb ?>" class="thumb-img" loading="lazy">
            </td>
            <td>
                <div class="fw-bold text-dark text-break">#<?= $p['id'] ?> - <?= $p['title'] ?></div>

                <?php if (!empty($p['private_note'])): ?>
                    <div class="mt-1 text-secondary fst-italic note-badge"
                        style="font-size: 11px; background: #fffbeb; padding: 2px 6px; border-radius: 4px; border: 1px dashed #fcd34d; display: inline-block;">
                        <i class="ph-fill ph-note-pencil text-warning"></i> <?= htmlspecialchars($p['private_note']) ?>
                    </div>
                <?php endif; ?>
            </td>
            <!-- CỘT DANH MỤC MỚI -->
            <td>
                <span class="badge bg-light text-dark border"><?= $catName ?></span>
            </td>
            <td>
                <div class="fw-bold text-danger" style="font-size: 15px;"><?= formatPrice($p['price']) ?></div>
            </td>
            <td><?= $p['status'] == 1 ? '<span class="badge-soft badge-status-active">Đang bán</span>' : '<span class="badge-soft badge-status-sold">Đã bán</span>' ?>
            </td>
            <td class="text-end pe-4">
                <a href="../detail.php?id=<?= $p['id'] ?>" target="_blank" class="btn-action btn-action-view me-1"><i
                        class="ph-bold ph-eye"></i></a>
                <a href="edit.php?id=<?= $p['id'] ?>" class="btn-action btn-action-edit me-1"><i
                        class="ph-bold ph-pencil-simple"></i></a>
                <a href="delete.php?id=<?= $p['id'] ?>" class="btn-action btn-action-delete"
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= time() ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <aside class="sidebar">
        <div class="brand"><i class="ph-fill ph-crown"></i> ADMIN PANEL</div>
        <nav class="d-flex flex-column gap-2">
            <a href="index.php" class="menu-item active"><i class="ph-duotone ph-squares-four"></i> Tổng Quan</a>
            <a href="add.php" class="menu-item"><i class="ph-duotone ph-stack"></i> Đăng Acc (Bulk)</a>
            <a href="categories.php" class="menu-item"><i class="ph-duotone ph-list-dashes"></i> Danh Mục</a>
            <a href="change_pass.php" class="menu-item"><i class="ph-duotone ph-lock-key"></i> Đổi mật khẩu</a>
            <div class="mt-auto"><a href="logout.php" class="menu-item text-danger fw-bold"><i
                        class="ph-duotone ph-sign-out"></i> Đăng xuất</a></div>
        </nav>
    </aside>

    <main class="main-content">
        <div class="content-container">
            <div class="top-header">
                <h4 class="m-0 text-dark">Quản lý sản phẩm</h4>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="stat-card total">
                        <div class="stat-info">
                            <div class="stat-label">Tổng Acc</div>
                            <div class="stat-value"><?= number_format($totalAcc) ?></div>
                        </div>
                        <div class="stat-icon"><i class="ph-duotone ph-shopping-cart"></i></div>
                    </div>
                </div>
            </div>

            <div class="admin-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3 w-100">
                    <!-- BỘ LỌC DANH MỤC MỚI -->
                    <div style="min-width: 200px;">
                        <select id="catFilter" class="form-select custom-input" onchange="applyFilter()">
                            <option value="0">-- Tất cả danh mục --</option>
                            <?php foreach ($cats as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $catId == $c['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="search-group flex-grow-1">
                        <i class="ph-bold ph-magnifying-glass"></i>
                        <input type="text" id="searchInput" placeholder="Tìm tên acc, mã số..."
                            value="<?= htmlspecialchars($keyword) ?>"
                            onkeypress="if(event.key === 'Enter') applyFilter();">
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" onclick="submitDelete()" id="btnDeleteMulti"
                            class="btn btn-danger btn-sm rounded-pill fw-bold px-3" style="display:none;"><i
                                class="ph-bold ph-trash"></i> Xóa (<span id="countSelect">0</span>)</button>
                        <a href="add.php"
                            class="btn btn-warning btn-sm rounded-pill fw-bold px-3 py-2 d-flex align-items-center gap-2"><i
                                class="ph-bold ph-plus"></i> Đăng Acc</a>
                    </div>
                </div>
            </div>

            <form id="formMultiDelete" method="POST" action=""><input type="hidden" name="btn_delete_multi" value="1">
                <div class="card-table desktop-table position-relative">
                    <div id="ajaxLoading" class="ajax-loading-overlay d-none">
                        <div class="spinner-border text-warning" role="status"></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle table-hover">
                            <thead>
                                <tr>
                                    <th class="ps-4" width="40"><input type="checkbox" class="form-check-input"
                                            onclick="toggleAll(this)"></th>
                                    <th width="80">Ảnh</th>
                                    <th>Thông tin Acc</th>
                                    <th>Danh mục</th> <!-- Cột mới -->
                                    <th>Giá tiền</th>
                                    <th>Trạng thái</th>
                                    <th class="text-end pe-4">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody"><?php renderTableBody($products); ?></tbody>
                        </table>
                    </div>
                </div>
            </form>
            <div id="paginationContainer" class="d-flex justify-content-center py-4">
                <?php renderPagination($page, $totalPages); ?></div>
        </div>
    </main>

    <div class="bottom-nav"><a href="index.php" class="nav-item active"><i class="ph-duotone ph-squares-four"></i></a><a
            href="add.php" class="nav-item">
            <div class="nav-item-add"><i class="ph-bold ph-plus"></i></div>
        </a><a href="#" class="nav-item disabled"><i class="ph-duotone ph-image" style="opacity:0.3"></i></a></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- JS Cập nhật cho bộ lọc danh mục -->
    <script>
        // ... (SweetAlert logic cũ) ...

        function loadPage(page) {
            const keyword = document.getElementById('searchInput').value;
            const catId = document.getElementById('catFilter').value;
            fetchData(page, keyword, catId);
        }

        function applyFilter() {
            const keyword = document.getElementById('searchInput').value;
            const catId = document.getElementById('catFilter').value;
            fetchData(1, keyword, catId);
        }

        function fetchData(page, keyword, catId) {
            const loading = document.getElementById('ajaxLoading');
            if (loading) loading.classList.remove('d-none');

            const url = `index.php?page=${page}&q=${encodeURIComponent(keyword)}&cat=${catId}&ajax=1`;

            fetch(url)
                .then(response => response.text())
                .then(data => {
                    const parts = data.split('<!--DIVIDER-->');
                    if (parts.length >= 2) {
                        document.getElementById('tableBody').innerHTML = parts[0];
                        document.getElementById('paginationContainer').innerHTML = parts[1];
                    }
                    if (loading) loading.classList.add('d-none');

                    const newUrl = `index.php?page=${page}&q=${encodeURIComponent(keyword)}&cat=${catId}`;
                    window.history.pushState({
                        path: newUrl
                    }, '', newUrl);

                    updateDeleteBtn();
                });
        }

        // ... (Các hàm Checkbox cũ giữ nguyên) ...
        function toggleAll(source) {
            document.querySelectorAll('.item-check').forEach(c => c.checked = source.checked);
            updateDeleteBtn();
        }

        function updateDeleteBtn() {
            const count = document.querySelectorAll('.item-check:checked').length;
            const btn = document.getElementById('btnDeleteMulti');
            const countSpan = document.getElementById('countSelect');
            if (countSpan) countSpan.innerText = count;
            if (btn) btn.style.display = count > 0 ? 'inline-block' : 'none';
        }

        function submitDelete() {
            Swal.fire({
                title: 'Xác nhận xóa?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Xóa ngay'
            }).then((result) => {
                if (result.isConfirmed) document.getElementById('formMultiDelete').submit();
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