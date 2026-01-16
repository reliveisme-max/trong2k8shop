<?php
// admin/includes/bottom_nav.php
$curPage = basename($_SERVER['PHP_SELF']);
?>
<div class="bottom-nav">
    <!-- 1. HOME (Danh sách) -->
    <a href="index.php" class="nav-item <?= ($curPage == 'index.php') ? 'active' : '' ?>">
        <i class="ph-bold ph-house"></i>
        <span>Home</span>
    </a>

    <!-- 2. ĐĂNG LẺ (Nút nổi bật) -->
    <a href="add_single.php" class="nav-item <?= ($curPage == 'add_single.php') ? 'active' : '' ?>">
        <i class="ph-fill ph-plus-circle" style="font-size: 24px; color: var(--primary);"></i>
        <span style="color: var(--primary); font-weight: 800;">Đăng Lẻ</span>
    </a>

    <!-- 3. ĐĂNG LÔ -->
    <a href="add_bulk.php"
        class="nav-item <?= ($curPage == 'add_bulk.php' || $curPage == 'add.php') ? 'active' : '' ?>">
        <i class="ph-bold ph-stack"></i>
        <span>Đăng Lô</span>
    </a>

    <!-- 4. MENU (Cài đặt: Pass, Danh mục, Logout) -->
    <a href="menu.php"
        class="nav-item <?= ($curPage == 'menu.php' || $curPage == 'categories.php' || $curPage == 'change_pass.php') ? 'active' : '' ?>">
        <i class="ph-bold ph-list"></i>
        <span>Menu</span>
    </a>
</div>