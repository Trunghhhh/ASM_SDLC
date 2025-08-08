<?php
// rooms.php
header('Content-Type: application/json');

// Hiển thị tất cả lỗi PHP trong quá trình phát triển
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

// Lấy các tham số từ request GET và làm sạch
$checkin_date = $_GET['checkin'] ?? null;
$checkout_date = $_GET['checkout'] ?? null;
$guests = filter_var($_GET['guests'] ?? '', FILTER_VALIDATE_INT);
$room_type = $_GET['roomType'] ?? null;
$min_price = filter_var($_GET['minPrice'] ?? '', FILTER_VALIDATE_FLOAT);
$max_price = filter_var($_GET['maxPrice'] ?? '', FILTER_VALIDATE_FLOAT);
$sort_by = $_GET['sortBy'] ?? 'price_asc';

// Mảng chứa các điều kiện WHERE và các tham số cho prepared statement
$conditions = [];
$params = [];
$types = '';

// Lọc phòng trống dựa trên ngày check-in và check-out
if (!empty($checkin_date) && !empty($checkout_date)) {
    // Câu lệnh con để lấy RoomID của các phòng bị trùng lịch
    // Sửa lại để sử dụng prepared statement an toàn hơn
    $sub_sql = "SELECT RoomID FROM asm_bookings WHERE CheckOut > ? AND CheckIn < ?";
    
    // Để prepared statement hoạt động đúng với subquery, chúng ta phải tách nó ra.
    $stmt_booked = $conn->prepare($sub_sql);
    if ($stmt_booked) {
        $stmt_booked->bind_param('ss', $checkin_date, $checkout_date);
        $stmt_booked->execute();
        $result_booked = $stmt_booked->get_result();

        $booked_room_ids = [];
        while ($row = $result_booked->fetch_assoc()) {
            $booked_room_ids[] = $row['RoomID'];
        }
        $stmt_booked->close();

        // Nếu có phòng đã đặt, thêm điều kiện NOT IN vào câu truy vấn chính
        if (!empty($booked_room_ids)) {
            $placeholders = implode(',', array_fill(0, count($booked_room_ids), '?'));
            $conditions[] = "r.RoomID NOT IN ($placeholders)";
            // Thêm các ID vào mảng params và types
            $params = array_merge($params, $booked_room_ids);
            $types .= str_repeat('i', count($booked_room_ids));
        }
    }
}

// Điều kiện lọc theo số khách
if ($guests !== false && $guests !== null && $guests > 0) {
    // Theo mã HTML của bạn, bạn có các tùy chọn 1, 2, 3 và 4+.
    // Điều kiện này sẽ lọc các phòng có sức chứa >= số khách.
    $conditions[] = "r.Capacity >= ?";
    $params[] = $guests;
    $types .= 'i';
}

// Điều kiện lọc theo loại phòng
if (!empty($room_type)) {
    $conditions[] = "r.Type = ?";
    $params[] = $room_type;
    $types .= 's';
}

// Điều kiện lọc theo khoảng giá
if ($min_price !== false && $min_price !== null) {
    $conditions[] = "r.Price >= ?";
    $params[] = $min_price;
    $types .= 'd';
}

if ($max_price !== false && $max_price !== null) {
    $conditions[] = "r.Price <= ?";
    $params[] = $max_price;
    $types .= 'd';
}

// Xây dựng câu truy vấn chính
$sql_select = "SELECT r.*";
$sql_from = "FROM asm_rooms r";
$sql_join = "";
$sql_orderby = "";

