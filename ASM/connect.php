<?php
// connect.php

// Bắt đầu session cho tất cả các trang
session_start();

// Thông tin kết nối cơ sở dữ liệu
$servername = "localhost"; // Tên máy chủ MySQL
$username = "root";       // Tên người dùng MySQL
$password = "";           // Mật khẩu MySQL
$database = "asm";        // Tên cơ sở dữ liệu

// Tạo kết nối bằng phương pháp hướng đối tượng
$conn = new mysqli($servername, $username, $password, $database);

// Kiểm tra kết nối
if ($conn->connect_error) {
    // Dừng chương trình và hiển thị lỗi nếu kết nối thất bại
    die("Kết nối CSDL thất bại: " . $conn->connect_error);
}

// Thiết lập bộ ký tự cho kết nối để hỗ trợ tiếng Việt
$conn->set_charset("utf8mb4");

// Bạn có thể bỏ dòng dưới đây nếu không cần kiểm tra kết nối thành công.
// echo "Kết nối CSDL thành công!";
?>