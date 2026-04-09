CREATE TABLE IF NOT EXISTS access_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('student', 'admin') NOT NULL,
    user_id INT NULL,
    numero_identificacion VARCHAR(20) NULL,
    email VARCHAR(150) NULL,
    display_name VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    must_change_password TINYINT(1) NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_access_accounts_role_identification (role, numero_identificacion),
    UNIQUE KEY uq_access_accounts_email (email),
    UNIQUE KEY uq_access_accounts_role_user (role, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO access_accounts (
    role,
    user_id,
    numero_identificacion,
    email,
    display_name,
    password_hash,
    must_change_password,
    is_active
) VALUES (
    'admin',
    NULL,
    '9999999999',
    'admin@superarse.edu.ec',
    'Administrador Principal',
    '$2y$10$Q5HkMmed2hFc9mSP8Wx3PuUYjowQB1rsD5KjjGrfLjUphzcRH3myq',
    1,
    1
)
ON DUPLICATE KEY UPDATE
    display_name = VALUES(display_name),
    is_active = VALUES(is_active);