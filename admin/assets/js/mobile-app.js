/**
 * admin/assets/js/mobile-app.js
 * Xử lý các tương tác riêng cho giao diện điện thoại
 */

document.addEventListener('DOMContentLoaded', function() {
    // 1. Tự động căn chỉnh padding dưới cùng để không bị Menu Đáy che
    adjustContentPadding();
    window.addEventListener('resize', adjustContentPadding);

    // 2. Trải nghiệm App: Tự động đóng Menu 3 chấm khi cuộn trang
    // (Giúp màn hình thoáng, không bị menu che nội dung khi đang lướt)
    window.addEventListener('scroll', function() {
        const openDropdowns = document.querySelectorAll('.dropdown-toggle.show');
        if (openDropdowns.length > 0) {
            document.body.click(); // Giả lập click ra ngoài để đóng dropdown
        }
    }, { passive: true });
});

// Hàm tính toán chiều cao Menu đáy
function adjustContentPadding() {
    const bottomNav = document.querySelector('.bottom-nav');
    const mainContent = document.querySelector('.main-content');
    
    // Chỉ chạy khi đang ở chế độ Mobile (có hiện bottom nav)
    if (bottomNav && getComputedStyle(bottomNav).display !== 'none' && mainContent) {
        const h = bottomNav.offsetHeight;
        // Thêm khoảng trống = chiều cao menu + 20px
        mainContent.style.paddingBottom = (h + 20) + 'px';
    }
}

// 3. Hàm Xác nhận xóa (Thiết kế popup nhỏ gọn cho Mobile)
// Dùng thay thế cho confirmDelete() trên PC
function confirmDeleteMobile(e, url) {
    e.preventDefault();
    Swal.fire({
        title: 'Xóa acc này?',
        text: "Hành động này không thể hoàn tác!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Xóa luôn',
        cancelButtonText: 'Hủy',
        reverseButtons: true, // Đảo nút Xóa sang phải cho thuận tay cái
        width: 320,           // Popup nhỏ vừa màn hình điện thoại
        padding: '1em',
        customClass: {
            title: 'fs-5 fw-bold',
            content: 'small text-muted'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
}