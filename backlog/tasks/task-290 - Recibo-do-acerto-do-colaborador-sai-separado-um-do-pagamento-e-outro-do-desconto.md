---
id: TASK-290
title: >-
  Recibo do acerto do colaborador sai separado: um do pagamento e outro do
  desconto
status: Done
assignee:
  - '@fabio'
created_date: '2026-11-21 17:42'
updated_date: '2026-11-21 20:29'
labels:
  - pessoas
dependencies: []
priority: high
type: feature
ordinal: 301000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Pedido do Fábio (21/11/2026): o recibo de salário do colaborador sai hoje como ENCONTRO DE CONTAS quando o acerto tem pagamento e desconto. Precisa sair um recibo do que estamos pagando e outro do que estamos descontando.

Origem: decisão 5 da TASK-216 (11/10/2026) — recibo do acerto de RH unificado com o do pagamento; só pagar = pagamento (colaborador assina), só receber = recebimento (loja assina), os dois = encontro de contas (PagamentoReciboService::montarAcerto, AcertoReciboPdf). Recibos do colaborador nasceram na TASK-1.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Acerto com pagamento e desconto gera dois recibos: um do que a loja paga e outro do que desconta
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Feito (21/11): PagamentoReciboService::montarAcerto devolve um recibo por lado — o do que a loja pagou (benefício + créditos; o colaborador declara e assina) e o do que descontou (vales; a loja declara e assina), nessa ordem; cada um com o valor do seu lado e o texto que o lado sozinho já tinha. Acerto de um lado só continua com um recibo. Novo PagamentoReciboService::recibos (lista de recibos do pagamento: acerto → montarAcerto, senão [montar]); pdf, pdfLote e pdfTermica usam ele. Cupom (documento-termica) percorre os recibos com a linha de corte entre eles. Encontro de contas de baixa comum (fora do RH) não mudou.

Decisão do Fábio (21/11): 'Pagamento em' sai a forma do pagamento nos dois recibos, como hoje (sem explicar o desconto). Nº = código do pagamento nos dois.

Roteiro: no dev, acerto 20 (período 8, colaborador 591, pagamento 80246615: benefício 278,82, vale 89,30). Pessoas → /rh/8/colaborador/591 → recibo do acerto de 30/07: duas páginas A5, RECIBO de 278,82 (colaborador assina) e RECIBO de 89,30 (loja assina). Recibos do período e do colaborador: esse acerto sai com as duas páginas. Contas → pagamento 80246615 → Recibo (A5, duas páginas) e cupom da térmica (dois recibos com o corte). Acerto só com benefício ou só com desconto: uma página, como antes.

Teste (21/11, autorizado), tudo com rollback: service — acerto 20 (pagamento 80246615) sai em dois recibos, pagamento 278,82 assinado pelo colaborador e recebimento 89,30 (5 vales) assinado pela loja; acerto 16 só pagamento e acerto 21 só desconto, um recibo cada; acerto 6 zerado, 422 e fica fora do lote; baixa 80248481 (fora do RH) continua ENCONTRO DE CONTAS. HTTP — rh/periodo/8/acertos/20/recibo 2 pág., /21/recibo 1 pág., /6/recibo 404, /591/recibos 3 pág., /recibos 4 pág. (16, 20 em duas, 21), pagamento/80246615/recibo 2 pág., cupom assinado do PDV 2 pág. Bug achado e corrigido: no cupom, os dois recibos um embaixo do outro passavam da altura da bobina e a assinatura da loja caía partida na página seguinte; agora um recibo por página (classe quebrar-pagina, o mesmo do vale do título), como no A5.
<!-- SECTION:NOTES:END -->
