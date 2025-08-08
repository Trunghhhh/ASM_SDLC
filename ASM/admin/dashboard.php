<?php
session_start();

// Kiểm tra xem người dùng đã đăng nhập và có vai trò 'Admin' hay không
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'Admin') {
    header('Location: ../login.php'); // Chuyển hướng về trang đăng nhập
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

    // --- 1. LẤY DỮ LIỆU THỐNG KÊ ---
    $totalRooms = $pdo->query("SELECT COUNT(*) FROM asm_rooms")->fetchColumn();
    $totalCustomers = $pdo->query("SELECT COUNT(*) FROM asm_user WHERE Role = 'Customer'")->fetchColumn();
    $today = date('Y-m-d');
    $stmtBookings = $pdo->prepare("SELECT COUNT(*) FROM asm_bookings WHERE DATE(CheckIn) = ?");
    $stmtBookings->execute([$today]);
    $todayBookings = $stmtBookings->fetchColumn();

    $firstDayOfMonth = date('Y-m-01');
    $stmtRevenue = $pdo->prepare("SELECT SUM(Amount) FROM asm_payments WHERE PaymentDate >= ?");
    $stmtRevenue->execute([$firstDayOfMonth]);
    $monthlyRevenue = number_format($stmtRevenue->fetchColumn() ?: 0, 0, ',', '.') . ' VNĐ';
    
    // --- 2. LẤY DỮ LIỆU ĐẶT PHÒNG GẦN ĐÂY ---
    $sqlRecentBookings = "
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
    $stmtRecentBookings = $pdo->query($sqlRecentBookings);
    $recentBookings = $stmtRecentBookings->fetchAll();

    // --- 3. TẠO HTML ĐỘNG CHO BẢNG ĐẶT PHÒNG ---
    $recentBookingsHtml = '';
    if (empty($recentBookings)) {
        $recentBookingsHtml = '<tr><td colspan="7" class="px-4 py-2 text-center text-gray-500">Không có đặt phòng nào gần đây.</td></tr>';
    } else {
        foreach ($recentBookings as $booking) {
            $statusClass = '';
            switch ($booking['Status']) {
                case 'Confirmed':
                    $statusClass = 'bg-green-100 text-green-800';
                    break;
                case 'Pending':
                    $statusClass = 'bg-yellow-100 text-yellow-800';
                    break;
                case 'Canceled':
                    $statusClass = 'bg-red-100 text-red-800';
                    break;
                default:
                    $statusClass = 'bg-gray-100 text-gray-800';
                    break;
            }
            $recentBookingsHtml .= '<tr>';
            $recentBookingsHtml .= '<td class="px-4 py-2 text-gray-700">' . htmlspecialchars($booking['BookingID']) . '</td>';
            $recentBookingsHtml .= '<td class="px-4 py-2 text-gray-700">' . htmlspecialchars($booking['FullName']) . '</td>';
            $recentBookingsHtml .= '<td class="px-4 py-2 text-gray-700">' . htmlspecialchars($booking['RoomNumber']) . '</td>';
            $recentBookingsHtml .= '<td class="px-4 py-2 text-gray-700">' . htmlspecialchars((new DateTime($booking['CheckIn']))->format('d/m/Y')) . '</td>';
            $recentBookingsHtml .= '<td class="px-4 py-2 text-gray-700">' . htmlspecialchars((new DateTime($booking['CheckOut']))->format('d/m/Y')) . '</td>';
            $recentBookingsHtml .= '<td class="px-4 py-2 text-gray-700">' . htmlspecialchars(number_format($booking['TotalPrice'], 0, ',', '.')) . ' VNĐ</td>';
            $recentBookingsHtml .= '<td class="px-4 py-2 text-gray-700"><span class="inline-block px-2 py-1 rounded-full text-xs font-semibold ' . $statusClass . '">' . htmlspecialchars($booking['Status']) . '</span></td>';
            $recentBookingsHtml .= '</tr>';
        }
    }

    // --- 4. ĐỌC TEMPLATE VÀ THAY THẾ PLACEHOLDER ---
    $template = file_get_contents('dashboard.html');
    $template = str_replace('', htmlspecialchars($totalRooms), $template);
    $template = str_replace('', htmlspecialchars($todayBookings), $template);
    $template = str_replace('', htmlspecialchars($totalCustomers), $template);
    $template = str_replace('', htmlspecialchars($monthlyRevenue), $template);
    $template = str_replace('', $recentBookingsHtml, $template);
    
    echo $template;

} catch (PDOException $e) {
    // Xử lý lỗi nếu có, hiển thị thông báo lỗi thân thiện
    echo "Lỗi: " . $e->getMessage();
}
?>