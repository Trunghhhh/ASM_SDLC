document.addEventListener('DOMContentLoaded', function() {
    const mainContent = document.getElementById('dynamicContent');
    const sidebarLinks = document.querySelectorAll('.sidebar-menu a');
    const adminNameEl = document.getElementById('adminName');
    const logoutBtn = document.getElementById('logoutBtn');

    // Hàm để tải nội dung của từng panel
    function loadPanel(panelName) {
        // Xóa trạng thái active của tất cả các liên kết
        sidebarLinks.forEach(link => link.classList.remove('active'));
        // Thêm trạng thái active cho liên kết hiện tại
        document.querySelector(`[data-panel="${panelName}"]`).classList.add('active');

        switch(panelName) {
            case 'dashboard':
                loadDashboardPanel();
                break;
            case 'rooms':
                loadRoomsPanel();
                break;
            case 'bookings':
                loadBookingsPanel();
                break;
            case 'users':
                loadUsersPanel();
                break;
            case 'reports':
                loadReportsPanel();
                break;
            case 'settings':
                loadSettingsPanel();
                break;
            default:
                mainContent.innerHTML = '<h2>Trang không tồn tại</h2>';
        }
    }

    // Gắn sự kiện click cho sidebar
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const panelName = this.dataset.panel;
            loadPanel(panelName);
        });
    });

    // Hàm load Dashboard
    function loadDashboardPanel() {
        mainContent.innerHTML = `
            <section class="stats-section">
                <div class="stat-card">
                    <i class="fas fa-bed icon-bed"></i>
                    <div class="stat-content">
                        <p>Tổng số phòng</p>
                        <h4 id="totalRooms">--</h4>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-calendar-check icon-booking"></i>
                    <div class="stat-content">
                        <p>Đặt phòng hôm nay</p>
                        <h4 id="todayBookings">--</h4>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-users icon-users"></i>
                    <div class="stat-content">
                        <p>Tổng số khách hàng</p>
                        <h4 id="totalUsers">--</h4>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-dollar-sign icon-revenue"></i>
                    <div class="stat-content">
                        <p>Doanh thu tháng này</p>
                        <h4 id="monthlyRevenue">-- VNĐ</h4>
                    </div>
                </div>
            </section>
            <section class="data-table-section">
                <div class="card">
                    <div class="card-header">
                        <h3>Đặt phòng gần đây</h3>
                    </div>
                    <div class="card-body">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Booking ID</th>
                                    <th>Khách hàng</th>
                                    <th>Phòng</th>
                                    <th>Ngày nhận</th>
                                    <th>Ngày trả</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody id="recentBookingsTable">
                                <tr><td colspan="7" style="text-align:center;">Đang tải dữ liệu...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        `;
        fetchDashboardData();
    }

    // Hàm tải dữ liệu dashboard
    async function fetchDashboardData() {
        try {
            const response = await fetch('api/get_stats.php');
            const data = await response.json();
            if (data.success) {
                document.getElementById('totalRooms').textContent = data.totalRooms;
                document.getElementById('todayBookings').textContent = data.todayBookings;
                document.getElementById('totalUsers').textContent = data.totalUsers;
                document.getElementById('monthlyRevenue').textContent = `${data.monthlyRevenue} VNĐ`;
                
                const tableBody = document.getElementById('recentBookingsTable');
                tableBody.innerHTML = '';
                data.recentBookings.forEach(booking => {
                    const row = `
                        <tr>
                            <td>${booking.BookingID}</td>
                            <td>${booking.CustomerName}</td>
                            <td>${booking.RoomNumber}</td>
                            <td>${booking.CheckIn}</td>
                            <td>${booking.CheckOut}</td>
                            <td>${booking.TotalPrice} VNĐ</td>
                            <td><span class="status-badge status-${booking.BookingStatus.toLowerCase()}">${booking.BookingStatus}</span></td>
                        </tr>
                    `;
                    tableBody.innerHTML += row;
                });
            }
        } catch (error) {
            console.error('Lỗi khi tải dữ liệu dashboard:', error);
            // Hiển thị thông báo lỗi trên UI
        }
    }

    // Hàm load Quản lý Phòng
    function loadRoomsPanel() {
        mainContent.innerHTML = `
            <h2>Quản lý Phòng</h2>
            <button class="btn btn-primary" id="addRoomBtn">Thêm phòng mới</button>
            <div class="card mt-20">
                <div class="card-header">
                    <h3>Danh sách phòng</h3>
                </div>
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Số phòng</th>
                                <th>Loại</th>
                                <th>Giá</th>
                                <th>Trạng thái</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody id="roomsTable">
                            <tr><td colspan="6" style="text-align:center;">Đang tải dữ liệu...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div id="roomFormContainer" class="hidden">
                </div>
        `;
        fetchRoomsData();
    }
    
    // Hàm tải dữ liệu phòng
    async function fetchRoomsData() {
        // ... (Logic gọi API get_rooms.php và điền vào bảng)
    }

    // Hàm load Quản lý Đặt phòng
    function loadBookingsPanel() {
        mainContent.innerHTML = `<h2>Quản lý Đặt phòng</h2>`;
        // ... (Tương tự như trên, gọi fetchBookingsData)
    }
    // ...
    // Các hàm loadUsersPanel, loadReportsPanel, loadSettingsPanel tương tự

    // Xử lý nút Đăng xuất
    logoutBtn.addEventListener('click', async function(e) {
        e.preventDefault();
        try {
            const response = await fetch('api/user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'logout' })
            });
            const data = await response.json();
            if (data.success) {
                window.location.href = '../login.html';
            }
        } catch (error) {
            console.error('Lỗi khi đăng xuất:', error);
        }
    });

    // Tải panel dashboard mặc định khi vừa vào trang
    loadPanel('dashboard');
});