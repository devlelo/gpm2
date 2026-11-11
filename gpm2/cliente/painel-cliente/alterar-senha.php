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
    elseif (strlen($nova) < 8) $erro = 'A nova palavra-passe deve ter pelo menos 8 caracteres.';
    elseif ($nova !== $confirmacao) $erro = 'A confirmação não corresponde à nova palavra-passe.';
    else {
        $stmt = $pdo->prepare("UPDATE customers SET password = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([password_hash($nova, PASSWORD_DEFAULT), (int)$cliente['id']]);
        $sucesso = 'A palavra-passe foi alterada com sucesso.';
    }
}
function senha_e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html><html lang="pt"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Alterar palavra-passe | GPM</title><style>
*{box-sizing:border-box}body{margin:0;background:#f5f7fb;font-family:Arial,sans-serif;color:#172033}header{background:#fff;padding:18px max(20px,calc((100% - 800px)/2));border-bottom:1px solid #e7eaf0;display:flex;justify-content:space-between;gap:15px;flex-wrap:wrap}a{color:#172033;text-decoration:none;font-weight:700}.brand{font-size:21px}.brand span{color:#FEA619}nav{display:flex;gap:15px;flex-wrap:wrap}main{max-width:800px;margin:30px auto;padding:0 20px}h1{margin:0 0 8px}.muted{color:#697386;font-size:14px}.card{background:#fff;border:1px solid #e7eaf0;border-radius:14px;padding:22px;margin-top:20px}.field{display:flex;flex-direction:column;gap:7px;margin-bottom:16px}label{font-weight:700;font-size:13px}input{width:100%;padding:12px;border:1px solid #d7dce5;border-radius:8px;font:inherit}.btn{border:0;background:#FEA619;padding:12px 17px;border-radius:8px;font-weight:700;cursor:pointer}.success,.error{padding:12px;border-radius:8px;margin:14px 0}.success{background:#e9f9ef;color:#176536}.error{background:#fff0f0;color:#9b1c1c}
</style></head><body><header><a class="brand" href="index.php">G<span>PM</span> · Área do Cliente</a><nav><a href="index.php">Painel</a><a href="pedidos.php">Minhas compras</a><a href="dados.php">Meus dados</a><a href="../logout.php">Sair</a></nav></header><main><h1>Alterar palavra-passe</h1><div class="muted">Usa uma palavra-passe forte com pelo menos 8 caracteres.</div><section class="card">
<?php if($erro): ?><div class="error"><?= senha_e($erro) ?></div><?php endif; ?><?php if($sucesso): ?><div class="success"><?= senha_e($sucesso) ?></div><?php endif; ?>
<form method="post"><?= csrf_field() ?><div class="field"><label for="password_current">Palavra-passe atual</label><input id="password_current" type="password" name="password_current" required autocomplete="current-password"></div><div class="field"><label for="password">Nova palavra-passe</label><input id="password" type="password" name="password" minlength="8" required autocomplete="new-password"></div><div class="field"><label for="password_confirmation">Confirmar nova palavra-passe</label><input id="password_confirmation" type="password" name="password_confirmation" minlength="8" required autocomplete="new-password"></div><button class="btn" type="submit">Guardar nova palavra-passe</button></form>
</section></main></body> </html>
