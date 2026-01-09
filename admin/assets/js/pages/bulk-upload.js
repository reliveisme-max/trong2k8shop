// admin/assets/js/pages/bulk-upload.js - V8: CLEAN VERSION (ĐÃ XÓA JS CŨ GÂY LỖI)

let rowCount = 0;      
let globalIndex = 0;   
let rowData = {};      
let currentRowId = 0;  
let isProcessing = false;

document.addEventListener('DOMContentLoaded', () => {
    // Tạo 10 dòng đầu tiên
    addRows(10);
    
    // Gắn sự kiện cho các nút (KHÔNG DÙNG oninput, onsubmit nũa)
    const btnGlobal = document.getElementById('btnApplyGlobal');
    if(btnGlobal) btnGlobal.addEventListener('click', applyGlobal);

    const btnAdd = document.getElementById('btnAddRows');
    if(btnAdd) btnAdd.addEventListener('click', () => addRows(5));

    const btnSubmit = document.getElementById('btnSubmitBulk');
    if(btnSubmit) {
        btnSubmit.type = "button"; // Chắc chắn không reload trang
        btnSubmit.addEventListener('click', submitBulk);
    }

    // Sự kiện chọn file
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

// 1. TẠO BẢNG NHẬP LIỆU (Đã xóa bỏ oninput="formatPrice")
function addRows(num) {
    const tbody = document.getElementById('tableBody');
    if (!tbody) return;

    let optionsHtml = '<option value="0">-- Chọn --</option>';
    if (typeof BULK_CONFIG !== 'undefined' && BULK_CONFIG.categories) {
        BULK_CONFIG.categories.forEach(cat => {
            optionsHtml += `<option value="${cat.id}">${cat.name}</option>`;
        });
    }

    for (let i = 0; i < num; i++) {
        globalIndex++;
        rowCount++;
        rowData[globalIndex] = [];

        const tr = document.createElement('tr');
        tr.id = `row_${globalIndex}`;
        
        // LƯU Ý DÒNG DƯỚI: Input Giá giờ chỉ là text bình thường, không có lệnh JS nào gắn vào
        tr.innerHTML = `
            <td class="text-center fw-bold text-secondary align-middle">${rowCount}</td>
            <td class="text-center">
                <div class="img-cell-box mx-auto" onclick="openImageModal(${globalIndex})" id="imgCell_${globalIndex}">
                    <i class="ph-bold ph-plus text-secondary fs-4"></i>
                </div>
            </td>
            <td>
                <input type="text" class="form-control fw-bold text-danger" name="prices[${globalIndex}]" placeholder="Nhập: 20m, 500k...">
            </td>
            <td>
                <input type="text" class="form-control text-primary fw-bold" name="titles[${globalIndex}]" placeholder="Để trống = Hiện giá">
            </td>
            <td>
                <select class="form-select text-dark" name="cats[${globalIndex}]">${optionsHtml}</select>
            </td>
            <td>
                <input type="text" class="form-control text-secondary" name="notes[${globalIndex}]" placeholder="...">
            </td>
            <td class="text-center align-middle">
                <i class="ph-bold ph-x text-danger fs-5 cursor-pointer" onclick="removeRow(${globalIndex})" style="cursor:pointer"></i>
            </td>
        `;
        tbody.appendChild(tr);
    }
}

function removeRow(id) {
    const row = document.getElementById(`row_${id}`);
    if (row) { row.remove(); delete rowData[id]; }
}

// 2. QUẢN LÝ ẢNH (MODAL)
window.openImageModal = function(id) {
    currentRowId = id;
    renderModalImages();
    const modalEl = document.getElementById('imageModal');
    if(modalEl) new bootstrap.Modal(modalEl).show();
}

window.removeRow = function(id) { removeRow(id); }

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
        const badge = index === 0 ? '<div class="position-absolute bottom-0 start-0 w-100 bg-warning text-white text-center py-1 small fw-bold" style="font-size:10px;">ẢNH BÌA</div>' : '';
        div.innerHTML = `<img src="${url}"><div class="btn-del-img" onclick="removeImage(${index})">×</div>${badge}`;
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

// 3. TIỆN ÍCH ÁP DỤNG CHUNG
function applyGlobal() {
    const noteEl = document.getElementById('globalNote');
    const catEl = document.getElementById('globalCategory');
    
    if (noteEl && noteEl.value) {
        document.querySelectorAll('input[name^="notes"]').forEach(el => el.value = noteEl.value);
    }
    if (catEl && catEl.value) {
        document.querySelectorAll('select[name^="cats"]').forEach(el => el.value = catEl.value);
    }
    
    if(typeof Swal !== 'undefined') {
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Đã áp dụng!', showConfirmButton: false, timer: 1000 });
    }
}

// 4. GỬI DỮ LIỆU (KHÔNG FORMAT GÌ CẢ - GỬI THẲNG 20m CHO PHP TỰ TÍNH)
async function submitBulk() {
    if (isProcessing) return;

    let validRows = [];
    for (const [rowId, files] of Object.entries(rowData)) {
        const rowEl = document.getElementById(`row_${rowId}`);
        if (!rowEl) continue;
        const price = rowEl.querySelector(`input[name="prices[${rowId}]"]`).value.trim();
        
        // Điều kiện: Chỉ cần có Giá + Ảnh
        if (price && files.length > 0) {
            validRows.push(rowId);
        }
    }

    if (validRows.length === 0) {
        Swal.fire('Thiếu thông tin', 'Cần nhập <b>Giá</b> và chọn <b>Ảnh</b> cho ít nhất 1 acc!', 'warning');
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
        html: `Đang đăng ${validRows.length} Acc. Vui lòng giữ nguyên trình duyệt!`,
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    try {
        for (let i = 0; i < validRows.length; i++) {
            const rowId = validRows[i];
            const files = rowData[rowId];
            
            // --- UPLOAD ẢNH (3 FILE/LẦN CHO AN TOÀN) ---
            let uploadedNames = [];
            const CHUNK_SIZE = 3; 

            for (let j = 0; j < files.length; j += CHUNK_SIZE) {
                const chunk = files.slice(j, j + CHUNK_SIZE);
                let fdImg = new FormData();
                fdImg.append('ajax_upload_mode', '1');
                
                chunk.forEach((f, idx) => {
                    fdImg.append('chunk_files[]', f);
                    fdImg.append('chunk_uids[]', j + idx); 
                });
                
                const resImg = await fetch('api/upload.php', { method: 'POST', body: fdImg });
                // Check an toàn cho JSON
                const textImg = await resImg.text();
                let dataImg;
                try { 
                    dataImg = JSON.parse(textImg); 
                } catch (e) { 
                    throw new Error(`Lỗi server khi upload ảnh: ${textImg.substring(0, 100)}...`); 
                }

                if (dataImg.status === 'success') {
                    Object.values(dataImg.data).forEach(n => uploadedNames.push(n));
                } else {
                    throw new Error(dataImg.msg || "Lỗi upload ảnh");
                }
            }

            // --- LƯU ACC ---
            const rowEl = document.getElementById(`row_${rowId}`);
            let fdPost = new FormData();
            fdPost.append('indexes[]', rowId);
            
            const titleInput = rowEl.querySelector(`input[name="titles[${rowId}]"]`);
            
            // Lấy nguyên văn giá trị người dùng nhập (ví dụ: "20m")
            const rawPrice = rowEl.querySelector(`input[name="prices[${rowId}]"]`).value.trim();

            fdPost.append(`title_${rowId}`, titleInput ? titleInput.value.trim() : '');
            fdPost.append(`cat_${rowId}`, rowEl.querySelector(`select[name="cats[${rowId}]"]`).value);
            fdPost.append(`price_${rowId}`, rawPrice); // Gửi "20m" sang PHP
            fdPost.append(`note_${rowId}`, rowEl.querySelector(`input[name="notes[${rowId}]"]`).value.trim());
            
            uploadedNames.forEach(name => fdPost.append(`uploaded_images_${rowId}[]`, name));

            const resPost = await fetch('api/process_bulk.php', { method: 'POST', body: fdPost });
            const textPost = await resPost.text();
            let dataPost;
            try { 
                dataPost = JSON.parse(textPost); 
            } catch (e) { 
                throw new Error(`Lỗi server khi lưu Acc: ${textPost.substring(0, 100)}...`); 
            }
            
            if (dataPost.status === 'success') {
                successCount++;
                removeRow(rowId);
            }
        }

        Swal.fire({ icon: 'success', title: 'Hoàn tất!', text: `Đã đăng thành công ${successCount} acc!` }).then(() => {
            if(document.querySelectorAll('#tableBody tr').length === 0) window.location.reload();
            else { isProcessing = false; btn.disabled = false; btn.innerHTML = originalText; }
        });

    } catch (err) {
        console.error(err);
        Swal.fire('Có lỗi', err.message, 'error');
        isProcessing = false; btn.disabled = false; btn.innerHTML = originalText;
    }
}