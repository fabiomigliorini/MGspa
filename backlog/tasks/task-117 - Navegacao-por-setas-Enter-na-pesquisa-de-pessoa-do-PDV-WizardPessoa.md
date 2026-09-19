---
id: TASK-117
title: Navegacao por setas + Enter na pesquisa de pessoa do PDV (WizardPessoa)
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-18 18:51'
updated_date: '2026-09-18 19:49'
labels:
  - negocios
dependencies: []
priority: high
type: bug
ordinal: 106000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
No PDV offline (negocios), dialog de pessoa (F10 / clicar em Consumidor), passo DOC: depois da pesquisa o operador espera o 1o registro ja selecionado, navegar com seta cima/baixo e confirmar com Enter. Hoje as setas nao fazem nada e o Enter no campo de pesquisa dispara o submit do q-form (salvar(), que e do passo 3 - cadastro), ou seja, efeito colateral errado.

Investigacao (2026-09-18): negocios/src/components/offline/WizardPessoa.vue nao tem NENHUM handler de teclado em nenhuma versao do git (6b0198807 ate hoje) - nao foi um commit recente que quebrou; o comportamento provavelmente vinha do PDV antigo. O IndexPage (hotkeys globais) nao intercepta setas. O padrao atual de lista operada por teclado no app e negocios/src/components/offline/receber/ListaOpcoes.vue (TASK-100/43).

Impacto: toda venda com cliente identificado obriga o caixa a tirar a mao do teclado pro mouse.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
1. Estado de selecao no WizardPessoa: ref indice = 0; zera sempre que opcoes muda (watch) -> 1o registro ja vem selecionado apos cada pesquisa.
2. q-item com :active="i === indice" active-class="bg-blue-1 text-primary" (mesmo visual do ListaOpcoes) + :ref pra scrollIntoView({ block: 'nearest' }) ao mover (lista fica dentro de q-scroll-area 70vh).
3. @keydown no q-input de Pesquisa (foco continua no campo, operador pode seguir digitando):
   - ArrowDown/ArrowUp: prevent + mover indice (clamp nas pontas, sem wrap, pra nao pular do ultimo pro primeiro sem querer);
   - Enter: prevent + stop (nao deixa o q-form submeter salvar()); se tem opcoes e nao esta consultando -> confirmar(opcoes[indice].codpessoa, null).
4. Enter com debounce pendente (digitou e deu Enter antes de 1s): nao confirmar resultado velho - ignorar enquanto consultando ou enquanto o texto mudou desde a ultima pesquisa (guardar textoPesquisado ao fim do pesquisa()).
5. Enter sem resultados (decidido pelo Fabio 2026-09-18): CPF valido -> confirmar(1, cnpj) (CONSUMIDOR C/CPF); CNPJ valido -> nova(false) (CADASTRAR CNPJ); qualquer outro texto -> nao faz nada.
6. Mouse continua funcionando igual (@click no item).
7. Validar: eslint/prettier no arquivo; testar no PDV dev - F10, digitar 'migliorini', aguardar, setas descem/sobem com scroll, Enter confirma a pessoa; Enter com CPF digitado; dialog 'Alterar Pessoa' (confirmar ja existente) abre com Enter e aceita OK por teclado.
<!-- SECTION:PLAN:END -->
