// admin/assets/js/admin-add.js - FINAL V2: LOGIC KÉO THẢ & TRỘN ẢNH

let fileStore = {}; // Kho chứa file từ máy tính (key: uuid, value: File)
let sortable; // Đối tượng SortableJS
let currentPage = 1;
let isLoading = false;
let hasMore = true;
let selectedLibFiles = []; // Mảng tạm chứa file chọn từ modal thư viện

document.addEventListener('DOMContentLoaded', function () {
    // 1. Khởi tạo Kéo thả
    const grid = document.getElementById('imageGrid');
    sortable = new Sortable(grid, {
        animation: 150,
        ghostClass: 'sortable-ghost',
        onEnd: function () {
            // Sau khi kéo thả xong thì làm gì? (Hiện tại chưa cần xử lý ngay)
        }
    });

    // 2. Lắng nghe sự kiện chọn file từ máy
    const fileInput = document.getElementById('fileInput');
    fileInput.addEventListener('change', function (e) {
        handleLocalFiles(e.target.files);
        // Reset input để chọn lại được file cũ nếu muốn
        fileInput.value = '';
    });

    // 3. Khởi tạo trạng thái Switch (Bán/Thuê)
    toggleSections();

    // 4. Xử lý cuộn thư viện (Infinite Scroll)
    const scrollArea = document.getElementById('scrollArea');
    if (scrollArea) {
        scrollArea.addEventListener('scroll', () => {
            if (scrollArea.scrollTop + scrollArea.clientHeight >= scrollArea.scrollHeight - 50) {
                if (hasMore && !isLoading) {
                    currentPage++;
                    fetchLibImages(currentPage);
                }
            }
        });
    }
});

// --- PHẦN 1: XỬ LÝ ẢNH (LOCAL & LIBRARY) ---

// Tạo ID ngẫu nhiên cho mỗi ảnh
function uuidv4() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

// Xử lý file từ máy tính upload lên
function handleLocalFiles(files) {
    Array.from(files).forEach(file => {
        // Chỉ nhận ảnh
        if (!file.type.startsWith('image/')) return;

        const uid = uuidv4();
        fileStore[uid] = file; // Lưu vào kho

        // Tạo giao diện preview
        const reader = new FileReader();
        reader.onload = function (e) {
            addToGrid(uid, e.target.result, 'local');
        }
        reader.readAsDataURL(file);
    });
}

