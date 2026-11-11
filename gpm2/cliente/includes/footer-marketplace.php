<!-- Footer Marketplace -->
<footer class="bg-[#0d233f] text-white pt-16 pb-8 border-t-4 border-[#f96b21] mt-auto">
    <div class="max-w-site mx-auto px-4 sm:px-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-10 mb-12">
            
            <!-- Logo e Descrição -->
            <div class="md:col-span-2">
                <a href="<?= $baseMarketplace ?>/index.php" class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 bg-[#f96b21] text-white flex items-center justify-center font-bold text-lg">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                    <span class="text-2xl font-black tracking-tighter">GPM.</span>
                </a>
                <p class="text-gray-400 text-sm max-w-sm leading-relaxed mb-6">
                    Materiais de construção para ajudar a transformar os seus projetos em realidade.
                </p>
                <div class="flex space-x-4 text-lg text-gray-400">
                    <a href="#" class="hover:text-white transition"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" class="hover:text-white transition"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" class="hover:text-white transition"><i class="fa-brands fa-linkedin-in"></i></a>
                    <a href="#" class="hover:text-white transition"><i class="fa-brands fa-whatsapp"></i></a>
                </div>
            </div>

            <!-- Links Úteis -->
            <div>
                <h3 class="text-xs font-bold uppercase tracking-widest text-[#f96b21] mb-6">Links Úteis</h3>
                <ul class="space-y-3 text-sm text-gray-300">
                    <li><a href="<?= $baseMarketplace ?>/index.php" class="hover:text-white transition">Início</a></li>
                    <li><a href="<?= $baseMarketplace ?>/index.php#produtos" class="hover:text-white transition">Produtos</a></li>
                    <li><a href="<?= $baseMarketplace ?>/carrinho.php" class="hover:text-white transition">Meu Carrinho</a></li>
                    <li><a href="<?= $baseMarketplace ?>/login.php" class="hover:text-white transition">Minha Conta</a></li>
                </ul>
            </div>

            <!-- Contactos -->
            <div>
                <h3 class="text-xs font-bold uppercase tracking-widest text-[#f96b21] mb-6">Contactos</h3>
                <p class="text-sm text-gray-400 mb-4 leading-relaxed">
                    Entre em contacto connosco para obter informações sobre produtos, preços e entregas.
                </p>
                <a href="<?= $baseMarketplace ?>/index.php#contactos" class="inline-block bg-[#f96b21] hover:bg-[#e05812] text-[#0d233f] font-bold text-xs uppercase tracking-wider px-6 py-3 transition shadow-md">
                    Fale Connosco
                </a>
            </div>
        </div>

        <!-- Barra Inferior -->
        <div class="border-t border-white/10 pt-6 flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-gray-500">
            <p>&copy; <?= date('Y') ?> GPM — Materiais de Construção. Todos os direitos reservados.</p>
            <p>Desenvolvido com foco em performance e B2B.</p>
        </div>
    </div>
</footer>

<!-- Script do Menu Mobile -->
<script>
(function () {
    const botao = document.getElementById('gpm-market-menu-btn');
    const menu = document.getElementById('gpm-market-nav');

    if (!botao || !menu) {
        return;
    }

    botao.addEventListener('click', function () {
        const aberto = menu.classList.toggle('aberto');

        botao.setAttribute('aria-expanded', aberto ? 'true' : 'false');
        botao.textContent = aberto ? '✕' : '☰';
    });
})();
</script>

</body>
</html>