---
id: TASK-233
title: >-
  Cobrança SafraPay cancelada esconde o cartão que o cliente pagou e o caixa
  lança à mão
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-10-17 16:28'
updated_date: '2026-10-17 16:47'
labels:
  - negocios
dependencies: []
priority: high
type: bug
ordinal: 244000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Relato do Fábio (17/10/2026): várias vezes por dia a cobrança SafraPay/Saurus fica CANCELED, mas o cliente pagou na maquininha (ela aceita pagar depois do cancelamento). O caixa lança o cartão à mão: retrabalho e risco de inconsistência. Exemplos: pedido 278542 (AMA Bravo, 16/10, R$ 52,00) e os dois de R$ 30,58 em BOT Alfa/Bravo (17/10).

Causa confirmada em produção (pedido 279482, 17/10): PdvController::consultarSaurusPedido só chama a Saurus quando o status local é 0 (pending). Com 1, 2 ou 3 devolve o registro do banco sem perguntar nada. Trocando o status para 0 no banco e consultando, o sistema puxou o pagamento na hora. O cancelamento (botão e o automático ao criar cobrança nova no mesmo PDV Saurus, CobrancaService::saurus -> cancelarPedidosAbertosPdv) só grava status 3 local; nenhuma requisição vai à Saurus, a maquininha continua aceitando o pagamento. A Stone (PagarMeService) consulta antes de cancelar e cancela na API.

Números do dump local (jan-jul/2026): 1 em cada 8 cobranças termina cancelada; das canceladas com cartão à mão na mesma venda, 1.327 em janeiro, 167 em julho. 56% dos cancelamentos vieram do automático.

Fora desta task (relatado, sem task): criação do pedido que falha/timeout é engolida (2.443 casos sem id da Saurus) e o PDV diz 'Cobrança criada'; a cobrança vai para o primeiro pinpad do PDV Saurus, não para o da maquineta ativa; não existe log das requisições à Saurus.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Consultar sempre pergunta à Saurus, qualquer que seja o status local do pedido; pedido cancelado que a Saurus diz pago vira aprovado e gera o pagamento do cartão na venda
- [ ] #2 Botão Cancelar consulta a Saurus antes e recusa o cancelamento quando o pedido já foi pago
- [ ] #3 Cancelar ou fechar a venda cancela sozinho os pedidos SafraPay ainda abertos dela, consultando antes
- [ ] #4 Criar cobrança nova no mesmo PDV Saurus não cancela mais a anterior; só fechar ou cancelar a venda cancela (na Stone a dinâmica é outra e continua como está)
- [ ] #5 Botão Desistir da cobrança (vermelho) pede confirmação: só cancela depois de digitar DESISTIR
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Correção (17/10/2026):
- api/app/Mg/Pdv/PdvController.php consultarSaurusPedido: sem a condição de status; toda consulta vai à Saurus.
- api/app/Mg/Saurus/SaurusService.php: cancelarPedido consulta antes e recusa (422) se já pago ou se a Saurus não responder; cancelarSeNaoPago (consulta, cancela só o que continua pendente, Saurus fora do ar cancela assim mesmo e vai pro log) usado pelo novo cancelarPedidosAbertosNegocio. cancelarPedidosAbertosPdv removido.
- api/app/Mg/Pagamento/CobrancaService.php saurus: não cancela mais a cobrança anterior do PDV ao criar uma nova (decisão do Fábio 17/10: a maquininha pode estar no meio do pagamento). Stone/PagarMe segue cancelando.
- api/app/Mg/Pdv/PdvNegocioService.php: fechar e cancelar chamam cancelarPedidosAbertosNegocio.
- components/cobranca/SaurusPedidoDialog.vue: botão Cancelar abre prompt que exige digitar CANCELAR; recusa do backend reconsulta e mostra o pagamento.
Não mexido: dialog da Stone/PagarMe segue cancelando sem confirmação. Pedido Saurus sem venda (baixa de título no contas) que ficar pendente não é cancelado por ninguém, fica pendente.

Teste (PDV, venda pequena, Receber -> SafraPay):
1. Cancelar a cobrança (digitar CANCELAR), pagar na maquininha, abrir a cobrança em Canceladas e clicar Consultar: vira aprovada e o cartão entra na venda.
2. Pagar na maquininha e só depois clicar Cancelar: recusa com 'já foi pago' e o cartão entra na venda.
3. Com a cobrança pendente, criar outra no mesmo PDV Saurus: a primeira continua pendente (não cancela mais).
4. Com cobrança pendente e sem pagar, fechar a venda com dinheiro (F3) ou cancelar a venda: a cobrança aparece cancelada.
5. Cancelado e pago antes da correção: abrir a cobrança e Consultar puxa o cartão; se já havia cartão à mão com outra autorização, a venda fica com dois e o manual precisa ser cancelado.
<!-- SECTION:NOTES:END -->

## Comments

<!-- COMMENTS:BEGIN -->
created: 2026-10-17 16:47
---
17/10: o botão do dialog passou de 'cancelar' (cinza) para 'Desistir da cobrança' (vermelho, color negative), porque 'Cancelar' no rodapé de dialog se lê como fechar a janela. O prompt de confirmação virou 'Desistir da cobrança?' com OK 'Desistir'; a palavra digitada continua CANCELAR (AC #3).
---
<!-- COMMENTS:END -->
