---
id: TASK-267
title: >-
  Títulos pagos da contabilidade não seguem a ordem dos dias: o mesmo dia
  aparece espalhado em cada portador
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-11-04 21:08'
updated_date: '2026-11-04 21:11'
labels:
  - contas
dependencies: []
priority: medium
type: enhancement
ordinal: 275000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
O PDF Títulos pagos (contas → Portadores → Contabilidade, TASK-256) agrupava por fiscal/gerencial → filial → portador → pagamento → títulos; dentro de cada filial a data recomeçava a cada portador e quem confere os comprovantes não achava o que foi pago num dia (dev, 01 a 14/07/2026: 10 filiais, 29 blocos de portador, 120 de pagamento em 11 dias).

Pedido de 04/11/2026: dia crescente e, dentro do dia, valor crescente; o portador pode se repetir em todos os dias. Decisões: a filial continua acima dos dias (pagamento com títulos de duas filiais aparece nas duas, como antes); resumo por portador no fim de cada filial; os pagamentos do dia se ordenam pelo valor do comprovante (o da linha do pagamento, entrada negativa, encontro de contas 0,00 primeiro); títulos dentro do pagamento também em valor crescente.

Arquivos: api/app/Mg/Pagamento/PagamentoContabilidadeService.php, api/resources/views/pagamento/contabilidade.blade.php.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Dentro de cada filial os pagamentos vêm por dia crescente
- [x] #2 Pagamentos do dia em valor do comprovante crescente, com o portador na linha do pagamento
- [x] #3 Títulos de cada pagamento em valor crescente
- [x] #4 Resumo por portador no fim de cada filial, somando o total da filial
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Implementação (04/11/2026), na árvore, sem commit:
- PagamentoContabilidadeService: order by gerencial, filial, transacao::date, codpagamento, mt.total, vencimento, número; agrupar() troca o nível do portador pelo dia, ordena os pagamentos do dia pelo valor do comprovante com sinal (entrada negativa) e monta o resumo por portador da filial (com nome em ordem alfabética, Compensação/Folha por último). O sinal do valor saiu da view para o serviço.
- View: faixa azul do dia (data · N pagamentos + totais), portador em azul na 1ª célula da linha do pagamento, bloco 'Por portador · filial' com os rótulos das colunas no fim da filial.
Conferido no dev, 01 a 14/07/2026: totais de cada filial e geral iguais aos de antes; com Todos, 89 dias e 228 pagamentos, nenhum fora de ordem; resumo de cada filial soma o total dela; período vazio mostra a mensagem.
Teste: contas https://sistema-dev.mgpapelaria.com.br:8086/portador → Contabilidade → 01/07/2026 a 14/07/2026 → Fiscal → Gerar PDF (o dev só tem pagamento até julho).
<!-- SECTION:NOTES:END -->
