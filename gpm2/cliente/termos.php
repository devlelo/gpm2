<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/iniciar.php';

$tituloPagina = 'Termos e Condições de Uso';
require __DIR__ . '/includes/header-marketplace.php';
?>

<section class="max-w-4xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
    <div class="mb-10">
        <p class="text-xs font-bold uppercase tracking-widest text-[#2F776F] mb-3">GPM Conecta · Informação legal</p>
        <h1 class="text-3xl sm:text-4xl font-black tracking-tight text-[#0A2945] mb-4">Termos e Condições de Uso</h1>
        <p class="text-sm text-gray-500">Última atualização: 17 de novembro de 2026</p>
        <p class="text-gray-600 leading-relaxed mt-5">Estes termos explicam as condições de acesso e utilização do marketplace GPM Conecta. Ao navegar na plataforma ou criar uma conta, o utilizador declara que leu e compreendeu estas condições.</p>
    </div>

    <div class="space-y-8 text-sm sm:text-base leading-7 text-gray-700">
        <section>
            <h2 class="text-lg font-bold text-[#0A2945] mb-2">1. Sobre a GPM Conecta</h2>
            <p>A GPM Conecta é uma plataforma digital da GPM destinada à apresentação e comercialização de materiais, equipamentos, ferramentas e outros produtos disponibilizados no catálogo. A informação de contacto e identificação da GPM pode ser consultada na página de <a class="text-[#2F776F] font-semibold underline" href="<?= e(BASE_URL) ?>/contacto.php">Contactos</a>.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-[#0A2945] mb-2">2. Conta do utilizador</h2>
            <p>Para concluir um pedido, poderá ser necessário criar uma conta e fornecer dados verdadeiros, completos e atualizados. O utilizador é responsável por manter a confidencialidade das suas credenciais e por comunicar à GPM qualquer utilização não autorizada da sua conta. A GPM poderá limitar ou suspender contas em caso de utilização abusiva, fraudulenta ou contrária à lei.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-[#0A2945] mb-2">3. Produtos, preços e disponibilidade</h2>
            <p>A GPM procura manter as descrições, imagens, preços e informações de disponibilidade atualizados. No entanto, a apresentação de um produto no catálogo não constitui, por si só, confirmação de stock ou aceitação definitiva de um pedido. A disponibilidade, o preço final e eventuais custos de entrega serão confirmados durante o processamento do pedido. Em caso de erro evidente ou indisponibilidade, a GPM contactará o cliente para apresentar as opções aplicáveis.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-[#0A2945] mb-2">4. Pedidos e confirmação</h2>
            <p>Ao submeter um pedido, o cliente confirma os produtos, quantidades, contactos e endereço indicados. O registo do pedido na plataforma representa uma solicitação de compra; a sua confirmação e os passos seguintes serão comunicados pela GPM através dos contactos fornecidos. O cliente deve verificar os dados antes de concluir o pedido e acompanhar as comunicações relativas à encomenda.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-[#0A2945] mb-2">5. Pagamentos e entrega</h2>
            <p>Os métodos de pagamento disponibilizados podem incluir transferência bancária, dinheiro e Multicaixa, conforme as opções apresentadas no processo de pedido. As condições, instruções de pagamento, custos e prazos de entrega aplicáveis serão confirmados pela GPM. A entrega depende da confirmação do pedido, da disponibilidade dos produtos e das condições logísticas acordadas.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-[#0A2945] mb-2">6. Utilização permitida</h2>
            <p>O utilizador compromete-se a não utilizar a plataforma para fins ilícitos, não fornecer informações falsas, não tentar aceder a áreas ou dados sem autorização e não interferir com o funcionamento, segurança ou disponibilidade do serviço.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-[#0A2945] mb-2">7. Cancelamentos e direitos do consumidor</h2>
            <p>Os pedidos de cancelamento, devolução ou reembolso serão tratados de acordo com a legislação aplicável em Angola e com a <a class="text-[#2F776F] font-semibold underline" href="<?= e($baseMarketplace) ?>/reembolsos.php">Política de Reembolsos e Cancelamentos</a>. Estes termos não afastam direitos legalmente reconhecidos ao consumidor.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-[#0A2945] mb-2">8. Alterações e disponibilidade da plataforma</h2>
            <p>A GPM poderá atualizar funcionalidades, catálogo e estes termos para refletir alterações operacionais ou legais. A versão em vigor ficará disponível nesta página. A plataforma poderá, ocasionalmente, ficar temporariamente indisponível por manutenção ou motivos técnicos.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-[#0A2945] mb-2">9. Contacto e legislação aplicável</h2>
            <p>Para esclarecer dúvidas sobre estes termos ou sobre um pedido, utilize a página de <a class="text-[#2F776F] font-semibold underline" href="<?= e(BASE_URL) ?>/contacto.php">Contactos</a>. A utilização da plataforma é regida pela legislação aplicável na República de Angola, sem prejuízo dos direitos imperativos dos consumidores.</p>
        </section>
    </div>

    <div class="mt-12 pt-6 border-t border-gray-200 flex flex-wrap gap-4 text-sm">
        <a class="font-semibold text-[#2F776F] hover:underline" href="<?= e($baseMarketplace) ?>/privacidade.php">Política de Privacidade</a>
        <a class="font-semibold text-[#2F776F] hover:underline" href="<?= e($baseMarketplace) ?>/reembolsos.php">Reembolsos e Cancelamentos</a>
    </div>
</section>

<?php require __DIR__ . '/includes/footer-marketplace.php'; ?>
