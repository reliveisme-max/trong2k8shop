/**
 * admin/assets/js/pages/product-form.js
 * FINAL FIXED: CHIA NHỎ UPLOAD (BATCHING) ĐỂ TRÁNH LỖI LIMIT SERVER
 */

let fileStore = {}; 
let sortable; 

document.addEventListener('DOMContentLoaded', function () {
    // 1. Khởi tạo Kéo thả
    const grid = document.getElementById('imageGrid');
    if (grid && typeof Sortable !== 'undefined') {
        sortable = new Sortable(grid, { animation: 150, ghostClass: 'sortable-ghost' });
    }

    // 2. Input File
    const fileInput = document.getElementById('fileInput');
    if (fileInput) {
        fileInput.addEventListener('change', function (e) {
            handleLocalFiles(e.target.files);
            fileInput.value = ''; 
        });
    }

    // 3. Tự động trạng thái
    const catSelect = document.querySelector('select[name="category_id"]');
    const hiddenStatus = document.querySelector('input[name="status"]'); 

    function checkAutoStatus() {
        if (!catSelect || !hiddenStatus) return;
        const selectedText = catSelect.options[catSelect.selectedIndex].text.toLowerCase();
        if (selectedText.includes('đã bán')) hiddenStatus.checked = false;
        else hiddenStatus.checked = true;
    }

    if (catSelect) {
        catSelect.addEventListener('change', checkAutoStatus);
        const urlParams = new URLSearchParams(window.location.search);
        if (!urlParams.has('id')) checkAutoStatus(); 
    }
});

// --- CÁC HÀM XỬ LÝ ẢNH ---
function initExistingImages(images) {
    if (!Array.isArray(images) || images.length === 0) return;
    images.forEach(filename => {
        const uid = 'old_' + Math.random().toString(36).substr(2, 9);
        addToGrid(uid, `../uploads/${filename}`, 'lib', filename);
    });
}

async function handleLocalFiles(files) {
    const fileArray = Array.from(files);
    for (const file of fileArray) {
        if (!file.type.startsWith('image/')) continue;
        await new Promise((resolve) => {
            const uid = uuidv4(); 
            fileStore[uid] = file;
            const blobUrl = URL.createObjectURL(file);
            addToGrid(uid, blobUrl, 'local', file.name);
            resolve();
        });
    }
}

function addToGrid(uid, src, type, filename = '') {
    const div = document.createElement('div');
    div.className = 'sortable-item';
    div.dataset.id = uid;    
    div.dataset.type = type;
    if (filename) div.dataset.filename = filename;
    div.innerHTML = `<img src="${src}"><div class="btn-remove-img" onclick="removeImage(this, '${uid}')"><i class="ph-bold ph-x"></i></div>`;
    document.getElementById('imageGrid').appendChild(div);
}

function removeImage(btn, uid) {
    btn.closest('.sortable-item').remove();
    if (fileStore[uid]) delete fileStore[uid];
}

// --- HÀM SUBMIT FORM (QUAN TRỌNG: ĐÃ SỬA LOGIC CHIA NHỎ) ---
async function submitForm() {
    const gridItems = document.querySelectorAll('.sortable-item');
    if (gridItems.length === 0) { 
        Swal.fire('Thiếu ảnh', 'Vui lòng chọn ít nhất 1 ảnh!', 'warning'); return; 
    }
    
    let localCount = 0;
    gridItems.forEach(item => { if(item.dataset.type === 'local') localCount++; });

    Swal.fire({
        title: 'Đang xử lý...',
        html: `Đang tối ưu và upload <b>${localCount}</b> ảnh mới...<br>Vui lòng đợi, không tắt trình duyệt.`,
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    try {
        const finalGalleryList = [];
        const localUploadTasks = [];

        // 1. Phân loại ảnh
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

        // 2. Nén toàn bộ ảnh trước (Client Side)
        const isCompress = document.getElementById('compressToggle')?.checked;
        const qualityVal = isCompress ? 0.8 : 0.95; 
        const widthVal   = isCompress ? 1200 : 2560;
        
        // Tạo danh sách các file đã nén sẵn sàng để upload
        const readyToUploadFiles = [];

        if (localUploadTasks.length > 0) {
            const compressionPromises = localUploadTasks.map(async (task) => {
                const file = fileStore[task.uid];
                if (file) {
                    const compressed = await compressImage(file, widthVal, qualityVal);
                    // Lưu lại file đã nén và task tương ứng
                    readyToUploadFiles.push({
                        file: compressed,
                        task: task
                    });
                }
            });
            await Promise.all(compressionPromises);
        }

        // 3. CHIA NHỎ VÀ GỬI LẦN LƯỢT (BATCH UPLOAD)
        // Mặc định PHP chỉ cho 20 file, ta gửi mỗi lần 10 file cho an toàn
        const BATCH_SIZE = 10; 
        
        for (let i = 0; i < readyToUploadFiles.length; i += BATCH_SIZE) {
            const batch = readyToUploadFiles.slice(i, i + BATCH_SIZE);
            
            const chunkFormData = new FormData();
            chunkFormData.append('ajax_upload_mode', '1');

            batch.forEach(item => {
                chunkFormData.append('chunk_files[]', item.file, item.file.name);
                chunkFormData.append('chunk_uids[]', item.task.uid);
            });

            // Gửi batch này lên server
            const response = await fetch('api/upload.php', { method: 'POST', body: chunkFormData });
            const resText = await response.text();
            
            let data;
            try {
                data = JSON.parse(resText);
            } catch (e) {
                console.error("Server Error Chunk:", resText);
                throw new Error("Lỗi Server khi upload: " + resText.substring(0, 200));
            }

            if (data.status === 'success') {
                const resultKeyMap = data.data;
                // Cập nhật tên file trả về vào mapItemRef
                batch.forEach(item => {
                    const svFilename = resultKeyMap[item.task.uid];
                    if (svFilename) item.task.mapItemRef.filename = svFilename;
                });
            } else {
                throw new Error('Upload thất bại: ' + (data.msg || 'Lỗi không xác định'));
            }
        }

        // 4. Gom tên ảnh cuối cùng và gửi Form thông tin
        const simpleGallery = finalGalleryList.map(item => item.filename).filter(name => name !== '');
        
        const mainForm = document.getElementById('addForm');
        const mainFormData = new FormData(mainForm);
        mainFormData.delete('gallery[]'); 
        mainFormData.set('final_gallery_list', JSON.stringify(simpleGallery));

        const finalRes = await fetch('process.php', { method: 'POST', body: mainFormData });
        
        if (finalRes.redirected) {
            window.location.href = finalRes.url;
        } else {
            const resText = await finalRes.text();
            if(resText.includes('success') || resText.includes('header') || resText.includes('window.location')) {
                 window.location.href = 'index.php?msg=updated';
            } else {
                 Swal.fire('Thông báo', 'Đã lưu nhưng có cảnh báo: <br>' + resText, 'info')
                 .then(() => window.location.href = 'index.php');
            }
        }

    } catch (error) {
        console.error(error);
        Swal.fire({
            icon: 'error',
            title: 'Có lỗi xảy ra!',
            html: error.message,
            width: 600
        });
    }
}