<?php
declare(strict_types=1);

/**
 * Configuração PDO única da GPM.
 * Produção: definir DB_HOST, DB_PORT, DB_NAME, DB_USER e DB_PASS no ambiente.
 * XAMPP local: os defaults só são permitidos quando APP_ENV=local (ou não definido).
 */
$environment = strtolower((string) (getenv('APP_ENV') ?: 'local'));
$required = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
$missing = [];

if ($environment === 'production') {
    foreach ($required as $key) {
        if (getenv($key) === false || (string) getenv($key) === '') {
            $missing[] = $key;
        }
    }
}

if ($missing !== []) {
    error_log('[GPM] Configuração de BD incompleta: ' . implode(', ', $missing));
    throw new RuntimeException('Configuração de base de dados incompleta. Contacte o administrador.');
}

if (!defined('DB_HOST')) define('DB_HOST', (string) (getenv('DB_HOST') ?: '127.0.0.1'));
if (!defined('DB_PORT')) define('DB_PORT', (string) (getenv('DB_PORT') ?: '3306'));
if (!defined('DB_NAME')) define('DB_NAME', (string) (getenv('DB_NAME') ?: 'gpm_mvp_nova'));
if (!defined('DB_USER')) define('DB_USER', (string) (getenv('DB_USER') ?: 'root'));
if (!defined('DB_PASS')) define('DB_PASS', (string) (getenv('DB_PASS') ?: ''));

if (!function_exists('db')) {
    function db(): PDO
    {
        static $pdo = null;
        if ($pdo instanceof PDO) {
            return $pdo;
        }

        $dsn = 'mysql:host=' . DB_HOST
            . ';port=' . DB_PORT
            . ';dbname=' . DB_NAME
            . ';charset=utf8mb4';

        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::ATTR_PERSISTENT => false,
        ]);

        return $pdo;
    }
}
