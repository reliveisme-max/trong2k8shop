// admin/assets/js/pages/bulk-upload.js - ULTRA FAST VERSION (BATCH RENDER)

let globalIndex = 0;   
let rowData = {};      
let currentRowId = 0;  
let nextIdCounter = 1; 
let isProcessing = false;

document.addEventListener('DOMContentLoaded', () => {
    // 1. Cấu hình
    if (typeof BULK_CONFIG !== 'undefined' && BULK_CONFIG.startId) {
        nextIdCounter = parseInt(BULK_CONFIG.startId);
    }
    // 2. Thêm 20 dòng
    addRows(20);

    // 3. Sự kiện
    const btnApply = document.getElementById('btnApplyGlobal');
    if(btnApply) btnApply.addEventListener('click', applyGlobal);

    const btnAdd = document.getElementById('btnAddRows');
    if(btnAdd) btnAdd.addEventListener('click', () => addRows(5));
    
    const btnSubmit = document.getElementById('btnSubmitBulk');
    if(btnSubmit) {
        btnSubmit.type = "button"; 
        btnSubmit.addEventListener('click', submitBulk);
    }

    // 4. Input File
    const fileInput = document.getElementById('modalFileInput');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            if (currentRowId > 0 && e.target.files.length > 0) {
                addFilesToRow(currentRowId, e.target.files);
                this.value = ''; 
            }
        });
    }
});

// --- TẠO ID DUY NHẤT ---
function generateUniqueId() {
    return 'img_' + Math.random().toString(36).substr(2, 9);
}

// --- THÊM DÒNG ---
function addRows(num) {
    const tbody = document.getElementById('tableBody');
    if (!tbody) return;

    let catOptions = '';
    if (typeof BULK_CONFIG !== 'undefined' && BULK_CONFIG.categories) {
        BULK_CONFIG.categories.forEach(cat => {
            catOptions += `<option value="${cat.id}">${cat.name}</option>`;
        });
    }

    const fragment = document.createDocumentFragment();
    for (let i = 0; i < num; i++) {
        globalIndex++; 
        let currentDisplayId = nextIdCounter++; 
        rowData[globalIndex] = []; 

        const tr = document.createElement('tr');
        tr.id = `row_${globalIndex}`;
        tr.innerHTML = `
            <td class="text-center fw-bold text-secondary align-middle fs-5">${currentDisplayId}</td>
            <td class="text-center">
                <div class="img-cell-box" onclick="openImageModal(${globalIndex})" id="imgCell_${globalIndex}">
                    <i class="ph-bold ph-plus text-secondary fs-4"></i>
                </div>
            </td>
            <td><input type="text" class="form-control fw-bold text-danger" name="prices[${globalIndex}]" onblur="formatBulkPrice(this)" placeholder="500k..."></td>
            <td><input type="text" class="form-control text-primary fw-bold" name="titles[${globalIndex}]" placeholder="Tiêu đề..."></td>
            <td><select class="form-select custom-input text-dark fw-bold" name="cats[${globalIndex}]">${catOptions}</select></td>
            <td><input type="text" class="form-control text-secondary" name="notes[${globalIndex}]" placeholder="Ghi chú..."></td>
            <td class="text-center align-middle">
                <i class="ph-bold ph-x text-danger fs-5 cursor-pointer" onclick="removeRow(${globalIndex})"></i>
            </td>
        `;
        fragment.appendChild(tr);
    }
    tbody.appendChild(fragment);
}

// --- [QUAN TRỌNG] MỞ MODAL SIÊU TỐC ---
window.openImageModal = function(id) {
    currentRowId = id;
    const container = document.getElementById('modalImgGrid');
    if(!container) return; 

    // 1. Xóa sạch container cũ ngay lập tức
    container.innerHTML = ''; 
    const files = rowData[currentRowId];

    if (files.length === 0) {
        container.innerHTML = '<div id="emptyMsg" class="text-center text-muted p-4 w-100" style="grid-column: span 4;">Chưa có ảnh</div>'; 
    } else {
        // 2. CHỈ VẼ TRƯỚC 12 ẢNH ĐỂ MODAL HIỆN NGAY LẬP TỨC (KHÔNG BỊ KHỰC)
        const INITIAL_BATCH = 12;
        const firstBatch = files.slice(0, INITIAL_BATCH);
        
        // Vẽ 12 ảnh đầu dùng DocumentFragment cho nhanh
        const fragment = document.createDocumentFragment();
        firstBatch.forEach(file => {
            fragment.appendChild(createImageElement(file));
        });
        container.appendChild(fragment);

        // 3. NẾU CÒN ẢNH, VẼ TIẾP SAU KHI MODAL ĐÃ HIỆN (ASYNC)
        if (files.length > INITIAL_BATCH) {
            setTimeout(() => {
                renderRemainingImages(files, INITIAL_BATCH, container);
            }, 150); // Delay 150ms để Modal mở xong hiệu ứng
        }
    }

    const modalEl = document.getElementById('imageModal');
    if(modalEl) new bootstrap.Modal(modalEl).show();
}

