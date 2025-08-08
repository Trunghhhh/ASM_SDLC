<?php
session_start();
require_once 'connect.php';

// Lấy thông tin tìm kiếm từ form
$destination = isset($_GET['destination']) ? trim($_GET['destination']) : '';
$checkin = isset($_GET['checkin']) ? $_GET['checkin'] : '';
$checkout = isset($_GET['checkout']) ? $_GET['checkout'] : '';
$guests = isset($_GET['guests']) ? (int)$_GET['guests'] : 1;

// Validate dates
$error_message = '';
if ($checkin && $checkout) {
    if (strtotime($checkin) >= strtotime($checkout)) {
        $error_message = 'Ngày trả phòng phải sau ngày nhận phòng!';
    }
    if (strtotime($checkin) < strtotime(date('Y-m-d'))) {
        $error_message = 'Ngày nhận phòng không thể là ngày trong quá khứ!';
    }
}

// Tính số đêm ở
$nights = 0;
if ($checkin && $checkout && empty($error_message)) {
    $nights = (strtotime($checkout) - strtotime($checkin)) / (60 * 60 * 24);
}

// Build search query
$search_conditions = [];
$params = [];

if (!empty($destination)) {
    $search_conditions[] = "(r.RoomNumber LIKE ? OR r.Type LIKE ?)";
    $params[] = "%$destination%";
    $params[] = "%$destination%";
}

// Chỉ hiển thị phòng Available
$search_conditions[] = "r.Status = 'Available'";

// Kiểm tra phòng không bị đặt trong khoảng thời gian yêu cầu
if ($checkin && $checkout && empty($error_message)) {
    $search_conditions[] = "r.RoomID NOT IN (
        SELECT DISTINCT b.RoomID 
        FROM bookings b 
        WHERE b.Status IN ('Confirmed', 'Pending') 
        AND (
            (b.CheckIn <= ? AND b.CheckOut > ?) OR
            (b.CheckIn < ? AND b.CheckOut >= ?) OR
            (b.CheckIn >= ? AND b.CheckOut <= ?)
        )
    )";
    $params[] = $checkin;
    $params[] = $checkin;
    $params[] = $checkout;
    $params[] = $checkout;
    $params[] = $checkin;
    $params[] = $checkout;
}

// Prepare SQL query
$sql = "SELECT r.*, 
        CASE 
            WHEN r.Type = 'Single' THEN 1
            WHEN r.Type = 'Double' THEN 2  
            WHEN r.Type = 'Suite' THEN 4
            ELSE 2
        END as max_guests
        FROM rooms r";

if (!empty($search_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $search_conditions);
}

$sql .= " ORDER BY r.Price ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Filter by guest count if specified
    if ($guests > 0) {
        $rooms = array_filter($rooms, function($room) use ($guests) {
            return $room['max_guests'] >= $guests;
        });
    }
} catch (PDOException $e) {
    $error_message = "Lỗi tìm kiếm: " . $e->getMessage();
    $rooms = [];
}

