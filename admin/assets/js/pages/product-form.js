// admin/assets/js/pages/product-form.js - CLEAN VERSION

let fileStore = {}; 
let sortable; 

document.addEventListener('DOMContentLoaded', function () {
    // 1. Khởi tạo Kéo thả ảnh
    const grid = document.getElementById('imageGrid');
    if (grid && typeof Sortable !== 'undefined') {
        sortable = new Sortable(grid, { 
            animation: 150, 
            ghostClass: 'sortable-ghost'
        });
    }

    // 2. Chọn file từ máy
    const fileInput = document.getElementById('fileInput');
    if (fileInput) {
        fileInput.addEventListener('change', function (e) {
            handleLocalFiles(e.target.files);
            fileInput.value = '';
        });
    }

    // 3. Auto Parse Title (Chỉ cần làm sạch tiêu đề)
    const titleInput = document.querySelector('input[name="title"]');
    if (titleInput) {
        titleInput.addEventListener('change', function() { cleanTitle(this.value); });
        titleInput.addEventListener('paste', function() { setTimeout(() => cleanTitle(this.value), 50); });
    }

    // 4. Smart Price (Nhập 5m -> 5.000.000)
    const priceInput = document.querySelector('input[name="price"]');
    if (priceInput) {
        priceInput.addEventListener('blur', function() { parsePriceShortcut(this); });
        priceInput.addEventListener('change', function() { parsePriceShortcut(this); });
    }
});

// LOAD ẢNH CŨ (Cho trang Edit)
function initExistingImages(images) {
    if (!Array.isArray(images) || images.length === 0) return;
    images.forEach(filename => {
        const uid = 'old_' + Math.random().toString(36).substr(2, 9);
        addToGrid(uid, `../uploads/${filename}`, 'lib', filename);
    });
    checkGridHeight();
}

// XỬ LÝ FILE LOCAL
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
    checkGridHeight();
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
    const item = btn.closest('.sortable-item');
    item.remove();
    if (fileStore[uid]) delete fileStore[uid];
    checkGridHeight();
}

// LOGIC SUBMIT FORM
async function submitForm() {
    const gridItems = document.querySelectorAll('.sortable-item');
    if (gridItems.length === 0) { 
        Swal.fire('Thiếu ảnh', 'Vui lòng chọn ít nhất 1 ảnh!', 'warning'); return; 
    }
    
    // Đếm ảnh mới
    let localCount = 0;
    gridItems.forEach(item => { if(item.dataset.type === 'local') localCount++; });

    Swal.fire({
        title: 'Đang xử lý...',
        html: `Đang nén và upload <b>${localCount}</b> ảnh mới...`,
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    try {
        const finalGalleryList = [];
        const localUploadTasks = [];

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

        if (localUploadTasks.length > 0) {
            const CHUNK_SIZE = 5; 
            for (let i = 0; i < localUploadTasks.length; i += CHUNK_SIZE) {
                const chunk = localUploadTasks.slice(i, i + CHUNK_SIZE);
                const chunkFormData = new FormData();
                chunkFormData.append('ajax_upload_mode', '1');

                const compressionPromises = chunk.map(async (task) => {
                    const file = fileStore[task.uid];
                    if (file) {
                        const compressed = await compressImage(file, 1200, 0.7);
                        chunkFormData.append('chunk_files[]', compressed, compressed.name);
                        chunkFormData.append('chunk_uids[]', task.uid); 
                    }
                });

                await Promise.all(compressionPromises);

                const response = await fetch('api/upload.php', { method: 'POST', body: chunkFormData });
                const data = await response.json();

                if (data.status === 'success') {
                    const resultKeyMap = data.data;
                    chunk.forEach(task => {
                        const svFilename = resultKeyMap[task.uid];
                        if (svFilename) {
                            task.mapItemRef.filename = svFilename;
                        }
                    });
                } else {
                    throw new Error('Upload ảnh thất bại.');
                }
            }
        }

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
            if(resText.includes('<script>') || resText.includes('header')) {
                 document.write(resText);
            } else {
                 Swal.fire({ icon: 'error', title: 'Lỗi', html: resText });
            }
        }

    } catch (error) {
        console.error(error);
        Swal.fire('Lỗi', error.message, 'error');
    }
}

// UTILS
function compressImage(file, maxWidth, quality) {
    return new Promise((resolve) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (event) => {
            const img = new Image();
            img.src = event.target.result;
            img.onload = () => {
                let width = img.width;
                let height = img.height;
                if (width > maxWidth) {
                    height = Math.round((height * maxWidth) / width);
                    width = maxWidth;
                }
                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);
                canvas.toBlob((blob) => {
                    const newFileName = file.name.replace(/\.[^/.]+$/, "") + ".webp";
                    const newFile = new File([blob], newFileName, { type: 'image/webp', lastModified: Date.now() });
                    resolve(newFile);
                }, 'image/webp', quality);
            };
        };
    });
}

function parsePriceShortcut(input) {
    let val = input.value.toLowerCase().trim();
    if (!val) return;
    const regex = /^([0-9]+[.,]?[0-9]*)(k|m|tr)([0-9]*)$/;
    const match = val.match(regex);
    if (match) {
        let mainNum = parseFloat(match[1].replace(',', '.'));
        let unit = match[2];
        let decimalPart = match[3];
        let money = 0;
        if (unit === 'k') money = mainNum * 1000;
        else if (unit === 'm' || unit === 'tr') {
            money = mainNum * 1000000;
            if (decimalPart) {
                if (decimalPart.length === 1) money += parseInt(decimalPart) * 100000;
                else if (decimalPart.length === 2) money += parseInt(decimalPart) * 10000;
            }
        }
        if (money > 0) input.value = money.toLocaleString('vi-VN').replace(/,/g, '.');
    } else {
        let value = input.value.replace(/\D/g, '');
        if (value !== '') input.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
}

function formatCurrency(input) {
    if (/[kmtr]/i.test(input.value)) return;
    let value = input.value.replace(/\D/g, '');
    if (value === '') return;
    input.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function cleanTitle(text) {
    if (!text) return;
    let workingText = text.replace(/^(mã|ms|code|acc|id|account)\s*[:.-]?\s*/gi, '');
    workingText = workingText.replace(/^[:.-]+|[:.-]+$/g, '').trim();
    workingText = workingText.replace(/\s\s+/g, ' ');
    document.querySelector('input[name="title"]').value = workingText;
}

function uuidv4() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

function checkGridHeight() {
    // Chỉ là hàm hình thức, giữ lại để tránh lỗi nếu có gọi
}