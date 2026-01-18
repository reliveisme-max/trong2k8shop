<!-- includes/floating_buttons.php -->
<style>
.floating-contact {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 9999;

    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    /* Khoảng cách rộng thoáng */

    background: #ffffff;
    padding: 10px 30px;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    border: 1px solid rgba(0, 0, 0, 0.05);

    min-width: 320px;
}

.contact-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-decoration: none !important;
    transition: all 0.2s ease;
    background: transparent;
    border: none;
    min-width: 60px;
}

/* Icon nét mảnh (Light) nhưng màu đậm (#333) */
.contact-btn i {
    font-size: 28px;
    /* Tăng size lên xíu cho nét mảnh dễ nhìn */
    color: #333;
    /* MÀU ĐẬM THEO YÊU CẦU */
    margin-bottom: 4px;
    transition: 0.2s;
}

/* Chữ bên dưới */
.btn-text {
    font-size: 11px;
    font-weight: 600;
    /* Giảm độ đậm chữ lại 1 chút cho thanh thoát */
    color: #333;
    /* Đồng bộ màu #333 */
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: 0.2s;
}

/* Hiệu ứng Hover */
.contact-btn:hover {
    transform: translateY(-3px);
}

.btn-home:hover i,
.btn-home:hover .btn-text {
    color: #F2A900;
}

.btn-fb:hover i,
.btn-fb:hover .btn-text {
    color: #1877F2;
}

.btn-zalo:hover i,
.btn-zalo:hover .btn-text {
    color: #0068FF;
}

.btn-call:hover i,
.btn-call:hover .btn-text {
    color: #EF4444;
}

@media (max-width: 768px) {
    .floating-contact {
        bottom: 15px;
        padding: 8px 15px;
        gap: 10px;
        width: 92%;
        min-width: auto;
    }

    .contact-btn {
        flex: 1;
    }

    .btn-text {
        font-size: 10px;
    }
}
</style>

<div class="floating-contact">
    <!-- 1. Trang chủ -->
    <a href="./" class="contact-btn btn-home">
        <!-- Đổi ph-bold thành ph-light -->
        <i class="ph-light ph-house"></i>
        <span class="btn-text">Home</span>
    </a>

    <!-- 2. Facebook -->
    <a href="https://www.facebook.com/truong.ttv.1999" target="_blank" class="contact-btn btn-fb">
        <i class="ph-light ph-facebook-logo"></i>
        <span class="btn-text">Facebook</span>
    </a>

    <!-- 3. Zalo -->
    <a href="https://zalo.me/0901999222" target="_blank" class="contact-btn btn-zalo">
        <i class="ph-light ph-chat-circle-dots"></i>
        <span class="btn-text">Zalo</span>
    </a>

    <!-- 4. Gọi điện -->
    <a href="tel:0901999222" class="contact-btn btn-call">
        <i class="ph-light ph-phone-call"></i>
        <span class="btn-text">Gọi Ngay</span>
    </a>
</div>