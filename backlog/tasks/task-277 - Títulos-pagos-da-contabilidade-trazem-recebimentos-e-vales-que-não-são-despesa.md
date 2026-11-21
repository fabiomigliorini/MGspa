---
id: TASK-277
title: Títulos pagos da contabilidade trazem recebimentos e vales que não são despesa
status: Done
assignee:
  - '@fabio'
created_date: '2026-11-17 19:49'
updated_date: '2026-11-21 19:48'
labels:
  - contas
dependencies: []
priority: medium
type: enhancement
ordinal: 290000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
O financeiro quer no PDF Títulos pagos (contas → Portadores → Contabilidade) só o que é despesa. Entrava toda linha ativa de título do pagamento: o encontro de contas trazia a Duplicata a Receber, e apareciam Vale Compras, Crédito Cliente, Vale Colaborador e Rubrica RH (julho no dev: 755 linhas de Duplicata a Pagar e ~390 de outros tipos).

Decisão (17/11/2026): entram Duplicata a Pagar, PIX/Depósito Pagar, Entrega Pagar, Adiantamento Fornecedor e Débito Fornecedor = os tipos com tbltipotitulo.pagar. Regra: a linha entra se tt.pagar. O pagamento com outras linhas mostra 'N de M títulos no filtro' e o valor cheio do comprovante.

**Decisão revista (Fábio, 21/11/2026): volta a regra da TASK-256.** O relatório serve para conferir o que está indo para a contabilidade; tirar o que não é despesa esconde os lançamentos errados, em vez de mostrar para corrigir. Em produção, outubro/2026, o filtro tt.pagar tirava 111 pagamentos: 38 encontros de Crédito Cliente com Duplicata a Receber, 53 acertos do RH (só os descontos do colaborador) e 17 com dinheiro saindo de portador (R$ 22.621,02). Entre eles, o 80251758: R$ 3.627,00 de fornecedor (Daterra) lançado como Vale Colaborador.

Arquivo: api/app/Mg/Pagamento/PagamentoContabilidadeService.php (linhas()).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Voltam ao PDF os pagamentos em que saiu dinheiro, os encontros de contas e o acerto do RH, mesmo sem título a pagar (regra da TASK-256)
- [x] #2 O encontro de contas volta a mostrar os dois lados
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Implementação (17/11/2026), na árvore, sem commit: linhas() troca o bloco 'saiu dinheiro / meio 91-92 / exists título a pagar' por 'and tt.pagar' (o terceiro or já deixava entrar todo pagamento com título a pagar, então o conjunto de pagamentos não muda). linhaspagamento segue contando todas as linhas: o pagamento com linha que não é despesa mostra 'N de M títulos no filtro' e o valor cheio do comprovante.
Conferido no dev, 01 a 14/07/2026, Fiscal: 392 → 311 linhas, 109 → 106 pagamentos, total 856.471,19 → 857.462,14; nenhuma Duplicata a Receber, Vale Compras, Crédito Cliente, Vale Colaborador ou Rubrica RH no PDF; resumo por portador soma o total de cada filial; 18 páginas (eram 20).
Teste: contas https://sistema-dev.mgpapelaria.com.br:8086/portador → Contabilidade → 01/07/2026 a 14/07/2026 → Fiscal → Gerar PDF.

Reversão (21/11/2026), na árvore, sem commit: linhas() volta ao bloco da TASK-256 (saiu dinheiro / meio 91-92 / exists título a pagar) no lugar do 'and tt.pagar' do commit b5efd5bbd, com um comentário no SQL dizendo que é conferência e que filtrar por tipo de título esconde o lançamento errado. O comentário do ContabilidadeDialog.vue volta ao de antes.
Correção da nota de 17/11: o conjunto de pagamentos mudava, sim (109 → 106 no dev): saíam os pagamentos sem nenhum título a pagar.
Teste: contas https://sistema-dev.mgpapelaria.com.br:8086/portador → Contabilidade → 01/07/2026 a 14/07/2026 → Fiscal → Gerar PDF: volta aos 392 títulos em 109 pagamentos (total 856.471,19), com a Duplicata a Receber nos encontros de contas e os vales e créditos de cliente de volta.
<!-- SECTION:NOTES:END -->
