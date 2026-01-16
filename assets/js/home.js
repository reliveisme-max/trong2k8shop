/**
 * assets/js/home.js
 * PHIÊN BẢN GỘP: MASONRY + AJAX PAGE + ADMIN EDIT
 * (Thay thế hoàn toàn main.js cũ)
 */

let msnry; // Biến Masonry toàn cục

document.addEventListener("DOMContentLoaded", function() {
    // 1. Kích hoạt Masonry (Xếp gạch) ngay khi vào trang
    initMasonryGrid();

    // 2. Xử lý đóng Dropdown phân trang khi bấm ra ngoài
    document.addEventListener('click', function(e) {
        const container = document.querySelector('.pagination-container-modern');
        if (container && !container.contains(e.target)) {
            const dropdown = document.getElementById('pagiDropdown');
            const trigger = document.getElementById('pagiTrigger');
            if (dropdown) dropdown.classList.remove('show');
            if (trigger) trigger.classList.remove('is-open');
        }
    });
});

// ==================================================
// A. LOGIC GIAO DIỆN KHÁCH (MASONRY & AJAX)
// ==================================================

function initMasonryGrid() {
    const grid = document.querySelector('#productGrid');
    if (!grid) return;

    // Hủy instance cũ nếu có để tránh lỗi
    if (msnry) {
        msnry.destroy();
        msnry = null;
    }

    // Chờ ảnh tải xong mới xếp
    if (typeof imagesLoaded === 'function') {
        imagesLoaded(grid, function() {
            msnry = new Masonry(grid, {
                itemSelector: '.feed-item-scroll',
                percentPosition: true
            });
            grid.style.opacity = '1';
        });
    }
}

function togglePaginationGrid() {
    const dropdown = document.getElementById('pagiDropdown');
    const trigger = document.getElementById('pagiTrigger');
    if (!dropdown) return;

    dropdown.classList.toggle('show');
    trigger.classList.toggle('is-open');

    if (dropdown.classList.contains('show')) {
        const activeItem = dropdown.querySelector('.pagi-num.active');
        if (activeItem) activeItem.scrollIntoView({ block: 'nearest', inline: 'center' });
    }
}

// Chuyển trang mượt (Ajax)
async function goToPage(pageNum) {
    if (pageNum < 1 || pageNum > window.totalPages || pageNum === window.currentPage) return;
    window.currentPage = pageNum;

    // Đóng dropdown
    const dropdown = document.getElementById('pagiDropdown');
    const trigger = document.getElementById('pagiTrigger');
    if (dropdown) dropdown.classList.remove('show');
    if (trigger) trigger.classList.remove('is-open');

    // Cập nhật UI phân trang
    updatePaginationUI(pageNum);
    
    // Gọi dữ liệu mới
    await loadGridData(pageNum);
}

function updatePaginationUI(pageNum) {
    const lbl = document.getElementById('lblCurrentPage');
    if (lbl) lbl.innerText = pageNum;

    document.querySelectorAll('.pagi-num').forEach(el => {
        el.classList.remove('active');
        if (parseInt(el.getAttribute('data-page')) === pageNum) el.classList.add('active');
    });

    const btnPrev = document.querySelector('.js-prev-btn');
    const btnNext = document.querySelector('.js-next-btn');

    if (btnPrev) {
        if (pageNum <= 1) { btnPrev.classList.add('disabled'); btnPrev.setAttribute('onclick', ''); }
        else { btnPrev.classList.remove('disabled'); btnPrev.setAttribute('onclick', `goToPage(${pageNum - 1})`); }
    }
    if (btnNext) {
        if (pageNum >= window.totalPages) { btnNext.classList.add('disabled'); btnNext.setAttribute('onclick', ''); }
        else { btnNext.classList.remove('disabled'); btnNext.setAttribute('onclick', `goToPage(${pageNum + 1})`); }
    }
}

