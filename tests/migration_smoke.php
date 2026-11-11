<?php
declare(strict_types=1);

/**
 * Read-only post-migration smoke test.
 * Set DB_HOST, DB_PORT, DB_NAME, DB_USER and DB_PASS to a disposable DB copy.
 * This script never writes to or deletes data.
 */
$required = ['DB_HOST', 'DB_NAME', 'DB_USER'];
foreach ($required as $key) {
    if (getenv($key) === false || getenv($key) === '') {
        fwrite(STDERR, "Missing environment variable: {$key}\n");
        exit(2);
    }
}
$host = getenv('DB_HOST');
$port = getenv('DB_PORT') ?: '3306';
$name = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS') ?: '';
$pdo = new PDO(
    "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
    $user,
    $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
$tables = ['login_attempts', 'gpm_schema_versions', 'supplier_integrations', 'supplier_product_mappings', 'supplier_sync_runs', 'payment_providers', 'payment_transactions', 'payment_webhook_events'];
foreach ($tables as $table) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
    $stmt->execute([$table]);
    if ((int) $stmt->fetchColumn() !== 1) {
        fwrite(STDERR, "Missing table after migrations: {$table}\n");
        exit(1);
    }
}
echo "OK: all expected security/integration tables exist in {$name}.\n";
