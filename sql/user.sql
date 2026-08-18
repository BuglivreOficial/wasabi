-- =========================================================
-- Users Table - MySQL
-- =========================================================

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Personal data
    username VARCHAR(50) NOT NULL,
    email VARCHAR(191) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    birth_date DATE DEFAULT NULL,
    gender ENUM('male', 'female', 'other', 'not_informed') DEFAULT 'not_informed',

    -- Authentication and security
    password_hash VARCHAR(255) NOT NULL,
    remember_token VARCHAR(255) DEFAULT NULL,
    password_reset_token VARCHAR(255) DEFAULT NULL,
    password_reset_expires_at DATETIME DEFAULT NULL,
    email_verified_at DATETIME DEFAULT NULL,
    two_factor_enabled BOOLEAN NOT NULL DEFAULT FALSE,

    -- Profile
    avatar_url VARCHAR(500) DEFAULT NULL,
    bio VARCHAR(500) DEFAULT NULL,

    -- Access control
    role ENUM('admin', 'moderator', 'support', 'vip', 'user') NOT NULL DEFAULT 'user',
    status ENUM('active', 'inactive', 'suspended', 'deleted') NOT NULL DEFAULT 'active',

    -- Audit / tracking
    last_login_at DATETIME DEFAULT NULL,
    last_login_ip VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL, -- soft delete

    -- Unique constraints
    UNIQUE KEY uk_users_username (username),
    UNIQUE KEY uk_users_email (email),

    -- Indexes for common queries
    INDEX idx_users_status (status),
    INDEX idx_users_role (role)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- Example insert
-- =========================================================
-- INSERT INTO users (username, email, password_hash)
-- VALUES ('johndoe', 'john.doe@email.com', '$2y$10$exampleHashHere');

-- =========================================================
-- Example query
-- =========================================================
-- SELECT id, username, email, role, status
-- FROM users
-- WHERE status = 'active';