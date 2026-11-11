<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_admin();

$pdo = db();
$edit = null;
$errorMessage = null;
$successMessage = null;

/*
|--------------------------------------------------------------------------
| Funções auxiliares
|--------------------------------------------------------------------------
*/

function product_upload_error_message(int $code): string
{
    return match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE =>
            'A imagem ultrapassa o tamanho permitido pelo servidor.',
        UPLOAD_ERR_PARTIAL =>
            'A imagem foi enviada parcialmente. Tente novamente.',
        UPLOAD_ERR_NO_TMP_DIR =>
            'A pasta temporária de uploads não existe.',
        UPLOAD_ERR_CANT_WRITE =>
            'O servidor não conseguiu gravar a imagem.',
        UPLOAD_ERR_EXTENSION =>
            'Uma extensão do PHP bloqueou o envio da imagem.',
        default => 'Ocorreu um erro no envio da imagem.',
    };
}

function product_validate_upload(array $file): ?string
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException(product_upload_error_message($error));
    }

    if (
        !isset($file['tmp_name'], $file['size']) ||
        !is_uploaded_file((string) $file['tmp_name'])
    ) {
        throw new RuntimeException('O ficheiro enviado é inválido.');
    }

    if ((int) $file['size'] <= 0 || (int) $file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Cada imagem deve ter no máximo 5 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file((string) $file['tmp_name']);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException(
            'Formato inválido. Utilize imagens JPG, PNG ou WEBP.'
        );
    }

    // Confirmar que o ficheiro pode ser reconhecido como imagem.
    if (@getimagesize((string) $file['tmp_name']) === false) {
        throw new RuntimeException('O ficheiro enviado não é uma imagem válida.');
    }

    return $allowed[$mime];
}

function product_save_uploaded_image(
    array $file,
    string $extension,
    string $directory
): string {
    if (!is_dir($directory)) {
        if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException(
                'Não foi possível criar a pasta de imagens.'
            );
        }
    }

    if (!is_writable($directory)) {
        throw new RuntimeException(
            'A pasta de imagens não permite escrita.'
        );
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
    $destination = $directory . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file((string) $file['tmp_name'], $destination)) {
        throw new RuntimeException(
            'Não foi possível guardar uma das imagens.'
        );
    }

    return $filename;
}

/*
|--------------------------------------------------------------------------
| Categorias
|--------------------------------------------------------------------------
*/

$cats = $pdo->query(
    'SELECT id, name FROM categories WHERE active = 1 ORDER BY name'
)->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Carregar produto para edição
|--------------------------------------------------------------------------
*/

if (isset($_GET['edit'])) {
    $statement = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $statement->execute([(int) $_GET['edit']]);
    $edit = $statement->fetch(PDO::FETCH_ASSOC) ?: null;

    if (!$edit) {
        flash('Produto não encontrado.', 'error');
        redirect('products.php');
    }
}

