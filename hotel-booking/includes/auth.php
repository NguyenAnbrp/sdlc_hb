<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user'])) {
    // Lưu thông báo vào session để hiển thị ở trang đăng nhập
    $_SESSION['auth_message'] = 'Vui lòng đăng nhập để tiếp tục.';
    header('Location: /hotel-booking/auth/login.php');
    exit;
}
