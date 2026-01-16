<?php
// admin/includes/sidebar.php
// Lấy tên file hiện tại để highlight menu (VD: index.php)
$curPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <!-- LOGO -->
    <div class="brand">
        <i class="ph-fill ph-crown" style="color: var(--accent);"></i> ADMIN PANEL
    </div>

    <nav class="d-flex flex-column gap-2">
        <!-- 1. TỔNG QUAN -->
        <a href="index.php" class="menu-item <?= ($curPage == 'index.php') ? 'active' : '' ?>">
            <i class="ph-bold ph-squares-four"></i> Tổng Quan
        </a>

        <!-- 2. ĐĂNG LẺ (Mới) -->
        <a href="add_single.php" class="menu-item <?= ($curPage == 'add_single.php') ? 'active' : '' ?>">
            <i class="ph-bold ph-plus-circle"></i> Đăng Acc Lẻ
        </a>

        <!-- 3. ĐĂNG LÔ (File cũ đổi tên) -->
        <a href="add_bulk.php"
            class="menu-item <?= ($curPage == 'add_bulk.php' || $curPage == 'add.php') ? 'active' : '' ?>">
            <i class="ph-bold ph-stack"></i> Đăng Acc Lô
        </a>

        <!-- 4. DANH MỤC -->
        <a href="categories.php" class="menu-item <?= ($curPage == 'categories.php') ? 'active' : '' ?>">
            <i class="ph-bold ph-list-dashes"></i> Danh Mục Game
        </a>

        <!-- 5. ĐỔI MẬT KHẨU -->
        <a href="change_pass.php" class="menu-item <?= ($curPage == 'change_pass.php') ? 'active' : '' ?>">
            <i class="ph-bold ph-lock-key"></i> Đổi mật khẩu
        </a>

        <!-- ĐĂNG XUẤT -->
        <div class="mt-auto">
            <a href="logout.php" class="menu-item text-danger fw-bold">
                <i class="ph-bold ph-sign-out"></i> Đăng xuất
            </a>
        </div>
    </nav>
</aside>