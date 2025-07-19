-- Migration script for adding expense management features
-- Run this script on an existing hotel_booking database

USE hotel_booking;

-- Booking expenses table
CREATE TABLE IF NOT EXISTS booking_expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    expense_type ENUM('laundry', 'food', 'beverages', 'other') NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

-- Expense items catalog (for quick selection)
CREATE TABLE IF NOT EXISTS expense_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category ENUM('laundry', 'food', 'beverages', 'other') NOT NULL,
    item_name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default expense items (only if table is empty)
INSERT IGNORE INTO expense_items (category, item_name, price) VALUES
-- Laundry services
('laundry', 'Shirt/Blouse Cleaning', 8.00),
('laundry', 'Pants/Trousers Cleaning', 10.00),
('laundry', 'Dress Cleaning', 15.00),
('laundry', 'Suit Cleaning', 25.00),
('laundry', 'Express Laundry (same day)', 20.00),
('laundry', 'Ironing Service', 5.00),

-- Food items
('food', 'Continental Breakfast', 18.00),
('food', 'American Breakfast', 25.00),
('food', 'Room Service Delivery', 5.00),
('food', 'Club Sandwich', 15.00),
('food', 'Caesar Salad', 12.00),
('food', 'Grilled Chicken', 22.00),
('food', 'Pasta Primavera', 18.00),
('food', 'Steak Dinner', 35.00),
('food', 'Fresh Fruit Platter', 14.00),

-- Beverages
('beverages', 'Coffee (per cup)', 4.00),
('beverages', 'Tea (per cup)', 3.50),
('beverages', 'Fresh Orange Juice', 6.00),
('beverages', 'Bottled Water', 3.00),
('beverages', 'Soft Drink', 4.50),
('beverages', 'Beer (local)', 8.00),
('beverages', 'Wine (house)', 12.00),
('beverages', 'Cocktail', 15.00),
('beverages', 'Energy Drink', 5.50),

-- Other services
('other', 'Minibar Usage', 0.00),
('other', 'WiFi Premium', 10.00),
('other', 'Parking Fee', 15.00),
('other', 'Spa Services', 0.00),
('other', 'Transportation', 0.00),
('other', 'Late Checkout Fee', 25.00);