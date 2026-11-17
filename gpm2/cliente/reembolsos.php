<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/iniciar.php';

$tituloPagina = 'Reembolsos e Cancelamentos';
require __DIR__ . '/includes/header-marketplace.php';
?>

<section class="max-w-4xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
    <div class="mb-10">
        <p class="text-xs font-bold uppercase tracking-widest text-[#2F776F] mb-3">GPM Conecta · Informação legal</p>
        <h1 class="text-3xl sm:text-4xl font-black tracking-tight text-[#0A2945] mb-4">Política de Reembolsos e Cancelamentos</h1>
        <p class="text-sm text-gray-500">Última atualização: 17 de novembro de 2026</p>
        <p class="text-gray-600 leading-relaxed mt-5">Esta política apresenta o procedimento para comunicar problemas com pedidos, solicitar cancelamentos e tratar situações em que possa existir lugar a devolução ou reembolso.</p>
    </div>

    <div class="space-y-8 text-sm sm:text-base leading-7 text-gray-700">
        <section>
            <h2 class="text-lg font-bold text-[#0A2945] mb-2">1. Como solicitar ajuda ou cancelamento</h2>
            <p>Para solicitar o cancelamento de um pedido ou comunicar um problema, contacte a GPM o mais cedo possível através da página de <a class="text-[#2F776F] font-semibold underline" href="<?= e(BASE_URL) ?>/contacto.php">Contactos</a>. Indique o número do pedido, os seus dados de contacto e uma descrição clara do motivo. A GPM analisará o estado do pedido e informará os passos seguintes.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-[#0A2945] mb-2">2. Cancelamento antes da expedição ou entrega</h2>
            <p>Quando o pedido ainda não tiver sido preparado ou expedido, a GPM procurará interromper o processamento após receber o pedido de cancelamento. A possibilidade de cancelamento depende do estado real da encomenda e será confirmada ao cliente. Se o pedido já estiver em preparação avançada, em transporte ou entregue, poderão aplicar-se procedimentos diferentes, sem prejuízo dos direitos legais do consumidor.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-[#0A2945] mb-2">3. Produto danificado, incorreto ou com defeito</h2>
            <p>Se receber um produto danificado, diferente do solicitado ou com defeito, contacte a GPM assim que possível. Sempre que viável, envie fotografias e mantenha a embalagem e os elementos relacionados com a entrega até receber instruções. A GPM avaliará a situação e apresentará uma solução adequada, que poderá incluir substituição, reparação, devolução ou reembolso, conforme o caso e a legislação aplicável.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-[#0A2945] mb-2">4. Devoluções</h2>
            <p>Não envie produtos para devolução sem antes contactar a GPM e receber instruções sobre o procedimento. A avaliação terá em conta o estado do produto, a natureza do problema, os documentos do pedido e os direitos que assistem ao consumidor. Esta regra de contacto prévio não limita direitos de reclamação ou de devolução previstos na legislação aplicável.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-[#0A2945] mb-2">5. Reembolsos</h2>
            <p>Quando se conclua que existe lugar a reembolso, a GPM confirmará o valor, o método e os passos necessários com o cliente. Sempre que possível, o reembolso será efetuado pelo método de pagamento original ou por outro meio acordado entre as partes. O prazo poderá depender do método utilizado e das entidades financeiras envolvidas; a GPM comunicará ao cliente a informação disponível para o caso concreto.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-[#0A2945] mb-2">6. Custos de entrega</h2>
            <p>O tratamento de custos de entrega em situações de cancelamento, devolução ou reembolso será analisado de acordo com o motivo, o estado do pedido e a legislação aplicável. Quando o problema resultar de erro ou incumprimento imputável à GPM, a solução terá em conta os direitos legalmente reconhecidos ao cliente.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-[#0A2945] mb-2">7. Direitos legais</h2>
            <p>Nada nesta política pretende excluir, limitar ou substituir direitos obrigatórios dos consumidores previstos na legislação da República de Angola. Em caso de divergência, prevalecem as disposições legais aplicáveis.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-[#0A2945] mb-2">8. Contacto</h2>
            <p>Para iniciar um pedido de cancelamento, devolução ou reembolso, aceda à página de <a class="text-[#2F776F] font-semibold underline" href="<?= e(BASE_URL) ?>/contacto.php">Contactos da GPM</a> e indique o número da encomenda, se já existir.</p>
        </section>
    </div>

    <div class="mt-12 pt-6 border-t border-gray-200 flex flex-wrap gap-4 text-sm">
        <a class="font-semibold text-[#2F776F] hover:underline" href="<?= e($baseMarketplace) ?>/termos.php">Termos e Condições</a>
        <a class="font-semibold text-[#2F776F] hover:underline" href="<?= e($baseMarketplace) ?>/privacidade.php">Política de Privacidade</a>
    </div>
</section>

<?php require __DIR__ . '/includes/footer-marketplace.php'; ?>
