---
id: TASK-118
title: 'Cobranca Saurus: separar criar do enviar (erro no envio nao atualiza listagem)'
status: To Do
assignee: []
created_date: '2026-09-18 20:59'
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
