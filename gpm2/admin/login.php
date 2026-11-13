<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

if (current_user()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $login = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    try {
        if ($login === '' || !filter_var($login, FILTER_VALIDATE_EMAIL) || strlen($password) > 4096) {
            $error = 'Credenciais inválidas ou conta inativa.';
        } elseif (login_is_limited($login)) {
            http_response_code(429);
            $error = 'Demasiadas tentativas. Aguarda 15 minutos antes de tentar novamente.';
        } else {
            $st = db()->prepare(
                'SELECT id, name, email, password, role, active
                 FROM users WHERE email = ? LIMIT 1'
            );
            $st->execute([$login]);
            $user = $st->fetch(PDO::FETCH_ASSOC);

            if (
                $user
                && (int) $user['active'] === 1
                && is_string($user['password'] ?? null)
                && password_verify($password, $user['password'])
                && in_array((string) $user['role'], ['admin', 'seller', 'stock_manager'], true)
            ) {
                login_clear_failures($login);
                session_regenerate_id(true);
                $_SESSION['gpm_user'] = [
                    'id' => (int) $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                ];
                redirect('index.php');
            }

            login_register_failure($login);
            $error = 'Credenciais inválidas ou conta inativa.';
        }
    } catch (Throwable $exception) {
        error_log('[GPM admin login] ' . $exception->getMessage());
        $error = 'Não foi possível validar o acesso neste momento.';
    }
}
?>
<!doctype html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#0A2945"><link rel="icon" type="image/png" href="../assets/img/GPMlogo.png?v=3">
    <title>Entrar | GPM</title>
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="login-page">
<form class="login-card" method="post" action="login.php">
    <div class="brand-login"><img src="../assets/img/GPMlogo.png?v=3" alt="GPM Conecta"><span class="brand-wordmark">gpm-conecta<small>PAINEL DE GESTÃO</small></span></div>
    <h1>Painel administrativo</h1>
    <p class="muted">Entre com a sua conta interna.</p>
    <?php if ($error !== ''): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
    <?= csrf_field() ?>
    <div class="field">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" autocomplete="username" maxlength="190" required>
    </div>
    <div class="field">
        <label for="password">Palavra-passe</label>
        <input id="password" name="password" type="password" autocomplete="current-password" maxlength="4096" required>
    </div>
    <button class="btn" type="submit">Entrar</button>
    <p class="hint">A conta administrativa deve existir na tabela users.</p>
</form>
</body>
</html>