// --- HÀM VẼ CÁC ẢNH CÒN LẠI (CHIA NHỎ ĐỂ KHÔNG ĐƠ) ---
function renderRemainingImages(allFiles, startIndex, container) {
    const BATCH_SIZE = 15; // Mỗi lần vẽ thêm 15 ảnh
    const endIndex = Math.min(startIndex + BATCH_SIZE, allFiles.length);
    
    // Dùng requestAnimationFrame để trình duyệt vẽ mượt mà
    requestAnimationFrame(() => {
        const fragment = document.createDocumentFragment();
        for (let i = startIndex; i < endIndex; i++) {
            fragment.appendChild(createImageElement(allFiles[i]));
        }
        container.appendChild(fragment);

        // Nếu vẫn còn, gọi đệ quy tiếp
        if (endIndex < allFiles.length) {
            renderRemainingImages(allFiles, endIndex, container);
        }
    });
}

// --- HÀM TẠO HTML CHO 1 ẢNH ---
function createImageElement(file) {
    if (!file.uid) file.uid = generateUniqueId();
    const url = URL.createObjectURL(file);
    const div = document.createElement('div');
    div.className = 'modal-item';
    div.id = file.uid;
    div.innerHTML = `<img src="${url}"><div class="btn-del-img" onclick="removeImage('${file.uid}')">×</div>`;
    return div;
}

// --- THÊM FILE ---
function addFilesToRow(rowId, fileList) {
    const newFiles = Array.from(fileList);
    newFiles.forEach(f => f.uid = generateUniqueId());
    rowData[rowId] = [...rowData[rowId], ...newFiles];

    // Nếu đang mở modal thì thêm ngay vào DOM
    if (currentRowId === rowId) {
        const container = document.getElementById('modalImgGrid');
        const emptyMsg = document.getElementById('emptyMsg');
        if (emptyMsg) emptyMsg.remove(); 

        // Vẽ luôn các ảnh mới thêm (Thường thêm ít nên ko cần batch)
        const fragment = document.createDocumentFragment();
        newFiles.forEach(file => fragment.appendChild(createImageElement(file)));
        container.appendChild(fragment);
    }
    updateCellPreview(rowId);
}

// --- XÓA ẢNH ---
window.removeImage = function(uid) {
    const el = document.getElementById(uid);
    if (el) el.remove();

    const fileIndex = rowData[currentRowId].findIndex(f => f.uid === uid);
    if (fileIndex > -1) rowData[currentRowId].splice(fileIndex, 1);

    if (rowData[currentRowId].length === 0) {
        const container = document.getElementById('modalImgGrid');
        if (container) container.innerHTML = '<div id="emptyMsg" class="text-center text-muted p-4 w-100" style="grid-column: span 4;">Chưa có ảnh</div>';
    }
    updateCellPreview(currentRowId);
}

// --- CẬP NHẬT PREVIEW ---
function updateCellPreview(rowId) {
    const cell = document.getElementById(`imgCell_${rowId}`);
    if(!cell) return;
    const files = rowData[rowId];
    if (files.length > 0) {
        const coverUrl = URL.createObjectURL(files[0]);
        const countText = files.length > 1 ? `<div class="img-cell-count">+${files.length - 1}</div>` : '';
        cell.innerHTML = `<img src="${coverUrl}">${countText}`;
        cell.style.borderStyle = 'solid';
        cell.style.borderColor = '#1877F2';
        cell.style.background = '#000';
    } else {
        cell.innerHTML = `<i class="ph-bold ph-plus text-secondary fs-4"></i>`;
        cell.style.borderStyle = 'dashed';
        cell.style.borderColor = '#d1d5db';
        cell.style.background = '#f9fafb';
    }
}

// --- UTILS ---
window.formatBulkPrice = function(input) {
    let val = input.value.trim().toLowerCase();
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
            if (decimalPart && decimalPart.length > 0) {
                if (decimalPart.length === 1) money += parseInt(decimalPart) * 100000;
                else if (decimalPart.length === 2) money += parseInt(decimalPart) * 10000;
                else if (decimalPart.length === 3) money += parseInt(decimalPart) * 1000;
            }
        }
        if (money > 0) input.value = money.toLocaleString('vi-VN').replace(/,/g, '.');
    } else {
        let value = input.value.replace(/\D/g, '');
        if (value !== '') input.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
}

