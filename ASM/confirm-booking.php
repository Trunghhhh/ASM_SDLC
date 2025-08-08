<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lấy dữ liệu từ form và lọc
    $name     = htmlspecialchars($_POST['name']);
    $phone    = htmlspecialchars($_POST['phone']);
    $checkin  = htmlspecialchars($_POST['checkin']);
    $checkout = htmlspecialchars($_POST['checkout']);
    $room     = htmlspecialchars($_POST['room']);

    // Định dạng ngày
    $checkinDate  = date("d/m/Y", strtotime($checkin));
    $checkoutDate = date("d/m/Y", strtotime($checkout));
} else {
    // Truy cập trực tiếp thì chuyển về form
    header("Location: booking_form.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Xác nhận thành công</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f0f0f0;
            padding: 50px;
        }
        .box {
            max-width: 550px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #28a745;
            margin-bottom: 20px;
        }
        p {
            font-size: 16px;
            line-height: 1.6;
        }
        .back-btn {
            display: inline-block;
            margin-top: 25px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }
        .back-btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

<div class="box">
    <h2>🎉 Đặt phòng thành công!</h2>
    <p><strong>👤 Họ tên:</strong> <?= $name ?></p>
    <p><strong>📞 Số điện thoại:</strong> <?= $phone ?></p>
    <p><strong>📅 Nhận phòng:</strong> <?= $checkinDate ?></p>
    <p><strong>📅 Trả phòng:</strong> <?= $checkoutDate ?></p>
    <p><strong>🏨 Loại phòng:</strong> <?= $room ?></p>
    <p>Cảm ơn bạn đã tin tưởng và sử dụng dịch vụ của chúng tôi!</p>

    <a href="index.html" class="back-btn">🔙 Về trang chủ</a>
</div>

</body>
</html>
