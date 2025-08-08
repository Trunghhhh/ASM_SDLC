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
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Lỗi kết nối cơ sở dữ liệu: ' . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        // Lấy danh sách đặt phòng
        try {
            $sql = "
                SELECT 
                    b.BookingID,
                    b.UserID,
                    b.RoomID,
                    b.CheckIn,
                    b.CheckOut,
                    b.TotalPrice,
                    b.Status,
                    u.FullName,
                    r.RoomNumber
                FROM asm_bookings b
                JOIN asm_user u ON b.UserID = u.UserID
                JOIN asm_rooms r ON b.RoomID = r.RoomID
                ORDER BY b.BookingID DESC
            ";
            $stmt = $pdo->query($sql);
            $bookings = $stmt->fetchAll();
            echo json_encode($bookings);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Lỗi cơ sở dữ liệu khi lấy dữ liệu: ' . $e->getMessage()]);
        }
        break;

    case 'PUT':
        // Cập nhật thông tin đặt phòng
        if (empty($data['BookingID'])) {
            http_response_code(400);
            echo json_encode(['message' => 'ID đặt phòng không được cung cấp.']);
            exit;
        }
        try {
            $stmt = $pdo->prepare("
                UPDATE asm_bookings 
                SET UserID = ?, RoomID = ?, CheckIn = ?, CheckOut = ?, TotalPrice = ?, Status = ?
                WHERE BookingID = ?
            ");
            $stmt->execute([
                $data['UserID'],
                $data['RoomID'],
                $data['CheckIn'],
                $data['CheckOut'],
                $data['TotalPrice'],
                $data['Status'],
                $data['BookingID']
            ]);
            echo json_encode(['message' => 'Cập nhật đặt phòng thành công.']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Lỗi cơ sở dữ liệu khi cập nhật: ' . $e->getMessage()]);
        }
        break;

    case 'DELETE':
        // Xóa đặt phòng
        if (empty($data['BookingID'])) {
            http_response_code(400);
            echo json_encode(['message' => 'ID đặt phòng không được cung cấp.']);
            exit;
        }
        try {
            $stmt = $pdo->prepare("DELETE FROM asm_bookings WHERE BookingID = ?");
            $stmt->execute([$data['BookingID']]);
            echo json_encode(['message' => 'Xóa đặt phòng thành công.']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Lỗi cơ sở dữ liệu khi xóa: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Phương thức không được hỗ trợ.']);
        break;
}
?>