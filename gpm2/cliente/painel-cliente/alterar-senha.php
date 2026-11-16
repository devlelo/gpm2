<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/iniciar.php';
$cliente = cliente_atual();
if (!$cliente) { $_SESSION['gpm_redirect_after_login'] = 'painel-cliente/alterar-senha.php'; header('Location: ../login.php'); exit; }
$pdo = db(); $erro = ''; $sucesso = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $atual = (string)($_POST['password_current'] ?? '');
    $nova = (string)($_POST['password'] ?? '');
    $confirmacao = (string)($_POST['password_confirmation'] ?? '');
    $stmt = $pdo->prepare("SELECT password FROM customers WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$cliente['id']]); $hash = $stmt->fetchColumn();
    if (!$hash || !password_verify($atual, (string)$hash)) $erro = 'A palavra-passe atual está incorreta.';
    elseif (strlen($nova) < 10 || strlen($nova) > 72) $erro = 'A nova palavra-passe deve ter entre 10 e 72 caracteres.';
    elseif ($nova !== $confirmacao) $erro = 'A confirmação não corresponde à nova palavra-passe.';
    else {
        $stmt = $pdo->prepare("UPDATE customers SET password = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([password_hash($nova, PASSWORD_DEFAULT), (int)$cliente['id']]);
        session_regenerate_id(true);
        $sucesso = 'A palavra-passe foi alterada com sucesso.';
    }
}
function senha_e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<?php $tituloPagina = 'Segurança da conta'; require __DIR__ . '/../includes/header.php'; ?>
<h1>Alterar palavra-passe</h1><div class="muted">Usa uma palavra-passe forte com pelo menos 10 caracteres.</div><section class="card">
<?php if($erro): ?><div class="error"><?= senha_e($erro) ?></div><?php endif; ?><?php if($sucesso): ?><div class="success"><?= senha_e($sucesso) ?></div><?php endif; ?>
<form method="post"><?= csrf_field() ?><div class="field"><label for="password_current">Palavra-passe atual</label><input id="password_current" type="password" name="password_current" required autocomplete="current-password"></div><div class="field"><label for="password">Nova palavra-passe</label><input id="password" type="password" name="password" minlength="10" maxlength="72" required autocomplete="new-password"></div><div class="field"><label for="password_confirmation">Confirmar nova palavra-passe</label><input id="password_confirmation" type="password" name="password_confirmation" minlength="8" required autocomplete="new-password"></div><button class="btn" type="submit">Guardar nova palavra-passe</button></form>
</section><?php require __DIR__ . '/../includes/footer.php'; ?>
