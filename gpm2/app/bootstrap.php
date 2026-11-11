<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Sessão segura
|--------------------------------------------------------------------------
*/

if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'httponly' => true,
        'secure'   => $secure,
        'samesite' => 'Lax',
    ]);

    session_start();
}

/*
|--------------------------------------------------------------------------
| Caminho base do projeto
|--------------------------------------------------------------------------
| Projeto localizado em C:\xampp\htdocs\gpm2
| URL local: http://localhost/gpm2
*/

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

if (!defined('BASE_URL')) {
    $scriptDirectory = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
    $baseDirectory = preg_replace('~/(?:admin|cliente)(?:/.*)?$~', '', $scriptDirectory);
    if (!is_string($baseDirectory)) {
        $baseDirectory = $scriptDirectory;
    }
    define('BASE_URL', rtrim($baseDirectory, '/'));
}

/*
|--------------------------------------------------------------------------
| Ligação à base de dados
|--------------------------------------------------------------------------
*/

require_once BASE_PATH . '/config/database.php';

/*
|--------------------------------------------------------------------------
| Escape de HTML
|--------------------------------------------------------------------------
*/

function e(mixed $value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

/*
|--------------------------------------------------------------------------
| Redirecionamento
|--------------------------------------------------------------------------
*/

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/*
|--------------------------------------------------------------------------
| Proteção CSRF
|--------------------------------------------------------------------------
*/

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="'
        . e(csrf_token())
        . '">';
}

function verify_csrf(): void
{
    $sent = (string) ($_POST['_csrf'] ?? '');
    $stored = (string) ($_SESSION['_csrf'] ?? '');

    if (
        $sent === ''
        || $stored === ''
        || !hash_equals($stored, $sent)
    ) {
        http_response_code(419);

        exit(
            'Pedido expirado ou token de segurança inválido. '
            . 'Volte atrás e tente novamente.'
        );
    }
}

/*
|--------------------------------------------------------------------------
| Utilizador autenticado
|--------------------------------------------------------------------------
*/

function current_user(): ?array
{
    $user = $_SESSION['gpm_user'] ?? null;

    return is_array($user) ? $user : null;
}

/*
|--------------------------------------------------------------------------
| Autenticação e permissões
|--------------------------------------------------------------------------
*/

function require_login(): void
{
    $user = current_user();

    if (!$user || empty($user['id'])) {
        redirect(BASE_URL . '/admin/login.php');
    }

    try {
        $statement = db()->prepare('SELECT active FROM users WHERE id = ? LIMIT 1');
        $statement->execute([(int)$user['id']]);
        $active = $statement->fetchColumn();
    } catch (Throwable $e) {
        error_log('[GPM auth] ' . $e->getMessage());
        http_response_code(503);
        exit('Não foi possível validar a sessão. Tenta novamente dentro de instantes.');
    }

    if ((int)$active !== 1) {
        unset($_SESSION['gpm_user']);
        session_regenerate_id(true);
        redirect(BASE_URL . '/admin/login.php');
    }
}

function require_admin(): void
{
    require_login();

    $user = current_user();

    if (($user['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Acesso não autorizado.');
    }
}

/**
 * Permite apenas os perfis especificados.
 *
 * Exemplo:
 * require_role(['admin', 'seller']);
 */
function require_role(array $roles): void
{
    require_login();

    $user = current_user();
    $role = (string) ($user['role'] ?? '');

    if (!in_array($role, $roles, true)) {
        http_response_code(403);
        exit('Acesso não autorizado.');
    }
}

/*
|--------------------------------------------------------------------------
| Mensagens temporárias (flash)
|--------------------------------------------------------------------------
*/

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['_flash'] = [
        'message' => $message,
        'type'    => $type,
    ];
}

function take_flash(): ?array
{
    $flash = $_SESSION['_flash'] ?? null;

    unset($_SESSION['_flash']);

    return is_array($flash) ? $flash : null;
}

/*
|--------------------------------------------------------------------------
| Formatação monetária
|--------------------------------------------------------------------------
*/

function money(mixed $value): string
{
    return number_format((float) $value, 2, ',', '.') . ' Kz';
}

/*
|--------------------------------------------------------------------------
| Layout administrativo
|--------------------------------------------------------------------------
*/

function layout_start(string $title): void
{
    $user = current_user();
    $flash = take_flash();

    echo '<!doctype html>';
    echo '<html lang="pt">';
    echo '<head>';
    echo '<meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<meta name="theme-color" content="#111827">';
    echo '<title>' . e($title) . ' | GPM</title>';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">';
    echo '<link rel="stylesheet" href="' . BASE_URL . '/admin/assets/css/admin.css">';
    echo '</head>';
    echo '<body class="admin-body">';

    require BASE_PATH . '/admin/includes/sidebar.php';

    echo '<main class="main">';
    echo '<header class="topbar">';
    echo '<div class="topbar-heading">';
    echo '<button class="sidebar-toggle" type="button" aria-label="Abrir menu" aria-controls="gpmSidebar" aria-expanded="false" id="gpmSidebarToggle"><span></span><span></span><span></span></button>';
    echo '<div><span class="eyebrow">GESTÃO COMERCIAL</span><h1>' . e($title) . '</h1></div>';
    echo '</div>';
    echo '<div class="user-chip"><span class="user-avatar">' . e(strtoupper(substr((string)($user['name'] ?? 'U'), 0, 1))) . '</span><span><strong>' . e($user['name'] ?? 'Utilizador') . '</strong><small>Administrador</small></span></div>';
    echo '</header>';

    if ($flash !== null) {
        $type = ($flash['type'] ?? 'success') === 'error' ? 'error' : 'success';
        echo '<div class="alert ' . $type . '" role="status">' . e($flash['message'] ?? '') . '</div>';
    }
}

function layout_end(): void
{
    echo '<footer class="footer">';
    echo 'GPM · Gestão de materiais de construção';
    echo '</footer>';

    echo '</main>';
    echo '</body>';
    echo '</html>';
}
