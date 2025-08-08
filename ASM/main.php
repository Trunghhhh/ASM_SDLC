<?php
session_start();

// Kiểm tra xem người dùng đã đăng nhập chưa
// Nếu chưa, chuyển hướng về trang đăng nhập
if (!isset($_SESSION['UserID'])) {
    header('Location: login.html');
    exit;
}

// Kết nối đến cơ sở dữ liệu
// Bạn có thể tạo file connect.php để tái sử dụng
try {
    $pdo = new PDO('mysql:host=localhost;dbname=asm;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Kết nối cơ sở dữ liệu thất bại: " . $e->getMessage());
}

// Lấy thông tin người dùng từ session
$user_full_name = htmlspecialchars($_SESSION['FullName']);

// Truy vấn để lấy 4 phòng nổi bật có sẵn
$featured_rooms_query = "SELECT * FROM asm_rooms WHERE Status = 'Available' LIMIT 4";
$stmt = $pdo->query($featured_rooms_query);
$featured_rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hotel Booking • Trang chủ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css"> <style>
        /* CSS từ main.html có thể được đặt ở đây hoặc tốt hơn là trong file style.css */
        :root{
            --primary: #9C27B0;
            --primary-light: #d999d9;
            --primary-dark: #7B1FA2;
            --bg: #f7f7f9;
            --text: #333;
            --success: #4CAF50;
            --warning: #FF9800;
        }
        *{box-sizing:border-box;margin:0;padding:0;font-family:Nunito,Roboto,Arial,sans-serif;}
        body{background:var(--bg);color:var(--text);line-height:1.6;}
        a{text-decoration:none;color:inherit;}
        .container{width:90%;max-width:1180px;margin:0 auto;}

        /* HEADER */
        header{background:#fff;padding:20px 0;box-shadow:0 2px 4px rgba(0,0,0,.08);position:sticky;top:0;z-index:100;}
        .logo{font-size:28px;font-weight:700;color:var(--primary);}
        nav{display:flex;gap:30px;font-weight:600;}
        nav a{padding:8px 16px;border-radius:20px;transition:all 0.3s ease;}
        nav a:hover{background:var(--primary-light);color:#fff;}
        .header-flex{display:flex;justify-content:space-between;align-items:center;}

        /* HERO/BANNER */
        .hero{
            background:linear-gradient(rgba(0,0,0,.4), rgba(0,0,0,.4)), url('download (8).jfif') center/cover;
            height:70vh;position:relative;color:#fff;
            display:flex;align-items:center;
        }
        .hero-content{position:relative;z-index:1;padding-left:8%;max-width:600px;}
        .hero h1{font-size:45px;line-height:1.2;margin-bottom:14px;text-shadow:2px 2px 4px rgba(0,0,0,0.5);}
        .hero p{font-size:18px;margin-bottom:26px;text-shadow:1px 1px 2px rgba(0,0,0,0.5);}
        .btn{
            display:inline-block;padding:14px 32px;border-radius:30px;
            background:var(--primary);color:#fff;font-weight:700;transition:.25s ease;
            cursor:pointer;border:none;font-size:16px;
        }
        .btn:hover{background:var(--primary-dark);transform:translateY(-2px);box-shadow:0 4px 12px rgba(156,39,176,0.3);}
        .btn-outline{background:transparent;border:2px solid #fff;color:#fff;}
        .btn-outline:hover{background:#fff;color:var(--primary);}

        /* SEARCH FORM */
        .search-box{
            margin-top:-60px;background:#fff;padding:25px 28px;border-radius:15px;
            box-shadow:0 8px 25px rgba(0,0,0,.15);display:flex;flex-wrap:wrap;gap:14px;
            position:relative;z-index:10;
        }
        .search-box input, .search-box select{
            padding:12px 14px;font-size:15px;border:2px solid #e0e0e0;border-radius:8px;flex:1 1 180px;
            transition:border-color 0.3s ease;
        }
        .search-box input:focus, .search-box select:focus{
            outline:none;border-color:var(--primary);
        }
        .search-box button{flex:1 1 140px;}

        /* WHY CHOOSE US */
        .features{padding:80px 0;background:#fff;}
        .features-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:40px;margin-top:40px;}
        .feature-item{text-align:center;padding:20px;}
        .feature-icon{width:60px;height:60px;background:var(--primary-light);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:24px;color:#fff;}
        .feature-item h3{margin-bottom:15px;color:var(--primary);}

        /* FEATURED ROOMS */
        h2.section-title{margin:70px 0 40px;text-align:center;font-size:32px;color:var(--primary);position:relative;}
        h2.section-title::after{content:'';width:60px;height:3px;background:var(--primary);position:absolute;bottom:-10px;left:50%;transform:translateX(-50%);}
        
        .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:30px;}
        .card{
            background:#fff;border-radius:15px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,.1);
            display:flex;flex-direction:column;transition:transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover{transform:translateY(-5px);box-shadow:0 8px 25px rgba(0,0,0,.15);}
        .card img{width:100%;height:200px;object-fit:cover;transition:transform 0.3s ease;}
        .card:hover img{transform:scale(1.05);}
        .card-body{padding:20px;flex:1;display:flex;flex-direction:column;}
        .card-body h3{font-size:20px;margin-bottom:10px;color:var(--text);}
        .card-body p{color:#666;margin-bottom:8px;}
        .price{margin-top:auto;font-weight:700;color:var(--primary);font-size:18px;margin-bottom:15px;}
        .card-actions{display:flex;gap:10px;}
        .btn-small{padding:8px 16px;font-size:14px;border-radius:20px;}
        .btn-secondary{background:#f5f5f5;color:var(--text);}
        .btn-secondary:hover{background:#e0e0e0;}

        /* TESTIMONIALS */
        .testimonials{background:var(--bg);padding:80px 0;}
        .testimonial-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:30px;margin-top:40px;}
        .testimonial{background:#fff;padding:30px;border-radius:15px;box-shadow:0 4px 15px rgba(0,0,0,.08);}
        .testimonial-text{font-style:italic;margin-bottom:20px;color:#555;}
        .testimonial-author{display:flex;align-items:center;gap:15px;}
        .author-avatar{width:50px;height:50px;border-radius:50%;background:var(--primary-light);}
        .author-info h4{color:var(--primary);margin-bottom:5px;}
        .author-info span{color:#777;font-size:14px;}

        /* NEWSLETTER */
        .newsletter{background:var(--primary);color:#fff;padding:60px 0;text-align:center;}
        .newsletter h3{font-size:28px;margin-bottom:15px;}
        .newsletter p{margin-bottom:30px;opacity:0.9;}
        .newsletter-form{display:flex;max-width:400px;margin:0 auto;gap:10px;}
        .newsletter-form input{flex:1;padding:12px 16px;border:none;border-radius:25px;font-size:16px;}
        .newsletter-form button{background:#fff;color:var(--primary);border:none;padding:12px 24px;border-radius:25px;font-weight:600;cursor:pointer;}

        /* FOOTER */
        footer{background:#333;color:#fff;padding:50px 0 20px;}
        .footer-content{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:40px;margin-bottom:30px;}
        .footer-section h4{color:var(--primary-light);margin-bottom:20px;}
        .footer-section p, .footer-section a{color:#ccc;line-height:1.8;}
        .footer-section a:hover{color:#fff;}
        .footer-bottom{text-align:center;padding-top:20px;border-top:1px solid #555;color:#999;}

        /* SCROLL TO TOP */
        .scroll-top{position:fixed;bottom:30px;right:30px;background:var(--primary);color:#fff;width:50px;height:50px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;opacity:0;visibility:hidden;transition:all 0.3s ease;z-index:1000;}
        .scroll-top.show{opacity:1;visibility:visible;}
        .scroll-top:hover{background:var(--primary-dark);}

        /* RESPONSIVE */
        @media(max-width:768px){
            .hero h1{font-size:32px;}
            .search-box{flex-direction:column;margin-top:-40px;}
            .search-box button{width:100%;}
            nav{flex-direction:column;gap:10px;}
            .header-flex{flex-direction:column;gap:20px;}
            .newsletter-form{flex-direction:column;}
            .card-actions{flex-direction:column;}
        }

        /* LOADING ANIMATION */
        .loading{display:none;color:var(--primary);}
        .loading.show{display:inline-block;}
    </style>
</head>
<body>

    <header>
        <div class="container header-flex">
            <a href="main.php" class="logo">🏨 HotelBooking</a>

            <nav>
                <a href="main.php">Trang chủ</a>
                <a href="rooms.php">Phòng</a>
                <a href="offers.html">Ưu đãi</a>
                <a href="contact.html">Liên hệ</a>
                
                <?php if (isset($_SESSION['UserID'])): ?>
                    <span>Chào mừng, <?= htmlspecialchars($_SESSION['FullName']) ?></span>
                    <a href="logout.php">Đăng xuất</a>
                <?php else: ?>
                    <a href="login.html">Đăng nhập</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <section class="hero">
        <div class="hero-content">
            <h1>Tìm khách sạn hoàn hảo cho chuyến đi của bạn</h1>
            <p>Hơn 5000 chỗ ở chất lượng, giá tốt nhất. Đặt phòng nhanh – xác nhận tức thì.</p>
            <a href="#search" class="btn">Bắt đầu tìm kiếm</a>
        </div>
    </section>

    <div class="container" id="search">
        <form class="search-box" action="search.php" method="get" onsubmit="return validateSearch()">
            <input type="text" name="destination" placeholder="Điểm đến (thành phố/khách sạn)" required>
            <input type="date" name="checkin" required>
            <input type="date" name="checkout" required>
            <select name="guests" required>
                <option value="">Chọn số khách</option>
                <option value="1">1 người lớn</option>
                <option value="2">2 người lớn</option>
                <option value="3">3 người lớn</option>
                <option value="4">4 người lớn</option>
            </select>
            <button type="submit" class="btn">
                <span class="btn-text">Tìm phòng</span>
                <span class="loading">Đang tìm...</span>
            </button>
        </form>
    </div>

    <section class="features">
        <div class="container">
            <h2 class="section-title">Tại sao chọn chúng tôi?</h2>
            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon">💳</div>
                    <h3>Thanh toán an toàn</h3>
                    <p>Hỗ trợ đa dạng phương thức thanh toán với bảo mật tối đa</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">⚡</div>
                    <h3>Xác nhận tức thì</h3>
                    <p>Nhận voucher xác nhận ngay sau khi đặt phòng thành công</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">🎯</div>
                    <h3>Giá tốt nhất</h3>
                    <p>Cam kết giá thấp nhất thị trường, hoàn tiền nếu tìm thấy giá rẻ hơn</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">📞</div>
                    <h3>Hỗ trợ 24/7</h3>
                    <p>Đội ngũ chăm sóc khách hàng luôn sẵn sàng hỗ trợ mọi lúc</p>
                </div>
            </div>
        </div>
    </section>

    <h2 class="section-title">Phòng nổi bật</h2>

    <div class="container">
        <div class="grid">
            <?php if (!empty($featured_rooms)): ?>
                <?php foreach ($featured_rooms as $room): ?>
                    <div class="card">
                        <img src="<?= htmlspecialchars($room['ImageURL']) ?>" alt="Hình ảnh phòng">
                        <div class="card-body">
                            <h3><?= htmlspecialchars($room['RoomNumber']) ?></h3>
                            <p>Loại phòng: <?= htmlspecialchars($room['Type']) ?></p>
                            <p>Tình trạng: <?= htmlspecialchars($room['Status']) ?></p>
                            <p class="price"><?= number_format($room['Price'], 0, ',', '.') ?> ₫ / đêm</p>
                            <div class="card-actions">
                                <a href="room-detail.php?id=<?= htmlspecialchars($room['RoomID']) ?>" class="btn btn-small">Xem chi tiết</a>
                                <button class="btn btn-small btn-secondary" onclick="quickBook(<?= htmlspecialchars($room['RoomID']) ?>)">Đặt ngay</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Hiện không có phòng nổi bật nào.</p>
            <?php endif; ?>
        </div>
    </div>

    <section class="testimonials">
        <div class="container">
            <h2 class="section-title">Khách hàng nói gì về chúng tôi</h2>
            <div class="testimonial-grid">
                <div class="testimonial">
                    <p class="testimonial-text">"Dịch vụ tuyệt vời! Phòng sạch sẽ, view đẹp và nhân viên rất thân thiện. Chắc chắn sẽ quay lại lần sau."</p>
                    <div class="testimonial-author">
                        <div class="author-avatar"></div>
                        <div class="author-info">
                            <h4>Nguyễn Minh Anh</h4>
                            <span>Đã ở 3 lần</span>
                        </div>
                    </div>
                </div>
                <div class="testimonial">
                    <p class="testimonial-text">"Đặt phòng dễ dàng, thanh toán nhanh chóng. Khách sạn đúng như mô tả, không có gì phải phàn nàn."</p>
                    <div class="testimonial-author">
                        <div class="author-avatar"></div>
                        <div class="author-info">
                            <h4>Trần Hoài Nam</h4>
                            <span>Khách VIP</span>
                        </div>
                    </div>
                </div>
                <div class="testimonial">
                    <p class="testimonial-text">"Gia đình tôi rất hài lòng với dịch vụ. Trẻ em thích thú với khu vui chơi và hồ bơi."</p>
                    <div class="testimonial-author">
                        <div class="author-avatar"></div>
                        <div class="author-info">
                            <h4>Lê Thị Hương</h4>
                            <span>Đánh giá 5⭐</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="newsletter">
        <div class="container">
            <h3>Đăng ký nhận ưu đãi đặc biệt</h3>
            <p>Nhận thông tin về các chương trình khuyến mãi và ưu đãi mới nhất</p>
            <form class="newsletter-form" onsubmit="return subscribeNewsletter()">
                <input type="email" placeholder="Nhập email của bạn" required>
                <button type="submit">Đăng ký</button>
            </form>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-content">
                </div>
            <div class="footer-bottom">
                © 2025 HotelBooking. All rights reserved. | Thiết kế bởi Team ASM
            </div>
        </div>
    </footer>

    <div class="scroll-top" onclick="scrollToTop()">↑</div>

    <script>
        // Form validation
        function validateSearch() {
            const checkin = document.querySelector('input[name="checkin"]').value;
            const checkout = document.querySelector('input[name="checkout"]').value;
            
            if (new Date(checkin) >= new Date(checkout)) {
                alert('Ngày trả phòng phải sau ngày nhận phòng!');
                return false;
            }
            
            // Show loading
            document.querySelector('.btn-text').style.display = 'none';
            document.querySelector('.loading').classList.add('show');
            
            return true;
        }

        // Quick booking
        function quickBook(roomId) {
            if (confirm('Bạn có muốn đặt phòng này ngay không?')) {
                window.location.href = `booking.php?room=${roomId}`;
            }
        }

        // Newsletter subscription
        function subscribeNewsletter() {
            alert('Cảm ơn bạn đã đăng ký! Chúng tôi sẽ gửi thông tin ưu đãi sớm nhất.');
            return false;
        }

        // Scroll to top functionality
        window.addEventListener('scroll', function() {
            const scrollTop = document.querySelector('.scroll-top');
            if (window.pageYOffset > 300) {
                scrollTop.classList.add('show');
            } else {
                scrollTop.classList.remove('show');
            }
        });

        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Set minimum date for check-in (today)
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.querySelector('input[name="checkin"]').min = today;
            document.querySelector('input[name="checkout"]').min = today;
            
            // Update checkout min date when checkin changes
            document.querySelector('input[name="checkin"]').addEventListener('change', function() {
                const checkinDate = new Date(this.value);
                checkinDate.setDate(checkinDate.getDate() + 1);
                document.querySelector('input[name="checkout"]').min = checkinDate.toISOString().split('T')[0];
            });
        });
    </script>
</body>
</html>