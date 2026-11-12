<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/iniciar.php';



$cliente = cliente_atual();
if (!$cliente) { $_SESSION['gpm_redirect_after_login'] = 'pedidos.php'; header('Location: login.php'); exit; }
$pdo = db();
$pedidoId = filter_input(INPUT_GET, 'pedido', FILTER_VALIDATE_INT);
if (!$pedidoId || $pedidoId < 1) { http_response_code(400); exit('Compra inválida.'); }
$stmt = $pdo->prepare("SELECT id, order_number FROM orders WHERE id = ? AND customer_id = ? LIMIT 1");
$stmt->execute([$pedidoId, (int)$cliente['id']]); $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$pedido) { http_response_code(404); exit('Compra não encontrada.'); }
$stmt = $pdo->prepare("SELECT id, status FROM conversations WHERE order_id = ? AND customer_id = ? LIMIT 1");
$stmt->execute([$pedidoId, (int)$cliente['id']]); $conversa = $stmt->fetch(PDO::FETCH_ASSOC);
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $body = trim((string)($_POST['body'] ?? ''));
    if (!$conversa) {
        $erro = 'Ainda não existe uma conversa para esta compra. Contacta a equipa GPM.';
    } elseif ($conversa['status'] !== 'open') {
        $erro = 'Esta conversa foi encerrada e já não aceita novas mensagens.';
    } elseif ($body === '' || mb_strlen($body) > 5000) {
        $erro = 'Escreve uma mensagem com até 5000 caracteres.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO messages (conversation_id, sender_type, customer_sender_id, user_sender_id, body, created_at) VALUES (?, 'customer', ?, NULL, ?, NOW())");
        $stmt->execute([(int)$conversa['id'], (int)$cliente['id'], $body]);
        $stmt = $pdo->prepare("UPDATE conversations SET last_message_at = NOW(), updated_at = NOW() WHERE id = ? AND customer_id = ?");
        $stmt->execute([(int)$conversa['id'], (int)$cliente['id']]);
        header('Location: chat.php?pedido=' . (int)$pedidoId);
        exit;
    }
}
$mensagens = [];
if ($conversa) {
    $stmt = $pdo->prepare("SELECT sender_type, body, created_at FROM messages WHERE conversation_id = ? ORDER BY created_at ASC, id ASC");
    $stmt->execute([(int)$conversa['id']]); $mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function chat_e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<?php $tituloPagina = 'Chat da compra'; require __DIR__ . '/../includes/header.php'; ?>
<h1>Chat da compra <?= chat_e($pedido['order_number']) ?></h1><div class="muted">Esta conversa é privada e está associada apenas a esta encomenda.</div>
<div class="chatbox"><div class="messages">
<?php if (!$conversa): ?><div class="empty">A conversa desta compra ainda não foi criada.</div>
<?php elseif (!$mensagens): ?><div class="empty">Ainda não existem mensagens. Envia a primeira mensagem à equipa GPM.</div>
<?php else: foreach($mensagens as $m): ?><div class="msg <?= $m['sender_type']==='customer'?'mine':'' ?>"><?= chat_e($m['body']) ?><span class="time"><?= $m['sender_type']==='customer'?'Tu':'Equipa GPM' ?> · <?= chat_e(date('d/m/Y H:i',strtotime((string)$m['created_at']))) ?></span></div><?php endforeach; endif; ?>
</div>
<?php if ($conversa && $conversa['status']==='open'): ?><form class="send" method="post"><?= csrf_field() ?><?php if($erro): ?><div class="error"><?= chat_e($erro) ?></div><?php endif; ?><label for="body">A tua mensagem</label><textarea id="body" name="body" maxlength="5000" required placeholder="Escreve aqui a tua mensagem..."></textarea><button class="btn" type="submit">Enviar mensagem</button></form>
<?php elseif($conversa): ?><div class="send">Esta conversa está encerrada.</div><?php endif; ?>
</div><p><a href="pedido.php?id=<?= (int)$pedidoId ?>">← Voltar aos detalhes da compra</a></p><?php require __DIR__ . '/../includes/footer.php'; ?>
