-- Additive security migration. Run only against a disposable/staging copy first.
-- Does not alter or delete existing customer, order, product or stock data.

CREATE TABLE IF NOT EXISTS login_attempts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    attempt_key CHAR(64) NOT NULL,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_login_attempts_key_time (attempt_key, attempted_at),
    KEY idx_login_attempts_time (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Keep this cleanup as a scheduled maintenance task, not on every request:
-- DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 30 DAY);
