<?php
session_start();
header('Content-Type: application/json');

// --- THÔNG SỐ KẾT NỐI CƠ SỞ DỮ LIỆU ---
$servername = "localhost";
$username = "root";
$password = "";
$database = "asm";

// --- TẠO KẾT NỐI VÀ XỬ LÝ LỖI KẾT NỐI ---
$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'error' => 'Lỗi kết nối cơ sở dữ liệu: ' . $conn->connect_error,
        'debug' => 'Vui lòng đảm bảo MySQL server đang chạy và các thông số kết nối (username, password, database) là chính xác.'
    ]);
    exit();
}
$conn->set_charset("utf8mb4");

// --- KIỂM TRA NGƯỜI DÙNG ĐÃ ĐĂNG NHẬP CHƯA ---
if (!isset($_SESSION['userid'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Bạn chưa đăng nhập. Vui lòng đăng nhập để xem hồ sơ.']);
    $conn->close();
    exit();
}

$user_id = $_SESSION['userid'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_user_info':
        // Lấy thông tin người dùng
        $stmt = $conn->prepare("SELECT UserID, FullName, Email, PhoneNumber, Address FROM asm_user WHERE UserID = ?");
        if ($stmt === false) {
            http_response_code(500);
            echo json_encode(['error' => 'Lỗi chuẩn bị câu lệnh SQL: ' . $conn->error]);
            break;
        }
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_info = $result->fetch_assoc();
        
        if ($user_info) {
            echo json_encode($user_info);
        } else {
            echo json_encode(['error' => 'Không tìm thấy thông tin người dùng.']);
        }
        $stmt->close();
        break;

    case 'update_user_info':
        // Cập nhật thông tin người dùng
        $fullname = $_POST['fullname'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';

        $stmt = $conn->prepare("UPDATE asm_user SET FullName = ?, PhoneNumber = ?, Address = ? WHERE UserID = ?");
        if ($stmt === false) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL: ' . $conn->error]);
            break;
        }
        $stmt->bind_param("sssi", $fullname, $phone, $address, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Cập nhật thông tin thành công.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật thông tin: ' . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'get_bookings':
        // Lấy lịch sử đặt phòng của người dùng
        $sql = "SELECT b.BookingID, r.RoomNumber, b.CheckIn, b.CheckOut, b.TotalPrice, b.Status 
                FROM asm_bookings b
                JOIN asm_rooms r ON b.RoomID = r.RoomID
                WHERE b.UserID = ?
                ORDER BY b.CheckIn DESC";

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            http_response_code(500);
            echo json_encode(['error' => 'Lỗi chuẩn bị câu lệnh SQL: ' . $conn->error]);
            break;
        }
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $bookings = [];
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
        $stmt->close();
        echo json_encode($bookings);
        break;

    case 'cancel_booking':
        // Hủy đặt phòng
        $booking_id = $_POST['booking_id'] ?? 0;
        
        $stmt = $conn->prepare("UPDATE asm_bookings SET Status = 'Cancelled' WHERE BookingID = ? AND UserID = ? AND Status = 'Pending'");
        if ($stmt === false) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL: ' . $conn->error]);
            break;
        }
        $stmt->bind_param("ii", $booking_id, $user_id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Hủy đặt phòng thành công.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể hủy đặt phòng. Vui lòng kiểm tra lại trạng thái hoặc mã đặt phòng.']);
        }
        $stmt->close();
        break;
        
    case 'pay_booking':
        // Thanh toán đặt phòng
        $booking_id = $_POST['booking_id'] ?? 0;
        
        $stmt = $conn->prepare("UPDATE asm_bookings SET Status = 'Confirmed' WHERE BookingID = ? AND UserID = ? AND Status = 'Pending'");
        if ($stmt === false) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị câu lệnh SQL: ' . $conn->error]);
            break;
        }
        $stmt->bind_param("ii", $booking_id, $user_id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Thanh toán thành công.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể thanh toán. Vui lòng kiểm tra lại trạng thái hoặc mã đặt phòng.']);
        }
        $stmt->close();
        break;

    default:
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Hành động không hợp lệ.']);
        break;
}

$conn->close();
?>