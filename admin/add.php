<?php
// admin/add.php
require_once '../includes/config.php';
session_start();

// (Tạm thời chưa check đăng nhập để bạn test cho dễ, sau này sẽ thêm vào sau)
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Acc Mới</title>
    <!-- Nhúng Bootstrap 5 Online cho nhanh -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    /* CSS tối ưu cho Mobile */
    body {
        background-color: #f0f2f5;
        padding-bottom: 80px;
    }

    .form-label {
        font-weight: bold;
        color: #333;
    }

    /* Khung upload ảnh đẹp */
    .upload-area {
        border: 2px dashed #cbd5e0;
        background: #fff;
        padding: 20px;
        text-align: center;
        border-radius: 12px;
        cursor: pointer;
        transition: 0.3s;
    }

    .upload-area:hover,
    .upload-area.active {
        border-color: #0d6efd;
        background-color: #f1f8ff;
    }

    /* Hiển thị ảnh xem trước */
    #preview-thumb img {
        width: 100%;
        height: auto;
        border-radius: 8px;
        margin-top: 10px;
    }

    #preview-gallery {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 10px;
    }

    #preview-gallery img {
        width: 70px;
        height: 70px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #ddd;
    }

    /* Nút đăng bài dính ở dưới màn hình điện thoại */
    .sticky-bottom-btn {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        padding: 15px;
        background: white;
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        z-index: 1000;
    }
    </style>
</head>

<body>

    <div class="container mt-3">
        <h4 class="mb-3 text-center text-primary">ĐĂNG ACC PUBG</h4>

        <!-- Form gửi dữ liệu sang process.php -->
        <form action="process.php" method="POST" enctype="multipart/form-data">

            <!-- 1. Tên Acc -->
            <div class="mb-3">
                <label class="form-label">Tiêu đề Acc</label>
                <input type="text" name="title" class="form-control form-control-lg" placeholder="VD: M416 Băng giá..."
                    required>
            </div>

            <!-- 2. Giá tiền -->
            <div class="mb-3">
                <label class="form-label">Giá bán (VNĐ)</label>
                <input type="number" name="price" class="form-control form-control-lg" placeholder="VD: 500000"
                    required>
            </div>

            <!-- 3. Ảnh đại diện (Bắt buộc) -->
            <div class="mb-3">
                <label class="form-label">Ảnh Bìa (1 ảnh)</label>
                <div class="upload-area" onclick="document.getElementById('thumbInput').click()">
                    <div id="preview-thumb-text">👉 Chạm để chọn ảnh bìa</div>
                    <div id="preview-thumb"></div>
                </div>
                <!-- Input ẩn -->
                <input type="file" id="thumbInput" name="thumb" accept="image/*" hidden required
                    onchange="previewSingle(this)">
            </div>

            <!-- 4. Album ảnh (Chọn nhiều) -->
            <div class="mb-3">
                <label class="form-label">Album ảnh chi tiết</label>
                <div class="upload-area" onclick="document.getElementById('galleryInput').click()">
                    <div>👉 Chạm để chọn nhiều ảnh (Album)</div>
                    <div id="preview-gallery"></div>
                </div>
                <!-- Input ẩn - Có thuộc tính multiple -->
                <input type="file" id="galleryInput" name="gallery[]" accept="image/*" multiple hidden
                    onchange="previewGallery(this)">
            </div>

            <!-- 5. Mô tả -->
            <div class="mb-3">
                <label class="form-label">Mô tả thêm</label>
                <textarea name="description" class="form-control" rows="3"
                    placeholder="Rank, Skin súng, Login FB/Twitter..."></textarea>
            </div>

            <!-- Nút Submit (Dính dưới đáy) -->
            <div class="sticky-bottom-btn">
                <button type="submit" name="btn_submit" class="btn btn-primary w-100 btn-lg">ĐĂNG BÁN NGAY</button>
            </div>
        </form>
    </div>

    <!-- JAVASCRIPT XỬ LÝ PREVIEW ẢNH (Không cần reload trang) -->
    <script>
    // 1. Xem trước ảnh bìa
    function previewSingle(input) {
        var previewZone = document.getElementById('preview-thumb');
        var textZone = document.getElementById('preview-thumb-text');

        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                previewZone.innerHTML = '<img src="' + e.target.result + '">';
                textZone.style.display = 'none'; // Ẩn chữ đi
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // 2. Xem trước Album
    function previewGallery(input) {
        var previewZone = document.getElementById('preview-gallery');
        previewZone.innerHTML = ''; // Xóa ảnh cũ nếu chọn lại

        if (input.files) {
            // Biến files thành mảng để lặp
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
    </script>

</body>

</html>