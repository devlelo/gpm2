<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/bootstrap.php';
require_admin();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $role = (string)($_POST['role'] ?? 'seller');
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 10 || !in_array($role, ['admin', 'seller', 'stock_manager'], true)) {
            throw new RuntimeException('Preenche os campos corretamente. A palavra-passe deve ter pelo menos 10 caracteres.');
        }
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, active) VALUES (?, ?, ?, ?, 1)');
        $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
        flash('Utilizador criado com sucesso.');
    } catch (Throwable $e) {
        error_log('[GPM users] ' . $e->getMessage());
        flash($e instanceof RuntimeException ? $e->getMessage() : 'Não foi possível criar o utilizador. Verifica se o email já está registado.', 'error');
    }
    redirect('users.php');
}
$rows = $pdo->query('SELECT id, name, email, role, active, created_at FROM users ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
layout_start('Utilizadores');
?>
<section class="panel">
    <h2>Criar utilizador interno</h2>
    <form method="post"><?= csrf_field() ?>
        <div class="form-grid">
            <div class="field"><label for="name">Nome</label><input id="name" name="name" required></div>
            <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" required></div>
            <div class="field"><label for="password">Palavra-passe inicial (mínimo 10 caracteres)</label><input id="password" name="password" type="password" minlength="10" required></div>
            <div class="field"><label for="role">Perfil</label><select id="role" name="role"><option value="seller">Vendedor</option><option value="stock_manager">Gestor de stock</option><option value="admin">Administrador</option></select></div>
        </div>
        <button type="submit">Criar utilizador</button>
    </form>
</section>
<section class="panel">
    <h2>Contas internas</h2>
    <div class="table-wrap"><table><thead><tr><th>Nome</th><th>Email</th><th>Perfil</th><th>Estado</th><th>Criado</th></tr></thead><tbody>
    <?php foreach ($rows as $row): ?><tr><td><?= e($row['name']) ?></td><td><?= e($row['email']) ?></td><td><?= e($row['role']) ?></td><td><span class="badge"><?= (int)$row['active'] === 1 ? 'Ativo' : 'Inativo' ?></span></td><td><?= e($row['created_at']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
</section>
<?php layout_end(); ?>
