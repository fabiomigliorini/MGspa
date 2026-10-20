---
id: TASK-29
title: 'Adicionar historico de Vendedor, Filial, Natureza e Pessoa para auditoria'
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-12 15:53'
updated_date: '2026-10-19 23:43'
labels:
  - negocios
dependencies: []
priority: medium
type: feature
ordinal: 117000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: negocios/todo — topo, sem secao. "Para auditar quem alterou e quando."
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Troca de vendedor, filial, natureza e pessoa do negócio grava na tblauditoria (AuditoriaService::registrar, tabela tblnegocio, antes/depois só do campo que mudou), com quem e quando
- [x] #2 Troca de PDV e de usuário do negócio grava na tblauditoria em qualquer status (aberta, fechada, cancelada, reaberta), com quem e quando
- [x] #3 Troca do local de estoque grava na tblauditoria depois do fechamento (fechada, cancelada ou reaberta), como a filial
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Decisões (Fábio, 19/10/2026): vendedor, filial, natureza e pessoa só gravam depois do fechamento (venda fechada, cancelada ou reaberta pelo gerente); com a venda aberta é o atendimento e não grava. PDV e usuário gravam em qualquer status.

Implementação: gancho `updated` no `Negocio::booted` chama `NegocioService::auditarTroca`, que grava `AuditoriaService::TIPO_NEGOCIO_ALTERADO` (22, 'Negócio alterado') na tblauditoria (tabela tblnegocio), antes/depois só dos campos que mudaram (codpdv, codusuario; e codpessoavendedor, codfilial, codnaturezaoperacao, codpessoa depois do fechamento); quem e quando pelo codusuariocriacao/criacao. Pega qualquer caminho que grave pelo model: envio do PDV, fechar, apropriar, unificação de comanda e POST negocio/{cod}/identificar-vendedor. O nascimento do negócio não grava. Sem DDL.

Teste: (1) no PDV, abrir uma venda fechada, trocar vendedor/cliente/natureza (mesmo lado) ou local de outra filial e transmitir: o Histórico do negócio mostra 'Negócio alterado' com usuário, hora e campo: antes → depois. (2) Trocar o vendedor numa venda aberta não grava. (3) Apropriar uma venda aberta de outro PDV, ou fechar uma venda aberta por outro usuário: grava codpdv/codusuario.

Testado no dev (19/10/2026), por tinker numa transação desfeita: fechada troca vendedor (grava só o campo, com o usuário logado); save sem troca e mesmo código em texto não gravam; aberta troca vendedor/pessoa não grava e troca PDV/usuário grava; cancelada troca natureza; reaberta troca pessoa; nascimento não grava; PdvNegocioService::negocioFechado com outro vendedor e com local de outra filial (codfilial); apropriar (codpdv); Histórico mostra 'Negócio alterado' com o usuário. 15 casos OK.

19/10/2026: Fábio pediu também o local de estoque (codestoquelocal), com a mesma regra da filial: só depois do fechamento. Antes, a troca de local dentro da mesma filial não ficava registrada.

Testado no dev (19/10/2026), tinker em transação desfeita: fechada com local da mesma filial pelo PdvNegocioService::negocioFechado grava só codestoquelocal (local temporário criado na transação, o dev tem um local por filial); local de outra filial grava codestoquelocal e codfilial numa linha; aberta não grava. 3 casos OK.
<!-- SECTION:NOTES:END -->
