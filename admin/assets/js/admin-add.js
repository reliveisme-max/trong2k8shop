// admin/assets/js/admin-add.js - V15: FINAL FIX UPLOAD KEY

let fileStore = {}; 
let sortable; 
let currentPage = 1;
let isLoading = false;
let hasMore = true;
let selectedLibFiles = []; 

document.addEventListener('DOMContentLoaded', function () {
    // 1. Khởi tạo Kéo thả
    const grid = document.getElementById('imageGrid');
    if (grid) {
        sortable = new Sortable(grid, {
            animation: 150,
            ghostClass: 'sortable-ghost',
        });
    }

    // 2. Upload từ máy tính
    const fileInput = document.getElementById('fileInput');
    if (fileInput) {
        fileInput.addEventListener('change', function (e) {
            handleLocalFiles(e.target.files);
            fileInput.value = ''; 
        });
    }

    // 3. Khởi tạo Switch & Auto Parse
    toggleSections();
    const titleInput = document.querySelector('input[name="title"]');
    if (titleInput) {
        titleInput.addEventListener('change', function() { autoParseEverything(this.value); });
        titleInput.addEventListener('paste', function() { setTimeout(() => autoParseEverything(this.value), 50); });
    }

    // 4. Scroll vô hạn thư viện
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

// =========================================================
// PHẦN 1: XỬ LÝ SUBMIT FORM (QUAN TRỌNG NHẤT)
// =========================================================

async function submitForm() {
    const gridItems = document.querySelectorAll('.sortable-item');
    if (gridItems.length === 0) { Swal.fire('Thiếu ảnh', 'Vui lòng chọn ít nhất 1 ảnh!', 'warning'); return; }
    
    const isSell = document.getElementById('switchSell').checked;
    const isRent = document.getElementById('switchRent').checked;
    if (!isSell && !isRent) { Swal.fire('Lỗi', 'Chưa chọn chế độ Bán hoặc Thuê!', 'warning'); return; }

    Swal.fire({
        title: 'Đang đăng bán...',
        html: 'Hệ thống đang nén ảnh và tối ưu hóa.<br><b>Vui lòng đợi trong giây lát!</b>',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    try {
        const formEl = document.getElementById('addForm');
        const formData = new FormData(formEl);
        
        // Xóa key cũ đi để tránh lỗi
        formData.delete('gallery[]');
        
        const libImages = []; 
        const orderMap = [];

        for (const item of gridItems) {
            const type = item.dataset.type;
            const uid = item.dataset.id;

            if (type === 'local') {
                const file = fileStore[uid];
                if (file) {
                    // Nén ảnh
                    const compressedFile = await compressImage(file, 1200, 0.8);
                    // [FIX] Đổi tên key thành 'files_to_upload[]'
                    formData.append('files_to_upload[]', compressedFile, file.name);
                    orderMap.push('local');
                }
            } else if (type === 'lib') {
                libImages.push(item.dataset.filename);
                orderMap.push('lib');
            }
        }

        formData.set('library_images', JSON.stringify(libImages));
        formData.set('order_map', JSON.stringify(orderMap));

        const response = await fetch('process.php', {
            method: 'POST',
            body: formData
        });

        if (response.redirected) {
            window.location.href = response.url;
        } else {
            const resText = await response.text();
            // Nếu PHP echo script alert thì cho chạy, còn nếu lỗi text thì hiện Swal
            if(resText.includes('<script>') || resText.includes('header')) {
                 document.write(resText);
            } else {
                 Swal.fire({
                    icon: 'error',
                    title: 'Thông báo từ Server',
                    html: resText
                 });
            }
        }

    } catch (error) {
        console.error(error);
        Swal.fire('Lỗi JS', 'Có lỗi xảy ra khi xử lý ảnh!', 'error');
    }
}

// Hàm nén ảnh
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
                    const newFile = new File([blob], file.name.replace(/\.[^/.]+$/, "") + ".webp", {
                        type: 'image/webp',
                        lastModified: Date.now()
                    });
                    resolve(newFile);
                }, 'image/webp', quality);
            };
        };
    });
}

// =========================================================
// PHẦN 2: GIAO DIỆN & AUTO FILL
// =========================================================

function checkGridHeight() {
    const grid = document.getElementById('imageGrid');
    const btn = document.getElementById('toggleGridBtn');
    if(!grid || !btn) return;
    const items = grid.querySelectorAll('.sortable-item');
    if (items.length > 4) btn.classList.remove('d-none');
    else { btn.classList.add('d-none'); grid.classList.remove('expanded'); resetToggleBtn(); }
}

function toggleGrid() {
    const grid = document.getElementById('imageGrid');
    const txt = document.getElementById('toggleText');
    const icon = document.querySelector('#toggleGridBtn i');
    if (grid.classList.contains('expanded')) {
        grid.classList.remove('expanded'); txt.innerText = 'Xem thêm ảnh'; icon.className = 'ph-bold ph-caret-down';
        grid.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
        grid.classList.add('expanded'); txt.innerText = 'Thu gọn'; icon.className = 'ph-bold ph-caret-up';
    }
}

function resetToggleBtn() {
    const txt = document.getElementById('toggleText');
    const icon = document.querySelector('#toggleGridBtn i');
    if(txt) txt.innerText = 'Xem thêm ảnh';
    if(icon) icon.className = 'ph-bold ph-caret-down';
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
    checkGridHeight();
}

