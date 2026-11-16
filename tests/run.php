<?php
declare(strict_types=1);

require_once __DIR__ . '/../gpm2/app/bootstrap.php';

$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    $checks++;
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
};

$assert(e('<script>alert(1)</script>') === '&lt;script&gt;alert(1)&lt;/script&gt;', 'HTML output is escaped');
$assert(strlen(login_rate_limit_key(' USER@example.com ')) === 64, 'login key is a SHA-256 digest');
$assert(login_rate_limit_key('user@example.com') === login_rate_limit_key(' USER@example.com '), 'login key normalizes identifier');
$assert(csrf_token() === csrf_token(), 'CSRF token remains stable during a session');
$assert(strlen((string) csrf_token()) === 64, 'CSRF token has 256 bits of entropy');

require __DIR__ . '/project_routes_test.php';

echo "OK: {$checks} security and project route checks passed.\n";
