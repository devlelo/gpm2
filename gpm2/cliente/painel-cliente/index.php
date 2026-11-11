<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/iniciar.php';


$cliente = cliente_atual();
if (!$cliente) {
    $_SESSION['gpm_redirect_after_login'] = 'painel-cliente/index.php';
    header('Location: ../login.php');
    exit;
}
$pdo = db();
$clienteId = (int)$cliente['id'];

$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE customer_id = ?");
$stmt->execute([$clienteId]);
$totalPedidos = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE customer_id = ? AND status IN ('pending','confirmed','preparing','shipped')");
$stmt->execute([$clienteId]);
$pedidosAtivos = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT id, order_number, total, status, ordered_at FROM orders WHERE customer_id = ? ORDER BY ordered_at DESC, id DESC LIMIT 5");
$stmt->execute([$clienteId]);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

function estado_pedido_cliente(string $status): string {
    $estados = [
        'pending' => 'Pendente',
        'confirmed' => 'Confirmado',
        'preparing' => 'Em preparação',
        'shipped' => 'Enviado',
        'completed' => 'Concluído',
        'cancelled' => 'Cancelado'
    ];
    return $estados[$status] ?? $status;
}
function dinheiro_cliente($valor): string {
    return number_format((float)$valor, 2, ',', '.') . ' Kz';
}
?>
<!doctype html>
<html lang="pt">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Painel do Cliente | GPM</title>
<style>
:root{--orange:#FEA619;--ink:#172033;--muted:#697386;--line:#e7eaf0;--bg:#f5f7fb}
*{box-sizing:border-box}body{margin:0;background:var(--bg);font-family:Arial,sans-serif;color:var(--ink)}
header{background:#fff;border-bottom:1px solid var(--line);padding:17px max(20px,calc((100% - 1150px)/2));display:flex;justify-content:space-between;align-items:center;gap:15px}
.brand{font-weight:800;font-size:22px;color:var(--ink);text-decoration:none}.brand span{color:var(--orange)}
nav{display:flex;gap:15px;flex-wrap:wrap}nav a,.link{color:#293347;text-decoration:none;font-size:14px;font-weight:700}
main{max-width:1150px;margin:28px auto;padding:0 20px}.welcome{display:flex;justify-content:space-between;align-items:center;gap:15px;flex-wrap:wrap;margin-bottom:22px}
h1{font-size:27px;margin:0 0 7px}.muted{color:var(--muted);font-size:14px}.cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-bottom:22px}
.card{background:white;border:1px solid var(--line);border-radius:15px;padding:21px;box-shadow:0 4px 16px #17203308}.metric{font-size:28px;font-weight:800;margin-top:10px}
.actions{display:flex;gap:10px;flex-wrap:wrap}.btn{display:inline-block;background:var(--orange);color:#1d2430;text-decoration:none;padding:11px 15px;border-radius:9px;font-weight:700;border:0;cursor:pointer}.btn.alt{background:#fff;border:1px solid var(--line)}
.section-title{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:14px}h2{font-size:19px;margin:0}
.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;min-width:580px}th,td{text-align:left;padding:13px 10px;border-bottom:1px solid var(--line);font-size:14px}th{color:var(--muted);font-size:12px;text-transform:uppercase}
.badge{display:inline-block;border-radius:20px;padding:5px 9px;background:#fff0d4;font-size:12px;font-weight:700}.empty{padding:25px;text-align:center;color:var(--muted)}
@media(max-width:700px){.cards{grid-template-columns:1fr}.welcome{align-items:flex-start}header{align-items:flex-start;flex-direction:column}main{margin-top:20px}}
</style></head>
<body>
<header>
<a class="brand" href="index.php">G<span>PM</span> <small style="font-size:12px;color:#697386">Área do Cliente</small></a>
<nav><a href="index.php">Painel</a><a href="pedidos.php">Minhas compras</a><a href="dados.php">Meus dados</a><a href="../logout.php">Sair</a></nav>
</header>
<main>
<div class="welcome">
<div><h1>Olá, <?= htmlspecialchars((string)($cliente['name'] ?? 'Cliente'), ENT_QUOTES, 'UTF-8') ?>!</h1><div class="muted">Acompanha as tuas compras e fala com a equipa GPM.</div></div>
<div class="actions"><a class="btn" href="../index.php">Continuar a comprar</a><a class="btn alt" href="dados.php">Editar os meus dados</a></div>
</div>
<div class="cards">
<div class="card"><div class="muted">Total de compras</div><div class="metric"><?= $totalPedidos ?></div></div>
<div class="card"><div class="muted">Compras em andamento</div><div class="metric"><?= $pedidosAtivos ?></div></div>
<div class="card"><div class="muted">Conta</div><div class="metric" style="font-size:20px">Ativa</div><div class="muted" style="margin-top:8px"><?= htmlspecialchars((string)($cliente['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div></div>
</div>
<section class="card">
<div class="section-title"><h2>Compras recentes</h2><a class="link" href="pedidos.php">Ver todas →</a></div>
<?php if (!$pedidos): ?>
<div class="empty">Ainda não tens compras. <a href="../index.php">Explora os produtos</a> e faz a tua primeira encomenda.</div>
<?php else: ?>
<div class="table-wrap"><table><thead><tr><th>Pedido</th><th>Data</th><th>Total</th><th>Estado</th><th>Ações</th></tr></thead><tbody>
<?php foreach ($pedidos as $p): ?>
<tr>
<td><strong><?= htmlspecialchars((string)$p['order_number'], ENT_QUOTES, 'UTF-8') ?></strong></td>
<td><?= htmlspecialchars(date('d/m/Y H:i', strtotime((string)$p['ordered_at'])), ENT_QUOTES, 'UTF-8') ?></td>
<td><?= dinheiro_cliente($p['total']) ?></td>
<td><span class="badge"><?= htmlspecialchars(estado_pedido_cliente((string)$p['status']), ENT_QUOTES, 'UTF-8') ?></span></td>
<td><a class="link" href="../pedido.php?id=<?= (int)$p['id'] ?>">Detalhes</a> · <a class="link" href="../chat.php?pedido=<?= (int)$p['id'] ?>">Chat</a></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</section>
</main></body></html>
