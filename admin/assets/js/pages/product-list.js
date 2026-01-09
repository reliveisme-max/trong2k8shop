// admin/assets/js/pages/product-list.js - CLEAN VERSION

document.addEventListener('DOMContentLoaded', function() {
    // 1. Xử lý thông báo từ URL (SweetAlert)
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');
    
    if (msg) {
        let text = '';
        if (msg === 'deleted_multi') text = `Đã xóa ${urlParams.get('count')} Acc`;
        else if (msg === 'added') text = 'Đã đăng acc mới';
        else if (msg === 'updated') text = 'Đã cập nhật acc';
        else if (msg === 'deleted') text = 'Đã xóa acc thành công';

        if (text) {
            Swal.fire('Thành công', text, 'success');
            const newUrl = window.location.pathname + window.location.search.replace(/[\?&]msg=[^&]+/, '').replace(/[\?&]count=[^&]+/, '');
            window.history.replaceState({}, document.title, newUrl);
        }
    }
});

// --- CÁC HÀM XỬ LÝ CHÍNH ---

function loadPage(page) {
    const keyword = document.getElementById('searchInput').value;
    fetchData(page, keyword);
}

function applyFilter() {
    const keyword = document.getElementById('searchInput').value;
    fetchData(1, keyword);
}

function fetchData(page, keyword) {
    const loading = document.getElementById('ajaxLoading');
    if(loading) loading.classList.remove('d-none');

    const url = `index.php?page=${page}&q=${encodeURIComponent(keyword)}&ajax=1`;

    fetch(url)
        .then(response => response.text())
        .then(data => {
            const parts = data.split('<!--DIVIDER-->');
            if (parts.length >= 2) {
                document.getElementById('tableBody').innerHTML = parts[0];
                document.getElementById('paginationContainer').innerHTML = parts[1];
            }
            if(loading) loading.classList.add('d-none');
            
            const newUrl = `index.php?page=${page}&q=${encodeURIComponent(keyword)}`;
            window.history.pushState({path: newUrl}, '', newUrl);

            updateDeleteBtn();
        })
        .catch(err => {
            console.error(err);
            if(loading) loading.classList.add('d-none');
        });
}

// --- CHECKBOX & DELETE ---
function toggleAll(source) {
    document.querySelectorAll('.item-check').forEach(c => c.checked = source.checked);
    updateDeleteBtn();
}

function updateDeleteBtn() {
    const count = document.querySelectorAll('.item-check:checked').length;
    const btn = document.getElementById('btnDeleteMulti');
    const countSpan = document.getElementById('countSelect');
    if(countSpan) countSpan.innerText = count;
    if(btn) btn.style.display = count > 0 ? 'inline-block' : 'none';
}

function submitDelete() {
    Swal.fire({
        title: 'Xác nhận xóa?',
        text: "Các Acc đã chọn sẽ bị xóa vĩnh viễn!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Xóa ngay',
        cancelButtonText: 'Hủy'
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