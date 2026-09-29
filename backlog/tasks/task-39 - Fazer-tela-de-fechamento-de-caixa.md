---
id: TASK-39
title: Fazer tela de fechamento de caixa
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-09-12 15:53'
updated_date: '2026-09-29 00:59'
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

Execução por milestones, um por conversa, validado na tela antes do seguinte. Esta task cobre M2 a M6. M0 (modelo de títulos) e M1 (receber título no balcão) são tasks próprias e vêm antes.

Consolida: TASK-33 (codportador na manutenção de PDV), TASK-34 (não movimentar dinheiro sem portador no PDV), TASK-48 (dinheiro só para Caixa/Gerente/Administrador) e TASK-84 (destino do Caixa Totais: negocios /caixa + contas Caixas).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 M2 - Caixa abre e fecha no PDV com contagem de moedas e cedulas, mostra a diferenca e gera o PDF Movimento do Caixa
- [ ] #2 M2 - Venda em dinheiro entra no caixa do PDV; sem caixa aberto ou PDV sem portador o Dinheiro fica bloqueado com o motivo
- [ ] #3 M2 - So Caixa da filial, Gerente ou Administrador recebe em dinheiro
- [ ] #4 M2 - Cancelar venda em dinheiro tira do caixa; com o caixa daquele dia fechado, so reabrindo
- [ ] #5 M2 - Lancamento avulso de entrada e saida no caixa aberto
- [ ] #6 M3 - Titulo recebido ou pago em dinheiro aparece no caixa (liquidacao do contas, recebimento no PDV, vale colaborador, adiantamento)
- [ ] #7 M4 - Sangria e suprimento entre caixas, cofre, troco, financeiro e banco, com confirmacao de quem recebe e cancelamento com justificativa
- [ ] #8 M4 - Nao transfere de ou para caixa fechado; caixa nao fecha com transferencia chegando pendente
- [ ] #9 M4 - Tela Caixas no contas com saldos dos portadores em especie e transferencias
- [ ] #10 M5 - Financeiro fecha periodo de cofre e banco pela data de corte; fechado e imutavel; reabre e fecha em ordem
- [ ] #11 M6 - Chips, ingressos e maquinetas de parceiros contados no caixa e virando titulo Repasse Parceiro no fechamento
<!-- AC:END -->
