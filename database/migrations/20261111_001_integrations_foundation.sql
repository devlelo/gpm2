-- GPM Conecta - Migration incremental 001
-- Requer MySQL 8.0+ ou MariaDB 10.4+; InnoDB; utf8mb4.
-- Executar apenas DEPOIS de backup e validação do schema atual.
-- Não apaga, renomeia nem recria tabelas existentes.
-- Credenciais externas devem ser cifradas pela aplicação (APP_KEY/KMS), nunca em texto simples.

CREATE TABLE IF NOT EXISTS gpm_schema_versions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  version_key VARCHAR(120) NOT NULL,
  checksum CHAR(64) NOT NULL,
  applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_gpm_schema_versions_key (version_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_integrations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  code VARCHAR(80) NOT NULL,
  name VARCHAR(180) NOT NULL,
  driver VARCHAR(80) NOT NULL,
  base_url VARCHAR(500) DEFAULT NULL,
  credentials_ciphertext MEDIUMTEXT DEFAULT NULL,
  settings_json JSON DEFAULT NULL,
  environment ENUM('sandbox','production') NOT NULL DEFAULT 'sandbox',
  status ENUM('draft','active','paused','error','disabled') NOT NULL DEFAULT 'draft',
  last_synced_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_supplier_integrations_code (code),
  KEY idx_supplier_integrations_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_product_mappings (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  supplier_integration_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED DEFAULT NULL,
  external_product_id VARCHAR(190) NOT NULL,
  external_sku VARCHAR(100) DEFAULT NULL,
  last_payload_hash CHAR(64) DEFAULT NULL,
  last_synced_at DATETIME DEFAULT NULL,
  sync_status ENUM('pending','synced','conflict','error','disabled') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_supplier_external_product (supplier_integration_id, external_product_id),
  KEY idx_supplier_mapping_product (product_id),
  CONSTRAINT fk_supplier_mapping_integration FOREIGN KEY (supplier_integration_id) REFERENCES supplier_integrations(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_supplier_mapping_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_sync_runs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  supplier_integration_id BIGINT UNSIGNED NOT NULL,
  direction ENUM('import','export','bidirectional') NOT NULL,
  status ENUM('queued','running','completed','partial','failed','cancelled') NOT NULL DEFAULT 'queued',
  records_seen INT UNSIGNED NOT NULL DEFAULT 0,
  records_created INT UNSIGNED NOT NULL DEFAULT 0,
  records_updated INT UNSIGNED NOT NULL DEFAULT 0,
  records_failed INT UNSIGNED NOT NULL DEFAULT 0,
  started_at DATETIME DEFAULT NULL,
  finished_at DATETIME DEFAULT NULL,
  error_summary TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_supplier_sync_runs_integration_date (supplier_integration_id, created_at),
  KEY idx_supplier_sync_runs_status (status),
  CONSTRAINT fk_supplier_sync_runs_integration FOREIGN KEY (supplier_integration_id) REFERENCES supplier_integrations(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_providers (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  code VARCHAR(80) NOT NULL,
  name VARCHAR(180) NOT NULL,
  driver VARCHAR(80) NOT NULL,
  environment ENUM('sandbox','production') NOT NULL DEFAULT 'sandbox',
  credentials_ciphertext MEDIUMTEXT DEFAULT NULL,
  settings_json JSON DEFAULT NULL,
  enabled TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_payment_providers_code (code),
  KEY idx_payment_providers_enabled (enabled, environment)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_transactions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id BIGINT UNSIGNED NOT NULL,
  payment_provider_id BIGINT UNSIGNED DEFAULT NULL,
  provider_code VARCHAR(80) NOT NULL,
  external_transaction_id VARCHAR(190) DEFAULT NULL,
  idempotency_key VARCHAR(100) NOT NULL,
  amount DECIMAL(15,2) NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'AOA',
  status ENUM('created','pending','authorized','paid','failed','cancelled','refunded','partially_refunded') NOT NULL DEFAULT 'created',
  checkout_url VARCHAR(1000) DEFAULT NULL,
  request_reference VARCHAR(190) DEFAULT NULL,
  metadata_json JSON DEFAULT NULL,
  paid_at DATETIME DEFAULT NULL,
  failed_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_payment_idempotency (idempotency_key),
  UNIQUE KEY uq_payment_provider_external (provider_code, external_transaction_id),
  KEY idx_payment_transactions_order (order_id, created_at),
  KEY idx_payment_transactions_status (status, created_at),
  CONSTRAINT fk_payment_transactions_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_payment_transactions_provider FOREIGN KEY (payment_provider_id) REFERENCES payment_providers(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_webhook_events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  payment_provider_id BIGINT UNSIGNED DEFAULT NULL,
  provider_code VARCHAR(80) NOT NULL,
  external_event_id VARCHAR(190) NOT NULL,
  event_type VARCHAR(120) NOT NULL,
  payload_hash CHAR(64) NOT NULL,
  payload_json JSON DEFAULT NULL,
  signature_valid TINYINT(1) NOT NULL DEFAULT 0,
  processing_status ENUM('received','verified','processed','ignored','failed') NOT NULL DEFAULT 'received',
  processing_error TEXT DEFAULT NULL,
  received_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  processed_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_payment_webhook_event (provider_code, external_event_id),
  KEY idx_payment_webhook_status_date (processing_status, received_at),
  CONSTRAINT fk_payment_webhook_provider FOREIGN KEY (payment_provider_id) REFERENCES payment_providers(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO gpm_schema_versions (version_key, checksum)
VALUES ('20261111_001_integrations_foundation', SHA2('20261111_001_integrations_foundation', 256))
ON DUPLICATE KEY UPDATE version_key = VALUES(version_key);
