<?php
// Bắt đầu một phiên làm việc để lưu trữ thông tin người dùng sau khi đăng nhập thành công.
session_start();

// Thiết lập thông tin kết nối cơ sở dữ liệu.
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "asm"; // Đã sửa tên CSDL để khớp với cấu trúc của bạn

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối CSDL thất bại: " . $conn->connect_error);
}

// Khởi tạo biến để lưu trữ lỗi
$login_err = "";

// Xử lý khi form đăng nhập được gửi đi bằng phương thức POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Lấy dữ liệu từ form
    $email = $_POST["email"] ?? '';
    $input_password = $_POST["password"] ?? '';
    $input_role = $_POST["role"] ?? '';
    $admin_code = $_POST["admin_code"] ?? '';

    // Chuẩn bị câu lệnh SQL an toàn để lấy thông tin người dùng dựa trên email
    $sql = "SELECT UserID, FullName, Email, Password, Role FROM asm_user WHERE Email = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            // Lấy dữ liệu người dùng
            $user = $result->fetch_assoc();
            
            // Kiểm tra mật khẩu đã băm bằng password_verify()
            if (password_verify($input_password, $user['Password'])) {
                // Mật khẩu đúng, bây giờ kiểm tra vai trò
                if ($user['Role'] !== $input_role) {
                    $login_err = "Vai trò không khớp với người dùng này.";
                } elseif ($user['Role'] === 'Admin' && $admin_code !== 'TRUKI') {
                    // Cảnh báo: Sử dụng mã cứng 'TRUKI' không phải là cách bảo mật tốt
                    $login_err = "Mã admin không chính xác.";
                } else {
                    // Đăng nhập thành công, lưu thông tin vào session
                    $_SESSION["loggedin"] = true;
                    $_SESSION["userid"] = $user['UserID'];
                    $_SESSION["fullname"] = $user['FullName'];
                    $_SESSION["email"] = $user['Email'];
                    $_SESSION["role"] = $user['Role'];

                    // Chuyển hướng người dùng đến trang phù hợp dựa trên vai trò
                    if ($_SESSION["role"] === "Admin") {
                        header("location: admin/dashboard.html");
                    } else { // Tất cả các vai trò khác (Customer)
                        header("location: main.html");
                    }
                    $stmt->close();
                    $conn->close();
                    exit; // Dừng script ngay lập tức sau khi chuyển hướng
                }
            } else {
                $login_err = "Mật khẩu không chính xác.";
            }
        } else {
            $login_err = "Không tìm thấy người dùng với email này.";
        }
        $stmt->close();
    } else {
        $login_err = "Có lỗi xảy ra. Vui lòng thử lại sau.";
    }
}

// Nếu có lỗi, lưu vào session và chuyển hướng trở lại trang đăng nhập.
// Điều này cũng sẽ xảy ra nếu người dùng truy cập trực tiếp file này mà không qua form POST.
if (!empty($login_err)) {
    $_SESSION['login_error'] = $login_err;
}

$conn->close();
header("location: login.html");
exit();
?>