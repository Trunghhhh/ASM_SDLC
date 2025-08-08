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
        // Lấy danh sách phòng
        try {
            // Sửa câu lệnh SQL để lấy đúng các cột từ bảng asm_rooms
            $stmt = $pdo->query("SELECT RoomID, RoomNumber, `Type`, Price, Status, ImageURL FROM asm_rooms ORDER BY RoomID DESC");
            $rooms = $stmt->fetchAll();
            echo json_encode($rooms);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Lỗi cơ sở dữ liệu khi lấy dữ liệu: ' . $e->getMessage()]);
        }
        break;

    case 'POST':
        // Thêm phòng mới
        if ($data['action'] === 'add') {
            if (empty($data['RoomNumber']) || empty($data['RoomType']) || empty($data['Price'])) {
                http_response_code(400);
                echo json_encode(['message' => 'Vui lòng điền đầy đủ các trường bắt buộc (Số phòng, Loại phòng, Giá).']);
                exit;
            }
            try {
                // Sửa câu lệnh INSERT để khớp với các cột trong bảng asm_rooms
                $stmt = $pdo->prepare("INSERT INTO asm_rooms (RoomNumber, `Type`, Price, Status, ImageURL) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $data['RoomNumber'],
                    $data['RoomType'],
                    $data['Price'],
                    $data['Status'] ?? 'Available',
                    $data['ImageURL'] ?? null
                ]);
                echo json_encode(['message' => 'Thêm phòng thành công.']);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Lỗi cơ sở dữ liệu khi thêm phòng: ' . $e->getMessage()]);
            }
        }
        break;

    case 'PUT':
        // Cập nhật phòng
        if ($data['action'] === 'edit') {
            if (empty($data['RoomID']) || empty($data['RoomNumber']) || empty($data['RoomType']) || empty($data['Price'])) {
                http_response_code(400);
                echo json_encode(['message' => 'Vui lòng điền đầy đủ các trường bắt buộc (ID phòng, Số phòng, Loại phòng, Giá).']);
                exit;
            }
            try {
                // Sửa câu lệnh UPDATE để khớp với các cột trong bảng asm_rooms
                $stmt = $pdo->prepare("UPDATE asm_rooms SET RoomNumber = ?, `Type` = ?, Price = ?, Status = ?, ImageURL = ? WHERE RoomID = ?");
                $stmt->execute([
                    $data['RoomNumber'],
                    $data['RoomType'],
                    $data['Price'],
                    $data['Status'] ?? 'Available',
                    $data['ImageURL'] ?? null,
                    $data['RoomID']
                ]);
                echo json_encode(['message' => 'Cập nhật phòng thành công.']);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Lỗi cơ sở dữ liệu khi cập nhật: ' . $e->getMessage()]);
            }
        }
        break;

    case 'DELETE':
        // Xóa phòng
        if ($data['action'] === 'delete') {
            if (empty($data['RoomID'])) {
                http_response_code(400);
                echo json_encode(['message' => 'ID phòng không được cung cấp.']);
                exit;
            }
            try {
                $stmt = $pdo->prepare("DELETE FROM asm_rooms WHERE RoomID = ?");
                $stmt->execute([$data['RoomID']]);
                echo json_encode(['message' => 'Xóa phòng thành công.']);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Lỗi cơ sở dữ liệu khi xóa: ' . $e->getMessage()]);
            }
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Phương thức không được hỗ trợ.']);
        break;
}
?>