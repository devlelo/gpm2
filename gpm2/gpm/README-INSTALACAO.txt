GPM — LADO DO CLIENTE (PHP PURO)
================================

Este pacote contém a área do cliente e o marketplace para o projeto GPM em PHP puro + MySQL.

CAMINHO PREVISTO
----------------
Extrai o conteúdo para:
C:\xampp\htdocs\gpm2\

A estrutura resultante deve ficar:
gpm2/
  app/bootstrap.php
  config/database.php
  assets/css/cliente.css
  assets/js/cliente.js
  assets/img/sem-imagem.svg
  cliente/
    index.php
    produto.php
    carrinho.php
    finalizar-pedido.php
    login.php
    cadastro.php
    logout.php
    pedidos.php
    pedido.php
    proforma.php
    chat.php
    perfil.php
    includes/

ANTES DE COPIAR
---------------
1. Faz uma cópia de segurança da pasta gpm2.
2. Não elimines o painel administrativo que já existe.
3. Se já tens config/database.php ou app/bootstrap.php, compara antes de substituir.
   Este pacote inclui versões mínimas para o lado do cliente; se os teus ficheiros
   atuais já têm funções usadas pelo administrador, preserva-os e integra apenas
   as funções que faltarem.
4. Confirma que a base de dados se chama gpm_mvp_nova. Se tiver outro nome,
   altera $database em config/database.php.
5. Confirma o utilizador e a palavra-passe do MySQL no mesmo ficheiro.

BASE DE DADOS ESPERADA
---------------------
O código do MVP usa os nomes ingleses das tabelas:
categories, products, customers, orders, order_items, proformas,
conversations, messages.

Colunas importantes usadas:
- categories: id, name, active
- products: id, category_id, name, description, price, stock_quantity,
  unit, sku, main_image, active, featured, created_at
- customers: id, customer_type, name, nif, phone, email, password,
  address, city, province, active
- orders: order_number, customer_id, responsible_user_id, customer_type,
  customer_name, customer_nif, customer_phone, customer_email,
  delivery_address, delivery_city, delivery_province, delivery_fee,
  discount, subtotal, total, payment_method, notes, ordered_at,
  created_at, updated_at
- order_items: order_id, product_id, product_name, product_sku,
  quantity, unit, unit_price, subtotal, created_at
- proformas: order_id, proforma_number, customer_name, customer_nif,
  customer_phone, customer_email, customer_address, subtotal,
  delivery_fee, discount, total, status, issue_date, notes, created_by,
  created_at, updated_at
- conversations: order_id, customer_id, assigned_user_id, status,
  last_message_at, created_at, updated_at
- messages: conversation_id, sender_type, customer_sender_id,
  user_sender_id, body, created_at

IMPORTANTE: O teu esquema real pode ter pequenas diferenças. Não importes um SQL novo
por cima da base de dados atual. Este pacote NÃO apaga nem recria tabelas.

IMAGENS DE PRODUTOS
-------------------
A página de detalhes usa products.main_image e tenta carregar também todas as imagens
da tabela product_images (product_id, image_path, sort_order) ou, em alternativa,
produto_imagens (produto_id, imagem, principal, ordem). Se a tabela não existir,
a imagem principal continua a ser mostrada.

COMO TESTAR
-----------
1. Inicia Apache e MySQL no XAMPP.
2. Abre: http://localhost/gpm2/cliente/
3. Abre um produto e testa a galeria.
4. Adiciona um produto ao carrinho.
5. Atualiza quantidades e remove um item.
6. Clica em finalizar pedido.
7. Cria uma conta ou entra. O carrinho deve continuar guardado na sessão.
8. Confirma a morada e envia o pedido.
9. Abre "Os meus pedidos", entra nos detalhes, consulta a proforma e envia
   uma mensagem no chat.

REGRAS IMPORTANTES
------------------
- Os preços são sempre consultados novamente na base de dados no checkout.
- O checkout cria pedido, itens, proforma e conversa dentro de uma transação.
- O checkout NÃO baixa stock. A confirmação comercial/saída de stock pertence
  ao lado administrativo, de acordo com o fluxo definido para o GPM.
- A proforma é uma visualização HTML imprimível. No navegador, escolhe
  "Imprimir" > "Guardar como PDF". Não é uma fatura fiscal.
- O cliente só consegue consultar os próprios pedidos e conversas.
- A conta precisa estar ativa para iniciar sessão.
- Palavra-passe guardada com password_hash e validada com password_verify.

NOTAS
-----
O ZIP foi montado com base nos ficheiros e nas estruturas partilhadas no projeto.
Como não tenho acesso à tua instância local do MySQL/XAMPP, a ligação e os fluxos
precisam de ser testados no teu computador. Se algum nome de coluna da tua BD for
diferente, o erro ficará registado no log do PHP e será necessário ajustar a query.
