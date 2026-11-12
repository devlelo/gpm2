# GPM Conecta — Estratégia de integrações

## Objetivo

Preparar o produto para conectar-se a gateways angolanos, fornecedores, marketplaces externos, transportadoras e serviços de comunicação sem acoplar o núcleo comercial a qualquer fornecedor específico.

## Pagamentos angolanos (fase futura)

Requisitos previstos:
- Pagamento por referência Multicaixa, quando disponibilizado pelo parceiro contratado.
- Multicaixa Express e outros métodos que o gateway disponibilizar e a GPM contratar.
- Criação de intenção/transação no servidor.
- Referência e instruções apresentadas ao cliente.
- Confirmação por webhook autenticado e/ou consulta servidor-a-servidor.
- Reconciliação de transações e tratamento de pagamentos pendentes, expirados, falhados e duplicados.
- Registo de estornos quando suportados.
- Comprovativo associado ao pedido/encomenda.

Não implementar integração real nem simular confirmação nesta fase. Não guardar dados de cartão.

## Fornecedores e marketplaces (fase futura)

O sistema deve aceitar conectores independentes por fornecedor. Cada conector declara capacidades suportadas: pesquisa, detalhe de produto, stock, preço, pedido de cotação, criação de encomenda, tracking e cancelamento.

A operação inicial é manual: o funcionário consulta o parceiro fora do sistema e regista o resultado. Mais tarde, o mesmo fluxo de negócio poderá receber resultados por API sem mudar a experiência do cliente.

## Comunicação

SMS e email são serviços externos. A aplicação emite eventos internos e despacha jobs; adaptadores tratam autenticação, formatação, envio, retorno e retries. O pedido e a conversa não dependem de a mensagem SMS ser entregue.

## Contratos e governança

- Versionar endpoints internos (/api/v1).
- Documentar schemas e exemplos OpenAPI.
- Autenticar integrações com credenciais rotacionáveis.
- Preferir assinatura HMAC para webhooks quando suportada.
- Validar timestamp, assinatura e janela anti-replay.
- Usar idempotency keys em operações mutáveis.
- Aplicar timeouts, limites, retries controlados e circuit breakers.
- Mapear erros externos para códigos internos estáveis.
- Não expor tokens nem payloads sensíveis em logs.
- Guardar logs de integração com correlação por pedido.
- Ter sandbox/test mode separado de produção.

## Critérios de aceitação para uma integração

1. Documentação e contrato do parceiro revistos.
2. Credenciais de teste guardadas fora do repositório.
3. Testes de contrato e casos de erro.
4. Idempotência e reprocessamento validados.
5. Logs e monitorização disponíveis.
6. Procedimento manual de contingência documentado.
7. Aprovação operacional da GPM antes de ativar em produção.
