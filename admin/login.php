<?php
// admin/login.php - TRANG ĐĂNG NHẬP (HARDCODE - KHÔNG CẦN DATABASE)
session_start();

// --- CẤU HÌNH TÀI KHOẢN ADMIN TẠI ĐÂY ---
$configUser = 'admin';
$configPass = '123456'; // Bạn đổi mật khẩu ở đây nhé
// -----------------------------------------

$error = '';

if (isset($_POST['btn_login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Kiểm tra trùng khớp
    if ($username === $configUser && $password === $configPass) {
        // Đăng nhập thành công -> Lưu session
        $_SESSION['is_admin_logged_in'] = true;
        header("Location: index.php");
        exit;
    } else {
        $error = 'Sai tài khoản hoặc mật khẩu!';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
    body {
        background: #09090b;
        color: #fff;
        font-family: 'Inter', sans-serif;
        height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .login-card {
        background: #18181b;
        padding: 40px;
        border-radius: 16px;
        border: 1px solid #27272a;
        width: 100%;
        max-width: 400px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
    }

    .form-control {
        background: #27272a;
        border: 1px solid transparent;
        color: #fff;
        padding: 12px;
    }

    .form-control:focus {
        background: #27272a;
        border-color: #f59e0b;
        color: #fff;
        box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.3);
    }

    .btn-login {
        background: #f59e0b;
        color: #000;
        font-weight: 700;
        width: 100%;
        padding: 12px;
        border: none;
    }

    .btn-login:hover {
        background: #d97706;
    }
    </style>
</head>

<body>

    <div class="login-card">
        <h3 class="text-center fw-bold mb-4">ADMIN LOGIN</h3>

        <?php if ($error): ?>
        <div class="alert alert-danger py-2 text-center text-sm mb-4"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="mb-2 text-secondary small">Tài khoản</label>
                <input type="text" name="username" class="form-control rounded-3" required placeholder="admin">
            </div>
            <div class="mb-4">
                <label class="mb-2 text-secondary small">Mật khẩu</label>
                <input type="password" name="password" class="form-control rounded-3" required placeholder="••••••">
            </div>
            <button type="submit" name="btn_login" class="btn btn-login rounded-3">ĐĂNG NHẬP</button>
        </form>

        <div class="text-center mt-4">
            <a href="../index.php" class="text-decoration-none text-secondary small">← Về trang chủ Shop</a>
        </div>
    </div>

</body>

</html>