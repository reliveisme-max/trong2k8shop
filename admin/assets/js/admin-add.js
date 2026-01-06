// admin/assets/js/admin-add.js - UPDATE V6 FINAL (FIX MODAL LOAD)

let fileStore = {}; 
let sortable; 
let currentPage = 1;
let isLoading = false;
let hasMore = true;
let selectedLibFiles = []; 

document.addEventListener('DOMContentLoaded', function () {
    // 1. Khởi tạo Kéo thả (SortableJS) cho lưới 4 cột
    const grid = document.getElementById('imageGrid');
    if (grid) {
        sortable = new Sortable(grid, {
            animation: 150,
            ghostClass: 'sortable-ghost',
        });
    }

    // 2. Lắng nghe sự kiện chọn file từ máy tính
    const fileInput = document.getElementById('fileInput');
    if (fileInput) {
        fileInput.addEventListener('change', function (e) {
            handleLocalFiles(e.target.files);
            fileInput.value = ''; 
        });
    }

    // 3. Khởi tạo trạng thái Switch (Bán/Thuê)
    toggleSections();

    // 4. Xử lý Cuộn vô hạn trong Modal
    const scrollArea = document.getElementById('scrollArea');
    if (scrollArea) {
        scrollArea.addEventListener('scroll', () => {
            if (scrollArea.scrollTop + scrollArea.clientHeight >= scrollArea.scrollHeight - 50) {
                if (hasMore && !isLoading) {
                    fetchLibImages(currentPage + 1);
                }
            }
        });
    }
});

// --- PHẦN 1: THƯ VIỆN ẢNH (MODAL FIX) ---

function openLibrary() {
    const grid = document.getElementById('libGrid');
    grid.className = 'nft-grid-5 p-3'; // Ép kiểu Grid 5 cột
    
    const modalEl = document.getElementById('libraryModal');
    const modal = new bootstrap.Modal(modalEl);
    
    modal.show();
    
    // [FIX] Luôn kiểm tra nếu lưới trống thì tải lại ngay
    if (grid.innerHTML.trim() === '') {
        // Reset trạng thái
        currentPage = 1;
        hasMore = true;
        isLoading = false; 
        fetchLibImages(1);
    }
}

async function fetchLibImages(page) {
    if (isLoading) return;
    isLoading = true;
    
    const loadingEl = document.getElementById('libLoading');
    if (loadingEl) loadingEl.classList.remove('d-none');

    try {
        // [FIX] Thêm timestamp để chống Cache trình duyệt
        const response = await fetch(`get_images.php?page=${page}&_t=${Date.now()}`);
        const data = await response.json();
        
        if (data.status === 'success') {
            const grid = document.getElementById('libGrid');
            
            data.data.forEach(filename => {
                const div = document.createElement('div');
                div.className = 'nft-card'; // CSS V6 (Vuông + Contain + Border)
                
                if (selectedLibFiles.includes(filename)) {
                    div.classList.add('active');
                }

                div.innerHTML = `
                    <img src="../uploads/${filename}" loading="lazy">
                    <div class="nft-check-icon"><i class="ph-bold ph-check"></i></div>
                `;

                // Logic chọn ảnh (Click trực tiếp)
                div.onclick = function() {
                    this.classList.toggle('active');
                    const isActive = this.classList.contains('active');
                    
                    if (isActive) {
                        if (!selectedLibFiles.includes(filename)) selectedLibFiles.push(filename);
                    } else {
                        selectedLibFiles = selectedLibFiles.filter(f => f !== filename);
                    }
                    updateSelectedCount();
                };
                
                grid.appendChild(div);
            });

            hasMore = data.has_more;
            currentPage = page;
        }
    } catch (error) {
        console.error('Lỗi tải ảnh:', error);
    } finally {
        isLoading = false;
        if (loadingEl) loadingEl.classList.add('d-none');
    }
}

function updateSelectedCount() {
    const el = document.getElementById('selectedCount');
    if (el) el.innerText = `Đã chọn: ${selectedLibFiles.length}`;
}

function confirmLibrarySelection() {
    selectedLibFiles.forEach(filename => {
        const uid = uuidv4();
        addToGrid(uid, `../uploads/${filename}`, 'lib', filename);
    });
    
    selectedLibFiles = [];
    updateSelectedCount();
    document.querySelectorAll('.nft-card.active').forEach(el => el.classList.remove('active'));
    
    const modalEl = document.getElementById('libraryModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    modal.hide();
}

// --- PHẦN 2: UPLOAD/KÉO THẢ ---

function uuidv4() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

function handleLocalFiles(files) {
    Array.from(files).forEach(file => {
        if (!file.type.startsWith('image/')) return;

        const uid = uuidv4();
        fileStore[uid] = file;

        const reader = new FileReader();
        reader.onload = function (e) {
            addToGrid(uid, e.target.result, 'local');
        }
        reader.readAsDataURL(file);
    });
}

function addToGrid(uid, src, type, filename = '') {
    const div = document.createElement('div');
    div.className = 'sortable-item'; // CSS V6 (Vuông + Tràn viền)
    div.dataset.id = uid;    
    div.dataset.type = type; 
    if (filename) div.dataset.filename = filename;

    div.innerHTML = `
        <img src="${src}">
        <div class="btn-remove-img" onclick="removeImage(this, '${uid}')">
            <i class="ph-bold ph-x"></i>
        </div>
    `;
    document.getElementById('imageGrid').appendChild(div);
}

function removeImage(btn, uid) {
    const item = btn.closest('.sortable-item');
    item.remove();
    if (fileStore[uid]) delete fileStore[uid];
}

// --- PHẦN 3: FORM LOGIC ---

function toggleSections() {
    const switchSell = document.getElementById('switchSell');
    const switchRent = document.getElementById('switchRent');

    if(switchSell && switchRent) {
        const sellSec = document.getElementById('sellSection');
        const rentSec = document.getElementById('rentSection');

        if(sellSec) sellSec.style.display = switchSell.checked ? 'block' : 'none';
        if(rentSec) rentSec.style.display = switchRent.checked ? 'block' : 'none';
    }
}

function formatCurrency(input) {
    let value = input.value.replace(/\D/g, '');
    if (value === '') return;
    input.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function submitForm() {
    const gridItems = document.querySelectorAll('.sortable-item');
    if (gridItems.length === 0) {
        Swal.fire('Thiếu ảnh', 'Vui lòng chọn ít nhất 1 ảnh!', 'warning');
        return;
    }

    const isSell = document.getElementById('switchSell').checked;
    const isRent = document.getElementById('switchRent').checked;
    if (!isSell && !isRent) {
        Swal.fire('Lỗi', 'Phải chọn ít nhất 1 chế độ (Bán hoặc Thuê)!', 'warning');
        return;
    }

    const dataTransfer = new DataTransfer();
    const libImages = []; 
    const orderMap = [];

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

    document.getElementById('fileInput').files = dataTransfer.files;
    document.getElementById('libraryInput').value = JSON.stringify(libImages);

    const oldMap = document.querySelector('input[name="order_map"]');
    if (oldMap) oldMap.remove();

    let mapInput = document.createElement('input');
    mapInput.type = 'hidden';
    mapInput.name = 'order_map';
    mapInput.value = JSON.stringify(orderMap);
    document.getElementById('addForm').appendChild(mapInput);

    document.getElementById('addForm').submit();
}