/*
|--------------------------------------------------------------------------
| Criar / atualizar / eliminar
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);

    try {
        verify_csrf();

        if ($action === 'save') {
            $name = trim((string) ($_POST['name'] ?? ''));
            $slug = trim((string) ($_POST['slug'] ?? ''));
            $sku = trim((string) ($_POST['sku'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $unit = trim((string) ($_POST['unit'] ?? 'unidade'));
            $unit = $unit !== '' ? $unit : 'unidade';

            $priceRaw = trim((string) ($_POST['price'] ?? ''));
            $stockRaw = trim((string) ($_POST['stock_quantity'] ?? ''));
            $minStockRaw = trim((string) ($_POST['min_stock'] ?? '0'));
            

            if ($slug === '') {
                $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
                $slugSource = $transliterated !== false ? $transliterated : $name;

                $slug = strtolower(trim(
                    preg_replace('/[^a-zA-Z0-9]+/', '-', $slugSource) ?? '',
                    '-'
                ));
            }

            foreach ([
                'preço' => $priceRaw,
                'stock' => $stockRaw,
                'stock mínimo' => $minStockRaw,
            ] as $label => $value) {
                if ($value === '' || !is_numeric($value)) {
                    throw new RuntimeException(
                        'O campo ' . $label . ' deve conter um número válido.'
                    );
                }
            }

            $price = (float) $priceRaw;
            $stock = (float) $stockRaw;
            $minStock = (float) $minStockRaw;

            if ($name === '' || $slug === '') {
                throw new RuntimeException(
                    'O nome e o slug do produto são obrigatórios.'
                );
            }

            if (
                $price < 0 || $stock < 0 || $minStock < 0 ||
                !is_finite($price) || !is_finite($stock) || !is_finite($minStock)
            ) {
                throw new RuntimeException(
                    'O preço e as quantidades não podem ser negativos.'
                );
            }

            $categoryRaw = (string) ($_POST['category_id'] ?? '');
            $categoryId = $categoryRaw !== '' ? (int) $categoryRaw : null;

            if ($categoryId !== null) {
                $statement = $pdo->prepare(
                    'SELECT id FROM categories WHERE id = ? AND active = 1'
                );
                $statement->execute([$categoryId]);

                if (!$statement->fetchColumn()) {
                    throw new RuntimeException(
                        'A categoria selecionada não existe ou está inativa.'
                    );
                }
            }

            $active = isset($_POST['active']) ? 1 : 0;

            /*
             * Validar SKU e slug antes de guardar.
             */
            $statement = $pdo->prepare(
                'SELECT id FROM products
                 WHERE (slug = ? OR (? IS NOT NULL AND sku = ?))
                   AND id <> ? LIMIT 1'
            );

            $statement->execute([
                $slug,
                $sku !== '' ? $sku : null,
                $sku !== '' ? $sku : null,
                $id,
            ]);

            if ($statement->fetchColumn()) {
                throw new RuntimeException(
                    'Este slug ou SKU já está a ser utilizado por outro produto.'
                );
            }

            /*
             * Validar imagens.
             */
            $mainFile = $_FILES['main_image'] ?? null;
            $mainExtension = null;

            if (is_array($mainFile)) {
                $mainExtension = product_validate_upload($mainFile);
            }

            $extraFiles = [];
            $extraExtensions = [];

            if (isset($_FILES['extra_images']['error'])
                && is_array($_FILES['extra_images']['error'])) {

                $count = count($_FILES['extra_images']['error']);

                if ($count > 20) {
                    throw new RuntimeException(
                        'Podes enviar no máximo 20 imagens adicionais de cada vez.'
                    );
                }

                for ($i = 0; $i < $count; $i++) {
                    $file = [
                        'name' => $_FILES['extra_images']['name'][$i] ?? '',
                        'type' => $_FILES['extra_images']['type'][$i] ?? '',
                        'tmp_name' => $_FILES['extra_images']['tmp_name'][$i] ?? '',
                        'error' => $_FILES['extra_images']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                        'size' => $_FILES['extra_images']['size'][$i] ?? 0,
                    ];

                    $extension = product_validate_upload($file);

                    if ($extension !== null) {
                        $extraFiles[] = $file;
                        $extraExtensions[] = $extension;
                    }
                }
            }

            /*
             * Guardar registos e imagens.
             *
             * Nota: a transação SQL não consegue reverter automaticamente
             * ficheiros já escritos no disco. Os ficheiros novos são
             * removidos se o processo falhar.
             */
            $uploadDirectory = __DIR__ . '/../public/uploads/produtos';
            $createdFiles = [];

            try {
                $pdo->beginTransaction();

                if ($id > 0) {
                    $statement = $pdo->prepare(
                        'UPDATE products
                         SET category_id = ?, name = ?, slug = ?, sku = ?,
                             description = ?, price = ?,
                             unit = ?, active = ?, min_stock = ?
                         WHERE id = ?'
                    );

                    /*
                     * O stock atual não é alterado neste formulário.
                     * As entradas/saídas devem ser registadas no módulo Stock.
                     */
                    $statement->execute([
                        $categoryId,
                        $name,
                        $slug,
                        $sku !== '' ? $sku : null,
                        $description,
                        $price,
                        $unit,
                        $active,
                        $minStock,
                        $id,
                    ]);
                } else {
                    $statement = $pdo->prepare(
                        'INSERT INTO products
                         (category_id, name, slug, sku, description, price,
                          stock_quantity, min_stock, unit, active)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );

                    $statement->execute([
                        $categoryId,
                        $name,
                        $slug,
                        $sku !== '' ? $sku : null,
                        $description,
                        $price,
                        $stock,
                        $minStock,
                        $unit,
                        $active,
                    ]);

                    $id = (int) $pdo->lastInsertId();

                    if ($stock > 0) {
                        $pdo->prepare(
                            'INSERT INTO stock_movements
                             (product_id, user_id, movement_type, quantity, stock_before, stock_after, reason)
                             VALUES (?, ?, ?, ?, ?, ?, ?)'
                        )->execute([
                            $id,
                            (int)current_user()['id'],
                            'entry',
                            $stock,
                            0,
                            $stock,
                            'Stock inicial do produto',
                        ]);
                    }
                }

                if ($mainExtension !== null && is_array($mainFile)) {
                    $filename = product_save_uploaded_image(
                        $mainFile,
                        $mainExtension,
                        $uploadDirectory
                    );

                    $createdFiles[] = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;
                    $relativePath = 'uploads/produtos/' . $filename;

                    $statement = $pdo->prepare(
                        'UPDATE products SET main_image = ? WHERE id = ?'
                    );
                    $statement->execute([$relativePath, $id]);
                }

                foreach ($extraFiles as $index => $file) {
                    $filename = product_save_uploaded_image(
                        $file,
                        $extraExtensions[$index],
                        $uploadDirectory
                    );

                    $createdFiles[] = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;
                    $relativePath = 'uploads/produtos/' . $filename;

                    $statement = $pdo->prepare(
                        'INSERT INTO product_images (product_id, image_path)
                         VALUES (?, ?)'
                    );

                    $statement->execute([$id, $relativePath]);
                }

                $pdo->commit();

            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                foreach ($createdFiles as $path) {
                    if (is_file($path)) {
                        @unlink($path);
                    }
                }

                throw $e;
            }

            flash('Produto guardado com sucesso.');
            redirect('products.php');
        }

        if ($action === 'delete') {
            if ($id <= 0) {
                throw new RuntimeException('ID de produto inválido.');
            }

            $statement = $pdo->prepare(
                'SELECT COUNT(*) FROM order_items WHERE product_id = ?'
            );
            $statement->execute([$id]);

            if ((int) $statement->fetchColumn() > 0) {
                $statement = $pdo->prepare(
                    'UPDATE products SET active = 0 WHERE id = ?'
                );
                $statement->execute([$id]);

                flash(
                    'O produto já tem vendas; foi desativado em vez de apagado.'
                );
                redirect('products.php');
            }

            $statement = $pdo->prepare(
                'SELECT main_image FROM products WHERE id = ?'
            );
            $statement->execute([$id]);
            $product = $statement->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                throw new RuntimeException('Produto não encontrado.');
            }

            $statement = $pdo->prepare(
                'SELECT image_path FROM product_images WHERE product_id = ?'
            );
            $statement->execute([$id]);
            $images = $statement->fetchAll(PDO::FETCH_ASSOC);

            $pdo->beginTransaction();

            try {
                $statement = $pdo->prepare(
                    'DELETE FROM products WHERE id = ?'
                );
                $statement->execute([$id]);

                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e;
            }

            $publicDirectory = __DIR__ . '/../public/';

            foreach ($images as $image) {
                $path = $publicDirectory . ltrim(
                    (string) $image['image_path'],
                    '/'
                );

                if (is_file($path)) {
                    @unlink($path);
                }
            }

            if (!empty($product['main_image'])) {
                $path = $publicDirectory . ltrim(
                    (string) $product['main_image'],
                    '/'
                );

                if (is_file($path)) {
                    @unlink($path);
                }
            }

            flash('Produto eliminado.');
            redirect('products.php');
        }

        throw new RuntimeException('Ação desconhecida.');

    } catch (Throwable $e) {
        error_log(
            '[GPM products.php] ' . $e->getMessage()
            . ' em ' . $e->getFile() . ':' . $e->getLine()
        );

        $errorMessage = $e instanceof RuntimeException
            ? $e->getMessage()
            : 'Ocorreu um erro interno. Consulte o log do PHP.';
    }
}

