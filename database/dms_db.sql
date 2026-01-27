-- Document Management System Database Schema
-- Run this SQL in your MySQL/phpMyAdmin to set up the database

CREATE DATABASE IF NOT EXISTS dms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dms_db;

-- Users table (Admin accounts only)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role VARCHAR(20) DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB;

-- Folders/Categories table
CREATE TABLE folders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Document Types table
CREATE TABLE document_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    order_index INT NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Projects table (inside folders)
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    folder_id INT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (folder_id) REFERENCES folders(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Documents table
CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size BIGINT,
    folder_id INT,
    project_id INT,
    document_type_id INT NOT NULL,
    description TEXT,
    tags VARCHAR(500),
    pdf_text_content MEDIUMTEXT,
    uploaded_by INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    download_count INT DEFAULT 0,
    FOREIGN KEY (folder_id) REFERENCES folders(id) ON DELETE SET NULL,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (document_type_id) REFERENCES document_types(id) ON DELETE RESTRICT,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    FULLTEXT INDEX idx_search (title, description, tags, pdf_text_content)
) ENGINE=InnoDB;

-- Activity logs (optional but useful for tracking)
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(50),
    document_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insert default admin account
-- Username: admin, Password: admin123 (change this after first login!)
INSERT INTO users (username, password, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin');

-- Insert default folders
INSERT INTO folders (name, description, created_by) VALUES
('General', 'General documents', 1),
('Contracts', 'Contract documents', 1),
('Reports', 'Report documents', 1),
('Invoices', 'Invoice documents', 1);

-- Insert document types in order
INSERT INTO document_types (name, order_index) VALUES
('Proposal', 1),
('RSSP', 2),
('Specsheet', 3),
('PC', 4),
('BP', 5),
('Asset Monitor', 6),
('PR', 7),
('PO', 8),
('DO/BAST', 9),
('Commencement', 10),
('Lain-lain', 11);
