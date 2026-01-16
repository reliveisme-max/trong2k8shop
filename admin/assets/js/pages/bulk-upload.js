// admin/assets/js/pages/bulk-upload.js
// V7: RESTORE CATEGORY COLUMN + DEFAULT 20 ROWS

let globalIndex = 0;   
let rowData = {};      
let currentRowId = 0;  
let isProcessing = false;
let nextIdCounter = 1; 

document.addEventListener('DOMContentLoaded', () => {
    // 1. Nhận cấu hình từ PHP
    if (typeof BULK_CONFIG !== 'undefined') {
        if (BULK_CONFIG.startId) nextIdCounter = parseInt(BULK_CONFIG.startId);
    }

    // 2. Thêm sẵn 20 dòng (Theo yêu cầu)
    addRows(20);

    // 3. Sự kiện nút bấm
    const btnApply = document.getElementById('btnApplyGlobal');
    if(btnApply) btnApply.addEventListener('click', applyGlobal);

    const btnAdd = document.getElementById('btnAddRows');
    if(btnAdd) btnAdd.addEventListener('click', () => addRows(5));
    
    const btnSubmit = document.getElementById('btnSubmitBulk');
    if(btnSubmit) {
        btnSubmit.type = "button"; 
        btnSubmit.addEventListener('click', submitBulk);
    }

    // 4. Input file modal
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

// --- HÀM THÊM DÒNG MỚI ---
function addRows(num) {
    const tbody = document.getElementById('tableBody');
    if (!tbody) return;

    // Tạo HTML cho ô chọn danh mục (Select)
    let catOptions = '';
    if (typeof BULK_CONFIG !== 'undefined' && BULK_CONFIG.categories) {
        BULK_CONFIG.categories.forEach(cat => {
            // Tự động chọn cái đầu tiên (browser mặc định chọn option đầu)
            catOptions += `<option value="${cat.id}">${cat.name}</option>`;
        });
    }

    for (let i = 0; i < num; i++) {
        globalIndex++; 
        let currentDisplayId = nextIdCounter++; 
        rowData[globalIndex] = [];

        const tr = document.createElement('tr');
        tr.id = `row_${globalIndex}`;
        
        tr.innerHTML = `
            <!-- CỘT ID -->
            <td class="text-center fw-bold text-secondary align-middle fs-5">
                ${currentDisplayId}
            </td>
            
            <!-- CỘT ẢNH -->
            <td class="text-center">
                <div class="img-cell-box mx-auto" onclick="openImageModal(${globalIndex})" id="imgCell_${globalIndex}">
                    <i class="ph-bold ph-plus text-secondary fs-4"></i>
                </div>
            </td>
            
            <!-- GIÁ TIỀN -->
            <td>
                <input type="text" class="form-control fw-bold text-danger" 
                       name="prices[${globalIndex}]" 
                       onblur="formatBulkPrice(this)"
                       placeholder="500k...">
            </td>
            
            <!-- TÊN ACC -->
            <td>
                <input type="text" class="form-control text-primary fw-bold" 
                       name="titles[${globalIndex}]" 
                       placeholder="Tiêu đề...">
            </td>
            
            <!-- [KHÔI PHỤC] CỘT DANH MỤC -->
            <td>
                <select class="form-select custom-input text-dark fw-bold" name="cats[${globalIndex}]">
                    ${catOptions}
                </select>
            </td>
            
            <!-- GHI CHÚ -->
            <td>
                <input type="text" class="form-control text-secondary" 
                       name="notes[${globalIndex}]" 
                       placeholder="Ghi chú...">
            </td>
            
            <!-- NÚT XÓA -->
            <td class="text-center align-middle">
                <i class="ph-bold ph-x text-danger fs-5 cursor-pointer" 
                   onclick="removeRow(${globalIndex})" 
                   style="cursor: pointer;"></i>
            </td>
        `;
        tbody.appendChild(tr);
    }
}

// Format giá (5m -> 5.000.000)
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

// Xóa dòng
function removeRow(id) {
    const row = document.getElementById(`row_${id}`);
    if (row) { 
        row.remove(); 
        delete rowData[id]; 
    }
}

// --- MODAL ẢNH (Giữ nguyên) ---
window.openImageModal = function(id) {
    currentRowId = id;
    renderModalImages();
    const modalEl = document.getElementById('imageModal');
    if(modalEl) new bootstrap.Modal(modalEl).show();
}

window.removeImage = function(index) {
    rowData[currentRowId].splice(index, 1);
    renderModalImages();
    updateCellPreview(currentRowId);
}

function renderModalImages() {
    const container = document.getElementById('modalImgGrid');
    if(!container) return; 
    container.innerHTML = '';
    const files = rowData[currentRowId];
    if (files.length === 0) {
        container.innerHTML = '<div class="text-center text-muted p-4 w-100" style="grid-column: span 4;">Chưa có ảnh</div>'; 
        return;
    }
    files.forEach((file, index) => {
        const url = URL.createObjectURL(file);
        const div = document.createElement('div');
        div.className = 'modal-item';
        div.innerHTML = `<img src="${url}"><div class="btn-del-img" onclick="removeImage(${index})">×</div>`;
        container.appendChild(div);
    });
}

function addFilesToRow(rowId, fileList) {
    const newFiles = Array.from(fileList);
    rowData[rowId] = [...rowData[rowId], ...newFiles];
    renderModalImages();
    updateCellPreview(rowId);
}

function updateCellPreview(rowId) {
    const cell = document.getElementById(`imgCell_${rowId}`);
    if(!cell) return;
    const files = rowData[rowId];
    if (files.length > 0) {
        const coverUrl = URL.createObjectURL(files[0]);
        const countText = files.length > 1 ? `+${files.length - 1}` : '';
        cell.innerHTML = `<img src="${coverUrl}"><div class="img-cell-count">${countText}</div>`;
        cell.style.background = '#000'; 
        cell.style.borderColor = '#1877F2';
    } else {
        cell.innerHTML = `<i class="ph-bold ph-plus text-secondary fs-4"></i>`;
        cell.style.background = '#f3f4f6'; 
        cell.style.borderColor = '#d1d5db';
    }
}

// Áp dụng chung (Ghi chú & Danh mục)
function applyGlobal() {
    const noteEl = document.getElementById('globalNote');
    const catEl = document.getElementById('globalCategory');
    
    // Áp dụng Ghi chú
    if (noteEl && noteEl.value) {
        document.querySelectorAll('input[name^="notes"]').forEach(el => el.value = noteEl.value);
    }
    
    // Áp dụng Danh mục
    if (catEl && catEl.value) {
        document.querySelectorAll('select[name^="cats"]').forEach(el => el.value = catEl.value);
    }
    
    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Đã áp dụng!', showConfirmButton: false, timer: 1000 });
}

// SUBMIT (LOGIC GỬI)
async function submitBulk() {
    if (isProcessing) return;

    let validRows = [];
    for (const [rowId, files] of Object.entries(rowData)) {
        const rowEl = document.getElementById(`row_${rowId}`);
        if (!rowEl) continue;
        const priceInput = rowEl.querySelector(`input[name="prices[${rowId}]"]`);
        const price = priceInput ? priceInput.value.trim() : '';
        // Điều kiện: Phải có Giá + Ảnh
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
            
            // Upload Ảnh (Chunk)
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

            // Gửi thông tin (Title, Price, Note, Cat)
            const rowEl = document.getElementById(`row_${rowId}`);
            let fdPost = new FormData();
            fdPost.append('indexes[]', rowId);
            
            const titleInput = rowEl.querySelector(`input[name="titles[${rowId}]"]`);
            // Lấy giá trị từ Select danh mục
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