async function loadGridData(pageNum) {
    const grid = document.getElementById('productGrid');
    if (!grid) return;

    if (msnry) { msnry.destroy(); msnry = null; }

    const currentHeight = grid.offsetHeight;
    grid.style.minHeight = currentHeight + 'px';

    // Cuộn lên đầu lưới
    const headerOffset = 100;
    const elementPosition = grid.getBoundingClientRect().top;
    const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
    window.scrollTo({ top: offsetPosition, behavior: "smooth" });

    // Hiện Spinner
    grid.innerHTML = `<div class="col-12 text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>`;

    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('page', pageNum);
    urlParams.set('ajax', '1');

    try {
        await new Promise(r => setTimeout(r, 300)); // Delay nhẹ cho mượt
        const res = await fetch('index.php?' + urlParams.toString());
        const html = await res.text();

        grid.innerHTML = html;
        initMasonryGrid();
        grid.style.minHeight = '0px'; 
        
        urlParams.delete('ajax');
        const newUrl = window.location.pathname + '?' + urlParams.toString();
        window.history.pushState({ path: newUrl }, '', newUrl);

    } catch (err) {
        console.error(err);
        grid.innerHTML = '<div class="col-12 text-center text-danger py-5">Lỗi kết nối!</div>';
    }
}

function copyCode(text) {
    navigator.clipboard.writeText(text).then(function() {
        const Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false, timer: 1500, timerProgressBar: true
        });
        Toast.fire({ icon: 'success', title: 'Đã sao chép!' });
    });
}

// ==================================================
// B. LOGIC ADMIN (SỬA NHANH & XÓA)
// ==================================================
// Thay thế hàm openQuickEdit cũ bằng hàm mới này
function openQuickEdit(e, id) {
    e.preventDefault(); 
    fetch('admin/api/get_product_info.php?id=' + id)
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                const p = data.data;
                document.getElementById('qeIdDisplay').innerText = p.id;
                document.getElementById('qeId').value = p.id;
                document.getElementById('qeTitle').value = p.title;
                document.getElementById('qeCategory').value = p.category_id;
                document.getElementById('qeNote').value = p.private_note || '';
                
                // Format giá
                document.getElementById('qePrice').value = new Intl.NumberFormat('vi-VN').format(p.price);
                document.getElementById('btnFullEdit').href = 'admin/edit.php?id=' + p.id;

                // --- [MỚI] LOGIC TỰ ĐỘNG TRẠNG THÁI ---
                const catSelect = document.getElementById('qeCategory');
                const statusInput = document.getElementById('qeStatus');

                // Hàm kiểm tra: Có chữ "đã bán" thì set status = 0, ngược lại = 1
                function autoUpdateStatus() {
                    const text = catSelect.options[catSelect.selectedIndex].text.toLowerCase();
                    if (text.includes('đã bán')) {
                        statusInput.value = 0;
                    } else {
                        statusInput.value = 1;
                    }
                }

                // Gán sự kiện: Khi đổi danh mục thì chạy hàm kiểm tra
                catSelect.onchange = autoUpdateStatus;
                
                // Chạy 1 lần ngay lúc mở modal để set đúng trạng thái ban đầu
                autoUpdateStatus();

                new bootstrap.Modal(document.getElementById('quickEditModal')).show();
            } else {
                Swal.fire('Lỗi', data.msg, 'error');
            }
        });
}

function saveQuickEdit() {
    const data = {
        id: document.getElementById('qeId').value,
        title: document.getElementById('qeTitle').value,
        status: document.getElementById('qeStatus').value,
        category_id: document.getElementById('qeCategory').value,
        price: document.getElementById('qePrice').value,
        private_note: document.getElementById('qeNote').value
    };

    fetch('admin/api/save_quick_edit.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(res => {
        if(res.status === 'success') {
            Swal.fire({
                icon: 'success', title: 'Đã lưu!', timer: 1000, showConfirmButton: false
            }).then(() => location.reload());
        } else {
            Swal.fire('Lỗi', res.msg, 'error');
        }
    });
}

function parsePrice(input) {
    let val = input.value.toLowerCase().trim();
    if (!val) return;
    const regex = /^([0-9]+[.,]?[0-9]*)(k|m|tr)([0-9]*)$/;
    const match = val.match(regex);
    if (match) {
        let mainNum = parseFloat(match[1].replace(',', '.'));
        let unit = match[2];
        let money = 0;
        if (unit === 'k') money = mainNum * 1000;
        else if (unit === 'm' || unit === 'tr') money = mainNum * 1000000;
        if (money > 0) input.value = money.toLocaleString('vi-VN').replace(/,/g, '.');
    } else {
        let value = input.value.replace(/\D/g, '');
        if (value !== '') input.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
}

function confirmDelHome(e, link) {
    e.preventDefault(); 
    e.stopPropagation();
    Swal.fire({
        title: 'Xóa Acc này?', text: "Xóa là mất luôn đấy nhé!", icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Xóa ngay', cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) window.location.href = link;
    });
}