function autoParseEverything(text) {
    if (!text) return;
    let workingText = text; let hasChange = false;

    // Giá thuê
    const rentRegex = /([0-9]+[.,]?[0-9]*)\s*(k|m|tr)?\s*\/\s*(h|g|giờ|ngày|d|day)/i;
    const rentMatch = workingText.match(rentRegex);
    if (rentMatch) {
        let val = parseFloat(rentMatch[1].replace(',', '.'));
        let mag = rentMatch[2] ? rentMatch[2].toLowerCase() : 'k'; 
        let unitStr = rentMatch[3].toLowerCase();
        let money = (mag === 'm' || mag === 'tr') ? val * 1000000 : val * 1000;
        let unitVal = ['ngày', 'd', 'day'].includes(unitStr) ? 2 : 1;

        if (money > 0) {
            document.querySelector('input[name="price_rent"]').value = money.toLocaleString('vi-VN').replace(/,/g, '.');
            document.querySelector('select[name="unit"]').value = unitVal;
            const swRent = document.getElementById('switchRent');
            if (swRent && !swRent.checked) { swRent.checked = true; toggleSections(); }
            let escapedMatch = rentMatch[0].replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); 
            workingText = workingText.replace(new RegExp(`(thuê|giá thuê|price)?\\s*[:.-]?\\s*${escapedMatch}`, 'gi'), ' ').trim();
            hasChange = true;
        }
    }

    // Giá bán
    const sellRegex = /([0-9]+[.,]?[0-9]*)\s*(m|tr|k)\s*([0-9]*)/i;
    const sellMatch = workingText.match(sellRegex);
    if (sellMatch) {
        let val = parseFloat(sellMatch[1].replace(',', '.'));
        let mag = sellMatch[2].toLowerCase();
        let dec = sellMatch[3];
        let money = 0;
        if (mag === 'k') money = val * 1000;
        else if (mag === 'm' || mag === 'tr') {
            money = val * 1000000;
            if (dec && dec.length > 0) {
                if (dec.length === 1) money += parseInt(dec) * 100000;
                else if (dec.length === 2) money += parseInt(dec) * 10000;
            }
        }
        if (money > 0) {
            document.querySelector('input[name="price"]').value = money.toLocaleString('vi-VN').replace(/,/g, '.');
            const swSell = document.getElementById('switchSell');
            if (swSell && !swSell.checked) { swSell.checked = true; toggleSections(); }
            let escapedMatch = sellMatch[0].replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            workingText = workingText.replace(new RegExp(`(bán|giá bán|giá)?\\s*[:.-]?\\s*${escapedMatch}`, 'gi'), ' ').trim();
            hasChange = true;
        }
    }

    if (hasChange) {
        workingText = workingText.replace(/^(mã|ms|code|acc|id|account)\s*[:.-]?\s*/gi, '');
        workingText = workingText.replace(/^[:.-]+|[:.-]+$/g, '').trim();
        workingText = workingText.replace(/\s\s+/g, ' ');
        document.querySelector('input[name="title"]').value = workingText;
    }
}

// =========================================================
// PHẦN 3: THƯ VIỆN & HELPER
// =========================================================

function openLibrary() {
    const grid = document.getElementById('libGrid');
    grid.className = 'nft-grid-5 p-3';
    selectedLibFiles = []; 
    const modalEl = document.getElementById('libraryModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
    document.getElementById('libGrid').innerHTML = '';
    currentPage = 1;
    hasMore = true;
    isLoading = false; 
    fetchLibImages(1);
}

async function fetchLibImages(page) {
    if (isLoading) return;
    isLoading = true;
    const loadingEl = document.getElementById('libLoading');
    if (loadingEl) loadingEl.classList.remove('d-none');
    try {
        const response = await fetch(`get_images.php?page=${page}&_t=${Date.now()}`);
        const data = await response.json();
        if (data.status === 'success') {
            const grid = document.getElementById('libGrid');
            data.data.forEach(filename => {
                const div = document.createElement('div');
                div.className = 'nft-card';
                div.dataset.filename = filename;
                div.innerHTML = `<img src="../uploads/${filename}" loading="lazy"><div class="nft-order-badge"></div>`;
                div.onclick = function() { toggleLibFile(filename); };
                grid.appendChild(div);
            });
            refreshSelectionBadges();
            hasMore = data.has_more;
            currentPage = page;
        }
    } catch (error) { console.error(error); } 
    finally { isLoading = false; if (loadingEl) loadingEl.classList.add('d-none'); }
}

function toggleLibFile(filename) {
    const index = selectedLibFiles.indexOf(filename);
    if (index === -1) selectedLibFiles.push(filename);
    else selectedLibFiles.splice(index, 1);
    refreshSelectionBadges();
}

function refreshSelectionBadges() {
    const cards = document.querySelectorAll('.nft-card');
    cards.forEach(card => {
        const fname = card.dataset.filename;
        const index = selectedLibFiles.indexOf(fname);
        const badge = card.querySelector('.nft-order-badge');
        if (index !== -1) {
            card.classList.add('active');
            badge.innerText = index + 1;
            badge.style.transform = 'scale(1)';
            badge.style.opacity = '1';
        } else {
            card.classList.remove('active');
            badge.innerText = '';
            badge.style.transform = 'scale(0)';
            badge.style.opacity = '0';
        }
    });
    updateSelectedCount();
}

function updateSelectedCount() {
    const el = document.getElementById('selectedCount');
    if (el) el.innerText = `Đã chọn: ${selectedLibFiles.length} ảnh`;
}

function confirmLibrarySelection() {
    selectedLibFiles.forEach(filename => {
        const uid = uuidv4();
        addToGrid(uid, `../uploads/${filename}`, 'lib', filename);
    });
    const modalEl = document.getElementById('libraryModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    modal.hide();
    checkGridHeight();
}

function uuidv4() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
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