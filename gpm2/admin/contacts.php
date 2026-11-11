<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/bootstrap.php';
require_role(['admin', 'seller']);

$rows = db()->query(
    'SELECT id, name, email, phone, subject, message, created_at
     FROM contact_messages
     ORDER BY created_at DESC, id DESC
     LIMIT 300'
)->fetchAll(PDO::FETCH_ASSOC);

layout_start('Mensagens de contacto');
?>
<section class="panel">
    <div class="toolbar">
        <h2>Mensagens recebidas pelo site</h2>
        <span class="muted"><?= count($rows) ?> mensagens recentes</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Data</th><th>Nome</th><th>Email</th><th>Telefone</th><th>Assunto</th><th>Mensagem</th></tr></thead>
            <tbody>
            <?php if (!$rows): ?><tr><td colspan="6" class="empty">Ainda não existem mensagens de contacto.</td></tr><?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['created_at']) ?></td>
                    <td><?= e($row['name']) ?></td>
                    <td><a href="mailto:<?= e($row['email']) ?>"><?= e($row['email']) ?></a></td>
                    <td><?= e($row['phone'] ?: '—') ?></td>
                    <td><?= e($row['subject'] ?: '—') ?></td>
                    <td style="min-width:260px;white-space:normal"><?= nl2br(e($row['message'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php layout_end(); ?>
