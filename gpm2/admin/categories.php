<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/bootstrap.php';
require_admin();
$pdo = db();
$edit = null;

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch() ?: null;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim((string)($_POST['name'] ?? ''));
            $slug = trim((string)($_POST['slug'] ?? ''));
            if ($name === '') throw new RuntimeException('O nome é obrigatório.');
            if ($slug === '') {
                $ascii = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
                $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $ascii !== false ? $ascii : $name), '-'));
            }
            $description = trim((string)($_POST['description'] ?? ''));
            $active = isset($_POST['active']) ? 1 : 0;
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE categories SET name = ?, slug = ?, description = ?, active = ? WHERE id = ?');
                $stmt->execute([$name, $slug, $description, $active, $id]);
                flash('Categoria atualizada.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO categories (name, slug, description, active) VALUES (?, ?, ?, ?)');
                $stmt->execute([$name, $slug, $description, $active]);
                flash('Categoria criada.');
            }
            redirect('categories.php');
        }
        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
            $stmt->execute([$id]);
            if ((int)$stmt->fetchColumn() > 0) {
                $pdo->prepare('UPDATE categories SET active = 0 WHERE id = ?')->execute([$id]);
                flash('A categoria tem produtos associados e foi desativada.');
            } else {
                $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
                flash('Categoria eliminada.');
            }
            redirect('categories.php');
        }
        throw new RuntimeException('Ação inválida.');
    } catch (Throwable $e) {
        error_log('[GPM categories] ' . $e->getMessage());
        flash($e instanceof PDOException ? 'Não foi possível guardar. Verifica se o nome ou slug já existe.' : $e->getMessage(), 'error');
        redirect('categories.php');
    }
}
$rows = $pdo->query(
    'SELECT c.*, COUNT(p.id) AS product_count
     FROM categories c LEFT JOIN products p ON p.category_id = c.id
     GROUP BY c.id ORDER BY c.id DESC'
)->fetchAll(PDO::FETCH_ASSOC);

layout_start('Categorias');
?>
<section class="panel">
    <h2><?= $edit ? 'Editar categoria' : 'Nova categoria' ?></h2>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
        <div class="form-grid">
            <div class="field"><label for="name">Nome</label><input id="name" name="name" required value="<?= e($edit['name'] ?? '') ?>"></div>
            <div class="field"><label for="slug">Slug</label><input id="slug" name="slug" value="<?= e($edit['slug'] ?? '') ?>" placeholder="Gerado automaticamente se vazio"></div>
            <div class="field"><label><input style="width:auto" type="checkbox" name="active" value="1"<?= (int)($edit['active'] ?? 1) === 1 ? ' checked' : '' ?>> Categoria ativa</label></div>
        </div>
        <div class="field"><label for="description">Descrição</label><textarea id="description" name="description" rows="3"><?= e($edit['description'] ?? '') ?></textarea></div>
        <div class="form-actions"><button type="submit">Guardar categoria</button><?php if ($edit): ?><a class="btn secondary" href="categories.php">Cancelar</a><?php endif; ?></div>
    </form>
</section>
<section class="panel">
    <h2>Categorias existentes</h2>
    <div class="table-wrap"><table><thead><tr><th>Nome</th><th>Slug</th><th>Produtos</th><th>Estado</th><th>Ações</th></tr></thead><tbody>
    <?php if (!$rows): ?><tr><td colspan="5" class="empty">Ainda não existem categorias.</td></tr><?php endif; ?>
    <?php foreach ($rows as $row): ?>
        <tr><td><?= e($row['name']) ?></td><td><?= e($row['slug']) ?></td><td><?= (int)$row['product_count'] ?></td><td><span class="badge"><?= (int)$row['active'] ? 'Ativa' : 'Inativa' ?></span></td><td><div class="actions"><a class="btn small secondary" href="categories.php?edit=<?= (int)$row['id'] ?>">Editar</a><form method="post" onsubmit="return confirm('Eliminar ou desativar esta categoria?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button class="danger small" type="submit">Eliminar</button></form></div></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
</section>
<?php layout_end(); ?>
