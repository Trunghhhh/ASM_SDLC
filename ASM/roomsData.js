// roomsData.js (hoặc đặt trực tiếp vào script của room-detail.html)
const roomsData = {
    "deluxe-king": {
        name: "Phòng Deluxe King",
        price: "2.500.000",
        description: "Phòng Deluxe rộng rãi, tiện nghi hiện đại, với tầm nhìn tuyệt đẹp ra thành phố hoặc biển. Giường King Size mang lại sự thoải mái tối đa.",
        bedType: "Giường King Size",
        capacity: "2 người lớn",
        area: "35 m²",
        amenities: [
            { icon: "fas fa-wifi", text: "Wi-Fi tốc độ cao" },
            { icon: "fas fa-tv", text: "TV màn hình phẳng" },
            { icon: "fas fa-snowflake", text: "Điều hòa nhiệt độ" },
            { icon: "fas fa-coffee", text: "Minibar & Ấm đun nước" },
            { icon: "fas fa-bath", text: "Phòng tắm riêng (vòi sen & bồn tắm)" },
            { icon: "fas fa-concierge-bell", text: "Dịch vụ phòng 24/7" },
            { icon: "fas fa-box", text: "Két an toàn" },
            { icon: "fas fa-desktop", text: "Bàn làm việc" }
        ],
        images: [
            "images/phong-deluxe-1.jpg",
            "images/phong-deluxe-2.jpg",
            "images/phong-deluxe-3.jpg",
            "images/phong-deluxe-4.jpg",
            "images/phong-deluxe-5.jpg",
            "images/phong-deluxe-6.jpg",
            "images/phong-deluxe-7.jpg"
        ]
    },
    "standard-twin": {
        name: "Phòng Standard Twin",
        price: "1.200.000",
        description: "Phòng tiêu chuẩn thoải mái với hai giường đơn, đầy đủ tiện nghi cơ bản, phù hợp cho bạn bè hoặc đồng nghiệp.",
        bedType: "2 Giường đơn",
        capacity: "2 người lớn",
        area: "25 m²",
        amenities: [
            { icon: "fas fa-wifi", text: "Wi-Fi miễn phí" },
            { icon: "fas fa-tv", text: "TV" },
            { icon: "fas fa-snowflake", text: "Điều hòa" },
            { icon: "fas fa-bath", text: "Phòng tắm riêng (vòi sen)" }
        ],
        images: [
            "images/phong-standard-1.jpg",
            "images/phong-standard-2.jpg",
            "images/phong-standard-3.jpg"
        ]
    },
    "royal-suite": {
        name: "Phòng Suite Hoàng Gia",
        price: "4.500.000",
        description: "Trải nghiệm đẳng cấp với căn suite sang trọng, có phòng khách riêng biệt, bồn tắm jacuzzi và tầm nhìn panorama tuyệt đẹp.",
        bedType: "Giường King Size cực lớn",
        capacity: "3 người lớn",
        area: "80 m²",
        amenities: [
            { icon: "fas fa-wifi", text: "Wi-Fi cao cấp" },
            { icon: "fas fa-tv", text: "2 TV màn hình phẳng" },
            { icon: "fas fa-snowflake", text: "Điều hòa trung tâm" },
            { icon: "fas fa-coffee", text: "Minibar đầy đủ & Máy pha cà phê" },
            { icon: "fas fa-bath", text: "Phòng tắm sang trọng (bồn tắm jacuzzi)" },
            { icon: "fas fa-concierge-bell", text: "Quản gia riêng 24/7" },
            { icon: "fas fa-box", text: "Két an toàn lớn" },
            { icon: "fas fa-desktop", text: "Khu vực làm việc" },
            { icon: "fas fa-couch", text: "Khu vực tiếp khách riêng" }
        ],
        images: [
            "images/phong-suite-1.jpg",
            "images/phong-suite-2.jpg",
            "images/phong-suite-3.jpg",
            "images/phong-suite-4.jpg"
        ]
    },
     "family-room": {
        name: "Phòng Gia đình",
        price: "3.000.000",
        description: "Phòng rộng rãi được thiết kế đặc biệt cho gia đình, với hai giường lớn hoặc một giường lớn và giường tầng, đảm bảo sự thoải mái cho mọi thành viên.",
        bedType: "1 Giường King + 2 Giường đơn",
        capacity: "4 người lớn",
        area: "50 m²",
        amenities: [
            { icon: "fas fa-wifi", text: "Wi-Fi tốc độ cao" },
            { icon: "fas fa-tv", text: "TV màn hình phẳng" },
            { icon: "fas fa-snowflake", text: "Điều hòa nhiệt độ" },
            { icon: "fas fa-coffee", text: "Minibar" },
            { icon: "fas fa-bath", text: "Phòng tắm riêng" },
            { icon: "fas fa-child", text: "Khu vui chơi trẻ em nhỏ" }
        ],
        images: [
            "images/phong-family-1.jpg",
            "images/phong-family-2.jpg",
            "images/phong-family-3.jpg",
            "images/phong-family-4.jpg"
        ]
    }
    // Thêm các phòng khác vào đây
};