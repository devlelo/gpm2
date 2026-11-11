<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_role(['admin', 'seller']);

$pdo = db();
$userId = (int)(current_user()['id'] ?? 0);
$mensagemErro = '';
$conversationId = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $conversationId = (int)($_POST['conversation_id'] ?? 0);
    $action = (string)($_POST['action'] ?? 'send');

    if ($action === 'toggle_status' && $conversationId > 0) {
        $statusStmt = $pdo->prepare('SELECT status FROM conversations WHERE id = ? LIMIT 1');
        $statusStmt->execute([$conversationId]);
        $currentStatus = $statusStmt->fetchColumn();

        if ($currentStatus === false) {
            $mensagemErro = 'Esta conversa não existe.';
        } else {
            $newStatus = $currentStatus === 'open' ? 'closed' : 'open';
            $pdo->prepare('UPDATE conversations SET status = ?, updated_at = NOW() WHERE id = ?')
                ->execute([$newStatus, $conversationId]);
            redirect('chat.php?id=' . $conversationId);
        }
    }

    $message = trim((string)($_POST['message'] ?? ''));

    if ($conversationId <= 0 || $message === '') {
        $mensagemErro = 'Escreve uma mensagem antes de enviar.';
    } elseif (mb_strlen($message) > 5000) {
        $mensagemErro = 'A mensagem não pode ultrapassar 5000 caracteres.';
    } else {
        $check = $pdo->prepare('SELECT id, status FROM conversations WHERE id = ? LIMIT 1');
        $check->execute([$conversationId]);
        $conversation = $check->fetch(PDO::FETCH_ASSOC);

        if (!$conversation) {
            $mensagemErro = 'Esta conversa não existe.';
        } elseif ($conversation['status'] !== 'open') {
            $mensagemErro = 'Esta conversa está fechada.';
        } else {
            $pdo->beginTransaction();
            try {
                $insert = $pdo->prepare(
                    "INSERT INTO messages
                     (conversation_id, sender_type, customer_sender_id, user_sender_id, body, created_at)
                     VALUES (?, 'staff', NULL, ?, ?, NOW())"
                );
                $insert->execute([$conversationId, $userId, $message]);

                $pdo->prepare(
                    'UPDATE conversations SET last_message_at = NOW(), updated_at = NOW() WHERE id = ?'
                )->execute([$conversationId]);

                $pdo->commit();
                redirect('chat.php?id=' . $conversationId);
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e;
            }
        }
    }
}

$conversas = $pdo->query(
    "SELECT c.id, c.order_id, c.status, c.last_message_at, c.updated_at,
            o.order_number, o.customer_name,
            (SELECT COUNT(*) FROM messages m
             WHERE m.conversation_id = c.id
               AND m.sender_type = 'customer'
               AND m.read_at IS NULL) AS mensagens_nao_lidas
     FROM conversations c
     LEFT JOIN orders o ON o.id = c.order_id
     ORDER BY COALESCE(c.last_message_at, c.updated_at) DESC, c.id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$conversa = null;
$mensagens = [];
if ($conversationId > 0) {
    $stmt = $pdo->prepare(
        'SELECT c.id, c.order_id, c.status, o.order_number
         FROM conversations c
         LEFT JOIN orders o ON o.id = c.order_id
         WHERE c.id = ? LIMIT 1'
    );
    $stmt->execute([$conversationId]);
    $conversa = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($conversa) {
        $read = $pdo->prepare(
            "UPDATE messages SET read_at = NOW()
             WHERE conversation_id = ?
               AND sender_type = 'customer'
               AND read_at IS NULL"
        );
        $read->execute([$conversationId]);

        $stmt = $pdo->prepare(
            'SELECT m.id, m.sender_type, m.user_sender_id, m.customer_sender_id,
                    m.body, m.read_at, m.created_at,
                    COALESCE(u.name, c.name, "Cliente") AS sender_name
             FROM messages m
             LEFT JOIN users u ON u.id = m.user_sender_id
             LEFT JOIN customers c ON c.id = m.customer_sender_id
             WHERE m.conversation_id = ?
             ORDER BY m.created_at ASC, m.id ASC'
        );
        $stmt->execute([$conversationId]);
        $mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

layout_start('Gestão de Conversas');
?>
<section class="panel">
    <h2>Atendimento aos clientes</h2>
    <p class="muted">Consulta as conversas associadas aos pedidos e responde aos clientes.</p>
    <?php if ($mensagemErro !== ''): ?><div class="alert error" role="alert"><?= e($mensagemErro) ?></div><?php endif; ?>
    <div class="chat-layout">
        <aside class="chat-list">
            <h2>Conversas</h2>
            <?php if (!$conversas): ?><p class="empty">Ainda não existem conversas.</p><?php endif; ?>
            <?php foreach ($conversas as $item): ?>
                <a class="conversation-link <?= $conversationId === (int)$item['id'] ? 'active' : '' ?>" href="chat.php?id=<?= (int)$item['id'] ?>">
                    <strong><?= e($item['customer_name'] ?: 'Cliente') ?></strong>
                    <br><small>Pedido <?= e($item['order_number'] ?: ('#' . $item['order_id'])) ?></small>
                    <div class="conversation-meta">
                        <span class="status"><?= $item['status'] === 'open' ? 'Aberta' : 'Fechada' ?></span>
                        <?php if ((int)$item['mensagens_nao_lidas'] > 0): ?><span class="unread"><?= (int)$item['mensagens_nao_lidas'] ?> novas</span><?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </aside>
        <section class="chat-panel">
            <?php if (!$conversa): ?>
                <h2>Seleciona uma conversa</h2>
                <p class="muted">Escolhe uma conversa na coluna ao lado para ver as mensagens.</p>
            <?php else: ?>
                <div class="toolbar">
                    <h2>Conversa · <?= e($conversa['order_number'] ?: ('Pedido #' . $conversa['order_id'])) ?></h2>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="conversation_id" value="<?= (int)$conversa['id'] ?>">
                        <input type="hidden" name="action" value="toggle_status">
                        <button type="submit" class="btn small secondary"><?= $conversa['status'] === 'open' ? 'Fechar conversa' : 'Reabrir conversa' ?></button>
                    </form>
                </div>
                <div class="messages" aria-live="polite">
                    <?php if (!$mensagens): ?><p class="empty">Ainda não existem mensagens.</p><?php endif; ?>
                    <?php foreach ($mensagens as $msg): $minha = $msg['sender_type'] === 'staff' && (int)$msg['user_sender_id'] === $userId; ?>
                        <article class="message <?= $minha ? 'mine' : '' ?>">
                            <strong><?= e($msg['sender_name']) ?></strong>
                            <div><?= nl2br(e($msg['body'])) ?></div>
                            <small><?= e($msg['created_at']) ?></small>
                        </article>
                    <?php endforeach; ?>
                </div>
                <?php if ($conversa['status'] === 'open'): ?>
                    <form method="post" class="chat-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="conversation_id" value="<?= (int)$conversa['id'] ?>">
                        <label for="message">A tua resposta</label>
                        <textarea id="message" name="message" placeholder="Escreve uma resposta..." maxlength="5000" required></textarea>
                        <button type="submit">Enviar mensagem</button>
                    </form>
                <?php else: ?>
                    <p class="empty">Esta conversa está fechada.</p>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>
</section>
<?php layout_end(); ?>
