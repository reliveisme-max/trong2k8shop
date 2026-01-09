// admin/assets/js/pages/bulk-upload.js - FINAL FIXED

let rowCount = 0;      
let globalIndex = 0;   
let rowData = {};      
let currentRowId = 0;  

document.addEventListener('DOMContentLoaded', () => {
    // 1. Tạo sẵn 20 dòng
    addRows(20);

    // 2. Sự kiện nút bấm
    document.getElementById('btnApplyGlobal').addEventListener('click', applyGlobal);
    document.getElementById('btnAddRows').addEventListener('click', () => addRows(5));
    document.getElementById('btnSubmitBulk').addEventListener('click', submitBulk);

    // 3. Sự kiện chọn file Modal
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

// ============================================================
// 1. QUẢN LÝ DÒNG (ROWS)
// ============================================================

function addRows(num) {
    const tbody = document.getElementById('tableBody');
    if (!tbody) return;

    // Tạo danh sách danh mục cho select box
    let optionsHtml = '<option value="0">-- Chọn --</option>';
    if (BULK_CONFIG.categories && Array.isArray(BULK_CONFIG.categories)) {
        BULK_CONFIG.categories.forEach(cat => {
            optionsHtml += `<option value="${cat.id}">${cat.name}</option>`;
        });
    }

    for (let i = 0; i < num; i++) {
        globalIndex++;
        rowCount++;
        
        const displayNum = BULK_CONFIG.startNum + (rowCount - 1); 
        rowData[globalIndex] = [];

        const tr = document.createElement('tr');
        tr.id = `row_${globalIndex}`;
        tr.innerHTML = `
            <td class="text-center fw-bold text-secondary align-middle">${rowCount}</td>
            
            <td class="text-center">
                <div class="img-cell-box mx-auto" onclick="openImageModal(${globalIndex})" id="imgCell_${globalIndex}">
                    <i class="ph-bold ph-plus text-secondary fs-4"></i>
                </div>
            </td>
            
            <td>
                <input type="text" class="form-control fw-bold text-primary" name="titles[${globalIndex}]" value="${BULK_CONFIG.prefix}${displayNum}">
            </td>

            <td>
                <select class="form-select text-dark" name="cats[${globalIndex}]">
                    ${optionsHtml}
                </select>
            </td>
            
            <td>
                <input type="text" class="form-control" name="prices[${globalIndex}]" placeholder="0" oninput="formatPrice(this)">
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
    if (row) {
        row.remove();
        delete rowData[id]; 
    }
}

// ============================================================
// 2. QUẢN LÝ ẢNH (MODAL)
// ============================================================

window.openImageModal = function(id) {
    currentRowId = id;
    const titleInput = document.querySelector(`input[name="titles[${id}]"]`);
    if(titleInput) {
        document.getElementById('modalRowTitle').innerText = titleInput.value;
    }
    renderModalImages();
    new bootstrap.Modal(document.getElementById('imageModal')).show();
}

window.removeRow = function(id) { removeRow(id); }

window.removeImage = function(index) {
    rowData[currentRowId].splice(index, 1);
    renderModalImages();
    updateCellPreview(currentRowId);
}

function renderModalImages() {
    const container = document.getElementById('modalImgGrid');
    container.innerHTML = '';
    const files = rowData[currentRowId];
    
    if (files.length === 0) {
        container.innerHTML = '<div class="text-center text-muted p-4 w-100" style="grid-column: span 4;">Chưa có ảnh nào</div>';
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

// ============================================================
// 3. TIỆN ÍCH (FIXED: ÁP DỤNG CHUNG)
// ============================================================

function applyGlobal() {
    // Chỉ lấy Ghi chú và Danh mục (Không lấy Giá nữa vì đã xóa ô input giá)
    const note = document.getElementById('globalNote').value;
    const catId = document.getElementById('globalCategory').value;
    
    if (note) {
        document.querySelectorAll('input[name^="notes"]').forEach(el => el.value = note);
    }
    
    if (catId) {
        document.querySelectorAll('select[name^="cats"]').forEach(el => el.value = catId);
    }
    
    Swal.fire({
        toast: true, position: 'top-end', icon: 'success', 
        title: 'Đã áp dụng!', showConfirmButton: false, timer: 1000 
    });
}

window.formatPrice = function(input) {
    let val = input.value;
    if (!val) return;
    let numStr = val.toLowerCase().trim();
    if (numStr.endsWith('k')) {
        let num = parseFloat(numStr) * 1000;
        input.value = num.toLocaleString('vi-VN').replace(/,/g, '.');
        return;
    } 
    if (numStr.endsWith('m') || numStr.endsWith('tr')) {
        let pureNum = parseFloat(numStr.replace(/[^0-9.]/g, ''));
        let num = pureNum * 1000000;
        input.value = num.toLocaleString('vi-VN').replace(/,/g, '.');
        return;
    }
    if (/[kmtr]/i.test(val)) return;
    let cleanVal = val.replace(/\D/g, '');
    if (cleanVal !== '') {
        input.value = cleanVal.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
}

// ============================================================
// 4. GỬI DỮ LIỆU
// ============================================================

async function submitBulk() {
    let formData = new FormData();
    let validCount = 0;

    for (const [rowId, files] of Object.entries(rowData)) {
        const rowEl = document.getElementById(`row_${rowId}`);
        if (!rowEl) continue;

        // Lấy dữ liệu
        const title = rowEl.querySelector(`input[name="titles[${rowId}]"]`).value.trim();
        const catId = rowEl.querySelector(`select[name="cats[${rowId}]"]`).value;
        const price = rowEl.querySelector(`input[name="prices[${rowId}]"]`).value.trim();
        const note  = rowEl.querySelector(`input[name="notes[${rowId}]"]`).value.trim();

        // Điều kiện: Phải có Giá và Ảnh
        if (price && files.length > 0) {
            validCount++;
            
            formData.append('indexes[]', rowId);
            formData.append(`title_${rowId}`, title);
            formData.append(`cat_${rowId}`, catId); // Gửi ID danh mục
            formData.append(`price_${rowId}`, price);
            formData.append(`note_${rowId}`, note);

            files.forEach((file) => {
                formData.append(`images_${rowId}[]`, file);
            });
        }
    }

    if (validCount === 0) {
        Swal.fire('Chưa đủ thông tin', 'Vui lòng nhập <b>Giá</b> và chọn <b>Ảnh</b> cho ít nhất 1 acc!', 'warning');
        return;
    }

    Swal.fire({
        title: `Đang đăng ${validCount} Acc...`,
        html: 'Hệ thống đang upload ảnh và lưu dữ liệu.<br>Vui lòng <b>không tắt trình duyệt</b>!',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    try {
        const response = await fetch('api/process_bulk.php', {
            method: 'POST',
            body: formData
        });

        const resText = await response.text();
        let result;
        try {
            result = JSON.parse(resText);
        } catch (e) {
            console.error("Lỗi JSON:", resText);
            throw new Error("Server lỗi: " + resText.substring(0, 100));
        }

        if (result.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Thành công!',
                text: `Đã đăng xong ${result.count} acc!`,
                confirmButtonText: 'Tuyệt vời'
            }).then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire('Có lỗi', result.msg, 'error');
        }

    } catch (error) {
        console.error(error);
        Swal.fire('Lỗi hệ thống', error.message, 'error');
    }
}