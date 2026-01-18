/**
 * admin/assets/js/pages/product-list.js
 * XỬ LÝ LỌC, PHÂN TRANG, XÓA, CHECKBOX
 */

let lastChecked = null;

document.addEventListener('DOMContentLoaded', function() {
    checkUrlMessage();
    initCheckboxLogic();
});

// --- 1. LOGIC CHECKBOX (CHỌN NHIỀU ĐỂ XÓA) ---
function initCheckboxLogic() {
    const checkboxes = document.querySelectorAll('.item-check');
    checkboxes.forEach(chk => {
        chk.addEventListener('click', function(e) {
            if (lastChecked && e.shiftKey) {
                let start = Array.from(checkboxes).indexOf(this);
                let end = Array.from(checkboxes).indexOf(lastChecked);
                const [lower, upper] = start < end ? [start, end] : [end, start];
                for (let i = lower; i <= upper; i++) {
                    checkboxes[i].checked = this.checked;
                }
            }
            lastChecked = this;
            updateActionButtons();
        });
    });
}

function toggleAll(source) {
    document.querySelectorAll('.item-check').forEach(c => c.checked = source.checked);
    updateActionButtons();
}

function updateActionButtons() {
    // 1. Lấy tất cả các ô đã chọn
    const checkedBoxes = document.querySelectorAll('.item-check:checked');
    
    // 2. Dùng Set để lọc các ID trùng nhau (Chỉ đếm ID duy nhất)
    const uniqueIds = new Set();
    checkedBoxes.forEach(chk => uniqueIds.add(chk.value));
    
    // 3. Đếm số lượng ID duy nhất
    const count = uniqueIds.size;

    const btnDelete = document.getElementById('btnDeleteMulti');
    const countSpan = document.getElementById('countSelect');
    
    if(countSpan) countSpan.innerText = count;
    
    // Hiện nút xóa nếu có chọn ít nhất 1 acc
    if(btnDelete) btnDelete.style.display = count > 0 ? 'block' : 'none';
}

// --- 2. LOGIC LỌC & PHÂN TRANG (AJAX) ---

// Chuyển trang
function loadPage(page) {
    const keyword = document.getElementById('searchInput').value;
    const catId = document.getElementById('catFilter').value;
    const note = document.getElementById('noteFilter').value; // Lấy giá trị lọc Note
    fetchData(page, keyword, catId, note);
}

// Áp dụng bộ lọc (Khi thay đổi select hoặc enter tìm kiếm)
function applyFilter() {
    const keyword = document.getElementById('searchInput').value;
    const catId = document.getElementById('catFilter').value;
    const note = document.getElementById('noteFilter').value; // Lấy giá trị lọc Note
    fetchData(1, keyword, catId, note); // Luôn về trang 1 khi lọc mới
}

// Hàm gọi AJAX
function fetchData(page, keyword, catId, note) {
    const loading = document.getElementById('ajaxLoading');
    if(loading) loading.classList.remove('d-none');

    // Tạo URL parameter chuẩn
    const params = new URLSearchParams({
        page: page,
        q: keyword,
        cat: catId,
        note: note,
        ajax: 1
    });

    fetch('index.php?' + params.toString())
        .then(response => response.text())
        .then(data => {
            const parts = data.split('<!--DIVIDER-->');
            if (parts.length >= 2) {
                document.getElementById('tableBody').innerHTML = parts[0];
                document.getElementById('paginationContainer').innerHTML = parts[1];
            }
            if(loading) loading.classList.add('d-none');
            
            // Cập nhật URL trên trình duyệt (để F5 không mất lọc)
            params.delete('ajax'); // Xóa tham số ajax để URL đẹp
            const newUrl = 'index.php?' + params.toString();
            window.history.pushState({path: newUrl}, '', newUrl);

            // Gán lại sự kiện cho các element mới sinh ra
            initCheckboxLogic();
            updateActionButtons();
        })
        .catch(err => {
            console.error('Lỗi tải dữ liệu:', err);
            if(loading) loading.classList.add('d-none');
        });
}

// --- 3. XỬ LÝ XÓA ---

// Xóa nhiều
function submitDelete() {
    Swal.fire({
        title: 'Xác nhận xóa?',
        text: "Các acc đã chọn sẽ bị xóa vĩnh viễn!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Xóa ngay',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) document.getElementById('formMultiDelete').submit();
    })
}

// Xóa lẻ (PC) - Mobile dùng hàm riêng trong mobile-app.js
function confirmDelete(e, url) {
    e.preventDefault();
    Swal.fire({
        title: 'Xóa Acc này?',
        text: "Hành động này không thể hoàn tác!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Xóa',
        cancelButtonText: 'Hủy'
    }).then((res) => { if (res.isConfirmed) window.location.href = url; });
}

// --- 4. TIỆN ÍCH ---
function checkUrlMessage() {
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');
    if (msg) {
        let text = '';
        if (msg === 'deleted_multi') text = `Đã xóa ${urlParams.get('count')} Acc`;
        else if (msg === 'added') text = 'Đã đăng acc mới thành công';
        else if (msg === 'updated') text = 'Cập nhật acc thành công';
        else if (msg === 'deleted') text = 'Đã xóa acc thành công';

        // Gọi hàm showToast từ admin-utils.js
        if (text && typeof showToast === 'function') showToast('success', text);
        
        // Xóa param msg trên URL để F5 ko hiện lại
        const newUrl = window.location.pathname + window.location.search.replace(/[\?&]msg=[^&]+/, '').replace(/[\?&]count=[^&]+/, '');
        window.history.replaceState({}, document.title, newUrl);
    }
}