/*
|--------------------------------------------------------------------------
| Listagem
|--------------------------------------------------------------------------
*/

$q = trim((string) ($_GET['q'] ?? ''));

$sql = 'SELECT p.*, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id';

$args = [];

if ($q !== '') {
    $sql .= ' WHERE p.name LIKE ? OR p.sku LIKE ?';
    $args = ['%' . $q . '%', '%' . $q . '%'];
}

$sql .= ' ORDER BY p.id DESC LIMIT 300';

$statement = $pdo->prepare($sql);
$statement->execute($args);
$rows = $statement->fetchAll(PDO::FETCH_ASSOC);

$editImages = [];

if ($edit) {
    $statement = $pdo->prepare(
        'SELECT id, image_path FROM product_images
         WHERE product_id = ? ORDER BY id DESC'
    );
    $statement->execute([(int) $edit['id']]);
    $editImages = $statement->fetchAll(PDO::FETCH_ASSOC);
}

layout_start('Produtos');
?>

<?php if ($errorMessage !== null): ?>
    <section class="panel" role="alert">
        <h2>Não foi possível guardar o produto</h2>
        <p><?= e($errorMessage) ?></p>
    </section>
<?php endif; ?>

<section class="panel">
    <h2><?= $edit ? 'Editar produto' : 'Adicionar produto' ?></h2>

    <form method="post" enctype="multipart/form-data" id="product-form">
        <?= csrf_field() ?>

        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">

        <div class="form-grid">
            <?php
            $fields = [
                ['name', 'Nome do produto', 'text', $edit['name'] ?? ''],
                ['slug', 'Slug (URL)', 'text', $edit['slug'] ?? ''],
                ['sku', 'Código/SKU', 'text', $edit['sku'] ?? ''],
                ['price', 'Preço (Kz)', 'number', $edit['price'] ?? '0.00'],
                ['stock_quantity', $edit ? 'Stock atual (gerido no módulo Stock)' : 'Stock inicial', 'number', $edit['stock_quantity'] ?? '0'],
                ['min_stock', 'Alerta de stock mínimo', 'number', $edit['min_stock'] ?? '0'],
                ['unit', 'Unidade', 'text', $edit['unit'] ?? 'unidade'],
            ];

            foreach ($fields as [$key, $label, $type, $value]):
            ?>
                <div class="field">
                    <label for="<?= e($key) ?>"><?= e($label) ?></label>
                    <input
                        id="<?= e($key) ?>"
                        name="<?= e($key) ?>"
                        type="<?= e($type) ?>"
                        value="<?= e($value) ?>"
                        <?= $type === 'number' ? 'step="0.001" min="0"' : '' ?>
                        <?= $edit && $key === 'stock_quantity' ? 'readonly' : '' ?>
                        <?= $key === 'name' ? 'required' : '' ?>
                    >
                </div>
            <?php endforeach; ?>

            <div class="field">
                <label for="category_id">Categoria</label>
                <select name="category_id" id="category_id">
                    <option value="">Sem categoria</option>

                    <?php foreach ($cats as $category): ?>
                        <option
                            value="<?= (int) $category['id'] ?>"
                            <?= (int) ($edit['category_id'] ?? 0)
                                === (int) $category['id'] ? 'selected' : '' ?>
                        >
                            <?= e($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label>Estado</label>
                <label>
                    <input
                        type="checkbox"
                        name="active"
                        value="1"
                        style="width:auto"
                        <?= (int) ($edit['active'] ?? 1) === 1 ? 'checked' : '' ?>
                    >
                    Produto ativo
                </label>
            </div>
        </div>

        <div class="field">
            <label for="description">Descrição</label>
            <textarea
                name="description"
                id="description"
                rows="4"
            ><?= e($edit['description'] ?? '') ?></textarea>
        </div>

        <div class="field">
            <label for="main_image">Imagem principal</label>
            <input
                type="file"
                name="main_image"
                id="main_image"
                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
            >

            <p class="muted">
                Formatos: JPG, PNG ou WEBP. Máximo de 5 MB.
            </p>

            <?php if (!empty($edit['main_image'])): ?>
                <p>Imagem atual:</p>
                <img
                    src="../public/<?= e($edit['main_image']) ?>"
                    alt="Imagem principal atual"
                    style="width:140px;height:120px;object-fit:contain"
                >
            <?php endif; ?>

            <div id="main-preview" class="image-preview"></div>
        </div>

        <div class="field">
            <label for="extra_images">Imagens adicionais</label>
            <input
                type="file"
                name="extra_images[]"
                id="extra_images"
                multiple
                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
            >

            <p class="muted">
                Podes escolher até 20 imagens por envio.
            </p>

            <div class="image-preview">
                <?php foreach ($editImages as $image): ?>
                    <div class="image-item">
                        <img
                            src="../public/<?= e($image['image_path']) ?>"
                            alt="Imagem adicional"
                        >
                    </div>
                <?php endforeach; ?>
            </div>

            <div id="extra-preview" class="image-preview"></div>
        </div>

        <div class="form-actions">
            <button type="submit">Guardar produto</button>

            <?php if ($edit): ?>
                <a class="btn secondary" href="products.php">
                    Cancelar edição
                </a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="panel">
    <div class="toolbar">
        <h2>Produtos registados</h2>

        <form method="get">
            <input
                name="q"
                placeholder="Pesquisar nome ou SKU"
                value="<?= e($q) ?>"
                style="min-width:220px"
            >
            <button type="submit">Pesquisar</button>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Imagem</th>
                    <th>Produto</th>
                    <th>Categoria</th>
                    <th>Preço</th>
                    <th>Stock</th>
                    <th>Estado</th>
                    <th>Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="7" class="empty">
                            Nenhum produto encontrado.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($rows as $product): ?>
                    <tr>
                        <td>
                            <?php if (!empty($product['main_image'])): ?>
                                <img
                                    src="../public/<?= e($product['main_image']) ?>"
                                    alt="<?= e($product['name']) ?>"
                                    style="width:55px;height:55px;object-fit:contain"
                                >
                            <?php else: ?>
                                <span class="muted">Sem imagem</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <strong><?= e($product['name']) ?></strong><br>
                            <span class="muted"><?= e($product['sku'] ?? '') ?></span>
                        </td>

                        <td><?= e($product['category_name'] ?? '—') ?></td>
                        <td><?= e(money($product['price'])) ?></td>

                        <td>
                            <?= e($product['stock_quantity']) ?>
                            <?= e($product['unit']) ?>
                        </td>

                        <td>
                            <span class="badge <?= (int) $product['active'] ? 'green' : 'red' ?>">
                                <?= (int) $product['active'] ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </td>

                        <td>
                            <div class="actions">
                                <a
                                    class="btn small secondary"
                                    href="products.php?edit=<?= (int) $product['id'] ?>"
                                >
                                    Editar
                                </a>

                                <form
                                    method="post"
                                    onsubmit="return confirm('Eliminar/desativar este produto?')"
                                >
                                    <?= csrf_field() ?>

                                    <input type="hidden" name="action" value="delete">
                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int) $product['id'] ?>"
                                    >

                                    <button class="danger small" type="submit">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<style>
