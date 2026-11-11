<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/iniciar.php';

$cliente = cliente_atual();
if (!$cliente) { $_SESSION['gpm_redirect_after_login'] = 'painel-cliente/dados.php'; header('Location: ../login.php'); exit; }
$pdo = db();
$stmt = $pdo->prepare("SELECT id, customer_type, name, nif, phone, email, address, city, province FROM customers WHERE id = ? LIMIT 1");
$stmt->execute([(int)$cliente['id']]); $dados = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$dados) { session_destroy(); header('Location: ../login.php'); exit; }
$erro = ''; $sucesso = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $nome = trim((string)($_POST['name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $telefone = trim((string)($_POST['phone'] ?? ''));
    $nif = trim((string)($_POST['nif'] ?? ''));
    $endereco = trim((string)($_POST['address'] ?? ''));
    $cidade = trim((string)($_POST['city'] ?? ''));
    $provincia = trim((string)($_POST['province'] ?? ''));
    if ($nome === '' || mb_strlen($nome) > 180) $erro = 'Indica um nome válido.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) $erro = 'Indica um e-mail válido.';
    elseif ($telefone === '' || mb_strlen($telefone) > 40) $erro = 'Indica um telefone válido.';
    else {
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ? AND id <> ? LIMIT 1");
        $stmt->execute([$email, (int)$cliente['id']]);
        if ($stmt->fetch()) $erro = 'Este e-mail já está associado a outra conta.';
        else {
            $stmt = $pdo->prepare("UPDATE customers SET name = ?, email = ?, phone = ?, nif = ?, address = ?, city = ?, province = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$nome, $email, $telefone, $nif !== '' ? $nif : null, $endereco !== '' ? $endereco : null, $cidade !== '' ? $cidade : null, $provincia !== '' ? $provincia : null, (int)$cliente['id']]);
            $_SESSION['gpm_customer']['name'] = $nome;
            $_SESSION['gpm_customer']['email'] = $email;
            $dados = array_merge($dados, ['name'=>$nome,'email'=>$email,'phone'=>$telefone,'nif'=>$nif,'address'=>$endereco,'city'=>$cidade,'province'=>$provincia]);
            $sucesso = 'Os teus dados foram atualizados.';
        }
    }
}
function dados_e($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html><html lang="pt"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Meus dados | GPM</title><style>
*{box-sizing:border-box}body{margin:0;background:#f5f7fb;font-family:Arial,sans-serif;color:#172033}header{background:#fff;padding:18px max(20px,calc((100% - 850px)/2));border-bottom:1px solid #e7eaf0;display:flex;justify-content:space-between;gap:15px;flex-wrap:wrap}a{color:#172033;text-decoration:none;font-weight:700}.brand{font-size:21px}.brand span{color:#FEA619}nav{display:flex;gap:15px;flex-wrap:wrap}main{max-width:850px;margin:30px auto;padding:0 20px}h1{margin:0 0 8px}.muted{color:#697386;font-size:14px}.card{background:#fff;border:1px solid #e7eaf0;border-radius:14px;padding:22px;margin-top:20px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:15px}.field{display:flex;flex-direction:column;gap:7px;margin-bottom:15px}label{font-weight:700;font-size:13px}input,textarea{width:100%;padding:12px;border:1px solid #d7dce5;border-radius:8px;font:inherit}.btn{border:0;background:#FEA619;padding:12px 17px;border-radius:8px;font-weight:700;cursor:pointer}.success,.error{padding:12px;border-radius:8px;margin:14px 0}.success{background:#e9f9ef;color:#176536}.error{background:#fff0f0;color:#9b1c1c}@media(max-width:600px){.grid{grid-template-columns:1fr}}
</style></head><body><header><a class="brand" href="index.php">G<span>PM</span> · Área do Cliente</a><nav><a href="index.php">Painel</a><a href="pedidos.php">Minhas compras</a><a href="dados.php">Meus dados</a><a href="../logout.php">Sair</a></nav></header><main><h1>Meus dados</h1><div class="muted">Mantém os teus dados e contactos atualizados para facilitar as entregas.</div><section class="card">
<?php if($erro): ?><div class="error"><?= dados_e($erro) ?></div><?php endif; ?><?php if($sucesso): ?><div class="success"><?= dados_e($sucesso) ?></div><?php endif; ?>
<form method="post"><?= csrf_field() ?><div class="grid">
<div class="field"><label for="name">Nome completo / empresa *</label><input id="name" name="name" maxlength="180" required value="<?= dados_e($dados['name']) ?>"></div>
<div class="field"><label for="email">E-mail *</label><input id="email" type="email" name="email" maxlength="190" required value="<?= dados_e($dados['email']) ?>"></div>
<div class="field"><label for="phone">Telefone *</label><input id="phone" name="phone" maxlength="40" required value="<?= dados_e($dados['phone']) ?>"></div>
<div class="field"><label for="nif">NIF</label><input id="nif" name="nif" maxlength="50" value="<?= dados_e($dados['nif']) ?>"></div>
<div class="field"><label for="province">Província</label><input id="province" name="province" maxlength="100" value="<?= dados_e($dados['province']) ?>"></div>
<div class="field"><label for="city">Cidade / Município</label><input id="city" name="city" maxlength="100" value="<?= dados_e($dados['city']) ?>"></div>
</div><div class="field"><label for="address">Endereço habitual</label><textarea id="address" name="address" rows="3" maxlength="2000"><?= dados_e($dados['address']) ?></textarea></div><button class="btn" type="submit">Guardar alterações</button> <a href="alterar-senha.php" style="margin-left:12px">Alterar palavra-passe</a></form>
</section></main></body></html>
