<?php
declare(strict_types=1);

/*
 * Bootstrap mínimo do lado do cliente GPM.
 * Não substitui o bootstrap administrativo existente se já tiveres um.
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $https,
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

require_once dirname(__DIR__) . '/config/database.php';

if (!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
if (!function_exists('escapar')) {
    function escapar($value): string { return e($value); }
}
if (!function_exists('redirect')) {
    function redirect(string $path): never {
        header('Location: ' . $path);
        exit;
    }
}
if (!function_exists('flash')) {
    function flash(string $type, string $message): void {
        $_SESSION['_gpm_flash'][$type] = $message;
    }
}
if (!function_exists('get_flash')) {
    function get_flash(string $type): ?string {
        $message = $_SESSION['_gpm_flash'][$type] ?? null;
        unset($_SESSION['_gpm_flash'][$type]);
        return is_string($message) ? $message : null;
    }
}
if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        if (empty($_SESSION['_gpm_csrf'])) {
            $_SESSION['_gpm_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_gpm_csrf'];
    }
}
if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
    }
}
if (!function_exists('verify_csrf')) {
    function verify_csrf(): void {
        $sent = (string)($_POST['_csrf'] ?? '');
        $known = (string)($_SESSION['_gpm_csrf'] ?? '');
        if ($sent === '' || $known === '' || !hash_equals($known, $sent)) {
            http_response_code(419);
            exit('A sessão expirou. Atualiza a página e tenta novamente.');
        }
    }
}
if (!function_exists('cliente_atual')) {
    function cliente_atual(): ?array {
        $c = $_SESSION['gpm_customer'] ?? null;
        return is_array($c) ? $c : null;
    }
}
if (!function_exists('exigir_cliente')) {
    function exigir_cliente(): array {
        $cliente = cliente_atual();
        if (!$cliente) {
            flash('error', 'Inicia sessão para aceder à tua área de cliente.');
            redirect('login.php');
        }
        return $cliente;
    }
}
if (!function_exists('money')) {
    function money($value): string {
        return number_format((float)$value, 2, ',', '.') . ' Kz';
    }
}
if (!function_exists('gpm_table_exists')) {
    function gpm_table_exists(PDO $pdo, string $table): bool {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
        $stmt->execute([$table]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
if (!function_exists('gpm_columns')) {
    function gpm_columns(PDO $pdo, string $table): array {
        $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}`");
        return array_column($stmt->fetchAll(), 'Field');
    }
}
if (!function_exists('gpm_product_image')) {
    function gpm_product_image(?string $path): string {
        $path = trim((string)$path);
        if ($path === '') return '../assets/img/sem-imagem.svg';
        if (preg_match('~^https?://~i', $path)) return $path;
        $path = str_replace('\\', '/', $path);
        if (str_contains($path, '..')) return '../assets/img/sem-imagem.svg';
        if (str_starts_with($path, '/')) return $path;
        return '../' . ltrim($path, '/');
    }
}
if (!function_exists('gpm_cart_count')) {
    function gpm_cart_count(): int {
        return array_sum(array_map('intval', $_SESSION['gpm_cart'] ?? []));
    }
}
