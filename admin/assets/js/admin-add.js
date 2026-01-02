// assets/js/admin-add.js

let currentPage = 1;
let isLoading = false;
let hasMore = true;
let currentMode = ''; // 'thumb' hoặc 'gallery'
let selectedFiles = []; 
let modalEl;
let scrollArea;
let gridEl;
let loadingEl;
let endDataEl;

// Khởi tạo các biến DOM khi trang tải xong
document.addEventListener('DOMContentLoaded', function() {
    modalEl = new bootstrap.Modal(document.getElementById('libraryModal'));
    scrollArea = document.getElementById('scrollArea');
    gridEl = document.getElementById('libGrid');
    loadingEl = document.getElementById('loadingIndicator');
    endDataEl = document.getElementById('endOfData');

    // Lắng nghe sự kiện cuộn
    if (scrollArea) {
        scrollArea.addEventListener('scroll', () => {
            if (scrollArea.scrollTop + scrollArea.clientHeight >= scrollArea.scrollHeight - 50) {
                if (hasMore && !isLoading) {
                    currentPage++;
                    fetchImages(currentPage);
                }
            }
        });
    }
});

// 1. GỌI API LẤY ẢNH
async function fetchImages(page) {
    if (isLoading || !hasMore) return;
    
    isLoading = true;
    if(loadingEl) loadingEl.style.display = 'block';

    try {
        const response = await fetch(`get_images.php?page=${page}`);
        const data = await response.json();

        if (data.status === 'success') {
            hasMore = data.has_more;
            
            data.data.forEach(filename => {
                const div = document.createElement('div');
                div.className = 'lib-item';
                if(selectedFiles.includes(filename)) {
                    div.classList.add('selected');
                }
                div.innerHTML = `<img src="../uploads/${filename}" loading="lazy">`;
                div.onclick = function() { toggleSelect(this, filename); };
                gridEl.appendChild(div);
            });

            if (!hasMore && endDataEl) {
                endDataEl.style.display = 'block';
            }
        }
    } catch (error) {
        console.error("Lỗi tải ảnh:", error);
    } finally {
        isLoading = false;
        if(loadingEl) loadingEl.style.display = 'none';
    }
}

// 2. MỞ MODAL
function openLibrary(mode) {
    currentMode = mode;
    if (gridEl && gridEl.innerHTML.trim() === '') {
        fetchImages(1);
    }
    if(modalEl) modalEl.show();
}

// 3. CHỌN ẢNH (CLICK)
function toggleSelect(el, filename) {
    if (currentMode === 'thumb') {
        document.querySelectorAll('.lib-item').forEach(e => e.classList.remove('selected'));
        el.classList.add('selected');
        selectedFiles = [filename];
    } else {
        if (el.classList.contains('selected')) {
            el.classList.remove('selected');
            selectedFiles = selectedFiles.filter(f => f !== filename);
        } else {
            el.classList.add('selected');
            selectedFiles.push(filename);
        }
    }
}

// 4. XÁC NHẬN CHỌN
function confirmSelection() {
    if (selectedFiles.length === 0) {
        modalEl.hide();
        return;
    }

    if (currentMode === 'thumb') {
        const file = selectedFiles[0];
        document.getElementById('preview-thumb').innerHTML = `<img src="../uploads/${file}">`;
        document.getElementById('inputSelectedThumb').value = file;
        document.getElementById('thumbInput').value = '';
    } else {
        const container = document.getElementById('preview-gallery');
        container.innerHTML = '';
        document.getElementById('inputSelectedGallery').value = JSON.stringify(selectedFiles);
        document.getElementById('galleryInput').value = '';

        selectedFiles.forEach(file => {
            const img = document.createElement('img');
            img.src = `../uploads/${file}`;
            container.appendChild(img);
        });
    }
    modalEl.hide();
}

// 5. PREVIEW KHI UPLOAD MỚI
function previewSingle(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-thumb').innerHTML = '<img src="' + e.target.result + '">';
            document.getElementById('inputSelectedThumb').value = '';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function previewGallery(input) {
    var previewZone = document.getElementById('preview-gallery');
    previewZone.innerHTML = '';
    document.getElementById('inputSelectedGallery').value = '';
    if (input.files) {
        Array.from(input.files).forEach(file => {
            var reader = new FileReader();
            reader.onload = function(e) {
                var img = document.createElement('img');
                img.src = e.target.result;
                previewZone.appendChild(img);
            }
            reader.readAsDataURL(file);
        });
    }
}