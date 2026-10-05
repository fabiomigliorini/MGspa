---
id: TASK-39
title: Fazer tela de fechamento de caixa
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-09-12 15:53'
updated_date: '2026-10-03 00:32'
labels:
  - negocios
  - contas
  - api
dependencies:
  - TASK-186
documentation:
  - backlog/docs/doc-3 - Plano-do-fechamento-de-caixa-por-milestones.md
priority: high
type: feature
ordinal: 59000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Hoje o fechamento de caixa é feito à mão no formulário "Movimento do Caixa" (contagem de moedas/cédulas/chips/ingressos, vendas à vista, recebimentos, cartões, PIX, sangrias, diferença). O sistema só tem o protótipo "Totais de Caixa" do MG Lara, que lista totais e não fecha nada.

Desenho: portador como razão único do dinheiro (tblportadormovimento), gaveta = portador em espécie com PDV apontando, períodos por portador (sessão da gaveta / corte do financeiro), transferências em dois passos, itens de parceiros virando título de repasse. Plano completo, decisões e roteiro de cada milestone em backlog/docs/doc-3.

Execução por milestones, um por conversa, validado na tela antes do seguinte. Esta task cobre M10 a M13 (razão, transferências, períodos e itens do caixa). A fundação (M1 a M8.1) está na TASK-188 e a sessão da gaveta foi feita no M9 (critérios M9.x na TASK-188).

Consolida: TASK-33 (codportador na manutenção de PDV), TASK-34 (não movimentar dinheiro sem portador no PDV), TASK-48 (dinheiro só para Caixa/Gerente/Administrador) e TASK-84 (destino do Caixa Totais: negocios /caixa + contas Caixas).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 M11 - Sangria e suprimento entre caixas, cofre, troco, financeiro e banco, com confirmacao de quem recebe e cancelamento com justificativa
- [ ] #2 M11 - Nao transfere de ou para caixa fechado; caixa nao fecha com transferencia chegando pendente
- [ ] #3 M11 - Tela Caixas no contas com saldos dos portadores em especie e transferencias
- [ ] #4 M12 - Financeiro fecha periodo de cofre e banco pela data de corte; fechado e imutavel; reabre e fecha em ordem (em cadeia)
- [ ] #5 M13 - Chips, ingressos e maquinetas de parceiros contados no caixa e virando titulo de repasse ao parceiro (Duplicata a Pagar) no fechamento
- [ ] #6 M10.1 Dinheiro, PIX, deposito, transferencia e boleto feitos depois do go-live aparecem no razao da gaveta, cofre, banco ou conta onde cairam: entrada positiva, saida negativa, na data da transacao
- [ ] #7 M10.2 Cancelar, estornar ou corrigir um pagamento (valor, portador, meio ou data, no contas ou na conferencia do gerente) acerta o razao junto; com o caixa daquele dinheiro ja conferido, so reabrindo
- [ ] #8 M10.3 O detalhe do pagamento, no contas e no PDV, mostra os lancamentos do razao, com a sessao do caixa ou o periodo em que cairam, e os desfeitos riscados
- [ ] #9 M10.4 O saldo de cada caixa, cofre e banco e o saldo inicial do periodo mais o que caiu ate hoje
- [ ] #10 M10.5 Pagamento, cheque, extrato bancario e bonificacao guardam a data e hora em que o fato aconteceu, separada de quando foi digitado (o PIX de ontem lancado hoje fica com a data de ontem)
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Planejamento do M10 (02/10/2026, com o Fábio; detalhe no doc-3, seção M10): o razão lança dinheiro, PIX, depósito, transferência e boleto, uma linha por lado; chamado explicitamente pelo PagamentoService e pela edição/correção; só do go-live (CONFERENCIA_INICIO) em diante; corrente de cofre/banco/adquirente nasce no 1º lançamento com início no go-live e saldo 0; coluna transacao = quando cai; na gaveta o razão trava na conferência do gerente; card Razão no detalhe do pagamento. Padronização transacao = fato gerador: pagamento, cheque, extrato e bonificação no M10; negócio e o resto do sistema em task própria. Critérios #1 a #6 antigos (gaveta) movidos para a TASK-188 como M9.1 a M9.6.

M10 executado em dev em 02/10/2026, na árvore, sem commit (ACs M10.x desmarcados até a validação). DDL api/database/razao.sql rodado 2x (idempotente): lancamento → transacao em pagamento, cheque, extrato e bonificação; tblportadormovimento recriada; tblportadortransferencia apagada. Backend: PortadorMovimentoService::sincronizar (chamado em PagamentoService criar/contrario/efetivar/cancelar, PagamentoTituloService pagamentoDaForma/daBaixa/atualizar, PagamentoCorrecaoService corrigir/incluir), PortadorPeriodoService (corrente, imutavel, saldo), razao[] no detalhe; rename em toda a API, @components, contas e pessoas. Frontend: card Razão no MgPagamentoDetalhe. Conferido com rollback no tinker (detalhe no doc-3, seção M10 'Como ficou no código'). Não aberto no navegador.

M10 commitado sem validação a pedido do Fábio (02/10/2026): ele valida amanhã, junto com M11 e M12. ACs M10.x seguem desmarcados até a validação.
<!-- SECTION:NOTES:END -->
