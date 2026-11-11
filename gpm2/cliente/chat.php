<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/iniciar.php';
exigir_cliente();

$pdo = db();
$clienteId = (int) cliente_atual()['id'];
$pedidoId = filter_input(INPUT_GET, 'pedido', FILTER_VALIDATE_INT);

if (!$pedidoId) {
    redirect('pedidos.php');
}

$stmt = $pdo->prepare(
    "SELECT c.id, c.status, o.order_number
     FROM conversations c
     INNER JOIN orders o ON o.id = c.order_id
     WHERE c.order_id = ? AND c.customer_id = ?
     LIMIT 1"
);
$stmt->execute([$pedidoId, $clienteId]);
$conversa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$conversa) {
    flash('error', 'Não tens acesso a esta conversa.');
    redirect('pedidos.php');
}

$conversaId = (int) $conversa['id'];
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $mensagem = trim($_POST['body'] ?? '');

    if ($conversa['status'] !== 'open') {
        $erro = 'Esta conversa está fechada.';
    } elseif ($mensagem === '') {
        $erro = 'Escreve uma mensagem antes de enviar.';
    } elseif (mb_strlen($mensagem) > 5000) {
        $erro = 'A mensagem não pode ultrapassar 5000 caracteres.';
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO messages (
                conversation_id, sender_type, customer_sender_id,
                user_sender_id, body, created_at
            ) VALUES (?, 'customer', ?, NULL, ?, NOW())"
        );
        $stmt->execute([$conversaId, $clienteId, $mensagem]);

        $stmt = $pdo->prepare(
            "UPDATE conversations
             SET last_message_at = NOW(), updated_at = NOW()
             WHERE id = ? AND customer_id = ?"
        );
        $stmt->execute([$conversaId, $clienteId]);

        redirect('chat.php?pedido=' . $pedidoId);
    }
}

$stmt = $pdo->prepare(
    "SELECT id, sender_type, customer_sender_id, user_sender_id,
            body, read_at, created_at
     FROM messages
     WHERE conversation_id = ?
     ORDER BY created_at ASC, id ASC"
);
$stmt->execute([$conversaId]);
$mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Marca como lidas as mensagens enviadas pela equipa. */
$stmt = $pdo->prepare(
    "UPDATE messages
     SET read_at = NOW()
     WHERE conversation_id = ?
       AND sender_type = 'staff'
       AND read_at IS NULL"
);
$stmt->execute([$conversaId]);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Conversa | GPM</title>
    <link rel="stylesheet" href="../assets/css/cliente.css">
    <style>
        body { background:#f5f5f5; }
        .pagina { max-width:850px; margin:25px auto; padding:15px; }
        .cabecalho, .chat, .escrever { background:#fff; border-radius:12px; padding:18px; margin-bottom:15px; }
        .mensagens { display:flex; flex-direction:column; gap:12px; }
        .mensagem { max-width:80%; padding:12px 15px; border-radius:12px; overflow-wrap:anywhere; }
        .cliente { background:#fff0d6; align-self:flex-end; }
        .equipa { background:#edf0f3; align-self:flex-start; }
        .autor { font-weight:bold; font-size:13px; margin-bottom:5px; }
        .data { color:#666; font-size:12px; margin-top:7px; }
        textarea { width:100%; padding:12px; border:1px solid #ddd; border-radius:8px; box-sizing:border-box; }
        .btn { margin-top:10px; background:#FEA619; padding:11px 16px; border:0; border-radius:8px; font-weight:bold; cursor:pointer; }
        .erro { background:#fff0e6; padding:12px; border-radius:8px; }
        .fechada { background:#eee; padding:12px; border-radius:8px; }
    </style>
</head>
<body>
<main class="pagina">
    <p><a href="pedido.php?id=<?= (int) $pedidoId ?>">← Voltar ao pedido</a></p>

    <section class="cabecalho">
        <h1>Conversa sobre <?= escapar($conversa['order_number']) ?></h1>
        <p>Conversa privada entre ti e a equipa GPM.</p>
        <p>Estado: <strong><?= $conversa['status'] === 'open' ? 'Aberta' : 'Fechada' ?></strong></p>
    </section>

    <section class="chat">
        <div class="mensagens">
            <?php if (!$mensagens): ?>
                <p>Ainda não existem mensagens.</p>
            <?php endif; ?>

            <?php foreach ($mensagens as $msg): ?>
                <?php $ehCliente = $msg['sender_type'] === 'customer'; ?>
                <article class="mensagem <?= $ehCliente ? 'cliente' : 'equipa' ?>">
                    <div class="autor">
                        <?= $ehCliente ? 'Tu' : 'Equipa GPM' ?>
                    </div>
                    <div><?= nl2br(escapar($msg['body'])) ?></div>
                    <div class="data"><?= escapar($msg['created_at']) ?></div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if ($erro): ?>
        <p class="erro"><?= escapar($erro) ?></p>
    <?php endif; ?>

    <?php if ($conversa['status'] === 'open'): ?>
        <form method="post" class="escrever">
            <?= csrf_field() ?>
            <label for="body"><strong>Escrever mensagem</strong></label>
            <textarea id="body" name="body" rows="4" maxlength="5000"
                      placeholder="Escreve a tua mensagem..." required></textarea>
            <button type="submit" class="btn">Enviar mensagem</button>
        </form>
    <?php else: ?>
        <div class="fechada">Esta conversa foi fechada pela equipa GPM.</div>
    <?php endif; ?>
</main>
</body>
</html>
