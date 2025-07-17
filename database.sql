-- Hotel Booking Engine Database Schema

CREATE DATABASE hotel_booking;
USE hotel_booking;

-- Admins table
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Room types table
CREATE TABLE room_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default room types
INSERT INTO room_types (name) VALUES 
('Single'), 
('Deluxe'), 
('Suite'), 
('Family'), 
('Presidential');

-- Rooms table
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_name VARCHAR(100) NOT NULL,
    room_type_id INT,
    description TEXT,
    price_per_night DECIMAL(10,2) NOT NULL,
    max_occupancy INT NOT NULL,
    amenities TEXT,
    cancellation_policy TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (room_type_id) REFERENCES room_types(id)
);

-- Room images table
CREATE TABLE room_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT,
    image_path VARCHAR(255) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

-- Room availability table
CREATE TABLE room_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT,
    date DATE NOT NULL,
    available_count INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    UNIQUE KEY unique_room_date (room_id, date)
);

-- Bookings table
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guest_name VARCHAR(100) NOT NULL,
    guest_email VARCHAR(100),
    guest_phone VARCHAR(20),
    room_id INT,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    guests_count INT NOT NULL,
    total_amount DECIMAL(10,2),
    booking_source VARCHAR(50) DEFAULT 'website',
    ota_booking_id VARCHAR(100),
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);

-- Inquiry form submissions
CREATE TABLE inquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    room_type VARCHAR(50),
    guests_count INT NOT NULL,
    message TEXT,
    status ENUM('new', 'responded', 'closed') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- OTA settings table
CREATE TABLE ota_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ota_name VARCHAR(50) NOT NULL,
    api_key VARCHAR(255),
    username VARCHAR(100),
    password VARCHAR(255),
    endpoint_url VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample data
INSERT INTO rooms (room_name, room_type_id, description, price_per_night, max_occupancy, amenities, cancellation_policy) VALUES
('Deluxe Ocean View', 2, 'Beautiful ocean view room with modern amenities', 150.00, 2, 'Wi-Fi,AC,TV,Mini Bar,Balcony', 'Free cancellation up to 24 hours before check-in'),
('Presidential Suite', 5, 'Luxurious suite with separate living area', 500.00, 4, 'Wi-Fi,AC,TV,Mini Bar,Balcony,Jacuzzi,Butler Service', 'Free cancellation up to 48 hours before check-in'),
('Family Room', 4, 'Spacious room perfect for families', 200.00, 4, 'Wi-Fi,AC,TV,Mini Bar,Connecting Rooms', 'Free cancellation up to 24 hours before check-in'),
('Standard Single', 1, 'Comfortable single room for business travelers', 80.00, 1, 'Wi-Fi,AC,TV,Work Desk', 'Free cancellation up to 24 hours before check-in');