<?php
header('Content-Type: application/json');
session_start();

// Kiểm tra xem người dùng đã đăng nhập và có vai trò 'Admin' hay không
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['message' => 'Bạn không có quyền truy cập.']);
    exit;
}

// Thông tin kết nối cơ sở dữ liệu
$host = 'localhost';
$dbname = 'asm';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Lấy tổng số phòng từ bảng asm_rooms
    $stmtRooms = $pdo->query("SELECT COUNT(*) FROM asm_rooms");
    $totalRooms = $stmtRooms->fetchColumn();

    // Lấy tổng số khách hàng (người dùng có vai trò 'Customer') từ bảng asm_user
    $stmtUsers = $pdo->query("SELECT COUNT(*) FROM asm_user WHERE Role = 'Customer'");
    $totalCustomers = $stmtUsers->fetchColumn();

    // Lấy số lượng đặt phòng trong ngày hôm nay từ bảng asm_bookings
    $today = date('Y-m-d');
    $stmtBookings = $pdo->prepare("SELECT COUNT(*) FROM asm_bookings WHERE DATE(CheckIn) = ?");
    $stmtBookings->execute([$today]);
    $todayBookings = $stmtBookings->fetchColumn();

    // Tính tổng doanh thu tháng này từ bảng asm_payments
    $firstDayOfMonth = date('Y-m-01');
    $stmtRevenue = $pdo->prepare("SELECT SUM(Amount) FROM asm_payments WHERE PaymentDate >= ?");
    $stmtRevenue->execute([$firstDayOfMonth]);
    $monthlyRevenue = $stmtRevenue->fetchColumn();
    
    $stats = [
        'totalRooms' => $totalRooms,
        'todayBookings' => $todayBookings,
        'totalCustomers' => $totalCustomers,
        'monthlyRevenue' => $monthlyRevenue
    ];

    echo json_encode($stats);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Lỗi kết nối hoặc truy vấn cơ sở dữ liệu: ' . $e->getMessage()]);
    exit;
}
?>