.image-preview {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 12px;
}

.image-item img,
.image-preview img {
    width: 120px;
    height: 110px;
    object-fit: contain;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 4px;
    background: #fff;
}

.image-item {
    display: inline-flex;
}
</style>

<script>
(function () {
    function showPreview(input, targetId) {
        const target = document.getElementById(targetId);
        target.replaceChildren();

        Array.from(input.files || []).forEach(function (file) {
            if (!file.type.startsWith('image/')) {
                return;
            }

            const wrapper = document.createElement('div');
            wrapper.className = 'image-item';

            const img = document.createElement('img');
            img.alt = 'Pré-visualização da imagem';

            const objectUrl = URL.createObjectURL(file);
            img.src = objectUrl;
            img.onload = function () {
                URL.revokeObjectURL(objectUrl);
            };

            wrapper.appendChild(img);
            target.appendChild(wrapper);
        });
    }

    document.getElementById('main_image').addEventListener('change', function () {
        showPreview(this, 'main-preview');
    });

    document.getElementById('extra_images').addEventListener('change', function () {
        showPreview(this, 'extra-preview');
    });

    const name = document.getElementById('name');
    const slug = document.getElementById('slug');

    name.addEventListener('input', function () {
        if (slug.dataset.manuallyEdited === '1') {
            return;
        }

        slug.value = this.value
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    });

    slug.addEventListener('input', function () {
        this.dataset.manuallyEdited = '1';
    });
})();
</script>

<?php layout_end(); ?>