// Cập nhật câu truy vấn cho trường hợp sắp xếp "Đánh giá cao nhất"
if ($sort_by === 'rating_desc') {
    // Giả định bảng asm_report có cột 'Rating' và 'RoomID'
    $sql_select .= ", AVG(ar.Rating) as avg_rating";
    $sql_from .= " LEFT JOIN asm_report ar ON r.RoomID = ar.RoomID";
    $sql_orderby = "GROUP BY r.RoomID ORDER BY avg_rating DESC, r.RoomID DESC";
} else {
    // Xử lý các trường hợp sắp xếp khác
    switch ($sort_by) {
        case 'price_asc':
            $sql_orderby = "ORDER BY r.Price ASC";
            break;
        case 'price_desc':
            $sql_orderby = "ORDER BY r.Price DESC";
            break;
        case 'popular_desc':
            // Giả định bạn có thể đếm số lượng booking để xác định độ phổ biến
            $sql_select .= ", (SELECT COUNT(*) FROM asm_bookings WHERE RoomID = r.RoomID) AS booking_count";
            $sql_orderby = "ORDER BY booking_count DESC";
            break;
        case 'newest_desc':
            // Giả định RoomID là trường tự tăng, đại diện cho phòng mới nhất
            $sql_orderby = "ORDER BY r.RoomID DESC";
            break;
        default:
            $sql_orderby = "ORDER BY r.Price ASC";
    }
}

$sql = "$sql_select $sql_from $sql_join";

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}

$sql .= " " . $sql_orderby;

try {
    $stmt = $conn->prepare($sql);
    
    // Kiểm tra và bind_param nếu có tham số
    if (!empty($types)) {
        // Sử dụng call_user_func_array để bind_param với mảng tham số động
        $a_params[] = & $types;
        for($i = 0; $i < count($params); $i++) {
            $a_params[] = & $params[$i];
        }
        call_user_func_array(array($stmt, 'bind_param'), $a_params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $rooms = [];
    while ($row = $result->fetch_assoc()) {
        // Giả định rằng cột 'ImageURL' trong CSDL lưu các URL cách nhau bằng dấu phẩy
        if (isset($row['ImageURL'])) {
            $row['ImageURLs'] = explode(',', $row['ImageURL']);
        }
        
        // Thêm trường tags để khớp với HTML/JS của bạn
        $row['tags'] = [];
        if ($row['Availability'] > 0) { // Giả sử có cột Availability
            $row['tags'][] = 'available';
        }
        // Thêm logic để xác định các tag khác như 'popular', 'new', 'discount', 'luxury'
        // Dựa trên dữ liệu trong CSDL của bạn
        if (isset($row['booking_count']) && $row['booking_count'] > 5) {
             $row['tags'][] = 'popular';
        }
        // Ví dụ: tag 'new' nếu RoomID là một trong những ID lớn nhất
        if ($row['RoomID'] > 10) {
            $row['tags'][] = 'new';
        }
        // Ví dụ: tag 'discount' nếu giá hiện tại < giá gốc
        if (isset($row['OriginalPrice']) && $row['OriginalPrice'] > $row['Price']) {
            $row['tags'][] = 'discount';
        }
        // Ví dụ: tag 'luxury' nếu giá > 5.000.000 VNĐ
        if ($row['Price'] > 5000000) {
            $row['tags'][] = 'luxury';
        }

        // Tạo cấu trúc dữ liệu trả về giống như trong HTML
        $rooms[] = [
            'RoomID' => $row['RoomID'],
            'Type' => $row['Type'],
            'Capacity' => $row['Capacity'],
            'Price' => $row['Price'],
            'Description' => $row['Description'],
            'ImageURL' => isset($row['ImageURLs']) ? $row['ImageURLs'][0] : 'default_image.jpg',
            'ImageURLs' => $row['ImageURLs'] ?? [],
            'Tags' => $row['tags'],
            'Rating' => $row['avg_rating'] ?? 'N/A', // Sử dụng avg_rating nếu có
            // Thêm các trường dữ liệu khác cần thiết cho giao diện người dùng
        ];
    }

    echo json_encode([
        'status' => 'success', 
        'data' => $rooms,
        'count' => count($rooms)
    ]);

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    if ($conn) {
        $conn->close();
    }
}
?>