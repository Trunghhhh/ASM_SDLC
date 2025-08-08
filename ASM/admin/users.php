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
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Lỗi kết nối cơ sở dữ liệu: ' . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        try {
            // Sửa câu lệnh SQL để lấy cả cột 'Address'
            $stmt = $pdo->query("SELECT UserID, FullName, Email, PhoneNumber, Address, Role FROM asm_user ORDER BY UserID DESC");
            $users = $stmt->fetchAll();
            echo json_encode($users);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Lỗi cơ sở dữ liệu khi lấy dữ liệu: ' . $e->getMessage()]);
        }
        break;

    case 'POST':
        if ($data['action'] === 'add') {
            if (empty($data['FullName']) || empty($data['Email']) || empty($data['Password'])) {
                http_response_code(400);
                echo json_encode(['message' => 'Vui lòng điền đầy đủ các trường bắt buộc (Họ tên, Email, Mật khẩu).']);
                exit;
            }
            try {
                $fullName = $data['FullName'];
                $email = $data['Email'];
                $password = password_hash($data['Password'], PASSWORD_DEFAULT);
                $phone = $data['Phone'] ?? null;
                $address = $data['Address'] ?? null; // Lấy dữ liệu địa chỉ mới
                $role = $data['Role'] ?? 'Customer';
                // Sửa câu lệnh INSERT để thêm cột 'Address'
                $stmt = $pdo->prepare("INSERT INTO asm_user (FullName, Email, Password, PhoneNumber, Address, Role) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$fullName, $email, $password, $phone, $address, $role]);
                echo json_encode(['message' => 'Thêm người dùng thành công.']);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Lỗi cơ sở dữ liệu khi thêm người dùng: ' . $e->getMessage()]);
            }
        }
        break;

    case 'PUT':
        if ($data['action'] === 'edit') {
            if (empty($data['UserID']) || empty($data['FullName']) || empty($data['Email'])) {
                http_response_code(400);
                echo json_encode(['message' => 'Vui lòng điền đầy đủ các trường bắt buộc (Họ tên, Email).']);
                exit;
            }
            try {
                $userID = $data['UserID'];
                $fullName = $data['FullName'];
                $email = $data['Email'];
                $phone = $data['Phone'] ?? null;
                $address = $data['Address'] ?? null; // Lấy dữ liệu địa chỉ mới
                $role = $data['Role'] ?? 'Customer';
                
                if (!empty($data['Password'])) {
                    $password = password_hash($data['Password'], PASSWORD_DEFAULT);
                    // Sửa câu lệnh UPDATE để thêm cột 'Address'
                    $stmt = $pdo->prepare("UPDATE asm_user SET FullName = ?, Email = ?, Password = ?, PhoneNumber = ?, Address = ?, Role = ? WHERE UserID = ?");
                    $stmt->execute([$fullName, $email, $password, $phone, $address, $role, $userID]);
                } else {
                    // Sửa câu lệnh UPDATE để thêm cột 'Address'
                    $stmt = $pdo->prepare("UPDATE asm_user SET FullName = ?, Email = ?, PhoneNumber = ?, Address = ?, Role = ? WHERE UserID = ?");
                    $stmt->execute([$fullName, $email, $phone, $address, $role, $userID]);
                }
                echo json_encode(['message' => 'Cập nhật người dùng thành công.']);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Lỗi cơ sở dữ liệu khi cập nhật: ' . $e->getMessage()]);
            }
        }
        break;

    case 'DELETE':
        if ($data['action'] === 'delete') {
            if (empty($data['UserID'])) {
                http_response_code(400);
                echo json_encode(['message' => 'ID người dùng không được cung cấp.']);
                exit;
            }
            try {
                $userID = $data['UserID'];
                $stmt = $pdo->prepare("DELETE FROM asm_user WHERE UserID = ?");
                $stmt->execute([$userID]);
                echo json_encode(['message' => 'Xóa người dùng thành công.']);
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