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
<?php $tituloPagina = 'Meus dados'; require __DIR__ . '/../includes/header.php'; ?>
<h1>Meus dados</h1><div class="muted">Mantém os teus dados e contactos atualizados para facilitar as entregas.</div><section class="card">
<?php if($erro): ?><div class="error"><?= dados_e($erro) ?></div><?php endif; ?><?php if($sucesso): ?><div class="success"><?= dados_e($sucesso) ?></div><?php endif; ?>
<form method="post"><?= csrf_field() ?><div class="grid">
<div class="field"><label for="name">Nome completo / empresa *</label><input id="name" name="name" maxlength="180" required value="<?= dados_e($dados['name']) ?>"></div>
<div class="field"><label for="email">E-mail *</label><input id="email" type="email" name="email" maxlength="190" required value="<?= dados_e($dados['email']) ?>"></div>
<div class="field"><label for="phone">Telefone *</label><input id="phone" name="phone" maxlength="40" required value="<?= dados_e($dados['phone']) ?>"></div>
<div class="field"><label for="nif">NIF</label><input id="nif" name="nif" maxlength="50" value="<?= dados_e($dados['nif']) ?>"></div>
<div class="field"><label for="province">Província</label><input id="province" name="province" maxlength="100" value="<?= dados_e($dados['province']) ?>"></div>
<div class="field"><label for="city">Cidade / Município</label><input id="city" name="city" maxlength="100" value="<?= dados_e($dados['city']) ?>"></div>
</div><div class="field"><label for="address">Endereço habitual</label><textarea id="address" name="address" rows="3" maxlength="2000"><?= dados_e($dados['address']) ?></textarea></div><button class="btn" type="submit">Guardar alterações</button> <a href="alterar-senha.php" style="margin-left:12px">Alterar palavra-passe</a></form>
</section><?php require __DIR__ . '/../includes/footer.php'; ?>
