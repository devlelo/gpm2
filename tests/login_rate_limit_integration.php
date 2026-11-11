<?php
declare(strict_types=1);

/**
 * Integration test for login throttling.
 * Run ONLY against a disposable database whose name ends in _test.
 */
$database = (string) (getenv('DB_NAME') ?: '');
if (!str_ends_with($database, '_test')) {
    fwrite(STDERR, "Refusing to run: DB_NAME must end with _test.\n");
    exit(2);
}
require_once __DIR__ . '/../gpm2/app/bootstrap.php';

$_SERVER['REMOTE_ADDR'] = '192.0.2.123';
$identifier = 'rate-limit-test-' . bin2hex(random_bytes(6)) . '@example.invalid';
$identifierKey = login_rate_limit_key($identifier);
$ipKey = login_ip_rate_limit_key();

try {
    if (login_is_limited($identifier)) {
        throw new RuntimeException('Fresh test identifier was already limited.');
    }
    for ($i = 0; $i < 5; $i++) {
        login_register_failure($identifier);
    }
    if (!login_is_limited($identifier)) {
        throw new RuntimeException('Five failures did not trigger identifier throttling.');
    }
    login_clear_failures($identifier);
    if (login_is_limited($identifier)) {
        throw new RuntimeException('Clearing identifier failures did not unlock the identifier.');
    }
    echo "OK: login throttle integration checks passed.\n";
} finally {
    $cleanup = db()->prepare('DELETE FROM login_attempts WHERE attempt_key IN (?, ?)');
    $cleanup->execute([$identifierKey, $ipKey]);
}
