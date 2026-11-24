# Atualização do fluxo B2B — pedidos de cotação

Esta atualização é incremental e mantém o HTML/CSS e a identidade visual existentes. Não substitui o esquema principal da base de dados.

## Antes de ativar

1. Faz uma cópia de segurança da base de dados `gpm_mvp_nova`.
2. Abre o phpMyAdmin e seleciona a base de dados usada pelo GPM.
3. Importa o ficheiro `2026_11_24_b2b_quotation.sql`.
4. Confirma que as colunas `products.min_order_quantity`, `orders.order_type`, `orders.request_status` e `orders.proposal_version` foram criadas.
5. Só depois publica os ficheiros desta branch no servidor PHP.

Não voltes a importar o dump principal para uma base que já contém dados: isso pode substituir ou duplicar dados existentes.

## Fluxo incluído

1. O cliente adiciona produtos ao carrinho e envia um pedido de cotação.
2. O sistema regista o pedido sem cobrar, emitir proforma automaticamente no checkout ou descontar stock.
3. O pedido aparece no painel administrativo, na lista de pedidos.
4. A equipa abre o pedido e prepara a proposta em `admin/quote.php`.
5. A proposta guarda quantidades aprovadas, preços, desconto, entrega, validade e condições. É criada uma proforma na tabela existente.
6. O administrador partilha o link da proposta pelo WhatsApp através do botão de envio.
7. O cliente aceita ou recusa a proposta. Ao aceitar, recebe um link privado de utilização única para indicar a localização.
8. Quando a localização chega, a equipa pode converter a cotação num pedido confirmado. A conversão atualiza os valores e as quantidades, mas não desconta stock automaticamente.

## Comunicação

O botão WhatsApp abre uma mensagem pré-preenchida; o envio depende de o utilizador confirmar no WhatsApp. Não existe ainda envio automático por WhatsApp, email ou SMS. Para isso será necessário configurar um fornecedor/API e credenciais no servidor. A ausência desse fornecedor não impede a criação de pedidos nem a preparação das propostas.

## Verificação manual recomendada

- Criar/entrar numa conta de cliente.
- Adicionar um produto com stock zero e confirmar que continua possível pedir cotação.
- Tentar enviar abaixo da quantidade mínima e confirmar a validação.
- Enviar uma cotação e verificar que o stock não muda e não é criada uma proforma no checkout.
- Abrir o pedido no painel, criar proposta e confirmar que a proforma foi registada.
- Partilhar o link, aceitar a proposta e enviar a localização.
- Confirmar a cotação no painel e verificar se os valores da proposta passam para o pedido.

## Limitações desta entrega

- A proforma é registada na base de dados e identificada no ecrã da proposta; a geração de PDF fiscal/formatado ainda depende de integração com o módulo documental existente.
- As notificações automáticas por email/SMS/WhatsApp ainda não estão ligadas a fornecedores externos.
- A conversão para pedido confirmado não movimenta stock automaticamente; a equipa deve validar a disponibilidade e registar movimentos no módulo Stock conforme a operação real.
