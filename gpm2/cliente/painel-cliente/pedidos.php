<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/iniciar.php';

$cliente = cliente_atual();
if (!$cliente) { $_SESSION['gpm_redirect_after_login'] = 'painel-cliente/pedidos.php'; header('Location: ../login.php'); exit; }
$pdo = db();
$stmt = $pdo->prepare("SELECT id, order_number, total, status, ordered_at FROM orders WHERE customer_id = ? ORDER BY ordered_at DESC, id DESC");
$stmt->execute([(int)$cliente['id']]);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$labels = ['pending'=>'Pendente','confirmed'=>'Confirmado','preparing'=>'Em preparação','shipped'=>'Enviado','completed'=>'Concluído','cancelled'=>'Cancelado'];
function pedidos_money($v): string { return number_format((float)$v, 2, ',', '.') . ' Kz'; }
?>
<!doctype html><html lang="pt"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Minhas compras | GPM</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f5f7fb;font-family:Arial,sans-serif;color:#172033}header{background:white;padding:18px max(20px,calc((100% - 1050px)/2));border-bottom:1px solid #e7eaf0;display:flex;justify-content:space-between;gap:15px;flex-wrap:wrap}a{color:#172033;text-decoration:none;font-weight:700}.brand{font-size:21px}.brand span{color:#FEA619}nav{display:flex;gap:15px;flex-wrap:wrap}main{max-width:1050px;margin:30px auto;padding:0 20px}h1{margin:0 0 8px}.muted{color:#697386;font-size:14px}.card{background:white;border:1px solid #e7eaf0;border-radius:14px;padding:20px;margin-top:20px;overflow-x:auto}table{width:100%;border-collapse:collapse;min-width:600px}th,td{text-align:left;padding:14px 10px;border-bottom:1px solid #e7eaf0;font-size:14px}th{font-size:12px;color:#697386;text-transform:uppercase}.badge{background:#fff0d4;border-radius:20px;padding:5px 9px;font-size:12px}.btn{display:inline-block;background:#FEA619;padding:9px 12px;border-radius:8px;margin:2px}.empty{text-align:center;padding:30px;color:#697386}
</style></head><body><header><a class="brand" href="index.php">G<span>PM</span> · Área do Cliente</a><nav><a href="index.php">Painel</a><a href="pedidos.php">Minhas compras</a><a href="dados.php">Meus dados</a><a href="../logout.php">Sair</a></nav></header>
<main><h1>Minhas compras</h1><div class="muted">Aqui encontras todas as encomendas feitas com a tua conta.</div><div class="card">
<?php if (!$pedidos): ?><div class="empty">Ainda não tens encomendas. <a href="../index.php">Ver produtos</a></div>
<?php else: ?><table><thead><tr><th>Número</th><th>Data</th><th>Total</th><th>Estado</th><th>Consultar</th></tr></thead><tbody>
<?php foreach($pedidos as $p): ?><tr><td><?= htmlspecialchars((string)$p['order_number'],ENT_QUOTES,'UTF-8') ?></td><td><?= htmlspecialchars(date('d/m/Y H:i',strtotime((string)$p['ordered_at'])),ENT_QUOTES,'UTF-8') ?></td><td><?= pedidos_money($p['total']) ?></td><td><span class="badge"><?= htmlspecialchars($labels[$p['status']] ?? $p['status'],ENT_QUOTES,'UTF-8') ?></span></td><td><a class="btn" href="../pedido.php?id=<?= (int)$p['id'] ?>">Detalhes</a><a class="btn" href="../chat.php?pedido=<?= (int)$p['id'] ?>">Chat</a></td></tr><?php endforeach; ?>
</tbody></table><?php endif; ?></div></main></body></html>
