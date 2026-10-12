---
id: TASK-31
title: Mostrar erro quando pessoa sem endereco ou telefone tentar devolucao
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-12 15:53'
updated_date: '2026-10-10 18:27'
labels:
  - negocios
dependencies: []
priority: high
type: bug
ordinal: 27000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: negocios/todo — topo, sem secao (prefixo BUG)

A tela de devolução não avisava nada sobre o cadastro do cliente. Sem endereço, a devolução recusava só depois do Confirmar (mensagem técnica de tributação), ou gravava e o vale não imprimia (vale.blade quebra em Pessoa->Cidade), ou a NF-e de devolução era rejeitada na transmissão (sem enderDest).

Decisão do Fábio: exigir endereço e telefone em toda devolução, com faixa vermelha ao abrir a tela e link para o cadastro no Pessoas em nova aba. Só front (negocios/src/pages/DevolucaoPage.vue), usando a Pessoa que vem no negócio recarregado do servidor: sem endereço = sem cidade ou endereco vazio/'Nao Informado'; sem telefone = nenhum telefone1..3 com dígito diferente de zero (o legado guarda '00 0000 0000', '0').
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Faixa vermelha ao abrir a devolução dizendo o que falta (endereço/telefone), com link para o cadastro em nova aba, e sem Confirmar
- [x] #2 Botão Devolução desabilitado para Consumidor, com dica 'Informe o cliente para fazer a devolução'
<!-- AC:END -->
