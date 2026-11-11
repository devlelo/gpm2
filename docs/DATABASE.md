# GPM Conecta — Contrato e governação da base de dados

## Fonte de verdade
A base atual `gpm_mvp_nova` é o schema de referência. A aplicação PHP e a futura aplicação Laravel devem utilizar o mesmo banco MySQL/MariaDB por ambiente. O ficheiro `gpm_mvp_nova (1).sql` na raiz é apenas uma referência estrutural sem dados de clientes; não é um mecanismo de atualização de produção.

## Domínios atuais identificados
- Identidade: `users`, `customers`, `customer_addresses`.
- Catálogo: `categories`, `products`, `product_images`.
- Comércio: `orders`, `order_items`, `proformas`.
- Stock: `stock_movements`.
- Comunicação: `conversations`, `messages`, `contact_messages`.
- Auditoria: `activity_log`, `order_status_history`.
- Configuração: `company_settings`.

## Regras obrigatórias
- PKs atuais BIGINT UNSIGNED devem ser preservadas para compatibilidade com Laravel/Eloquent.
- Não alterar nomes, tipos, nulabilidade ou semântica de colunas existentes sem migration explícita e plano de compatibilidade.
- Não reutilizar IDs nem apagar encomendas, itens, movimentos de stock, pagamentos ou histórico.
- Valores monetários devem continuar em DECIMAL; nunca FLOAT/DOUBLE.
- Datas armazenadas em UTC; converter para fuso de apresentação na camada de aplicação.
- Estados de negócio precisam de histórico e transições validadas no servidor.
- Chaves externas devem ter índices e constraints.
- Dados de encomenda devem preservar snapshots do produto/cliente no momento da compra.
- Emails de clientes podem não ser únicos no schema atual; não assumir unicidade até resolver duplicados e decidir política de negócio.
- Antes de adicionar FKs/UNIQUE a tabelas antigas, verificar órfãos e duplicados.

## Migrations
As alterações novas ficam em `database/migrations/`, com nome ordenável por data e sequência. Cada migration deve:
1. declarar pré-requisitos e versão mínima do MySQL/MariaDB;
2. ser aditiva sempre que possível;
3. verificar existência de tabela/coluna/índice antes de criar;
4. não inserir dados de exemplo em produção;
5. incluir plano de backup, validação e rollback seguro;
6. ser testada numa cópia recente da base real.

A tabela `gpm_schema_versions` regista a versão do pacote incremental deste repositório. No futuro, o Laravel usará a sua tabela padrão `migrations` para controlar migrations Laravel. Não executar as duas ferramentas sobre a mesma alteração sem um plano de coordenação.

## Nova arquitetura de pagamentos e fornecedores
A migration `20261111_001_integrations_foundation.sql` adiciona:
- `supplier_integrations`: configuração de cada fornecedor e driver.
- `supplier_product_mappings`: correspondência entre IDs/SKUs externos e produtos internos.
- `supplier_sync_runs`: histórico de sincronizações.
- `payment_providers`: gateways configurados por ambiente.
- `payment_transactions`: cobranças associadas às encomendas, com idempotência.
- `payment_webhook_events`: eventos recebidos, verificação de assinatura e processamento idempotente.

A migration não altera as tabelas atuais. Credenciais são guardadas como ciphertext gerado pela aplicação; não colocar segredo em `settings_json`.

## Verificações antes de cada atualização
- Backup consistente concluído e restauração testada.
- Versão do servidor e engine confirmadas.
- Contagem de registos e integridade referencial registadas.
- Migration testada em staging.
- Aplicação antiga e nova testadas contra o mesmo schema.
- Logs sem credenciais nem dados pessoais desnecessários.
