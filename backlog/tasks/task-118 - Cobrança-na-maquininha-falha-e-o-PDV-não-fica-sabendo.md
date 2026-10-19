---
id: TASK-118
title: Cobrança na maquininha falha e o PDV não fica sabendo
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-18 20:59'
updated_date: '2026-10-19 21:53'
labels:
  - negocios
dependencies: []
priority: medium
type: bug
ordinal: 125000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: teste do wizard Receber (TASK-100), 2026-09-18. Ao criar cobranca Saurus/SafraPay e o backend dar erro, a listagem de pagamentos/cobrancas do negocio nao atualiza — as tentativas ficam la (pending/cinza) sem o PDV saber.

Causa: PdvController::criarSaurusPedido faz tudo numa requisicao so: cancelarPedidosAbertosPdv -> SaurusService::criarPedido, que salva o tblsauruspedido e em seguida renova a autorizacao do PDV (ApiService::functionAutorizacao, FORA do try) e envia o pedido (functionPedidoCriar, dentro de try que engole o erro). Se a autorizacao ou qualquer passo depois do save() estoura, o pedido ja esta gravado mas a resposta vem 500; o front (negocio.js criarSaurusPedido) cai no catch, so faz console.log e nao chama atualizarNegocioPeloObjeto — a listagem fica desatualizada. E quando o envio falha dentro do try, volta 200 com um pedido que nunca chegou na maquininha (sem id), e o front trata como sucesso.

Proposta: dois passos. (1) backend cria o pedido local e devolve o negocio atualizado (sempre commitado); (2) chamada separada envia/reenvia para a Saurus, com erro explicito ao PDV e status do pedido refletindo a falha. Front atualiza a listagem apos o passo 1 independente do resultado do envio, e o SaurusPedidoDialog oferece reenviar. Avaliar o mesmo padrao para PagarMe (criarPagarMePedido) e PIX (criarPixCob). Ja existe reenviarSaurusPedido no PdvController, aproveitar.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Erro ao criar a cobrança aparece no PDV
- [x] #2 Cobrança que a SafraPay não confirmou avisa no dialog, com Reenviar
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Revisão do backlog com o Fábio (10/10/2026): o #1 foi feito na TASK-188 (commit c12254f04, components/stores/cobrancaStore.js). O servidor continua igual: SaurusService::criarPedido grava o pedido e engole o erro do envio, devolvendo 200 com pedido sem id.

Com o Fábio (19/10/2026): sem chamada em dois passos. O estado já existe: tblsauruspedido.id nulo = a SafraPay não confirmou o recebimento. A criação continua sem estourar (no timeout a cobrança costuma chegar e ser paga; a consulta é pelo idfaturapag): em jul/26 foram 194 pedidos sem id, 163 deles pagos; jan/26 teve 3.699 sem id. Feito: SaurusService::enviarPedido (autoriza, envia, grava o id; sem id = 422 com o retTexto da Saurus), usado pela criação e pelo Reenviar; renovação da chave junta em SaurusService::autorizar (eram três cópias; a do reenviar usava a chave velha porque o $pdv->fresh() não era atribuído) e roda antes de gravar, para não deixar pedido órfão. SaurusPedidoDialog: aviso em vermelho e foco no Reenviar quando pendente e sem id; o reenvio guarda o pedido devolvido e o aviso some. Teste: cobrar SafraPay com a URL da Saurus errada no .env local → dialog com aviso; corrigir a URL e Reenviar → aviso some e o pedido ganha id.
<!-- SECTION:NOTES:END -->