// Get room amenities mapping
$amenities_map = [
    'Single' => ['WiFi miễn phí', 'Điều hòa', 'TV LCD', 'Minibar'],
    'Double' => ['WiFi miễn phí', 'Điều hòa', 'TV LCD', 'Minibar', 'Ban công', 'Két an toàn'],
    'Suite' => ['WiFi miễn phí', 'Điều hòa', 'Smart TV', 'Minibar', 'Ban công', 'Két an toàn', 'Bồn tắm', 'Phòng khách riêng']
];

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Kết quả tìm kiếm • Hotel Booking</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <style>
        :root{
            --primary: #9C27B0;
            --primary-light: #d999d9;
            --primary-dark: #7B1FA2;
            --bg: #f7f7f9;
            --text: #333;
            --success: #4CAF50;
            --error: #f44336;
            --warning: #FF9800;
        }
        
        *{box-sizing:border-box;margin:0;padding:0;font-family:Nunito,Roboto,Arial,sans-serif;}
        body{background:var(--bg);color:var(--text);line-height:1.6;}
        a{text-decoration:none;color:inherit;}
        .container{width:90%;max-width:1180px;margin:0 auto;}

        /* Header */
        header{background:#fff;padding:15px 0;box-shadow:0 2px 4px rgba(0,0,0,.08);margin-bottom:30px;}
        .header-flex{display:flex;justify-content:space-between;align-items:center;}
        .logo{font-size:24px;font-weight:700;color:var(--primary);}
        .back-link{color:var(--primary);font-weight:600;padding:8px 16px;border:2px solid var(--primary);border-radius:20px;transition:all 0.3s;}
        .back-link:hover{background:var(--primary);color:#fff;}

        /* Search Summary */
        .search-summary{
            background:#fff;padding:20px;border-radius:10px;margin-bottom:30px;
            box-shadow:0 2px 8px rgba(0,0,0,.08);
        }
        .search-info{display:flex;flex-wrap:wrap;gap:20px;align-items:center;margin-bottom:15px;}
        .search-info span{background:var(--bg);padding:8px 12px;border-radius:6px;font-weight:600;}
        .modify-search{color:var(--primary);font-weight:600;cursor:pointer;}
        .modify-search:hover{text-decoration:underline;}

        /* Error/Success Messages */
        .message{padding:15px 20px;border-radius:8px;margin-bottom:20px;font-weight:600;}
        .error{background:#ffebee;color:var(--error);border-left:4px solid var(--error);}
        .success{background:#e8f5e8;color:var(--success);border-left:4px solid var(--success);}
        .info{background:#e3f2fd;color:#1976d2;border-left:4px solid #1976d2;}

        /* Results Header */
        .results-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;flex-wrap:wrap;gap:15px;}
        .results-count{font-size:18px;font-weight:600;color:var(--primary);}
        
        /* Sort & Filter */
        .sort-filter{display:flex;gap:15px;align-items:center;flex-wrap:wrap;}
        .sort-filter select{padding:8px 12px;border:2px solid #ddd;border-radius:6px;background:#fff;cursor:pointer;}
        .sort-filter select:focus{outline:none;border-color:var(--primary);}

        /* Room Grid */
        .rooms-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(350px,1fr));gap:25px;}
        
        .room-card{
            background:#fff;border-radius:12px;overflow:hidden;
            box-shadow:0 4px 12px rgba(0,0,0,.1);transition:all 0.3s ease;
        }
        .room-card:hover{transform:translateY(-3px);box-shadow:0 8px 20px rgba(0,0,0,.15);}
        
        .room-image{position:relative;height:220px;overflow:hidden;}
        .room-image img{width:100%;height:100%;object-fit:cover;transition:transform 0.3s;}
        .room-card:hover .room-image img{transform:scale(1.05);}
        
        .availability-badge{
            position:absolute;top:15px;right:15px;background:var(--success);
            color:#fff;padding:5px 10px;border-radius:15px;font-size:12px;font-weight:600;
        }
        
        .room-content{padding:20px;}
        .room-type{font-size:20px;font-weight:700;color:var(--text);margin-bottom:8px;}
        .room-number{color:#666;margin-bottom:12px;font-weight:600;}
        
        .room-features{margin-bottom:15px;}
        .feature-list{display:flex;flex-wrap:wrap;gap:8px;}
        .feature-item{background:var(--bg);padding:4px 8px;border-radius:4px;font-size:12px;color:#555;}
        
        .amenities{margin-bottom:15px;}
        .amenities-title{font-weight:600;margin-bottom:8px;color:var(--primary);}
        .amenities-list{display:flex;flex-wrap:wrap;gap:6px;}
        .amenity{background:#f0f0f0;padding:3px 8px;border-radius:12px;font-size:11px;color:#666;}
        
        .room-footer{display:flex;justify-content:space-between;align-items:center;padding-top:15px;border-top:1px solid #eee;}
        .price-info{text-align:left;}
        .price{font-size:22px;font-weight:700;color:var(--primary);}
        .price-note{font-size:12px;color:#666;margin-top:2px;}
        
        .book-btn{
            background:var(--primary);color:#fff;padding:10px 20px;
            border:none;border-radius:6px;font-weight:600;cursor:pointer;
            transition:all 0.3s ease;
        }
        .book-btn:hover{background:var(--primary-dark);transform:translateY(-1px);}
        
        /* No Results */
        .no-results{
            text-align:center;padding:60px 20px;background:#fff;
            border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.08);
        }
        .no-results h3{color:var(--primary);margin-bottom:15px;font-size:24px;}
        .no-results p{color:#666;margin-bottom:20px;}
        .suggestions{background:var(--bg);padding:20px;border-radius:8px;margin-top:20px;}
        .suggestions h4{margin-bottom:10px;color:var(--text);}
        .suggestions ul{text-align:left;max-width:300px;margin:0 auto;}
        
        /* Mobile Responsive */
        @media(max-width:768px){
            .search-info{flex-direction:column;align-items:flex-start;}
            .results-header{flex-direction:column;align-items:flex-start;}
            .sort-filter{width:100%;justify-content:space-between;}
            .rooms-grid{grid-template-columns:1fr;}
            .room-footer{flex-direction:column;gap:10px;text-align:center;}
        }
    </style>
</head>

<body>

    <!-- Header -->
    <header>
        <div class="container header-flex">
            <a href="main.html" class="logo">🏨 HotelBooking</a>
            <a href="main.html" class="back-link">← Quay lại trang chủ</a>
        </div>
    </header>

    <div class="container">
        
        <!-- Search Summary -->
        <div class="search-summary">
            <div class="search-info">
                <?php if ($destination): ?>
                    <span>📍 <?= htmlspecialchars($destination) ?></span>
                <?php endif; ?>
                
                <?php if ($checkin && $checkout): ?>
                    <span>📅 <?= date('d/m/Y', strtotime($checkin)) ?> - <?= date('d/m/Y', strtotime($checkout)) ?></span>
                    <span>🌙 <?= $nights ?> đêm</span>
                <?php endif; ?>
                
                <span>👥 <?= $guests ?> khách</span>
            </div>
            <span class="modify-search" onclick="showModifyForm()">✏️ Thay đổi tìm kiếm</span>
        </div>

        <!-- Error Messages -->
        <?php if ($error_message): ?>
            <div class="message error">
                ⚠️ <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <!-- Results -->
        <?php if (empty($error_message)): ?>
            
            <!-- Results Header -->
            <div class="results-header">
                <div class="results-count">
                    🔍 Tìm thấy <?= count($rooms) ?> phòng phù hợp
                </div>
                <div class="sort-filter">
                    <select onchange="sortRooms(this.value)">
                        <option value="price_asc">Giá: Thấp → Cao</option>
                        <option value="price_desc">Giá: Cao → Thấp</option>
                        <option value="type">Loại phòng</option>
                    </select>
                </div>
            </div>

            <!-- Rooms Grid -->
            <?php if (count($rooms) > 0): ?>
                <div class="rooms-grid" id="roomsGrid">
                    <?php foreach ($rooms as $room): ?>
                        <div class="room-card" data-price="<?= $room['Price'] ?>" data-type="<?= $room['Type'] ?>">
                            <div class="room-image">
                                <img src="<?= htmlspecialchars($room['ImageURL']) ?>" 
                                     alt="<?= htmlspecialchars($room['Type']) ?>"
                                     onerror="this.src='https://via.placeholder.com/400x250/9C27B0/ffffff?text=Hotel+Room'">
                                <div class="availability-badge">✅ Còn trống</div>
                            </div>
                            
                            <div class="room-content">
                                <h3 class="room-type"><?= htmlspecialchars($room['Type']) ?></h3>
                                <div class="room-number">Phòng số: <?= htmlspecialchars($room['RoomNumber']) ?></div>
                                
                                <div class="room-features">
                                    <div class="feature-list">
                                        <span class="feature-item">👥 Tối đa <?= $room['max_guests'] ?> khách</span>
                                        <span class="feature-item">📶 WiFi miễn phí</span>
                                        <span class="feature-item">❄️ Điều hòa</span>
                                    </div>
                                </div>
                                
                                <?php if (isset($amenities_map[$room['Type']])): ?>
                                <div class="amenities">
                                    <div class="amenities-title">🏨 Tiện nghi:</div>
                                    <div class="amenities-list">
                                        <?php foreach ($amenities_map[$room['Type']] as $amenity): ?>
                                            <span class="amenity"><?= $amenity ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="room-footer">
                                    <div class="price-info">
                                        <div class="price"><?= number_format($room['Price'], 0, ',', '.') ?> ₫</div>
                                        <?php if ($nights > 0): ?>
                                            <div class="price-note">
                                                Tổng <?= $nights ?> đêm: <strong><?= number_format($room['Price'] * $nights, 0, ',', '.') ?> ₫</strong>
                                            </div>
                                        <?php else: ?>
                                            <div class="price-note">/ đêm</div>
                                        <?php endif; ?>
                                    </div>
                                    <button class="book-btn" onclick="bookRoom(<?= $room['RoomID'] ?>)">
                                        📝 Đặt phòng
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
            <?php else: ?>
                <!-- No Results -->
                <div class="no-results">
                    <h3>😔 Không tìm thấy phòng phù hợp</h3>
                    <p>Rất tiếc, chúng tôi không tìm thấy phòng nào khớp với yêu cầu của bạn.</p>
                    
                    <div class="suggestions">
                        <h4>💡 Gợi ý:</h4>
                        <ul>
                            <li>• Thử thay đổi ngày nhận/trả phòng</li>
                            <li>• Giảm số lượng khách</li>
                            <li>• Tìm kiếm với từ khóa khác</li>
                            <li>• Xem tất cả phòng có sẵn</li>
                        </ul>
                    </div>
                    
                    <br>
                    <a href="main.html" class="book-btn">🏠 Về trang chủ</a>
                </div>
            <?php endif; ?>
            
        <?php endif; ?>
    </div>

    <script>
        // Sort rooms function
        function sortRooms(sortBy) {
            const grid = document.getElementById('roomsGrid');
            const cards = Array.from(grid.children);
            
            cards.sort((a, b) => {
                switch(sortBy) {
                    case 'price_asc':
                        return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
                    case 'price_desc':
                        return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
                    case 'type':
                        return a.dataset.type.localeCompare(b.dataset.type);
                    default:
                        return 0;
                }
            });
            
            // Clear and re-append sorted cards
            grid.innerHTML = '';
            cards.forEach(card => grid.appendChild(card));
        }

        // Book room function
        function bookRoom(roomId) {
            const checkin = '<?= $checkin ?>';
            const checkout = '<?= $checkout ?>';
            const guests = '<?= $guests ?>';
            
            let url = `booking.html?room=${roomId}`;
            if (checkin) url += `&checkin=${checkin}`;
            if (checkout) url += `&checkout=${checkout}`;
            if (guests) url += `&guests=${guests}`;
            
            window.location.href = url;
        }

        // Show modify search form
        function showModifyForm() {
            const destination = '<?= $destination ?>';
            const checkin = '<?= $checkin ?>';
            const checkout = '<?= $checkout ?>';
            const guests = '<?= $guests ?>';
            
            let url = `main.html#search`;
            window.location.href = url;
        }

        // Auto-highlight search terms
        document.addEventListener('DOMContentLoaded', function() {
            const searchTerm = '<?= htmlspecialchars($destination) ?>';
            if (searchTerm) {
                const roomTypes = document.querySelectorAll('.room-type');
                roomTypes.forEach(type => {
                    const text = type.textContent;
                    const highlighted = text.replace(new RegExp(searchTerm, 'gi'), 
                        match => `<mark style="background:yellow;">${match}</mark>`);
                    type.innerHTML = highlighted;
                });
            }
        });
    </script>

</body>
</html>