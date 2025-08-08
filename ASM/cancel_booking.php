<?php
// Tích hợp file kết nối
require_once 'connect.php';
session_start();

// Kiểm tra xem người dùng đã đăng nhập chưa và có phải là phương thức POST không
if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: login.php");
    exit();
}

$booking_id = $_POST['booking_id'];
$user_id = $_SESSION['user_id'];

// Cập nhật trạng thái đơn hàng thành 'Cancelled'
$cancel_sql = "UPDATE asm_bookings SET Status = 'Cancelled' WHERE BookingID = ? AND UserID = ?";
$cancel_stmt = $conn->prepare($cancel_sql);
$cancel_stmt->bind_param("ii", $booking_id, $user_id);

if ($cancel_stmt->execute()) {
    echo "<script>alert('Đơn đặt phòng đã được hủy thành công!'); window.location.href='profile.php';</script>";
} else {
    echo "<script>alert('Có lỗi xảy ra khi hủy đơn đặt phòng.'); window.location.href='profile.php';</script>";
}

$cancel_stmt->close();
?>