// admin/assets/js/admin-add.js - FINAL VERSION (HỖ TRỢ EDIT MODE + NÚT XÓA)

let currentPage = 1;
let isLoading = false;
let hasMore = true;
let currentMode = ''; 
let selectedFiles = []; 
let modalEl;
let scrollArea;
let gridEl;
let loadingEl;
let endDataEl;

// QUẢN LÝ FILE UPLOAD & THƯ VIỆN
// Dùng DataTransfer để can thiệp vào input file
let dtThumb = new DataTransfer(); 
let dtGallery = new DataTransfer(); 
let libGalleryArr = []; // Mảng chứa tên file từ thư viện (bao gồm cả ảnh cũ)

document.addEventListener('DOMContentLoaded', function() {
    var modalElement = document.getElementById('libraryModal');
    if (modalElement) {
        modalEl = new bootstrap.Modal(modalElement);
    }
    scrollArea = document.getElementById('scrollArea');
    gridEl = document.getElementById('libGrid');
    loadingEl = document.getElementById('loadingIndicator');
    endDataEl = document.getElementById('endOfData');

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

// --- HÀM MỚI: KHỞI TẠO DỮ LIỆU KHI SỬA (EDIT MODE) ---
// Hàm này được gọi từ file edit.php
function initEditData(thumb, galleryJson) {
    // 1. Xử lý Thumb cũ
    if (thumb && thumb !== '') {
        const thumbContainer = document.getElementById('preview-thumb');
        thumbContainer.innerHTML = `
            <div class="preview-item">
                <img src="../uploads/${thumb}">
                <button type="button" class="btn-remove-img" onclick="clearThumbLib()">&times;</button>
            </div>`;
        // Gán giá trị vào input hidden để nếu không sửa gì thì vẫn giữ nguyên
        document.getElementById('inputSelectedThumb').value = thumb;
    }

    // 2. Xử lý Gallery cũ
    if (galleryJson) {
        try {
            // galleryJson truyền vào là string, parse ra mảng
            // Nếu galleryJson là object/array sẵn (do PHP json_encode) thì dùng luôn
            const arr = (typeof galleryJson === 'string') ? JSON.parse(galleryJson) : galleryJson;
            
            if (Array.isArray(arr)) {
                libGalleryArr = arr; // Gán ảnh cũ vào mảng quản lý
                renderGallery();     // Vẽ ra màn hình (tự động có nút X)
            }
        } catch (e) {
            console.error("Lỗi parse gallery:", e);
        }
    }
}

// 1. API LẤY ẢNH TỪ SERVER
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
                if(selectedFiles.includes(filename)) div.classList.add('selected');
                div.innerHTML = `<img src="../uploads/${filename}" loading="lazy">`;
                div.onclick = function() { toggleSelect(this, filename); };
                gridEl.appendChild(div);
            });
            if (!hasMore && endDataEl) endDataEl.style.display = 'block';
        }
    } catch (error) {
        console.error("Lỗi:", error);
    } finally {
        isLoading = false;
        if(loadingEl) loadingEl.style.display = 'none';
    }
}

// 2. MỞ MODAL THƯ VIỆN
function openLibrary(mode) {
    currentMode = mode;
    if (gridEl && gridEl.innerHTML.trim() === '') fetchImages(1);
    // Reset selection visual
    document.querySelectorAll('.lib-item').forEach(e => e.classList.remove('selected'));
    selectedFiles = [];
    if(modalEl) modalEl.show();
}

// 3. CHỌN ẢNH (CLICK VÀO ẢNH)
function toggleSelect(el, filename) {
    if (currentMode === 'thumb') {
        document.querySelectorAll('.lib-item').forEach(e => e.classList.remove('selected'));
        el.classList.add('selected');
        selectedFiles = [filename];
    } else {
        if (el.classList.contains('selected')) {
            el.classList.remove('selected');
            selectedFiles = selectedFiles.filter(f => f !== filename);
        } else {
            el.classList.add('selected');
            selectedFiles.push(filename);
        }
    }
}

