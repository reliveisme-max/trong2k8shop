<?php
// admin/categories.php - V2: UPDATE DISPLAY ORDER
require_once 'auth.php';
require_once '../includes/config.php';

// 1. XỬ LÝ THÊM DANH MỤC
if (isset($_POST['btn_add'])) {
    $name = trim($_POST['name']);
    $order = isset($_POST['order']) ? (int)$_POST['order'] : 0; // Lấy số thứ tự

    if (!empty($name)) {
        // Thêm cả tên và thứ tự vào DB
        $stmt = $conn->prepare("INSERT INTO categories (name, display_order) VALUES (:name, :order)");
        $stmt->execute([':name' => $name, ':order' => $order]);
        header("Location: categories.php");
        exit;
    }
}

// 2. XỬ LÝ XÓA DANH MỤC
if (isset($_GET['del'])) {
    $id = (int)$_GET['del'];
    $conn->prepare("DELETE FROM categories WHERE id = :id")->execute([':id' => $id]);
    $conn->prepare("UPDATE products SET category_id = 0 WHERE category_id = :id")->execute([':id' => $id]);
    header("Location: categories.php");
    exit;
}

// 3. LẤY DANH SÁCH (SẮP XẾP THEO THỨ TỰ ƯU TIÊN)
// ORDER BY display_order ASC: Số nhỏ lên đầu (1, 2, 3...)
$cats = $conn->query("SELECT * FROM categories ORDER BY display_order ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Danh Mục</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= time() ?>">
</head>

<body>
    <aside class="sidebar">
        <!-- LOGO MỚI -->
        <div class="brand"><i class="ph-fill ph-crown"></i> ADMIN PANEL</div>

        <nav class="d-flex flex-column gap-2">
            <a href="index.php" class="menu-item"><i class="ph-bold ph-squares-four"></i> Tổng Quan</a>

            <!-- MENU MỚI -->
            <a href="add.php" class="menu-item"><i class="ph-bold ph-plus-circle"></i> Đăng Acc Mới</a>

            <a href="categories.php" class="menu-item"><i class="ph-bold ph-list-dashes"></i> Danh Mục Game</a>
            <a href="change_pass.php" class="menu-item"><i class="ph-bold ph-lock-key"></i> Đổi mật khẩu</a>

            <div class="mt-auto">
                <a href="logout.php" class="menu-item text-danger fw-bold"><i class="ph-bold ph-sign-out"></i> Đăng
                    xuất</a>
            </div>
        </nav>
    </aside>

    <main class="main-content">
        <div class="d-flex align-items-center mb-4">
            <h4 class="m-0 fw-bold text-dark">Quản Lý Danh Mục</h4>
        </div>

        <div class="row g-4">
            <!-- FORM THÊM -->
            <div class="col-md-4">
                <div class="form-card">
                    <h6 class="fw-bold mb-3">Thêm Danh Mục Mới</h6>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Tên Danh Mục</label>
                            <input type="text" name="name" class="form-control custom-input"
                                placeholder="VD: Acc Order, Acc Có Sẵn..." required>
                        </div>

                        <!-- Ô NHẬP THỨ TỰ MỚI -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Thứ tự hiển thị</label>
                            <input type="number" name="order" class="form-control custom-input" value="0"
                                placeholder="Số nhỏ hiện trước (1, 2, 3...)">
                            <div class="form-text">Số nhỏ sẽ hiển thị trước.</div>
                        </div>

                        <button type="submit" name="btn_add" class="btn btn-primary w-100 fw-bold">THÊM NGAY</button>
                    </form>
                </div>
            </div>

            <!-- DANH SÁCH -->
            <div class="col-md-8">
                <div class="form-card p-0 overflow-hidden">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Thứ tự</th> <!-- Cột mới -->
                                <th>Tên Danh Mục</th>
                                <th class="text-end pe-4">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cats as $c): ?>
                                <tr>
                                    <!-- Hiển thị Thứ tự -->
                                    <td class="ps-4">
                                        <span class="badge bg-secondary rounded-pill"><?= $c['display_order'] ?></span>
                                    </td>
                                    <td class="fw-bold text-primary"><?= htmlspecialchars($c['name']) ?></td>

                                    <td class="text-end pe-4">
                                        <a href="?del=<?= $c['id'] ?>" class="btn btn-light text-danger border btn-sm"
                                            onclick="return confirm('Xóa danh mục này?');">
                                            <i class="ph-bold ph-trash"></i> Xóa
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($cats)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Chưa có danh mục nào.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>

</html>