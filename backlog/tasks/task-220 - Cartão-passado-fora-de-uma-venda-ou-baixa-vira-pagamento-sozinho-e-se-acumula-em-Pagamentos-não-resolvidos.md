---
id: TASK-220
title: >-
  Cartão passado fora de uma venda ou baixa vira pagamento sozinho e se acumula
  em Pagamentos não resolvidos
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-11 01:37'
updated_date: '2026-10-11 22:51'
labels:
  - contas
  - negocios
  - api
dependencies: []
priority: high
type: bug
ordinal: 231000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Mesma regra da TASK-219 (PIX), agora para o cartão integrado (Stone, SafraPay): o pagamento só nasce numa tela que sabe para que ele serve, que é a venda aberta no negócio ou a baixa de títulos e o adiantamento. A integração só confirma a cobrança. Se a tela não terminou, nada fica automático e quem precisar lança o cartão na mão. A conferência da maquineta com o borderô é o que acha o cartão que entrou e não foi lançado.

Hoje (TASK-188, critérios #27, #32 e #37) o pedido da Stone ou da SafraPay sem venda vira pagamento sozinho, e o cartão da venda cancelada fica órfão. Os dois caem em Pagamentos não resolvidos. No dev há dois exemplos: os pedidos Stone 634897 e 634898, importados em 08/10 sem venda.

O Fábio decidiu (10/10/2026) não repetir no cartão o controle de utilização do PIX: lança na mão e, se fizer falta, automatiza depois.

Prioridade alta: mesma frente do go-live do caixa que a TASK-219.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Pedido da Stone ou da SafraPay que não é de uma venda aberta não vira pagamento sozinho, inclusive o importado da maquininha
- [x] #2 Cartão cobrado na baixa de títulos ou no adiantamento: o pagamento nasce quando a tela grava; fechada antes, não fica pagamento
- [x] #3 Venda cancelada cancela junto o pagamento do cartão integrado (o estorno na maquininha é manual)
- [x] #4 Sai a tela Pagamentos não resolvidos (contas e PDV) e a forma Já recebido do wizard
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
## Implementação (10/10/2026)

- **Stone** (`PagarMeService`) e **SafraPay** (`SaurusService`): `vincularPagamento` só cria o pagamento do pedido da venda aberta; o pedido sem venda e o da venda cancelada não viram pagamento (e o da venda cancelada não ressuscita no reprocessamento). Novo `pagar(pedido, valor, campos)`: o pagamento nasce quando a baixa ou o adiantamento grava, um por pedido, com o valor do cartão, a maquineta e a hora em que o cartão passou. `cartaoDoPedido`/`maquinetaDoPedido` reúnem o que era repetido.
- **Baixa e adiantamento** (`PagamentoTituloService::pagamentoDaForma`/`portadorPrevisto`): forma com `codpagarmepedido`/`codsauruspedido` no lugar do `codpagamento` (sai o `pagamentoExistente`); FormRequests atualizados. O wizard (`baixaTitulosStore`) manda o id do pedido confirmado; o getter `formaConfirmada` segura no Gravar o dinheiro que já entrou.
- **Venda cancelada** (`PdvNegocioService::cancelar`): todos os pagamentos cancelados junto; sai `PagamentoService::amarrarVenda`.
- **Saem**: as telas Pagamentos não resolvidos (contas: menu e `pagamento/Pendentes.vue`; PDV: botão laranja e `PagamentoPendentesPage.vue`), `MgPagamentosPendentes`, a forma Já recebido (`FormaRecebido`), as rotas `pagamento/pendentes`, `duplicados`, `ja-lancado`, `devolver` (contas e PDV) e `negocio/{}/pagamento/{}/amarrar`, o `?codpagamento=` do Receber Títulos (contas e PDV) e o selo Não resolvido do detalhe do pagamento. `PagamentoPendenciaService` ficou só com saldo/amarrado/livre do detalhe.
- `MgBaixaTitulos` e `MgAdiantamentoDialog`: a prop `pagamento` virou `pix` (só o PIX sem uso usa).

## Testes (10/10/2026)
- **Serviço** (tinker, transação desfeita): pedido Stone 634898 sem venda não vira pagamento na confirmação; adiantamento com ele criou o pagamento (maquineta 86, aut. VRL8JE, data do cartão); usar de novo, valor errado e pedido de venda recusam; o mesmo na SafraPay (pedido 233920); venda 4460247 (Stone) e 4438520 (SafraPay) canceladas cancelam o cartão e reprocessar o pedido não o ressuscita.
- **Tela** (Chrome headless): menu do contas sem Não Resolvidos; wizard do Receber Títulos sem Já recebido; PDV sem o botão laranja e Receber Título carregando; PIX: Baixar títulos (Falta/Passou/Bate) e Adiantamento (valor travado, sem data) seguem funcionando; detalhe do pagamento sem o selo.

## No dev
Ficaram, como pagamentos comuns, os dois Stone avulsos (80248307, 80248308) que o código antigo criou e duas sobras de teste (80247735 PIX R$ 1, 80247875 dinheiro R$ 3).

Limpeza do dev (11/10/2026): cancelados os Stone 80248307 e 80248308 e o PIX 80247735 (o PIX 264901 voltou a ficar sem uso). O 80247875 (dinheiro R$ 3, teste) ficou: está no período fechado do Caixa Atacado (10/10 16:53) e cancelar exige reabrir o período.

## Bateria de regressão antes do go-live (11/10/2026)
- **Serviço**, 13 de 13 ok (tinker, transação desfeita): baixa em dinheiro no cofre, por transferência e por compensação; forma antiga com codpagamento recusada; adiantamento em dinheiro; vale colaborador; PIX QR de venda (cria o pagamento e marca o PIX); Stone e SafraPay de venda reprocessados mantêm o pagamento; devolução no cartão; autorizador com PIX e Stone; detalhe e listagem de pagamentos; listagem PIX por uso.
- **API**: PIX já usado e pedido Stone de venda recusados com a mensagem, sem gravar nada; PIX inexistente barrado na validação; rotas removidas respondem 404.
- **Telas** do contas (pagamentos, detalhes, Receber Títulos, PIX, títulos) e do PDV (pagamentos, Receber Título): sem erro de console nem de API.
- **Lint**: completo no contas e no negocios, sem erro.
- **Corrigido**: dois comentários que ainda citavam o Já recebido (FormaPortador e PdvNegocioPagamentoService).
<!-- SECTION:NOTES:END -->
