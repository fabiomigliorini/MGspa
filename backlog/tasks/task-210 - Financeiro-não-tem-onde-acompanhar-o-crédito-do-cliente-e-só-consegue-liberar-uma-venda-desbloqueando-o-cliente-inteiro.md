---
id: TASK-210
title: >-
  Financeiro não tem onde acompanhar o crédito do cliente e só consegue liberar
  uma venda desbloqueando o cliente inteiro
status: To Do
assignee: []
created_date: '2026-10-10 20:17'
labels:
  - contas
dependencies: []
priority: high
type: feature
ordinal: 222000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Pedido do Fábio (10/10/2026): refatorar o controle de crédito. O financeiro abre um painel de crédito, vê as vendas pendentes do cliente, marca aquela venda como autorizada e o caixa consegue fechar com base nessa sinalização.

Hoje: limite, tolerância de atraso e 'Crédito Bloqueado' ficam no cadastro da pessoa (pessoas/src/components/pessoa/CardCliente.vue). O F3 do caixa recusa a venda com 'Solicite Liberação de Crédito ao Departamento Financeiro!' (api/app/Mg/Pdv/PdvNegocioService.php, PdvNegocioPrazoService::avaliaLimiteCredito). Para liberar, o financeiro desbloqueia o cliente ou sobe o limite, o que solta o cliente para tudo, sem registro de quem mudou nem de onde consultou. Cliente cadastrado pela SEFAZ/Receita já nasce com creditobloqueado = true (PessoaService::createPelaSefazReceitaWs). A mesma regra de crédito está em três lugares: PdvNegocioPrazoService::avaliaLimiteCredito, PessoaService::podeVenderAPrazo e CertidaoTipoService.

Sinergia: TASK-203 (painel de pendências: 'venda aguardando liberação de crédito' entra como item levando a esta tela) e TASK-209 (histórico/auditoria e MgHistorico para o histórico das alterações de crédito).

A decidir: autorização cai se o valor a prazo da venda mudar depois de autorizada? A venda só aparece no painel depois que o PDV sincroniza com o servidor.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Painel de crédito com indicadores: clientes bloqueados, acima do limite, em atraso além da tolerância e vendas aguardando liberação
- [ ] #2 Histórico das alterações de limite, bloqueio e tolerância: quem, quando, de quanto para quanto
- [ ] #3 Registro das consultas de crédito do cliente: data, onde consultou (SPC, Serasa...), resultado e observação
- [ ] #4 Na tela do cliente, as vendas pendentes dele; o financeiro marca a venda como autorizada (registrando quem e quando), sem fechar a venda
- [ ] #5 O F3 do caixa aceita a venda autorizada mesmo com crédito bloqueado ou estourado; a autorização vale só para aquela venda
- [ ] #6 A regra de crédito fica num lugar só no backend
<!-- AC:END -->
