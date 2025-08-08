<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['message' => 'Bạn không có quyền truy cập.']);
    exit;
}

$host = 'localhost';
$dbname = 'asm';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Lấy 10 đặt phòng gần đây nhất bằng cách JOIN các bảng
    $sql = "
        SELECT 
            b.BookingID,
            u.FullName,
            r.RoomNumber,
            b.CheckIn,
            b.CheckOut,
            b.TotalPrice,
            b.Status
        FROM asm_bookings b
        JOIN asm_user u ON b.UserID = u.UserID
        JOIN asm_rooms r ON b.RoomID = r.RoomID
        ORDER BY b.BookingID DESC
        LIMIT 10
    ";
    
    $stmt = $pdo->query($sql);
    $recentBookings = $stmt->fetchAll();

    echo json_encode($recentBookings);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Lỗi kết nối hoặc truy vấn cơ sở dữ liệu: ' . $e->getMessage()]);
    exit;
}
?>