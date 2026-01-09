// admin/assets/js/pages/bulk-upload.js

let rowCount = 0; // Đếm số dòng hiển thị (1, 2, 3...)
let globalIndex = 0; // ID định danh duy nhất cho mỗi dòng (để không bị trùng khi xóa)
let rowData = {}; // Kho lưu trữ File: { 1: [File, File], 2: [] }
let currentRowId = 0; // ID của dòng đang mở Modal

document.addEventListener('DOMContentLoaded', () => {
// 1. Tạo sẵn 20 dòng khi vào trang
addRows(20);

// 2. Lắng nghe sự kiện các nút bấm
document.getElementById('btnApplyGlobal').addEventListener('click', applyGlobal);
document.getElementById('btnAddRows').addEventListener('click', () => addRows(5));
document.getElementById('btnSubmitBulk').addEventListener('click', submitBulk);

// 3. Lắng nghe sự kiện chọn file trong Modal
const fileInput = document.getElementById('modalFileInput');
if (fileInput) {
fileInput.addEventListener('change', function(e) {
if (currentRowId > 0 && e.target.files.length > 0) {
addFilesToRow(currentRowId, e.target.files);
this.value = ''; // Reset để chọn lại được file cũ
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

for (let i = 0; i < num; i++) { globalIndex++; rowCount++; // Tính mã số hiển thị (MS101, MS102...) // BULK_CONFIG được
    truyền từ PHP ở file add.php const displayNum=BULK_CONFIG.startNum + (rowCount - 1); // Khởi tạo kho ảnh rỗng cho
    dòng này rowData[globalIndex]=[]; const tr=document.createElement('tr'); tr.id=`row_${globalIndex}`; tr.innerHTML=`
    <td class="text-center fw-bold text-secondary align-middle">${rowCount}</td>

    <td class="text-center">
        <div class="img-cell-box mx-auto" onclick="openImageModal(${globalIndex})" id="imgCell_${globalIndex}">
            <i class="ph-bold ph-plus text-secondary fs-4"></i>
        </div>
    </td>

    <td>
        <input type="text" class="form-control fw-bold text-primary" name="titles[${globalIndex}]"
            value="${BULK_CONFIG.prefix}${displayNum}">
    </td>

    <td>
        <input type="text" class="form-control" name="prices[${globalIndex}]" placeholder="0"
            oninput="formatPrice(this)">
    </td>

    <td>
        <input type="text" class="form-control text-secondary" name="notes[${globalIndex}]" placeholder="...">
    </td>

    <td class="text-center align-middle">
        <i class="ph-bold ph-x text-danger fs-5 cursor-pointer" onclick="removeRow(${globalIndex})"
            style="cursor:pointer"></i>
    </td>
    `;
    tbody.appendChild(tr);
    }
    }

    function removeRow(id) {
    const row = document.getElementById(`row_${id}`);
    if (row) {
    row.remove();
    delete rowData[id]; // Xóa dữ liệu ảnh trong bộ nhớ để giải phóng RAM
    }
    }

    // ============================================================
    // 2. QUẢN LÝ ẢNH (MODAL)
    // ============================================================

    // Hàm này cần public ra window để gọi được từ onclick trong HTML
    window.openImageModal = function(id) {
    currentRowId = id;

    // Lấy mã acc hiện tại để hiển thị lên tiêu đề Modal
    const titleInput = document.querySelector(`input[name="titles[${id}]"]`);
    if(titleInput) {
    document.getElementById('modalRowTitle').innerText = titleInput.value;
    }

    renderModalImages();

    // Mở Modal Bootstrap
    const modalEl = document.getElementById('imageModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
    }

    // Hàm này cũng cần public để gọi từ nút xóa trong HTML
    window.removeRow = function(id) {
    removeRow(id);
    }

    // Hàm này public để nút xóa ảnh trong modal gọi
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
    container.innerHTML = '<div class="text-center text-muted p-4 w-100" style="grid-column: span 4;">Chưa có ảnh nào
    </div>';
    return;
    }

    files.forEach((file, index) => {
    const url = URL.createObjectURL(file);
    const div = document.createElement('div');
    div.className = 'modal-item';

    // Badge ảnh bìa cho cái đầu tiên
    const badge = index === 0
    ? '<div class="position-absolute bottom-0 start-0 w-100 bg-warning text-white text-center py-1 small fw-bold"
        style="font-size:10px;">ẢNH BÌA</div>'
    : '';

    div.innerHTML = `
    <img src="${url}">
    <div class="btn-del-img" onclick="removeImage(${index})">×</div>
    ${badge}
    `;
    container.appendChild(div);
    });
    }

    function addFilesToRow(rowId, fileList) {
    const newFiles = Array.from(fileList);
    // Cộng dồn vào mảng cũ
    rowData[rowId] = [...rowData[rowId], ...newFiles];

    renderModalImages();
    updateCellPreview(rowId);
    }

    function updateCellPreview(rowId) {
    const cell = document.getElementById(`imgCell_${rowId}`);
    const files = rowData[rowId];

    if (files.length > 0) {
    // Lấy ảnh đầu tiên làm bìa preview
    const coverUrl = URL.createObjectURL(files[0]);
    const countText = files.length > 1 ? `+${files.length - 1}` : '';

    cell.innerHTML = `
    <img src="${coverUrl}">
    <div class="img-cell-count">${countText}</div>
    `;
    cell.style.background = '#000';
    cell.style.borderColor = '#1877F2';
    } else {
    cell.innerHTML = `<i class="ph-bold ph-plus text-secondary fs-4"></i>`;
    cell.style.background = '#f3f4f6';
    cell.style.borderColor = '#d1d5db';
    }
    }


    // ============================================================
    // 3. CÁC TIỆN ÍCH HỖ TRỢ (UTILS)
    // ============================================================

    function applyGlobal() {
    const note = document.getElementById('globalNote').value;
    const price = document.getElementById('globalPrice').value;

    if (note) {
    document.querySelectorAll('input[name^="notes"]').forEach(el => el.value = note);
    }
    if (price) {
    document.querySelectorAll('input[name^="prices"]').forEach(el => {
    el.value = price;
    // Gọi hàm format để nó tự thêm dấu chấm nếu cần
    formatPrice(el);
    });
    }

    Swal.fire({
    toast: true, position: 'top-end', icon: 'success',
    title: 'Đã áp dụng!', showConfirmButton: false, timer: 1000
    });
    }

    // Hàm format giá (Public ra window để gọi từ oninput)
    window.formatPrice = function(input) {
    let val = input.value;
    if (!val) return;

    // Xử lý nhập tắt (5m, 100k)
    let numStr = val.toLowerCase().trim();

    if (numStr.endsWith('k')) {
    let num = parseFloat(numStr) * 1000;
    input.value = num.toLocaleString('vi-VN').replace(/,/g, '.');
    return;
    }

    if (numStr.endsWith('m') || numStr.endsWith('tr')) {
    // Xóa chữ cái để lấy số
    let pureNum = parseFloat(numStr.replace(/[^0-9.]/g, ''));
    let num = pureNum * 1000000;
    input.value = num.toLocaleString('vi-VN').replace(/,/g, '.');
    return;
    }

    // Format số thường (xóa ký tự lạ, thêm dấu chấm)
    // Nếu đang gõ k hoặc m thì không can thiệp vội
    if (/[kmtr]/i.test(val)) return;

    let cleanVal = val.replace(/\D/g, '');
    if (cleanVal !== '') {
    input.value = cleanVal.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
    }


    // ============================================================
    // 4. GỬI DỮ LIỆU (AJAX SUBMIT)
    // ============================================================

    async function submitBulk() {
    let formData = new FormData();
    let validCount = 0;

    // Duyệt qua tất cả các dòng dữ liệu
    for (const [rowId, files] of Object.entries(rowData)) {
    // Kiểm tra xem dòng này còn trên bảng không (hay đã bị xóa)
    const rowEl = document.getElementById(`row_${rowId}`);
    if (!rowEl) continue;

    // Lấy dữ liệu từ input
    const titleInput = rowEl.querySelector(`input[name="titles[${rowId}]"]`);
    const priceInput = rowEl.querySelector(`input[name="prices[${rowId}]"]`);
    const noteInput = rowEl.querySelector(`input[name="notes[${rowId}]"]`);

    const title = titleInput.value.trim();
    const price = priceInput.value.trim();
    const note = noteInput.value.trim();

    // Điều kiện để được lưu: Phải có Giá VÀ có ít nhất 1 Ảnh
    if (price && files.length > 0) {
    validCount++;

    formData.append('indexes[]', rowId); // Danh sách ID hợp lệ
    formData.append(`title_${rowId}`, title);
    formData.append(`price_${rowId}`, price);
    formData.append(`note_${rowId}`, note);

    // Gửi toàn bộ file ảnh của dòng này
    files.forEach((file) => {
    formData.append(`images_${rowId}[]`, file);
    });
    }
    }

    if (validCount === 0) {
    Swal.fire('Chưa đủ thông tin', 'Vui lòng nhập <b>Giá</b> và chọn <b>Ảnh</b> cho ít nhất 1 acc!', 'warning');
    return;
    }

    // Hiển thị loading
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

    // Parse kết quả
    // Lưu ý: Đôi khi PHP trả về lỗi kèm HTML, cần lọc
    const resText = await response.text();

    let result;
    try {
    result = JSON.parse(resText);
    } catch (e) {
    console.error("Lỗi JSON:", resText);
    throw new Error("Server trả về dữ liệu lỗi: " + resText.substring(0, 100));
    }

    if (result.status === 'success') {
    Swal.fire({
    icon: 'success',
    title: 'Thành công!',
    text: `Đã đăng xong ${result.count} acc!`,
    confirmButtonText: 'Tuyệt vời'
    }).then(() => {
    window.location.reload(); // Reload để làm lô mới
    });
    } else {
    Swal.fire('Có lỗi', result.msg, 'error');
    }

    } catch (error) {
    console.error(error);
    Swal.fire('Lỗi hệ thống', error.message, 'error');
    }
    }