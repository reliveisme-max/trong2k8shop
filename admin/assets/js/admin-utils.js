/**
 * admin/assets/js/admin-utils.js
 * CHỨA CÁC HÀM TIỆN ÍCH DÙNG CHUNG CHO TOÀN BỘ ADMIN
 */

// 1. CẤU HÌNH TOAST (Thông báo nhỏ góc màn hình)
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 1500,
    timerProgressBar: true
});

function showToast(icon, title) {
    Toast.fire({ icon: icon, title: title });
}

// 2. XỬ LÝ NHẬP GIÁ TẮT (VD: 5m -> 5.000.000)
// Dùng cho sự kiện onblur="..."
function parsePriceShortcut(input) {
    let val = input.value.toLowerCase().trim();
    if (!val) return;

    // Regex bắt các dạng: 20m, 1tr5, 500k
    const regex = /^([0-9]+[.,]?[0-9]*)(k|m|tr)([0-9]*)$/;
    const match = val.match(regex);

    if (match) {
        let mainNum = parseFloat(match[1].replace(',', '.'));
        let unit = match[2];
        let decimalPart = match[3];
        let money = 0;

        if (unit === 'k') {
            money = mainNum * 1000;
        } else if (unit === 'm' || unit === 'tr') {
            money = mainNum * 1000000;
            
            // Xử lý số lẻ sau m (VD: 1m5 -> 1.500.000)
            if (decimalPart && decimalPart.length > 0) {
                if (decimalPart.length === 1) money += parseInt(decimalPart) * 100000;
                else if (decimalPart.length === 2) money += parseInt(decimalPart) * 10000;
                else if (decimalPart.length === 3) money += parseInt(decimalPart) * 1000;
            }
        }

        if (money > 0) {
            input.value = money.toLocaleString('vi-VN').replace(/,/g, '.');
        }
    } else {
        // Format lại số thường (xóa ký tự lạ)
        let value = input.value.replace(/\D/g, '');
        if (value !== '') {
            input.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }
    }
}

// 3. XỬ LÝ NÚT GẠT TRẠNG THÁI (SWITCH)
// Dùng ở trang Danh sách (index.php)
function toggleStatus(el, id) {
    const status = el.checked ? 1 : 0;
    
    // Gọi API cập nhật
    fetch('api/toggle_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, status: status })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            showToast('success', 'Đã cập nhật trạng thái!');
        } else {
            Swal.fire('Lỗi', data.msg, 'error');
            el.checked = !el.checked; // Trả lại trạng thái cũ nếu lỗi
        }
    })
    .catch(err => {
        console.error(err);
        el.checked = !el.checked;
        showToast('error', 'Lỗi kết nối!');
    });
}

// 4. TẠO ID NGẪU NHIÊN (UUID)
// Dùng để định danh file ảnh khi upload
function uuidv4() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

// 5. NÉN ẢNH SANG WEBP (Promise)
// Dùng trước khi upload để giảm dung lượng
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
                
                // Tính toán tỷ lệ resize
                if (width > maxWidth) {
                    height = Math.round((height * maxWidth) / width);
                    width = maxWidth;
                }
                
                // Vẽ lên Canvas
                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);
                
                // Xuất ra Blob (WebP)
                canvas.toBlob((blob) => {
                    const newFileName = file.name.replace(/\.[^/.]+$/, "") + ".webp";
                    const newFile = new File([blob], newFileName, { 
                        type: 'image/webp', 
                        lastModified: Date.now() 
                    });
                    resolve(newFile);
                }, 'image/webp', quality);
            };
        };
    });
}