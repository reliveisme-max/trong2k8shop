/**
 * admin/assets/js/pages/product-list.js
 * UPDATE V2: ADD BULK EDIT LOGIC
 */

let lastChecked = null;
let bulkModalInstance = null; // Biến lưu Modal

document.addEventListener('DOMContentLoaded', function() {
    checkUrlMessage();
    initCheckboxLogic();
});

// --- LOGIC 1: CHECKBOX THÔNG MINH (SHIFT CLICK) ---
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
    const count = document.querySelectorAll('.item-check:checked').length;
    const btnDelete = document.getElementById('btnDeleteMulti');
    const btnEditMulti = document.getElementById('btnEditMulti');
    const countSpan = document.getElementById('countSelect');
    
    if(countSpan) countSpan.innerText = count;
    
    const displayStyle = count > 0 ? 'inline-block' : 'none';
    if(btnDelete) btnDelete.style.display = displayStyle;
    if(btnEditMulti) btnEditMulti.style.display = displayStyle;
}

// --- LOGIC 2: SỬA HÀNG LOẠT (BULK EDIT) ---

// Mở Modal
function openBulkEdit() {
    const count = document.querySelectorAll('.item-check:checked').length;
    if (count === 0) return;

    document.getElementById('lblBulkCount').innerText = count;
    
    const el = document.getElementById('bulkEditModal');
    bulkModalInstance = new bootstrap.Modal(el);
    bulkModalInstance.show();
}

// Chuyển đổi ô nhập khi chọn loại hành động
function toggleBulkInput() {
    const action = document.getElementById('bulkAction').value;
    
    // Ẩn hết
    document.querySelectorAll('.bulk-input-box').forEach(el => el.classList.add('d-none'));
    
    // Hiện cái cần thiết
    if (action === 'status') document.getElementById('boxStatus').classList.remove('d-none');
    else if (action === 'category') document.getElementById('boxCategory').classList.remove('d-none');
    else if (action === 'price') document.getElementById('boxPrice').classList.remove('d-none');
}

// Gửi dữ liệu đi (Submit)
function submitBulkEdit() {
    const ids = Array.from(document.querySelectorAll('.item-check:checked')).map(cb => cb.value);
    const action = document.getElementById('bulkAction').value;
    let value = '';

    if (action === 'status') value = document.getElementById('valStatus').value;
    else if (action === 'category') value = document.getElementById('valCategory').value;
    else if (action === 'price') value = document.getElementById('valPrice').value;

    // Hiệu ứng Loading nút bấm
    const btn = document.querySelector('#bulkEditModal .btn-primary');
    const originalText = btn.innerHTML;
    btn.innerHTML = 'Đang lưu...';
    btn.disabled = true;

    fetch('api/bulk_edit.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ ids: ids, action: action, value: value })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            if(bulkModalInstance) bulkModalInstance.hide();
            Swal.fire({
                icon: 'success',
                title: 'Thành công!',
                text: 'Đã cập nhật dữ liệu hàng loạt.',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                location.reload(); // Tải lại trang để thấy thay đổi
            });
        } else {
            Swal.fire('Lỗi', data.msg || 'Có lỗi xảy ra', 'error');
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Lỗi', 'Lỗi kết nối Server', 'error');
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

// --- LOGIC 3: LỌC & TÌM KIẾM ---
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
    if(loading) loading.classList.remove('d-none');

    const url = `index.php?page=${page}&q=${encodeURIComponent(keyword)}&cat=${catId}&ajax=1`;

    fetch(url)
        .then(response => response.text())
        .then(data => {
            const parts = data.split('<!--DIVIDER-->');
            if (parts.length >= 2) {
                document.getElementById('tableBody').innerHTML = parts[0];
                document.getElementById('paginationContainer').innerHTML = parts[1];
            }
            if(loading) loading.classList.add('d-none');
            
            const newUrl = `index.php?page=${page}&q=${encodeURIComponent(keyword)}&cat=${catId}`;
            window.history.pushState({path: newUrl}, '', newUrl);

            // Re-init events
            initCheckboxLogic();
            updateActionButtons();
        });
}

// --- LOGIC 4: XÓA ---
function submitDelete() {
    Swal.fire({
        title: 'Xác nhận xóa?',
        text: "Hành động này không thể hoàn tác!",
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
        title: 'Xóa Acc này?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Xóa'
    }).then((res) => { if (res.isConfirmed) window.location.href = url; });
}

// --- LOGIC 5: UTILS ---
function checkUrlMessage() {
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');
    if (msg) {
        let text = '';
        if (msg === 'deleted_multi') text = `Đã xóa ${urlParams.get('count')} Acc`;
        else if (msg === 'added') text = 'Đã đăng acc mới thành công';
        else if (msg === 'updated') text = 'Cập nhật acc thành công';
        else if (msg === 'deleted') text = 'Đã xóa acc thành công';

        if (text && typeof showToast === 'function') showToast('success', text);
        
        const newUrl = window.location.pathname + window.location.search.replace(/[\?&]msg=[^&]+/, '').replace(/[\?&]count=[^&]+/, '');
        window.history.replaceState({}, document.title, newUrl);
    }
}