// Xử lý file chọn từ Thư viện
function confirmLibrarySelection() {
    selectedLibFiles.forEach(filename => {
        const uid = uuidv4();
        // Lưu filename vào dataset của div
        addToGrid(uid, `../uploads/${filename}`, 'lib', filename);
    });
    
    // Reset modal
    selectedLibFiles = [];
    document.querySelectorAll('.lib-item.selected').forEach(el => el.classList.remove('selected'));
    
    // Đóng modal
    const modalEl = document.getElementById('libraryModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    modal.hide();
}

// Hàm chung: Vẽ ô ảnh vào lưới
function addToGrid(uid, src, type, filename = '') {
    const div = document.createElement('div');
    div.className = 'sortable-item';
    div.dataset.id = uid;   // ID để tìm lại file
    div.dataset.type = type; // 'local' hoặc 'lib'
    if (filename) div.dataset.filename = filename; // Nếu là lib thì lưu tên file

    div.innerHTML = `
        <img src="${src}">
        <div class="btn-remove-img" onclick="removeImage(this, '${uid}')">
            <i class="ph-bold ph-x"></i>
        </div>
    `;
    document.getElementById('imageGrid').appendChild(div);
}

// Xóa ảnh khỏi lưới
function removeImage(btn, uid) {
    const item = btn.closest('.sortable-item');
    item.remove();
    // Nếu là file local thì xóa khỏi kho để giải phóng bộ nhớ (không bắt buộc nhưng tốt)
    if (fileStore[uid]) delete fileStore[uid];
}

// --- PHẦN 2: LOGIC FORM & SUBMIT ---

function toggleSections() {
    const isSell = document.getElementById('switchSell').checked;
    const isRent = document.getElementById('switchRent').checked;

    const sellSec = document.getElementById('sellSection');
    const rentSec = document.getElementById('rentSection');

    sellSec.style.display = isSell ? 'block' : 'none';
    rentSec.style.display = isRent ? 'block' : 'none';
}

function submitForm() {
    const gridItems = document.querySelectorAll('.sortable-item');
    if (gridItems.length === 0) {
        Swal.fire('Lỗi', 'Vui lòng chọn ít nhất 1 ảnh!', 'error');
        return;
    }

    // Kiểm tra chọn loại acc
    const isSell = document.getElementById('switchSell').checked;
    const isRent = document.getElementById('switchRent').checked;
    if (!isSell && !isRent) {
        Swal.fire('Lỗi', 'Vui lòng chọn ít nhất 1 chế độ (Bán hoặc Thuê)!', 'error');
        return;
    }

    // 1. TÁI CẤU TRÚC FILE INPUT (QUAN TRỌNG)
    // Chúng ta cần sắp xếp lại fileStore theo đúng thứ tự trên giao diện
    const dataTransfer = new DataTransfer();
    const libImages = []; // Mảng chứa tên file thư viện
    const orderMap = [];  // Mảng đánh dấu thứ tự: ['local', 'lib', 'local'...]

    gridItems.forEach(item => {
        const type = item.dataset.type;
        const uid = item.dataset.id;

        if (type === 'local') {
            const file = fileStore[uid];
            if (file) {
                dataTransfer.items.add(file);
                orderMap.push('local');
            }
        } else if (type === 'lib') {
            libImages.push(item.dataset.filename);
            orderMap.push('lib');
        }
    });

    // Cập nhật input file thật
    document.getElementById('fileInput').files = dataTransfer.files;
    
    // Cập nhật input hidden cho thư viện
    document.getElementById('libraryInput').value = JSON.stringify(libImages);

    // Tạo thêm input hidden để gửi Map thứ tự (giúp PHP biết đường mà lần)
    // Ta sẽ tạo dynamic input vì form chưa có sẵn cái này
    let mapInput = document.createElement('input');
    mapInput.type = 'hidden';
    mapInput.name = 'order_map';
    mapInput.value = JSON.stringify(orderMap);
    document.getElementById('addForm').appendChild(mapInput);

    // Gửi form
    document.getElementById('addForm').submit();
}

// --- PHẦN 3: THƯ VIỆN ẢNH (MODAL) ---

function openLibrary() {
    const grid = document.getElementById('libGrid');
    if (grid.innerHTML.trim() === '') fetchLibImages(1);
    
    const modal = new bootstrap.Modal(document.getElementById('libraryModal'));
    modal.show();
}

async function fetchLibImages(page) {
    if (isLoading) return;
    isLoading = true;

    try {
        const response = await fetch(`get_images.php?page=${page}`);
        const data = await response.json();
        if (data.status === 'success') {
            hasMore = data.has_more;
            const grid = document.getElementById('libGrid');
            
            data.data.forEach(filename => {
                const div = document.createElement('div');
                div.className = 'lib-item';
                div.innerHTML = `<img src="../uploads/${filename}" loading="lazy">`;
                div.onclick = function() {
                    this.classList.toggle('selected');
                    if (this.classList.contains('selected')) {
                        selectedLibFiles.push(filename);
                    } else {
                        selectedLibFiles = selectedLibFiles.filter(f => f !== filename);
                    }
                };
                grid.appendChild(div);
            });
        }
    } catch (error) {
        console.error(error);
    } finally {
        isLoading = false;
    }
}

// --- PHẦN 4: TIỆN ÍCH KHÁC ---

function formatCurrency(input) {
    let value = input.value.replace(/\D/g, '');
    if (value === '') return;
    input.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}