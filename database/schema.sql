-- Schema only (không hardcode tên database).
-- Database được Core\Installer tạo tự động theo DB_DATABASE trong .env,
-- hoặc tạo thủ công rồi import file này.
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE branches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    description TEXT,
    image VARCHAR(255),
    open_time TIME DEFAULT '05:00:00',
    close_time TIME DEFAULT '22:00:00',
    courts_count INT DEFAULT 10,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE courts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    branch_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    type ENUM('standard', 'vip') DEFAULT 'standard',
    status ENUM('active', 'maintenance') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
);

CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_code VARCHAR(50) NOT NULL UNIQUE,
    user_id INT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    customer_email VARCHAR(255),
    court_id INT NOT NULL,
    branch_id INT NOT NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    duration_hours DECIMAL(4,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    expires_at TIMESTAMP NULL DEFAULT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (court_id) REFERENCES courts(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
);

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    method ENUM('qr', 'momo', 'cash') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
    transaction_id VARCHAR(255),
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

CREATE TABLE pricing_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    branch_id INT NULL,
    day_type ENUM('weekday', 'weekend', 'all') DEFAULT 'all',
    start_hour TIME NOT NULL,
    end_hour TIME NOT NULL,
    price_per_hour DECIMAL(10,2) NOT NULL,
    label VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
);

CREATE TABLE owners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    is_super_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE owner_branches (
    owner_id INT NOT NULL,
    branch_id INT NOT NULL,
    PRIMARY KEY (owner_id, branch_id),
    FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
);

-- Insert 13 branches
INSERT INTO branches (name, address, city, phone, description, courts_count) VALUES 
('SmashZone Cầu Giấy', '123 Cầu Giấy', 'Hà Nội', '0901234567', 'Cơ sở hiện đại, bãi xe rộng.', 10),
('SmashZone Thanh Xuân', '45 Nguyễn Trãi', 'Hà Nội', '0901234568', 'Gần đại học KHXH&NV.', 10),
('SmashZone Đống Đa', '78 Thái Hà', 'Hà Nội', '0901234569', 'Trung tâm quận Đống Đa.', 10),
('SmashZone Hai Bà Trưng', '12 Giải Phóng', 'Hà Nội', '0901234570', 'Không gian thoáng mát.', 10),
('SmashZone Nam Từ Liêm', '99 Lê Đức Thọ', 'Hà Nội', '0901234571', 'Gần sân vận động Mỹ Đình.', 10),
('SmashZone Quận 1', '1 Lê Duẩn', 'Hồ Chí Minh', '0901234572', 'Trung tâm sầm uất.', 10),
('SmashZone Quận 7', '123 Nguyễn Văn Linh', 'Hồ Chí Minh', '0901234573', 'Khu Phú Mỹ Hưng.', 10),
('SmashZone Tân Bình', '45 Cộng Hòa', 'Hồ Chí Minh', '0901234574', 'Gần sân bay Tân Sơn Nhất.', 10),
('SmashZone Thủ Đức', '89 Võ Văn Ngân', 'Hồ Chí Minh', '0901234575', 'Khu sinh viên đông đúc.', 10),
('SmashZone Hải Châu', '100 Lê Duẩn', 'Đà Nẵng', '0901234576', 'Trung tâm thành phố biển.', 10),
('SmashZone Sơn Trà', '200 Võ Nguyên Giáp', 'Đà Nẵng', '0901234577', 'Gần bãi biển Phạm Văn Đồng.', 10),
('SmashZone Ngô Quyền', '50 Lạch Tray', 'Hải Phòng', '0901234578', 'Khu thể thao nổi tiếng.', 10),
('SmashZone Ninh Kiều', '30 Đường 3/2', 'Cần Thơ', '0901234579', 'Trung tâm bến Ninh Kiều.', 10);

-- Insert 10 courts (A1..A10) for each of the 13 branches = 130 courts
-- Pure SQL (no stored procedure / DELIMITER) so it can run via PDO too.
INSERT INTO courts (branch_id, name, type)
SELECT b.id, CONCAT('A', n.j), IF(n.j <= 2, 'vip', 'standard')
FROM branches b
CROSS JOIN (
    SELECT 1 AS j UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5
    UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10
) n
ORDER BY b.id, n.j;

-- Global pricing rules
INSERT INTO pricing_rules (branch_id, day_type, start_hour, end_hour, price_per_hour, label) VALUES
(NULL, 'weekday', '05:00:00', '08:00:00', 80000, 'Off-peak Sáng'),
(NULL, 'weekday', '08:00:00', '14:00:00', 150000, 'Peak Ngày'),
(NULL, 'weekday', '14:00:00', '17:00:00', 80000, 'Off-peak Chiều'),
(NULL, 'weekday', '17:00:00', '22:00:00', 150000, 'Peak Tối'),
(NULL, 'weekend', '05:00:00', '22:00:00', 180000, 'Cuối tuần (+20%)');

-- Super admin (admin / admin123)
-- Password hashed using bcrypt for 'admin123'
INSERT INTO owners (username, password, name, is_super_admin) VALUES 
('admin', '$2y$10$rH17d.DaWXnrHuVVt6bDI.N7G/kpkP2XxqndlrUm1C.SpgYoHBb72', 'Super Admin', TRUE);

-- Sample users
INSERT INTO users (name, phone, email, password) VALUES 
('Nguyễn Văn Khách', '0987654321', 'khach@gmail.com', '$2y$10$yRinI/tZAwyVUUlr5SDup.lJOjS/aGmn89/MtsyVyE5P2XYTaE.ay');

-- Sample bookings for testing
INSERT INTO bookings (booking_code, user_id, customer_name, customer_phone, customer_email, court_id, branch_id, booking_date, start_time, end_time, duration_hours, total_price, status) VALUES
('BB-20231015-ABCDE', 1, 'Nguyễn Văn Khách', '0987654321', 'khach@gmail.com', 1, 1, CURDATE(), '18:00:00', '20:00:00', 2.0, 300000, 'confirmed');

INSERT INTO payments (booking_id, method, amount, status) VALUES
(1, 'momo', 300000, 'paid');
