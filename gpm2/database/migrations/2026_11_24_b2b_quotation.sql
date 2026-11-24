-- GPM | Base incremental para pedidos de cotação B2B
-- Executar uma única vez na base gpm_mvp_nova.
-- Não altera nem elimina dados existentes.

ALTER TABLE products
    ADD COLUMN IF NOT EXISTS min_order_quantity DECIMAL(15,3) NOT NULL DEFAULT 1.000;

ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS order_type ENUM('sale','quote_request') NOT NULL DEFAULT 'sale',
    ADD COLUMN IF NOT EXISTS request_status VARCHAR(30) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS proposal_version INT UNSIGNED NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS quote_proposals (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    version INT UNSIGNED NOT NULL,
    status ENUM('draft','sent','accepted','rejected','expired','superseded') NOT NULL DEFAULT 'draft',
    currency CHAR(3) NOT NULL DEFAULT 'AOA',
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    delivery_fee DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    discount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    valid_until DATE DEFAULT NULL,
    conditions TEXT DEFAULT NULL,
    sent_at DATETIME DEFAULT NULL,
    responded_at DATETIME DEFAULT NULL,
    created_by BIGINT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_quote_proposal_version (order_id, version),
    KEY idx_quote_proposal_status (status),
    CONSTRAINT fk_quote_proposal_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quote_proposal_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    proposal_id BIGINT UNSIGNED NOT NULL,
    request_item_id BIGINT UNSIGNED NOT NULL,
    product_name VARCHAR(200) NOT NULL,
    unit VARCHAR(40) NOT NULL DEFAULT 'unidade',
    requested_quantity DECIMAL(15,3) NOT NULL,
    approved_quantity DECIMAL(15,3) NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    note VARCHAR(500) DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_quote_proposal_items_proposal (proposal_id),
    CONSTRAINT fk_quote_proposal_items_proposal FOREIGN KEY (proposal_id) REFERENCES quote_proposals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS delivery_location_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME DEFAULT NULL,
    revoked_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_delivery_token_hash (token_hash),
    KEY idx_delivery_token_order (order_id),
    CONSTRAINT fk_delivery_token_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_deliveries (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT UNSIGNED DEFAULT NULL,
    channel ENUM('internal','email','whatsapp','sms') NOT NULL,
    recipient VARCHAR(190) NOT NULL,
    template_key VARCHAR(100) NOT NULL,
    status ENUM('queued','sent','failed') NOT NULL DEFAULT 'queued',
    provider_reference VARCHAR(190) DEFAULT NULL,
    error_message TEXT DEFAULT NULL,
    sent_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notification_order (order_id),
    KEY idx_notification_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
