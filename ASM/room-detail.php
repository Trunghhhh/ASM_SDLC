<?php
// Fake data mẫu - có thể sau này lấy từ database
$room = [
    'name' => 'Hi-Home Suites - Hanoi Downtown Aqua Central',
    'address' => '44 Đường Yên Phụ, Quận Ba Đình, Hà Nội',
    'image' => 'room.jpg',
    'price' => 1200000,
    'available' => 5,
    'status' => 'Còn phòng',
    'description' => 'Phòng rộng rãi, view thành phố, có bếp và hồ bơi trong nhà.',
    'features' => ['WiFi miễn phí', 'Bãi đỗ xe', 'Hồ bơi trong nhà', 'Ban công', 'Điều hoà']
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết phòng | <?php echo htmlspecialchars($room['name']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 30px;
        }
        .container {
            max-width: 1000px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0,0,0,0.08);
        }
        .room-header {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
        }
        .room-image img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
        }
        .room-info {
            flex: 1;
        }
        .room-info h2 {
            margin: 0 0 10px;
            font-size: 24px;
            color: #333;
        }
        .room-info p {
            margin: 5px 0;
            color: #555;
        }
        ul.features {
            list-style: none;
            padding-left: 0;
            margin-top: 10px;
        }
        ul.features li {
            background: #e0f7fa;
            display: inline-block;
            margin: 5px 5px 0 0;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
        }
        .book-button {
            margin-top: 25px;
        }
        .book-button button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .book-button button:hover {
            background-color: #0056b3;
        }
        @media (max-width: 768px) {
            .room-header {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="room-header">
            <div class="room-image">
                <img src="<?php echo htmlspecialchars($room['image']); ?>" alt="Ảnh phòng khách sạn">
            </div>
            <div class="room-info">
                <h2><?php echo htmlspecialchars($room['name']); ?></h2>
                <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($room['address']); ?></p>
                <p><strong>Giá:</strong> <?php echo number_format($room['price'], 0, ',', '.') . ' VND / đêm'; ?></p>
                <p><strong>Trạng thái:</strong> <?php echo htmlspecialchars($room['status']); ?></p>
                <p><strong>Số lượng còn trống:</strong> <?php echo (int)$room['available']; ?></p>
                <p><strong>Mô tả:</strong> <?php echo htmlspecialchars($room['description']); ?></p>
                <p><strong>Tiện nghi:</strong></p>
                <ul class="features">
                    <?php foreach ($room['features'] as $feature): ?>
                        <li><?php echo htmlspecialchars($feature); ?></li>
                    <?php endforeach; ?>
                </ul>
                <div class="book-button">
                    <form action="booking_form.php" method="GET" onsubmit="return confirm('Bạn chắc chắn muốn đặt phòng này?')">
                        <input type="hidden" name="room" value="<?php echo urlencode($room['name']); ?>">
                        <button type="submit">Đặt phòng ngay</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
