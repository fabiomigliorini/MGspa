---
id: TASK-236
title: >-
  Baixa de títulos lançada na forma ou no banco errado obriga a selecionar todos
  os títulos de novo
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-10-17 19:58'
updated_date: '2026-10-17 22:07'
labels:
  - contas
dependencies: []
priority: medium
type: feature
ordinal: 247000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Pedido do Fábio (17/10/2026): o usuário amarra a baixa ao banco errado, ou registra em dinheiro o que foi cartão. Com vários títulos, cancelar e refazer obriga a selecionar tudo de novo no Receber ou Pagar Títulos.

Decisão: a forma não se troca no lugar. Ficam duas ações independentes no detalhe do pagamento do contas: Cancelar (já existe, estorna as baixas e reabre os títulos) e Duplicar (abre a baixa com os mesmos títulos e valores já preenchidos). Duplicar não cancela o original sozinho.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Botão Duplicar no detalhe do pagamento de títulos (contas) abre o Receber ou Pagar Títulos com os mesmos títulos, valores, pessoa, data, referente e observação já preenchidos; a forma é escolhida de novo
- [ ] #2 Duplicar não cancela o original; com o original ainda valendo, recusa dizendo para cancelar antes
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Implementação (17/10/2026, aguardando teste do Fábio; nada commitado):

Servidor
- PagamentoDetalheResource: cada linha de baixa ganha duplicavel (Liquidação 600 e Amortização 300) e o título ganha operacao (DB a receber, CR a pagar, como na tela de baixa). O pagamento ganha duplicavel = não é venda nem acerto de RH, não é baixa do banco (boleto) e tem linha de baixa. Vale e adiantamento (Implantação) ficam de fora.
- Nenhuma rota nova: a baixa duplicada é a mesma POST v1/pagamento.

Telas
- Detalhe do pagamento (contas): botão Duplicar (content_copy) no cabeçalho, ao lado do lápis, com :to para pagamento/novo?duplicar=ID.
- Receber ou Pagar Títulos (pagamento/Nova.vue): com ?duplicar=, lê o pagamento e abre com os títulos, valores (capital, juros, multa, desconto, total), pessoa, data com hora, referente e observação dele. A forma se escolhe de novo no wizard. Original ainda valendo: recusa ao abrir ('Cancele o pagamento #N antes de duplicar: os títulos dele continuam baixados') e a tela fica vazia, como o PIX já usado.
- MgBaixaTitulos ganhou a prop inicial; sem ela (PDV e contas sem duplicar) nada muda.

Testes
- Lint e prettier: contas (Nova, Detalhe) e negocios (usos do MgBaixaTitulos) sem erro.
- Tinker, transação desfeita: o pagamento cancelado 80247912 (dinheiro, Caixa Centro, 2 títulos, um a receber e um a pagar) duplicado com o mesmo mapa da tela, em dinheiro no Caixa Financeiro: grava os mesmos títulos e valores, na data do original, e o original continua cancelado. Com a data de hoje, também grava. O efetivado 80248409: o servidor recusa pelo saldo. Venda, vale e boleto: duplicavel false. Nada ficou gravado.
- Não testado no navegador: gerar token de acesso para o Chrome headless foi recusado pela permissão da sessão.

Limite conhecido: 2 pagamentos antigos baixam o mesmo título em duas linhas; duplicados, o servidor recusa ('O mesmo título está duas vezes na baixa').

Roteiro de teste
1. Contas, Pagamentos, um recebimento de títulos ainda valendo: Duplicar recusa pedindo para cancelar antes.
2. Cancelar esse pagamento; no detalhe cancelado, Duplicar: a tela abre com os mesmos títulos marcados e os mesmos valores; no botão de baixo, data, pessoa, referente e observação do original.
3. Receber, escolher a forma certa (outro banco, cartão, dinheiro): grava e abre o detalhe do pagamento novo; o original continua cancelado.
4. Pagamento de venda, vale/adiantamento e boleto não mostram o Duplicar.
<!-- SECTION:NOTES:END -->
