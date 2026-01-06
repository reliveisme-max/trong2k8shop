// admin/assets/js/admin-add.js - FINAL VERSION: LOGIC XỬ LÝ ẢNH & FORM

let fileStore = {}; // Kho chứa file upload từ máy tính (key: uuid, value: File)
let sortable; // Đối tượng SortableJS
let currentPage = 1;
let isLoading = false;
let hasMore = true;
let selectedLibFiles = []; // Mảng tạm chứa file chọn từ modal thư viện

document.addEventListener('DOMContentLoaded', function () {
    // 1. Khởi tạo Kéo thả (SortableJS)
    const grid = document.getElementById('imageGrid');
    if (grid) {
        sortable = new Sortable(grid, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function () {
                // Kéo thả xong thì không cần làm gì đặc biệt, 
                // vì khi submit ta sẽ quét lại DOM để lấy thứ tự mới nhất.
            }
        });
    }

    // 2. Lắng nghe sự kiện chọn file từ máy tính
    const fileInput = document.getElementById('fileInput');
    if (fileInput) {
        fileInput.addEventListener('change', function (e) {
            handleLocalFiles(e.target.files);
            // Reset input để có thể chọn lại chính file đó nếu lỡ xóa
            fileInput.value = '';
        });
    }

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

// --- PHẦN 1: CÁC HÀM XỬ LÝ ẢNH (CORE) ---

// Tạo ID ngẫu nhiên cho mỗi ảnh (để quản lý fileStore)
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
        fileStore[uid] = file; // Lưu file vào kho bộ nhớ

        // Tạo giao diện preview
        const reader = new FileReader();
        reader.onload = function (e) {
            addToGrid(uid, e.target.result, 'local');
        }
        reader.readAsDataURL(file);
    });
}

// Xử lý xác nhận chọn từ Thư viện
function confirmLibrarySelection() {
    selectedLibFiles.forEach(filename => {
        const uid = uuidv4();
        // Thêm vào lưới với type='lib'
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

// Hàm chung: Vẽ ô ảnh vào lưới (Được dùng bởi cả Add mới và Edit cũ)
function addToGrid(uid, src, type, filename = '') {
    const div = document.createElement('div');
    div.className = 'sortable-item';
    div.dataset.id = uid;    // ID định danh
    div.dataset.type = type; // 'local' hoặc 'lib'
    if (filename) div.dataset.filename = filename; // Lưu tên file nếu là ảnh cũ/thư viện

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
    // Nếu là file local thì xóa khỏi kho để giải phóng bộ nhớ
    if (fileStore[uid]) delete fileStore[uid];
}

// --- PHẦN 2: LOGIC FORM & SUBMIT (QUAN TRỌNG NHẤT) ---

function toggleSections() {
    const switchSell = document.getElementById('switchSell');
    const switchRent = document.getElementById('switchRent');

    if(switchSell && switchRent) {
        const isSell = switchSell.checked;
        const isRent = switchRent.checked;

        const sellSec = document.getElementById('sellSection');
        const rentSec = document.getElementById('rentSection');

        if(sellSec) sellSec.style.display = isSell ? 'block' : 'none';
        if(rentSec) rentSec.style.display = isRent ? 'block' : 'none';
    }
}

function submitForm() {
    // 1. Kiểm tra ảnh
    const gridItems = document.querySelectorAll('.sortable-item');
    if (gridItems.length === 0) {
        Swal.fire('Thiếu ảnh', 'Vui lòng chọn ít nhất 1 ảnh cho sản phẩm!', 'warning');
        return;
    }

    // 2. Kiểm tra chế độ (Bán hoặc Thuê)
    const isSell = document.getElementById('switchSell').checked;
    const isRent = document.getElementById('switchRent').checked;
    if (!isSell && !isRent) {
        Swal.fire('Lỗi', 'Vui lòng chọn ít nhất 1 chế độ (Bán hoặc Thuê)!', 'warning');
        return;
    }

    // 3. TÁI CẤU TRÚC DỮ LIỆU ĐỂ GỬI ĐI
    // Chúng ta phải sắp xếp lại file theo đúng thứ tự trên màn hình (Do người dùng kéo thả)
    const dataTransfer = new DataTransfer();
    const libImages = []; // Danh sách tên file ảnh cũ/thư viện
    const orderMap = [];  // Bản đồ thứ tự: ['local', 'lib', 'local', 'lib'...]

    gridItems.forEach(item => {
        const type = item.dataset.type;
        const uid = item.dataset.id;

        if (type === 'local') {
            const file = fileStore[uid];
            if (file) {
                dataTransfer.items.add(file); // Thêm file vào input thật
                orderMap.push('local');       // Đánh dấu vị trí này là file mới
            }
        } else if (type === 'lib') {
            libImages.push(item.dataset.filename); // Lưu tên file cũ
            orderMap.push('lib');                  // Đánh dấu vị trí này là file cũ
        }
    });

    // Cập nhật input file thật (để PHP nhận được $_FILES)
    document.getElementById('fileInput').files = dataTransfer.files;
    
    // Cập nhật input hidden cho thư viện (để PHP nhận được tên ảnh cũ)
    document.getElementById('libraryInput').value = JSON.stringify(libImages);

    // Tạo input hidden cho Order Map (để PHP biết cách trộn)
    // Xóa input cũ nếu có (đề phòng submit nhiều lần)
    const oldMap = document.querySelector('input[name="order_map"]');
    if (oldMap) oldMap.remove();

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
    // Chỉ load lần đầu hoặc khi trống
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
                // Tránh duplicate ID nếu load nhiều lần (dùng class thay vì id cho item)
                const div = document.createElement('div');
                div.className = 'nft-card'; // Dùng class CSS mới
                div.innerHTML = `
                    <input type="checkbox" style="display:none"> <!-- Dummy input logic -->
                    <img src="../uploads/${filename}" loading="lazy">
                    <div class="nft-check-icon"><i class="ph-bold ph-check"></i></div>
                `;
                
                // Logic chọn ảnh
                div.onclick = function() {
                    this.classList.toggle('selected'); // Toggle visual class
                    const input = this.querySelector('input');
                    input.checked = !input.checked; // Toggle checkbox ảo để CSS hoạt động (nếu dùng :has)

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

// --- PHẦN 4: TIỆN ÍCH ---

function formatCurrency(input) {
    let value = input.value.replace(/\D/g, '');
    if (value === '') return;
    input.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}