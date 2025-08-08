<?php
// Hiển thị lỗi nếu có
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Kết nối CSDL
include "connect.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $role = "Customer";

    $check_query = "SELECT * FROM asm_user WHERE Email = ?";
    $stmt = mysqli_prepare($conn, $check_query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            echo "<script>alert('❌ Email đã tồn tại.'); window.history.back();</script>";
        } else {
            $insert_query = "INSERT INTO asm_user (FullName, Email, Password, PhoneNumber, Address, Role) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_insert = mysqli_prepare($conn, $insert_query);
            if ($stmt_insert) {
                mysqli_stmt_bind_param($stmt_insert, "ssssss", $fullname, $email, $password, $phone, $address, $role);
                if (mysqli_stmt_execute($stmt_insert)) {
                    echo "<script>alert('✅ Đăng ký thành công!'); window.location='login.html';</script>";
                    exit();
                } else {
                    echo "❌ Lỗi khi thêm người dùng: " . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt_insert);
            }
        }
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conn);
} else {
    echo "Vui lòng gửi dữ liệu từ form.";
}
?>
