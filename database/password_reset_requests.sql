CREATE TABLE IF NOT EXISTS password_reset_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NULL COMMENT 'FK lógica a access_accounts.id',
    role ENUM('student', 'admin') NOT NULL,
    display_name VARCHAR(150) NOT NULL,
    contact VARCHAR(150) NOT NULL COMMENT 'cédula (student) o email (admin)',
    status ENUM('pending', 'resolved', 'cancelled') NOT NULL DEFAULT 'pending',
    ip_address VARCHAR(45) NULL,
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_by VARCHAR(150) NULL,
    resolved_at DATETIME NULL,
    INDEX idx_prr_status (status),
    INDEX idx_prr_account_id (account_id),
    INDEX idx_prr_requested_at (requested_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
