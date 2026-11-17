---
id: TASK-277
title: Títulos pagos da contabilidade trazem recebimentos e vales que não são despesa
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-11-17 19:49'
updated_date: '2026-11-17 19:50'
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

Arquivo: api/app/Mg/Pagamento/PagamentoContabilidadeService.php (linhas()).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Só entram títulos de tipo a pagar (Duplicata, PIX/Depósito e Entrega Pagar, Adiantamento e Débito Fornecedor)
- [x] #2 Encontro de contas mostra só o lado a pagar
- [x] #3 Pagamento sem título a pagar não aparece
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Implementação (17/11/2026), na árvore, sem commit: linhas() troca o bloco 'saiu dinheiro / meio 91-92 / exists título a pagar' por 'and tt.pagar' (o terceiro or já deixava entrar todo pagamento com título a pagar, então o conjunto de pagamentos não muda). linhaspagamento segue contando todas as linhas: o pagamento com linha que não é despesa mostra 'N de M títulos no filtro' e o valor cheio do comprovante.
Conferido no dev, 01 a 14/07/2026, Fiscal: 392 → 311 linhas, 109 → 106 pagamentos, total 856.471,19 → 857.462,14; nenhuma Duplicata a Receber, Vale Compras, Crédito Cliente, Vale Colaborador ou Rubrica RH no PDF; resumo por portador soma o total de cada filial; 18 páginas (eram 20).
Teste: contas https://sistema-dev.mgpapelaria.com.br:8086/portador → Contabilidade → 01/07/2026 a 14/07/2026 → Fiscal → Gerar PDF.
<!-- SECTION:NOTES:END -->
