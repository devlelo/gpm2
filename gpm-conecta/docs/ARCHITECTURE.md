# GPM Conecta — Arquitetura proposta

## Princípios

- Laravel como aplicação central.
- API-first para permitir portal web, futuras aplicações e integrações externas.
- Domínio comercial independente de fornecedores, gateways e transportadoras.
- Integrações substituíveis por contratos/adaptadores.
- Processamento assíncrono para SMS, email, webhooks e chamadas externas.
- Auditoria de alterações comerciais e de permissões.
- Segurança multiutilizador e isolamento dos dados dos clientes.
- Primeiro fluxo manual funcional; automação acrescentada por etapas.

## Base candidata

Aimeos Laravel é o candidato principal em avaliação devido ao posicionamento API-first e suporte a cenários B2B. Aimeos não deve ser tratado como o domínio inteiro da GPM: pedidos de fornecimento, consultas a fornecedores, negociação e propostas terão módulos próprios.

A escolha definitiva depende de uma instalação de prova e da revisão das versões estáveis, dependências, licença, testes, extensibilidade e compatibilidade do ambiente de alojamento.

## Módulos de domínio

- Identity & Access: autenticação, clientes, funcionários, papéis e permissões.
- Company Accounts: empresa, NIF, contactos e moradas.
- Catalog: categorias, produtos, atributos, imagens e unidades.
- Cart: seleção temporária e quantidades.
- Customer Requests: pedido original do cliente.
- Procurement: consultas e respostas de fornecedores.
- Quotations: propostas comerciais e versões.
- Orders: encomendas confirmadas e respetivo ciclo operacional.
- Conversations: mensagens por pedido.
- Documents: geração, armazenamento e versões PDF.
- Notifications: email, SMS e notificações internas.
- Payments: contratos, transações e reconciliação, ativados numa fase futura.
- Logistics: expedição, transportadora e entrega.
- Integrations: adaptadores e credenciais de sistemas externos.
- Audit: registo imutável de ações críticas.

## Fronteiras essenciais

### Pedido não é encomenda confirmada
O carrinho submetido cria um Customer Request. Só após análise, proposta e aceitação é criado ou promovido o compromisso comercial para Order, conforme regras transacionais explícitas.

### Integrações não controlam o domínio
Gateway, marketplace externo ou fornecedor não atualiza diretamente tabelas internas. Cada conector traduz a resposta externa para comandos internos validados.

### API pública e API interna
- API pública versionada: catálogo público, autenticação, perfil, pedidos próprios, mensagens e documentos autorizados.
- API interna: operações administrativas e de funcionários, protegidas por permissões.
- API de integração: endpoints autenticados para webhooks e conectores, com assinatura, idempotência e limites de utilização.

## Estratégia de dados

Entidades principais previstas:
- users
- customer_companies
- customer_addresses
- products / categories / product_attributes
- carts / cart_items
- customer_requests / customer_request_items
- suppliers / supplier_connections
- supplier_inquiries / supplier_inquiry_items / supplier_responses
- quotations / quotation_versions / quotation_items
- orders / order_items
- order_status_history
- conversations / messages / message_attachments
- documents
- notification_deliveries
- payment_transactions / payment_events (fase futura)
- integration_connections / integration_logs
- audit_logs

Valores monetários devem usar decimal com precisão definida, nunca float. Guardar moeda por documento/transação. Guardar snapshots de descrição, unidade, preço e quantidade para preservar o histórico mesmo quando o catálogo mudar.

## Pagamentos futuros

O módulo Payments terá contrato independente, por exemplo:
- PaymentGateway
- createPayment()
- verifyPayment()
- handleWebhook()
- refund(), se suportado pelo parceiro

Cada transação terá referência interna, referência do gateway, valor, moeda, estado, método, timestamps e eventos recebidos. Webhooks serão autenticados, idempotentes e reconciliados com consulta servidor-a-servidor. Não confiar no redirecionamento do browser como prova de pagamento.

O modelo deve suportar pagamentos por referência Multicaixa e outros métodos disponibilizados por um gateway angolano contratado. Nenhum gateway está escolhido ou integrado nesta fase.

## APIs de fornecedores e marketplaces

Cada parceiro terá um SupplierConnector com operações opcionais, como:
- searchProducts()
- getProductDetails()
- checkAvailability()
- requestQuote()
- createSupplierOrder()
- getSupplierOrderStatus()
- cancelSupplierOrder()

Nem todos os fornecedores suportarão todas as operações. O sistema deve indicar a capacidade suportada por conector e permitir fallback manual.

Normalizar moeda, unidades, disponibilidade, prazos, identificadores externos e erros. Guardar payloads externos necessários para auditoria, removendo segredos e dados excessivos. Usar filas, timeouts, retries com backoff, circuit breaker quando apropriado e idempotency keys para operações que criam encomendas.

## Eventos e filas

Eventos de domínio previstos:
- CustomerRequestSubmitted
- SupplierInquiryCreated
- SupplierResponseReceived
- QuotationIssued
- QuotationAccepted
- OrderConfirmed
- OrderStatusChanged
- PaymentInitiated
- PaymentConfirmed
- ShipmentDispatched
- OrderDelivered

Consumidores assíncronos enviam notificações e sincronizam integrações. A falha de um serviço externo não deve reverter uma ação comercial já persistida.

## Segurança

- RBAC com princípio do menor privilégio.
- Policies para isolamento por cliente e empresa.
- Validação server-side de todos os dados.
- Proteção CSRF nas sessões web e autenticação apropriada para API.
- Rate limiting e proteção contra abuso.
- Segredos apenas em variáveis de ambiente/secret manager.
- Verificação de assinatura de webhooks.
- Uploads com validação de MIME, tamanho, extensão e armazenamento privado.
- URLs temporárias para documentos privados.
- Logs sem passwords, tokens ou dados sensíveis desnecessários.
- Backups e política de retenção.
- Testes de autorização negativos.

## Estratégia de testes

- Unitários para regras de domínio e transições.
- Feature tests para registo, pedidos, propostas, chat e documentos.
- Testes de autorização para impedir acesso cruzado entre clientes.
- Testes de idempotência e assinatura de webhooks.
- Contract tests para conectores de gateways e fornecedores.
- Testes de regressão após alterações de módulos.

## Infraestrutura

Produção exige alojamento compatível com PHP/Laravel, base de dados relacional, armazenamento persistente, HTTPS, scheduler e worker de filas. Vercel não será presumido como alojamento do backend Laravel tradicional. O frontend poderá ser servido pela própria aplicação Laravel ou separado quando houver benefício real.

## Decisões ainda abertas

- Versão Laravel e versão Aimeos estável compatível.
- Filament ou painel administrativo próprio.
- MySQL ou PostgreSQL conforme hosting e requisitos.
- Fornecedor SMS.
- Gateway angolano e contrato.
- Fornecedores/marketplaces externos prioritários.
- Regras fiscais e modelos finais de documentos.