// 4. XÁC NHẬN CHỌN TỪ THƯ VIỆN
function confirmSelection() {
    if (selectedFiles.length === 0) {
        modalEl.hide(); return;
    }

    if (currentMode === 'thumb') {
        const file = selectedFiles[0];
        document.getElementById('preview-thumb').innerHTML = `
            <div class="preview-item">
                <img src="../uploads/${file}">
                <button type="button" class="btn-remove-img" onclick="clearThumbLib()">&times;</button>
            </div>`;
        document.getElementById('inputSelectedThumb').value = file;
        document.getElementById('thumbInput').value = ''; // Clear file input
    } else {
        // Cộng dồn vào mảng thư viện hiện tại
        selectedFiles.forEach(f => {
            if(!libGalleryArr.includes(f)) libGalleryArr.push(f);
        });
        renderGallery();
    }
    modalEl.hide();
}

// 5. XỬ LÝ UPLOAD FILE TỪ MÁY (LOCAL)
function previewSingle(input) {
    if (input.files && input.files[0]) {
        // Update DataTransfer
        dtThumb = new DataTransfer();
        dtThumb.items.add(input.files[0]);
        input.files = dtThumb.files;

        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-thumb').innerHTML = `
                <div class="preview-item">
                    <img src="${e.target.result}">
                    <button type="button" class="btn-remove-img" onclick="clearThumbLocal()">&times;</button>
                </div>`;
            document.getElementById('inputSelectedThumb').value = ''; // Clear lib input
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function previewGallery(input) {
    if (input.files) {
        // Cộng dồn file vào DataTransfer
        Array.from(input.files).forEach(file => {
            dtGallery.items.add(file);
        });
        // Cập nhật lại input chính bằng danh sách mới
        input.files = dtGallery.files; 
        renderGallery();
    }
}

// 6. HÀM RENDER GALLERY (VẼ LẠI GIAO DIỆN)
function renderGallery() {
    const container = document.getElementById('preview-gallery');
    container.innerHTML = '';
    
    // Cập nhật input hidden thư viện (để gửi lên server)
    document.getElementById('inputSelectedGallery').value = JSON.stringify(libGalleryArr);

    // A. Vẽ ảnh từ Local Upload
    Array.from(dtGallery.files).forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const div = document.createElement('div');
            div.className = 'preview-item';
            div.innerHTML = `
                <img src="${e.target.result}">
                <button type="button" class="btn-remove-img" onclick="removeGalleryLocal(${index})">&times;</button>
            `;
            container.appendChild(div);
        }
        reader.readAsDataURL(file);
    });

    // B. Vẽ ảnh từ Thư viện (Bao gồm ảnh cũ)
    libGalleryArr.forEach((filename, index) => {
        const div = document.createElement('div');
        div.className = 'preview-item';
        div.innerHTML = `
            <img src="../uploads/${filename}">
            <button type="button" class="btn-remove-img" onclick="removeGalleryLib(${index})">&times;</button>
        `;
        container.appendChild(div);
    });
}

// 7. CÁC HÀM XÓA (REMOVE)
function clearThumbLocal() {
    document.getElementById('thumbInput').value = '';
    document.getElementById('preview-thumb').innerHTML = '';
    dtThumb = new DataTransfer();
}

function clearThumbLib() {
    document.getElementById('inputSelectedThumb').value = '';
    document.getElementById('preview-thumb').innerHTML = '';
}

function removeGalleryLocal(index) {
    // Xóa file khỏi DataTransfer
    dtGallery.items.remove(index);
    // Cập nhật lại input file
    document.getElementById('galleryInput').files = dtGallery.files;
    // Vẽ lại
    renderGallery();
}

function removeGalleryLib(index) {
    // Xóa khỏi mảng (chỉ xóa khỏi danh sách bài viết, không xóa file gốc)
    libGalleryArr.splice(index, 1);
    // Vẽ lại
    renderGallery();
}

// 8. FORMAT TIỀN TỆ
function formatCurrency(input) {
    let value = input.value.replace(/\D/g, '');
    if (value === '') { input.value = ''; return; }
    input.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}