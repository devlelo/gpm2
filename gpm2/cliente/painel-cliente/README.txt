GPM — PAINEL DO CLIENTE (PHP PURO)

Ficheiros incluídos:
- cliente/painel.php
- cliente/pedidos.php
- cliente/pedido.php
- cliente/chat.php
- cliente/dados.php
- cliente/alterar-senha.php

INSTALAÇÃO
1. Faz uma cópia de segurança da pasta atual cliente.
2. Copia os seis ficheiros PHP para C:\xampp\htdocs\gpm2\cliente\
3. Não substituas includes/iniciar.php, login.php ou cadastro.php.
4. Confirma que app/bootstrap.php disponibiliza db(), csrf_field() e verify_csrf(), e que cliente/includes/iniciar.php carrega esse bootstrap e define cliente_atual().
5. Confirma que a tabela customers tem password, address, city, province, updated_at e que as tabelas orders, order_items, conversations, messages e proformas correspondem ao esquema atual.
6. Abre http://localhost/gpm2/cliente/painel.php após iniciar sessão como cliente.

IMPORTANTE
- Os pedidos são sempre filtrados pelo ID do cliente autenticado.
- O chat só mostra a conversa associada a uma encomenda que pertence ao cliente.
- O botão Sair aponta para cliente/logout.php. Se esse ficheiro não existir, cria-o conforme instruções na conversa ou adapta o link para o logout de cliente existente.
- O código espera que customers.password contenha hashes produzidos por password_hash().
- Se a tua estrutura tiver nomes de colunas diferentes, não executes em produção antes de alinhar o código com o esquema real.
