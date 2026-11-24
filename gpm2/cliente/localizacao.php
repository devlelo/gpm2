<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/iniciar.php';

$pdo = db();
$token = trim((string)($_GET['token'] ?? ''));
if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    http_response_code(404);
    exit('Link de localização inválido.');
}
$hash = hash('sha256', $token);
$stmt = $pdo->prepare(
    'SELECT t.id AS token_id, t.order_id, t.customer_id, o.order_number, o.customer_phone
     FROM delivery_location_tokens t
     INNER JOIN orders o ON o.id = t.order_id
     WHERE t.token_hash = ? AND t.used_at IS NULL AND t.revoked_at IS NULL AND t.expires_at > NOW()
     LIMIT 1'
);
$stmt->execute([$hash]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$record) {
    http_response_code(410);
    exit('Este link expirou ou já foi utilizado. Contacta a GPM para solicitar um novo link.');
}
$provincias = ['Bengo','Benguela','Bié','Cabinda','Cuando','Cubango','Cuanza-Norte','Cuanza-Sul','Cunene','Huambo','Huíla','Icolo e Bengo','Luanda','Lunda-Norte','Lunda-Sul','Malanje','Moxico','Moxico Leste','Namibe','Uíge','Zaire'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $street = trim((string)($_POST['street'] ?? ''));
    $neighborhood = trim((string)($_POST['neighborhood'] ?? ''));
    $reference = trim((string)($_POST['reference'] ?? ''));
    $city = trim((string)($_POST['city'] ?? ''));
    $province = trim((string)($_POST['province'] ?? ''));
    if ($street === '' || $neighborhood === '' || $city === '' || !in_array($province, $provincias, true)) {
        $error = 'Preenche a rua, o bairro, a cidade e uma província válida.';
    } elseif (strlen($street) > 180 || strlen($neighborhood) > 120 || strlen($reference) > 255 || strlen($city) > 100) {
        $error = 'Um dos campos ultrapassou o limite permitido.';
    } else {
        $address = $street . ', Bairro ' . $neighborhood . ', ' . $city . ', ' . $province;
        if ($reference !== '') $address .= '. Referência: ' . $reference;
        try {
            $pdo->beginTransaction();
            $lock = $pdo->prepare('SELECT id FROM delivery_location_tokens WHERE id = ? AND used_at IS NULL AND revoked_at IS NULL AND expires_at > NOW() FOR UPDATE');
            $lock->execute([(int)$record['token_id']]);
            if (!$lock->fetchColumn()) throw new RuntimeException('Este link já foi utilizado ou expirou.');
            $pdo->prepare('UPDATE orders SET delivery_address = ?, delivery_street = ?, delivery_neighborhood = ?, delivery_reference = ?, delivery_city = ?, delivery_province = ?, request_status = \'location_received\' WHERE id = ?')
                ->execute([$address, $street, $neighborhood, $reference ?: null, $city, $province, (int)$record['order_id']]);
            $pdo->prepare('UPDATE delivery_location_tokens SET used_at = NOW() WHERE id = ?')->execute([(int)$record['token_id']]);
            $pdo->commit();
            require_once __DIR__ . '/includes/header-marketplace.php';
            ?>
            <main class="max-w-site mx-auto px-4 sm:px-6 py-16 flex-1">
                <section class="max-w-2xl mx-auto bg-white border border-gray-100 shadow-sm p-8 text-center">
                    <i class="fa-solid fa-check-circle text-emerald-600 text-5xl mb-5"></i>
                    <h1 class="text-2xl font-black text-[#0d233f] uppercase mb-3">Localização recebida</h1>
                    <p class="text-sm text-gray-600">A morada foi associada ao pedido <?= escapar($record['order_number']) ?>. A equipa GPM poderá agora confirmar os detalhes de entrega.</p>
                </section>
            </main>
            <?php require_once __DIR__ . '/includes/footer-marketplace.php'; ?>
            </body></html>
            <?php
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('[GPM delivery location] ' . $e->getMessage());
            $error = $e instanceof RuntimeException ? $e->getMessage() : 'Não foi possível guardar a localização.';
        }
    }
}

require_once __DIR__ . '/includes/header-marketplace.php';
?>
<main class="max-w-site mx-auto px-4 sm:px-6 py-12 flex-1 relative w-full">
    <div class="max-w-2xl mx-auto">
        <span class="inline-block bg-[#f96b21]/10 text-[#f96b21] text-[10px] font-bold uppercase tracking-widest px-3 py-1 mb-3">Entrega · <?= escapar($record['order_number']) ?></span>
        <h1 class="text-3xl md:text-4xl font-black text-[#0d233f] uppercase tracking-tight mb-3">Localização de entrega</h1>
        <p class="text-sm text-gray-500 mb-8">Este link é privado e só pode ser utilizado uma vez. Indica onde pretendes receber o pedido.</p>
        <?php if ($error !== ''): ?><div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 text-sm"><?= escapar($error) ?></div><?php endif; ?>
        <form method="post" class="bg-white border border-gray-100 shadow-sm p-6 sm:p-8 space-y-5">
            <?= csrf_field() ?>
            <div><label class="block text-xs font-bold text-[#0d233f] uppercase tracking-wider mb-2" for="province">Província *</label><select id="province" name="province" required class="w-full px-4 py-3 text-sm border border-gray-300 bg-gray-50"><option value="">Seleciona</option><?php foreach ($provincias as $p): ?><option value="<?= escapar($p) ?>"><?= escapar($p) ?></option><?php endforeach; ?></select></div>
            <div><label class="block text-xs font-bold text-[#0d233f] uppercase tracking-wider mb-2" for="city">Cidade / Município *</label><input id="city" name="city" maxlength="100" required class="w-full px-4 py-3 text-sm border border-gray-300 bg-gray-50"></div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5"><div><label class="block text-xs font-bold text-[#0d233f] uppercase tracking-wider mb-2" for="street">Rua *</label><input id="street" name="street" maxlength="180" required class="w-full px-4 py-3 text-sm border border-gray-300 bg-gray-50"></div><div><label class="block text-xs font-bold text-[#0d233f] uppercase tracking-wider mb-2" for="neighborhood">Bairro *</label><input id="neighborhood" name="neighborhood" maxlength="120" required class="w-full px-4 py-3 text-sm border border-gray-300 bg-gray-50"></div></div>
            <div><label class="block text-xs font-bold text-[#0d233f] uppercase tracking-wider mb-2" for="reference">Ponto de referência</label><textarea id="reference" name="reference" rows="3" maxlength="255" class="w-full px-4 py-3 text-sm border border-gray-300 bg-gray-50"></textarea></div>
            <button type="submit" class="w-full bg-[#0d233f] hover:bg-[#163154] text-white font-bold text-xs uppercase tracking-wider px-8 py-4">Enviar localização</button>
        </form>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer-marketplace.php'; ?>
</body>
</html>
