-- Library Management System Database Schema
-- Run this script to create the complete database structure

CREATE DATABASE IF NOT EXISTS db_perpustakaan;
USE db_perpustakaan;

-- Table: anggota (Members)
CREATE TABLE IF NOT EXISTS anggota (
    anggota_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    nim VARCHAR(20) UNIQUE NOT NULL,
    class VARCHAR(50),
    gender ENUM('Male', 'Female') NOT NULL,
    date_of_birth DATE NOT NULL,
    place_of_birth VARCHAR(100),
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: users (System Users)
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    anggota_id INT NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    p_role ENUM('admin', 'staff', 'member') NOT NULL DEFAULT 'member',
    status ENUM('active', 'inactive', 'banned') NOT NULL DEFAULT 'inactive',
    registration_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (anggota_id) REFERENCES anggota(anggota_id) ON DELETE CASCADE
);

-- Table: books
CREATE TABLE IF NOT EXISTS books (
    book_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255),
    publisher VARCHAR(255),
    year_published YEAR,
    isbn VARCHAR(20),
    category VARCHAR(100),
    description TEXT,
    cover LONGBLOB,
    stock INT NOT NULL DEFAULT 0,
    available_stock INT NOT NULL DEFAULT 0,
    location VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: borrowings
CREATE TABLE IF NOT EXISTS borrowings (
    borrowing_id INT AUTO_INCREMENT PRIMARY KEY,
    anggota_id INT NOT NULL,
    book_id INT NOT NULL,
    borrow_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE NULL,
    status ENUM('borrowed', 'returned', 'overdue', 'lost') NOT NULL DEFAULT 'borrowed',
    fine_amount DECIMAL(10,2) DEFAULT 0.00,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (anggota_id) REFERENCES anggota(anggota_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE
);

-- Table: logs (Activity Logs)
CREATE TABLE IF NOT EXISTS logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    table_name VARCHAR(100),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Table: categories (Book Categories)
CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: fines (Fine Management)
CREATE TABLE IF NOT EXISTS fines (
    fine_id INT AUTO_INCREMENT PRIMARY KEY,
    borrowing_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    reason VARCHAR(255),
    status ENUM('pending', 'paid', 'waived') NOT NULL DEFAULT 'pending',
    paid_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (borrowing_id) REFERENCES borrowings(borrowing_id) ON DELETE CASCADE
);

-- Indexes for better performance
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_p_role ON users(p_role);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_anggota_nim ON anggota(nim);
CREATE INDEX idx_books_title ON books(title);
CREATE INDEX idx_books_author ON books(author);
CREATE INDEX idx_books_category ON books(category);
CREATE INDEX idx_borrowings_status ON borrowings(status);
CREATE INDEX idx_borrowings_dates ON borrowings(borrow_date, due_date);
CREATE INDEX idx_logs_user_action ON logs(user_id, action);

-- Insert default categories
INSERT INTO categories (name, description) VALUES
('Fiction', 'Fictional literature and novels'),
('Non-Fiction', 'Educational and factual books'),
('Science', 'Scientific and technical books'),
('History', 'Historical books and biographies'),
('Technology', 'Computer science and technology books'),
('Literature', 'Classic and modern literature'),
('Reference', 'Dictionaries, encyclopedias, and reference materials'),
('Children', 'Books for children and young adults');

-- Insert sample books
INSERT INTO books (title, author, publisher, year_published, category, stock, available_stock, description) VALUES
('The Great Gatsby', 'F. Scott Fitzgerald', 'Scribner', 1925, 'Fiction', 5, 5, 'A classic American novel set in the Jazz Age'),
('To Kill a Mockingbird', 'Harper Lee', 'J.B. Lippincott & Co.', 1960, 'Fiction', 3, 3, 'A novel about racial injustice in the American South'),
('1984', 'George Orwell', 'Secker & Warburg', 1949, 'Fiction', 4, 4, 'A dystopian social science fiction novel'),
('Introduction to Algorithms', 'Thomas H. Cormen', 'MIT Press', 2009, 'Technology', 2, 2, 'Comprehensive textbook on computer algorithms'),
('A Brief History of Time', 'Stephen Hawking', 'Bantam Books', 1988, 'Science', 3, 3, 'Popular science book about cosmology');

-- Trigger to update available_stock when borrowing status changes
DELIMITER //
CREATE TRIGGER update_book_stock_after_borrow
    AFTER INSERT ON borrowings
    FOR EACH ROW
BEGIN
    UPDATE books 
    SET available_stock = available_stock - 1 
    WHERE book_id = NEW.book_id;
END//

CREATE TRIGGER update_book_stock_after_return
    AFTER UPDATE ON borrowings
    FOR EACH ROW
BEGIN
    IF OLD.status != 'returned' AND NEW.status = 'returned' THEN
        UPDATE books 
        SET available_stock = available_stock + 1 
        WHERE book_id = NEW.book_id;
    END IF;
END//
DELIMITER ;

-- Show tables created
SHOW TABLES;
