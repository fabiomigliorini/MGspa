---
id: TASK-271
title: Gerente não consegue alterar a data do cartão no período da maquineta
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-11-06 14:07'
updated_date: '2026-11-06 15:10'
labels:
  - contas
dependencies: []
priority: high
type: bug
ordinal: 282000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Sintoma: na tela do período da maquineta (contas, /maquineta/{cod}/{codperiodo}), o Gerente da filial vê o botão de alterar a data da venda no cartão, preenche data e justificativa e recebe 403 ao gravar: "Alterar a data: só o operador ou o gestor de cada portador do pagamento (no cartão, o da adquirente)". O jeito de contornar é pedir a alguém que seja gestor da adquirente.

Causa: a tela abre para Gerente da filial, Financeiro e Administrador (ConferenciaAutorizador), e o Corrigir e o Registro indevido da mesma linha seguem essa regra (PagamentoCorrecaoService::autorizar). Já o alterar a data (TASK-204) segue o papel do usuário no portador da adquirente (tblportadorusuario: gestor; ou operador com os dois períodos abertos), em LancamentoDataService::autorizarPagamento. No dev, nenhum Gerente tem papel G nas adquirentes (Stone, SafraPay, Cielo...), só D ou nada. O docblock do LancamentoDataService já dizia "no cartão, quem confere a maquineta". Precedente: o estorno de pagamento de venda é do Gerente da filial, como o Reabrir (Fabio, 16/10/2026, PagamentoTituloAutorizador::motivoBloqueioEstorno).

Decisão (06/11/2026): na tela da maquineta, alterar a data do cartão é pela permissão de Gerente (Gerente da filial do pagamento, Financeiro, Administrador), inclusive para antes do go-live (12/10/2026).

Fora do escopo, com a regra de hoje (papel no portador): o lápis do pagamento e o extrato do caixa/banco.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Na tela da maquineta, o Gerente da filial (e o Financeiro e o Administrador) altera a data da venda no cartão sem precisar de papel no portador da adquirente, como já faz no Corrigir e no Registro indevido
- [x] #2 A data do cancelamento do cartão também segue essa regra
- [x] #3 O Gerente também leva o cartão para antes do go-live (decisão de 06/11/2026): o cartão sai do período e a auditoria guarda o antes, o depois e a justificativa
- [x] #4 Período conferido continua recusando, tanto o de onde o cartão sai quanto o para onde vai
- [x] #5 Gerente de outra filial não altera o cartão de uma venda que não é da filial dele (a mesma regra do Corrigir)
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
O risco da TASK-227 (tirar pagamento da conferência levando para antes do go-live) passa a incluir o Gerente nesta tela. No cartão o borderô da maquininha denuncia.

Validado em 06/11/2026 no dev pela rota (POST v1/maquineta-lote/{id}/pagamento/{cod}/data, com auth:api), cada caso numa transação desfeita: 16/16. A regra antiga recusava a Gerente elizandra (403, sintoma); com a correção, Gerente da filial, Financeiro e Administrador alteram a venda e o cancelamento; antes do go-live o cartão sai do período com auditoria; período conferido de onde sai e para onde vai recusa (422); Gerente de outra filial e Caixa recusam (403); fora da tela, o gestor da adquirente continua pela regra do portador. Falta a validação na tela.

Dados de teste no dev (06/11/2026), loja do Centro: 41 vendas reais de cartão clonadas para 02 a 06/11 com períodos novos na CEN Alfa (104: 9 conferido, 10 pendente, 11 aberto) e CEN Bravo (96: 12 aberto). Marca observacoes = 'TESTE TASK-271'. Caso principal: a venda #04550197 (05/11 07:52, R$ 7,50) é de 04/11 18:40; levada para lá, o período 10 bate com o borderô (13 / R$ 344,71).
<!-- SECTION:NOTES:END -->
