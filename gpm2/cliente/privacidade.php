<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/iniciar.php';

$tituloPagina = 'Política de Privacidade';
require __DIR__ . '/includes/header-marketplace.php';
?>

<section class="max-w-4xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
    <div class="mb-10">
        <p class="text-xs font-bold uppercase tracking-widest text-[#2F776F] mb-3">GPM Conecta · Informação legal</p>
        <h1 class="text-3xl sm:text-4xl font-black tracking-tight text-[#0A2945] mb-4">Política de Privacidade</h1>
        <p class="text-sm text-gray-500">Última atualização: 17 de novembro de 2026</p>
        <p class="text-gray-600 leading-relaxed mt-5">Esta política explica, de forma simples, como a GPM Conecta trata os dados pessoais necessários para disponibilizar a plataforma, gerir contas e processar pedidos.</p>
    </div>

    <div class="space-y-8 text-sm sm:text-base leading-7 text-gray-700">
        <section>
            <h2 class="text-lg font-bold text-[#0A2945] mb-2">1. Que dados podemos recolher</h2>
            <p>Consoante a utilização da plataforma, podemos tratar dados como nome, endereço de email, número de telefone, NIF quando fornecido, tipo de cliente, morada, cidade, província, produtos e quantidades solicitadas, método de pagamento escolhido, histórico de pedidos e informações enviadas voluntariamente pelo utilizador.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-[#0A2945] mb-2">2. Para que utilizamos os dados</h2>
            <ul class="list-disc pl-6 space-y-2">
                <li>Criar e administrar a conta do cliente;</li>
                <li>Registar, confirmar, preparar e acompanhar pedidos;</li>
                <li>Contactar o cliente sobre produtos, pagamentos, entregas ou questões relacionadas com o pedido;</li>
                <li>Manter registos administrativos, contabilísticos e de segurança;</li>
                <li>Melhorar o funcionamento e a qualidade da plataforma;</li>
                <li>Cumprir obrigações legais e responder a solicitações legítimas das autoridades competentes.</li>
            </ul>
        </section>

        <section>
            <h2 class="text-lg font-bold text-[#0A2945] mb-2">3. Partilha de informações</h2>
            <p>Os dados pessoais não são disponibilizados publicamente nem comercializados. Poderão ser partilhados apenas quando necessário para executar o pedido — por exemplo, com intervenientes responsáveis pela entrega ou pelo processamento do pagamento —, prestar suporte técnico à plataforma, cumprir obrigações legais ou proteger direitos legítimos. Sempre que aplicável, a partilha deve limitar-se à informação necessária para a finalidade em causa.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-[#0A2945] mb-2">4. Conservação e segurança</h2>
            <p>A GPM conserva os dados pelo período necessário às finalidades descritas, incluindo o cumprimento de obrigações legais e a gestão de pedidos. São adotadas medidas técnicas e organizativas destinadas a proteger as informações contra acesso, alteração, divulgação ou perda não autorizados. Nenhum sistema de transmissão ou armazenamento pode, contudo, ser considerado absolutamente isento de risco.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-[#0A2945] mb-2">5. Cookies e sessões</h2>
            <p>A plataforma pode utilizar cookies ou tecnologias semelhantes estritamente necessários ao funcionamento, incluindo a manutenção da sessão e de funcionalidades como o carrinho. Se forem introduzidas ferramentas adicionais de análise ou publicidade que utilizem cookies, esta política será atualizada para explicar a sua finalidade e as opções disponíveis ao utilizador.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-[#0A2945] mb-2">6. Direitos do titular dos dados</h2>
            <p>O utilizador pode solicitar informações sobre os seus dados pessoais e, nos termos da legislação aplicável, pedir acesso, correção ou eliminação, bem como apresentar questões ou reclamações sobre o respetivo tratamento. Alguns dados poderão ter de ser conservados quando exista uma obrigação legal ou outra razão legítima aplicável.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-[#0A2945] mb-2">7. Dados de menores</h2>
            <p>A plataforma destina-se a pessoas com capacidade legal para realizar as operações nela disponibilizadas. Não solicitamos intencionalmente dados de menores para criação autónoma de contas ou realização de pedidos.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-[#0A2945] mb-2">8. Alterações e contacto</h2>
            <p>Esta política poderá ser atualizada quando os serviços ou as práticas de tratamento de dados forem alterados. Para exercer direitos ou esclarecer dúvidas, contacte a GPM através da página de <a class="text-[#2F776F] font-semibold underline" href="<?= e(BASE_URL) ?>/contacto.php">Contactos</a>.</p>
        </section>
    </div>

    <div class="mt-12 pt-6 border-t border-gray-200 flex flex-wrap gap-4 text-sm">
        <a class="font-semibold text-[#2F776F] hover:underline" href="<?= e($baseMarketplace) ?>/termos.php">Termos e Condições</a>
        <a class="font-semibold text-[#2F776F] hover:underline" href="<?= e($baseMarketplace) ?>/reembolsos.php">Reembolsos e Cancelamentos</a>
    </div>
</section>

<?php require __DIR__ . '/includes/footer-marketplace.php'; ?>
