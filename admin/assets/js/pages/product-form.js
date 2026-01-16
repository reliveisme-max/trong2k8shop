/**
 * admin/assets/js/pages/product-form.js
 * LOGIC XỬ LÝ FORM THÊM/SỬA SẢN PHẨM
 * Phụ thuộc: admin-utils.js (compressImage, uuidv4)
 */

let fileStore = {}; // Lưu trữ file gốc để xử lý
let sortable; 

document.addEventListener('DOMContentLoaded', function () {
    // 1. Khởi tạo Kéo thả ảnh (SortableJS)
    const grid = document.getElementById('imageGrid');
    if (grid && typeof Sortable !== 'undefined') {
        sortable = new Sortable(grid, { 
            animation: 150, 
            ghostClass: 'sortable-ghost'
        });
    }

    // 2. Xử lý khi chọn file từ máy tính
    const fileInput = document.getElementById('fileInput');
    if (fileInput) {
        fileInput.addEventListener('change', function (e) {
            handleLocalFiles(e.target.files);
            fileInput.value = ''; // Reset input để chọn lại file trùng tên vẫn được
        });
    }
    // [LOGIC MỚI] Tự động set trạng thái theo danh mục
    const catSelect = document.querySelector('select[name="category_id"]');
    const hiddenStatus = document.getElementById('autoStatus');

    function checkAutoStatus() {
        if (!catSelect || !hiddenStatus) return;
        
        const selectedText = catSelect.options[catSelect.selectedIndex].text.toLowerCase();
        
        // Nếu tên danh mục có chữ "đã bán" -> Bỏ tick (Status = 0)
        // Ngược lại -> Tự động tick (Status = 1)
        if (selectedText.includes('đã bán')) {
            hiddenStatus.checked = false;
        } else {
            hiddenStatus.checked = true;
        }
    }

    if (catSelect) {
        // Chạy ngay khi đổi danh mục
        catSelect.addEventListener('change', checkAutoStatus);
        
        // Chạy 1 lần khi vừa vào trang (để check đúng trạng thái hiện tại)
        checkAutoStatus(); 
    }
});

// HÀM: Load ảnh cũ từ Server (Được gọi từ file PHP)
function initExistingImages(images) {
    if (!Array.isArray(images) || images.length === 0) return;
    images.forEach(filename => {
        // Tạo ID giả để quản lý
        const uid = 'old_' + Math.random().toString(36).substr(2, 9);
        addToGrid(uid, `../uploads/${filename}`, 'lib', filename);
    });
}

// HÀM: Xử lý file mới chọn từ máy
async function handleLocalFiles(files) {
    const fileArray = Array.from(files);
    for (const file of fileArray) {
        if (!file.type.startsWith('image/')) continue;
        await new Promise((resolve) => {
            const uid = uuidv4(); // Hàm từ admin-utils.js
            fileStore[uid] = file;
            const blobUrl = URL.createObjectURL(file);
            addToGrid(uid, blobUrl, 'local', file.name);
            resolve();
        });
    }
}

// HÀM: Vẽ ô ảnh ra màn hình
function addToGrid(uid, src, type, filename = '') {
    const div = document.createElement('div');
    div.className = 'sortable-item';
    div.dataset.id = uid;    
    div.dataset.type = type;
    if (filename) div.dataset.filename = filename;
    div.innerHTML = `<img src="${src}"><div class="btn-remove-img" onclick="removeImage(this, '${uid}')"><i class="ph-bold ph-x"></i></div>`;
    document.getElementById('imageGrid').appendChild(div);
}

// HÀM: Xóa ảnh
function removeImage(btn, uid) {
    btn.closest('.sortable-item').remove();
    if (fileStore[uid]) delete fileStore[uid];
}

// HÀM: Submit Form (Xử lý upload & gửi dữ liệu)
async function submitForm() {
    const gridItems = document.querySelectorAll('.sortable-item');
    if (gridItems.length === 0) { 
        Swal.fire('Thiếu ảnh', 'Vui lòng chọn ít nhất 1 ảnh!', 'warning'); return; 
    }
    
    // Đếm ảnh mới cần upload
    let localCount = 0;
    gridItems.forEach(item => { if(item.dataset.type === 'local') localCount++; });

    // Hiện loading
    Swal.fire({
        title: 'Đang xử lý...',
        html: `Đang tối ưu và upload <b>${localCount}</b> ảnh mới...`,
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    try {
        const finalGalleryList = [];
        const localUploadTasks = [];

        // Phân loại ảnh (Cũ vs Mới)
        gridItems.forEach(item => {
            const type = item.dataset.type;
            const uid = item.dataset.id;
            
            if (type === 'local') {
                const mapItem = { type: 'local', uid: uid, filename: '' };
                finalGalleryList.push(mapItem); 
                localUploadTasks.push({ uid: uid, mapItemRef: mapItem }); 
            } else {
                finalGalleryList.push({ type: 'lib', filename: item.dataset.filename });
            }
        });

        // Thực hiện Upload Chunk (nếu có ảnh mới)
        if (localUploadTasks.length > 0) {
            const chunkFormData = new FormData();
            chunkFormData.append('ajax_upload_mode', '1');

            // Nén ảnh (Sử dụng hàm compressImage từ admin-utils.js)
            const compressionPromises = localUploadTasks.map(async (task) => {
                const file = fileStore[task.uid];
                if (file) {
                    const compressed = await compressImage(file, 1200, 0.7);
                    chunkFormData.append('chunk_files[]', compressed, compressed.name);
                    chunkFormData.append('chunk_uids[]', task.uid); 
                }
            });

            await Promise.all(compressionPromises);

            // Gửi lên Server
            const response = await fetch('api/upload.php', { method: 'POST', body: chunkFormData });
            const data = await response.json();

            if (data.status === 'success') {
                const resultKeyMap = data.data;
                localUploadTasks.forEach(task => {
                    const svFilename = resultKeyMap[task.uid];
                    if (svFilename) task.mapItemRef.filename = svFilename;
                });
            } else {
                throw new Error('Upload ảnh thất bại: ' + (data.msg || 'Lỗi không xác định'));
            }
        }

        // Gom tên ảnh cuối cùng
        const simpleGallery = finalGalleryList.map(item => item.filename).filter(name => name !== '');
        
        // Tạo FormData chính
        const mainForm = document.getElementById('addForm');
        const mainFormData = new FormData(mainForm);
        mainFormData.delete('gallery[]'); // Xóa dữ liệu file thừa
        mainFormData.set('final_gallery_list', JSON.stringify(simpleGallery));

        // Gửi Form về process.php
        const finalRes = await fetch('process.php', { method: 'POST', body: mainFormData });
        
        if (finalRes.redirected) {
            window.location.href = finalRes.url;
        } else {
            const resText = await finalRes.text();
            // Xử lý các kiểu phản hồi của process.php
            if(resText.includes('success') || resText.includes('header') || resText.includes('window.location')) {
                 window.location.href = 'index.php?msg=updated';
            } else {
                 // Nếu ko redirect, có thể do lỗi PHP, hiện thông báo
                 console.log(resText);
                 window.location.href = 'index.php?msg=updated'; // Mặc định về trang chủ nếu ko lỗi nghiêm trọng
            }
        }

    } catch (error) {
        console.error(error);
        Swal.fire('Lỗi', error.message, 'error');
    }
}