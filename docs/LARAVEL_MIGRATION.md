# Plano de migração gradual para Laravel — GPM Conecta

## Objetivo

Migrar a aplicação PHP atual para Laravel mantendo a mesma base de dados MySQL e os registos existentes. A migração será gradual; não se recria a base de dados.

## Regras que não podem ser quebradas

- A base de dados atual é a fonte oficial dos dados.
- Laravel e a aplicação antiga usam as mesmas variáveis DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME e DB_PASSWORD durante a fase de transição.
- Nunca executar php artisan migrate:fresh, db:wipe, DROP DATABASE ou importar um dump completo sobre produção.
- Não renomear nem apagar tabelas/colunas antes de migrar e validar todos os consumidores.
- Todas as tabelas operacionais devem usar InnoDB e relações compatíveis com a versão MySQL/MariaDB do servidor.
- Fazer backup verificável antes de qualquer migration e testar primeiro numa cópia isolada.

## Fase 0 — inventário e cópia de ensaio

1. Identificar a versão exata de MySQL/MariaDB e PHP.
2. Criar um dump consistente com rotinas, triggers e constraints quando existirem.
3. Restaurar o dump numa base de dados de ensaio com outro nome, por exemplo gpm_mvp_nova_test.
4. Comparar contagens de registos das tabelas críticas: users, customers, products, orders, order_items, stock_movements, conversations e messages.
5. Executar as migrations incrementais nessa cópia.
6. Executar php tests/migration_smoke.php apontando apenas para a cópia.

## Fase 1 — Laravel sem recriar o esquema

1. Criar o projeto Laravel numa pasta/repositório separado; não substituir a aplicação atual.
2. Configurar a ligação MySQL no .env do Laravel com os mesmos parâmetros da base de dados oficial.
3. Criar uma migration de baseline que apenas verifica a existência das tabelas e colunas atuais; não deve criar novamente tabelas existentes nem apagar dados.
4. Executar essa migration de baseline para que o Laravel passe a gerir o histórico a partir desse ponto.
5. Manter os modelos Eloquent com os nomes reais das tabelas e chaves atuais. Definir explicitamente table, primaryKey, timestamps e casts quando o esquema legado exigir.
6. Criar migrations Laravel apenas para novas alterações, sempre aditivas e reversíveis quando possível.

### Exemplo de baseline seguro

Existe um modelo em docs/laravel/2026_11_11_000000_baseline_existing_schema.php.example. Copia-o para a pasta database/migrations do futuro projeto Laravel e ajusta a lista de tabelas/colunas após o inventário final.

A migration inicial do Laravel deve verificar a presença das tabelas críticas com Schema::hasTable() e falhar com uma mensagem clara se faltar alguma. O método down() não deve apagar tabelas legadas; deve lançar uma exceção explicando que a reversão do baseline é manual e controlada.

Não gerar migrations que façam Schema::drop, dropIfExists ou recriem as tabelas atuais.

## Fase 2 — coexistência controlada

- Começar com módulos de leitura (catálogo, consulta de pedidos e painel).
- Comparar os resultados da aplicação Laravel com a aplicação PHP antiga.
- Migrar depois escrita de dados por módulo, com testes de integração e transações.
- Durante coexistência, evitar que duas aplicações atualizem o mesmo fluxo sem regras de concorrência e compatibilidade.
- Não alterar o formato de passwords até existir uma estratégia de autenticação compatível e testada.

## Fase 3 — corte

1. Agendar janela de manutenção.
2. Fazer backup final e testar a restauração.
3. Suspender escritas na aplicação antiga.
4. Aplicar apenas migrations já aprovadas e ensaiadas.
5. Validar login, permissões, catálogo, checkout, pagamentos, stock, chat e pedidos.
6. Reabrir escrita apenas depois da validação.
7. Manter plano de rollback para o código; não restaurar um dump antigo sobre dados novos sem análise.

## Critérios de aceitação

- Contagens e totais críticos antes/depois coincidem.
- Nenhum utilizador, cliente, produto, pedido, item, movimento de stock ou mensagem desapareceu.
- Hashes de passwords continuam verificáveis.
- Chaves estrangeiras e índices estão íntegros.
- Testes funcionais e de autorização passam.
- Backup e procedimento de restauração foram testados.

## Estado

Este documento define o procedimento. A migração Laravel ainda não foi executada e não deve começar antes de concluir o inventário e o ensaio numa cópia real da base de dados.
