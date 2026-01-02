// admin/assets/js/admin-add.js

let currentPage = 1;
let isLoading = false;
let hasMore = true;
let currentMode = ''; // 'thumb' hoặc 'gallery'
let selectedFiles = []; 
let modalEl;
let scrollArea;
let gridEl;
let loadingEl;
let endDataEl;

// KHỞI TẠO KHI DOM LOAD XONG
document.addEventListener('DOMContentLoaded', function() {
    // Khởi tạo Modal Bootstrap
    var modalElement = document.getElementById('libraryModal');
    if (modalElement) {
        modalEl = new bootstrap.Modal(modalElement);
    }

    scrollArea = document.getElementById('scrollArea');
    gridEl = document.getElementById('libGrid');
    loadingEl = document.getElementById('loadingIndicator');
    endDataEl = document.getElementById('endOfData');

    // Lắng nghe sự kiện cuộn để tải thêm ảnh (Infinite Scroll)
    if (scrollArea) {
        scrollArea.addEventListener('scroll', () => {
            if (scrollArea.scrollTop + scrollArea.clientHeight >= scrollArea.scrollHeight - 50) {
                if (hasMore && !isLoading) {
                    currentPage++;
                    fetchImages(currentPage);
                }
            }
        });
    }
});

// 1. GỌI API LẤY ẢNH TỪ SERVER
async function fetchImages(page) {
    if (isLoading || !hasMore) return;
    
    isLoading = true;
    if(loadingEl) loadingEl.style.display = 'block';

    try {
        const response = await fetch(`get_images.php?page=${page}`);
        const data = await response.json();

        if (data.status === 'success') {
            hasMore = data.has_more;
            
            data.data.forEach(filename => {
                const div = document.createElement('div');
                div.className = 'lib-item';
                // Nếu ảnh này đã được chọn trước đó thì highlight lên
                if(selectedFiles.includes(filename)) {
                    div.classList.add('selected');
                }
                div.innerHTML = `<img src="../uploads/${filename}" loading="lazy">`;
                div.onclick = function() { toggleSelect(this, filename); };
                gridEl.appendChild(div);
            });

            if (!hasMore && endDataEl) {
                endDataEl.style.display = 'block';
            }
        }
    } catch (error) {
        console.error("Lỗi tải ảnh:", error);
    } finally {
        isLoading = false;
        if(loadingEl) loadingEl.style.display = 'none';
    }
}

// 2. MỞ MODAL THƯ VIỆN
function openLibrary(mode) {
    currentMode = mode;
    // Nếu chưa có ảnh nào được load thì load trang 1
    if (gridEl && gridEl.innerHTML.trim() === '') {
        fetchImages(1);
    }
    // Reset selection visual nếu chuyển chế độ (tuỳ chọn)
    if(modalEl) modalEl.show();
}

// 3. CHỌN ẢNH (CLICK VÀO ẢNH)
function toggleSelect(el, filename) {
    if (currentMode === 'thumb') {
        // Chế độ chọn 1 ảnh (Thumb): Bỏ chọn tất cả cái khác
        document.querySelectorAll('.lib-item').forEach(e => e.classList.remove('selected'));
        el.classList.add('selected');
        selectedFiles = [filename];
    } else {
        // Chế độ chọn nhiều (Gallery)
        if (el.classList.contains('selected')) {
            el.classList.remove('selected');
            selectedFiles = selectedFiles.filter(f => f !== filename);
        } else {
            el.classList.add('selected');
            selectedFiles.push(filename);
        }
    }
}

// 4. XÁC NHẬN CHỌN ẢNH TỪ THƯ VIỆN
function confirmSelection() {
    if (selectedFiles.length === 0) {
        modalEl.hide();
        return;
    }

    if (currentMode === 'thumb') {
        // Xử lý cho Ảnh Bìa
        const file = selectedFiles[0];
        document.getElementById('preview-thumb').innerHTML = `<img src="../uploads/${file}">`;
        document.getElementById('inputSelectedThumb').value = file;
        
        // Reset input file thường để tránh gửi đè
        document.getElementById('thumbInput').value = '';
    } else {
        // Xử lý cho Album
        const container = document.getElementById('preview-gallery');
        container.innerHTML = ''; // Xóa preview cũ
        
        // Gán JSON mảng ảnh vào input hidden
        document.getElementById('inputSelectedGallery').value = JSON.stringify(selectedFiles);
        document.getElementById('galleryInput').value = '';

        // Hiển thị ra màn hình
        selectedFiles.forEach(file => {
            const img = document.createElement('img');
            img.src = `../uploads/${file}`;
            container.appendChild(img);
        });
    }
    modalEl.hide();
}

// 5. PREVIEW KHI UPLOAD FILE TỪ MÁY TÍNH (THUMB)
function previewSingle(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-thumb').innerHTML = '<img src="' + e.target.result + '">';
            // Xóa giá trị chọn từ thư viện để ưu tiên file upload mới
            document.getElementById('inputSelectedThumb').value = '';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// 6. PREVIEW KHI UPLOAD FILE TỪ MÁY TÍNH (GALLERY)
function previewGallery(input) {
    var previewZone = document.getElementById('preview-gallery');
    previewZone.innerHTML = ''; // Clear cũ
    document.getElementById('inputSelectedGallery').value = ''; // Xóa chọn từ thư viện
    
    if (input.files) {
        Array.from(input.files).forEach(file => {
            var reader = new FileReader();
            reader.onload = function(e) {
                var img = document.createElement('img');
                img.src = e.target.result;
                previewZone.appendChild(img);
            }
            reader.readAsDataURL(file);
        });
    }
}

// 7. HÀM FORMAT TIỀN TỆ (Thêm dấu chấm khi nhập)
// Được gọi từ sự kiện oninput ở thẻ input giá tiền
function formatCurrency(input) {
    // Xóa tất cả ký tự không phải số
    let value = input.value.replace(/\D/g, '');
    
    // Nếu rỗng thì return
    if (value === '') {
        input.value = '';
        return;
    }
    
    // Dùng Regex để thêm dấu chấm sau mỗi 3 số
    input.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}