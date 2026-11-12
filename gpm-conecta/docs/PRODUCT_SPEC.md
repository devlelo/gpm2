# GPM Conecta — Especificação funcional

## 1. Posicionamento

A GPM Conecta é uma plataforma integrada de fornecimento para empresas e projetos. Ajuda clientes a adquirir, através de um único ponto de contacto, materiais, equipamentos e soluções necessários para construir, equipar e operar instalações.

Alimentação profissional é uma categoria da oferta, não o posicionamento principal.

## 2. Perfis de utilizador

### Visitante
- Explora a apresentação institucional.
- Navega categorias e produtos.
- Pesquisa e filtra o catálogo.
- Consulta informação pública de produtos.
- Precisa de criar conta para submeter um pedido.

### Cliente
- Regista-se e autentica-se.
- Mantém dados pessoais e empresariais, incluindo NIF.
- Gere contactos e moradas.
- Cria vários pedidos independentes.
- Consulta estado e histórico.
- Conversa com a GPM por pedido.
- Consulta, descarrega e partilha documentos PDF.
- Pode repetir um pedido anterior criando um novo pedido, sem alterar o histórico.

### Funcionário GPM
- Consulta pedidos recebidos.
- Assume responsabilidade por pedidos.
- Regista consultas manuais a fornecedores.
- Atualiza disponibilidade, preços, quantidades e prazos.
- Envia perguntas, propostas e documentos.
- Propõe alternativas.
- Atualiza estados com motivo e registo de auditoria.

### Administrador GPM
- Gere utilizadores, permissões, equipa, catálogo e categorias.
- Acompanha todos os pedidos e indicadores.
- Configura integrações e parâmetros operacionais.
- Consulta logs e auditoria.

## 3. Jornada principal

1. Visitante abre o website.
2. Explora categorias e produtos sem autenticação.
3. Adiciona produtos e quantidades ao carrinho.
4. Ao submeter, autentica-se ou cria conta.
5. O formulário recolhe nome, email, telefone, empresa, NIF, morada e observações necessárias.
6. O sistema cria um pedido com referência única e fotografia dos itens, quantidades e dados comerciais apresentados.
7. O pedido entra em análise, ainda não é uma venda confirmada.
8. Funcionário GPM consulta um ou mais fornecedores, inicialmente de forma manual.
9. A GPM regista respostas, custos, disponibilidade, prazo, validade e condições.
10. Funcionário conversa com o cliente no chat associado ao pedido.
11. GPM envia proposta comercial versionada.
12. Cliente aceita, pede alteração ou recusa.
13. A GPM confirma o fornecimento apenas após validação interna e aceitação do cliente.
14. Pedido confirmado segue para preparação, expedição e entrega.

## 4. Estados de pedido

- `submitted`: pedido recebido.
- `under_review`: em análise pela GPM.
- `supplier_check`: consulta de fornecedores.
- `awaiting_customer`: aguarda informação ou resposta do cliente.
- `proposal_sent`: proposta enviada.
- `customer_accepted`: cliente aceitou a proposta.
- `confirmed`: GPM confirmou o fornecimento.
- `preparing`: em preparação.
- `dispatched`: expedido/encaminhado.
- `delivered`: entregue.
- `partially_available`: disponibilidade parcial proposta.
- `rejected`: recusado pela GPM.
- `cancelled`: cancelado.

As transições devem ser validadas no servidor e guardadas com autor, data, estado anterior, novo estado e motivo. Não permitir saltos arbitrários.

## 5. Pedido, consulta, proposta e encomenda

São entidades diferentes e não devem ser reduzidas a uma única tabela:

- Pedido do cliente: intenção original, imutável quanto aos itens e quantidades inicialmente submetidos.
- Consulta ao fornecedor: solicitação e resposta de cada fornecedor para satisfazer itens do pedido.
- Proposta GPM: preço, quantidades, disponibilidade, prazo, validade e condições apresentados ao cliente; deve suportar versões.
- Encomenda confirmada: compromisso de fornecimento criado apenas após aprovação da GPM e aceitação do cliente.

## 6. Documentos

- Recibo de submissão do pedido: prova de receção, não é fatura nem confirmação de venda.
- Orçamento/proposta: documento comercial com validade e versão.
- Proforma: emitida quando aplicável, depois da validação comercial e conforme regras da empresa.
- Comprovativos de pagamento: fase futura, vinculados a transações verificadas.

Todos os PDFs devem identificar a GPM, cliente, NIF, referência, linhas, quantidades, valores, moeda, data, validade e estado do documento. A natureza legal/fiscal dos documentos deve ser validada pela GPM antes da produção.

## 7. Portal do cliente

- Perfil e dados empresariais.
- Pedidos ativos.
- Histórico de pedidos.
- Detalhe e linha temporal de cada pedido.
- Conversa por pedido.
- Propostas e documentos descarregáveis.
- Ação para repetir pedido, criando novo registo.

## 8. Administração

- Dashboard de pedidos por estado.
- Gestão de produtos, categorias e atributos.
- Gestão de clientes e NIF.
- Fila de pedidos e atribuição a funcionários.
- Consultas a fornecedores e comparação de respostas.
- Construtor de propostas e versões.
- Chat e anexos.
- Gestão de estados e entregas.
- Relatórios e auditoria.

## 9. Notificações

Notificações por eventos: pedido recebido, funcionário atribuído, mensagem nova, proposta enviada, pedido confirmado, pagamento confirmado (fase futura), expedição e entrega.

SMS deve usar um adaptador de fornecedor externo. Guardar estado, resposta, tentativas e erros. Não expor credenciais no frontend.

## 10. Critérios de aceitação

- Visitante consegue navegar sem conta.
- Visitante não consegue submeter pedido sem autenticação.
- Cadastro exige NIF e valida os campos obrigatórios.
- Cliente só acede aos próprios dados e pedidos.
- Cliente consegue criar vários pedidos.
- Pedido enviado não aparece como venda confirmada.
- Funcionário consegue registar consulta a fornecedor e proposta.
- Cliente consegue conversar e descarregar documentos.
- Alterações de estado ficam auditadas.
- Falha de SMS não bloqueia o pedido.
- Sistema permanece utilizável sem gateway de pagamento e sem APIs de fornecedores.


## 11. Catálogo público e ficha de produto

- O catálogo público tem página própria com pesquisa por nome, referência e palavras-chave, além de filtro por área.
- Cada referência publicada abre uma ficha individual através de uma referência estável.
- A ficha apresenta apenas descrição e especificações validadas.
- Preços, disponibilidade, quantidades mínimas e prazos são confirmados pela GPM após consulta comercial.
- Uma ficha pública não representa uma proposta nem confirma stock.
- Enquanto não existirem referências aprovadas, o catálogo e a ficha apresentam estados vazios claros.
- O catálogo frontend atual não possui backend, autenticação, carrinho persistente nem submissão de pedidos.