function removeRow(id) {
    const row = document.getElementById(`row_${id}`);
    if (row) { row.remove(); delete rowData[id]; }
}

function applyGlobal() {
    const noteEl = document.getElementById('globalNote');
    const catEl = document.getElementById('globalCategory');
    if (noteEl && noteEl.value) document.querySelectorAll('input[name^="notes"]').forEach(el => el.value = noteEl.value);
    if (catEl && catEl.value) document.querySelectorAll('select[name^="cats"]').forEach(el => el.value = catEl.value);
    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Đã áp dụng!', showConfirmButton: false, timer: 1000 });
}

async function submitBulk() {
    if (isProcessing) return;

    let validRows = [];
    for (const [rowId, files] of Object.entries(rowData)) {
        const rowEl = document.getElementById(`row_${rowId}`);
        if (!rowEl) continue;
        const priceInput = rowEl.querySelector(`input[name="prices[${rowId}]"]`);
        const price = priceInput ? priceInput.value.trim() : '';
        if (price && files.length > 0) validRows.push(rowId);
    }

    if (validRows.length === 0) { 
        Swal.fire('Thiếu thông tin', 'Cần nhập ít nhất <b>Giá bán</b> và chọn <b>Ảnh</b>!', 'warning'); 
        return; 
    }

    isProcessing = true;
    const btn = document.getElementById('btnSubmitBulk');
    const originalText = btn.innerHTML;
    btn.disabled = true; 
    btn.innerHTML = '<i class="ph-bold ph-spinner ph-spin"></i> ĐANG XỬ LÝ...';

    let successCount = 0;
    Swal.fire({ 
        title: 'Đang xử lý...', 
        html: `Đang đăng <b>${validRows.length}</b> Acc...<br>Vui lòng không tắt trình duyệt!`, 
        allowOutsideClick: false, 
        didOpen: () => { Swal.showLoading(); } 
    });

    try {
        for (let i = 0; i < validRows.length; i++) {
            const rowId = validRows[i];
            const files = rowData[rowId];
            let uploadedNames = [];
            
            const CHUNK_SIZE = 3; 
            for (let j = 0; j < files.length; j += CHUNK_SIZE) {
                const chunk = files.slice(j, j + CHUNK_SIZE);
                let fdImg = new FormData();
                fdImg.append('ajax_upload_mode', '1');
                chunk.forEach((f, idx) => { fdImg.append('chunk_files[]', f); fdImg.append('chunk_uids[]', j + idx); });
                const resImg = await fetch('api/upload.php', { method: 'POST', body: fdImg });
                const dataImg = await resImg.json();
                if (dataImg.status === 'success') Object.values(dataImg.data).forEach(n => uploadedNames.push(n));
            }

            const rowEl = document.getElementById(`row_${rowId}`);
            let fdPost = new FormData();
            fdPost.append('indexes[]', rowId);
            const titleInput = rowEl.querySelector(`input[name="titles[${rowId}]"]`);
            const catInput = rowEl.querySelector(`select[name="cats[${rowId}]"]`);
            const priceInput = rowEl.querySelector(`input[name="prices[${rowId}]"]`);
            const noteInput = rowEl.querySelector(`input[name="notes[${rowId}]"]`);
            
            fdPost.append(`title_${rowId}`, titleInput ? titleInput.value.trim() : '');
            fdPost.append(`cat_${rowId}`, catInput ? catInput.value : 0);
            fdPost.append(`price_${rowId}`, priceInput ? priceInput.value.trim() : '');
            fdPost.append(`note_${rowId}`, noteInput ? noteInput.value.trim() : '');
            uploadedNames.forEach(name => fdPost.append(`uploaded_images_${rowId}[]`, name));

            const resPost = await fetch('api/process_bulk.php', { method: 'POST', body: fdPost });
            const dataPost = await resPost.json();
            if (dataPost.status === 'success') { successCount++; removeRow(rowId); }
        }
        Swal.fire({ icon: 'success', title: 'Hoàn tất!', text: `Đã đăng thành công ${successCount} acc!` }).then(() => {
            if(document.querySelectorAll('#tableBody tr').length === 0) window.location.reload();
            else { isProcessing = false; btn.disabled = false; btn.innerHTML = originalText; }
        });
    } catch (err) {
        console.error(err);
        Swal.fire('Lỗi', 'Có lỗi xảy ra: ' + err.message, 'error');
        isProcessing = false; btn.disabled = false; btn.innerHTML = originalText;
    }
}