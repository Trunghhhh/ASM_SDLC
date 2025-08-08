<?php
// booking.php
header('Content-Type: application/json');

// Hiển thị tất cả lỗi PHP trong quá trình phát triển.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- KẾT NỐI CƠ SỞ DỮ LIỆU TRỰC TIẾP ---
// Thay thế các giá trị dưới đây bằng thông tin CSDL của bạn
$servername = "localhost";
$username = "root";
$password = ""; // Nếu không có mật khẩu, để trống
$dbname = "asm";

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Lỗi kết nối CSDL: ' . $conn->connect_error]);
    exit();
}
// --- KẾT THÚC KHỐI KẾT NỐI ---

// Kiểm tra phương thức gửi dữ liệu
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Lấy và làm sạch dữ liệu từ form
    $room_id = filter_var($_POST['room_id'] ?? '', FILTER_VALIDATE_INT);
    $checkin_date = $_POST['checkin_date'] ?? '';
    $checkout_date = $_POST['checkout_date'] ?? '';
    $total_price = filter_var($_POST['total_price'] ?? '', FILTER_VALIDATE_FLOAT);
    $fullName = trim($_POST['fullName'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $phone = trim($_POST['phone'] ?? '');
    
    // Kiểm tra dữ liệu đầu vào cơ bản
    if (!$room_id || !$checkin_date || !$checkout_date || !$total_price || empty($fullName) || !$email || empty($phone)) {
        http_response_code(400); // Bad Request
        echo json_encode([
            'status' => 'error', 
            'message' => 'Dữ liệu không hợp lệ. Vui lòng điền đầy đủ các trường bắt buộc.'
        ]);
        exit();
    }
    
    // Kiểm tra ngày tháng
    $checkin_datetime = new DateTime($checkin_date);
    $checkout_datetime = new DateTime($checkout_date);
    
    if ($checkout_datetime <= $checkin_datetime) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Ngày trả phòng phải sau ngày nhận phòng.']);
        exit();
    }

    // Bắt đầu giao dịch để đảm bảo các câu lệnh SQL chạy cùng nhau
    $conn->begin_transaction();

    try {
        // --- BƯỚC 1: KIỂM TRA PHÒNG CÓ SẴN KHÔNG ---
        // Sửa lại câu lệnh SQL để kiểm tra sự chồng chéo lịch đặt phòng một cách chính xác.
        // Điều kiện mới kiểm tra xem ngày nhận phòng mới có trước ngày trả phòng cũ
        // VÀ ngày trả phòng mới có sau ngày nhận phòng cũ hay không.
        $sql = "SELECT COUNT(*) FROM asm_bookings WHERE RoomID = ? 
                AND CheckOut > ? AND CheckIn < ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $room_id, $checkin_date, $checkout_date);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_array();
        $bookings_count = $row[0];
        $stmt->close();
        
        if ($bookings_count > 0) {
            $conn->rollback();
            http_response_code(409); // Conflict
            echo json_encode([
                'status' => 'error', 
                'message' => 'Phòng đã có người đặt trong khoảng thời gian này. Vui lòng chọn phòng hoặc ngày khác.'
            ]);
            exit();
        }

        // --- BƯỚC 2: TÌM HOẶC TẠO USER ---
        $sql = "SELECT UserID FROM asm_user WHERE Email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        $userID = 0;
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $userID = $user['UserID'];
        } else {
            $sql = "INSERT INTO asm_user (FullName, Email, PhoneNumber, RoleID) VALUES (?, ?, ?, 2)"; // RoleID 2 là Customer
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $fullName, $email, $phone);
            $stmt->execute();
            $userID = $stmt->insert_id;
        }
        $stmt->close();

        // --- BƯỚC 3: TẠO BẢN GHI ĐẶT PHÒNG ---
        $sql = "INSERT INTO asm_bookings (UserID, RoomID, CheckIn, CheckOut, TotalPrice, Status) VALUES (?, ?, ?, ?, ?, 'Pending')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iissd", $userID, $room_id, $checkin_date, $checkout_date, $total_price);
        $stmt->execute();
        $booking_id = $stmt->insert_id;
        $stmt->close();
        
        // --- BƯỚC 4: CẬP NHẬT TRẠNG THÁI PHÒNG (Optional) ---
        // Bạn có thể bỏ comment đoạn code này nếu muốn cập nhật trạng thái phòng ngay lập tức.
        // $sql = "UPDATE asm_rooms SET Status = 'Booked' WHERE RoomID = ?";
        // $stmt = $conn->prepare($sql);
        // $stmt->bind_param("i", $room_id);
        // $stmt->execute();
        // $stmt->close();

        // Hoàn tất giao dịch
        $conn->commit();

        echo json_encode([
            'status' => 'success', 
            'message' => 'Đặt phòng thành công! Mã đặt phòng của bạn là #' . $booking_id, 
            'booking_id' => $booking_id
        ]);

    } catch (Exception $e) {
        // Nếu có lỗi, hủy bỏ tất cả thay đổi
        $conn->rollback();
        http_response_code(500); // Internal Server Error
        echo json_encode([
            'status' => 'error', 
            'message' => 'Đặt phòng thất bại. Lỗi hệ thống: ' . $e->getMessage()
        ]);
    }

    $conn->close();

} else {
    // Trả về lỗi nếu yêu cầu không phải là POST
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Yêu cầu không hợp lệ.']);
}
?>