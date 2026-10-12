---
id: TASK-199
title: >-
  Cada janela do sistema tem um jeito: título, linhas e botões mudam de tela
  para tela
status: To Do
assignee: []
created_date: '2026-10-06 19:43'
updated_date: '2026-10-10 19:06'
labels:
  - components
dependencies: []
priority: low
type: chore
ordinal: 212000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Quem usa o sistema abre um diálogo e cada um tem uma cara: título grande ('Editar portador', text-h6), título pequeno espaçado ('EDITAR TÍTULO', text-overline), com ou sem linha separando título e botões, explicação embaixo do título ou dentro do formulário. O padrão é o diálogo de editar título (contas/src/pages/titulo/Detalhe.vue), aplicado nas telas do portador, do período, do caixa e do item do caixa em 06/10/2026 (TASK-39).

Modelo:
- q-card flat, largura 400/600/700px com max-width 90-95vw.
- Título: <q-card-section class="text-grey-9 text-overline">EM MAIÚSCULAS</q-card-section>; com dado no meio (nome do portador, do item) usa text-uppercase em vez de escrever em maiúsculas.
- <q-separator inset /> logo abaixo do título e logo acima do q-card-actions.
- A explicação que ficava sob o título vira o primeiro texto do corpo, depois da linha: <q-card-section class="text-caption text-grey-7 q-pb-none">.
- Botões flat, Cancelar com tabindex=-1, sem persistent (regras que já valem).

Levantamento de 06/10/2026 (arquivos com <q-dialog>, contagem aproximada pelos que já têm text-overline e q-separator inset): pessoas 41 (~35 no padrão), notas 20 (~0), negocios 19 (~1), contas 25 (~21; faltam CorrecaoPagamentoDialog, fechamento/Index, fechamento/Venda, pix/Index), estoque 9 (~9), agro 23 (~0), @components 14 (~3). Conferir arquivo por arquivo: o grep só aproxima.

Dúvida para o Fábio antes de começar: o modelo de títulos usa q-card bordered flat; a memória do projeto diz diálogo sem bordered. Decidir um dos dois e aplicar em todos.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Diálogos do pessoas no modelo (título overline, linha abaixo do título e acima dos botões, explicação no corpo)
- [ ] #2 Diálogos do notas no modelo (título overline, linha abaixo do título e acima dos botões, explicação no corpo)
- [ ] #3 Diálogos do negocios no modelo (título overline, linha abaixo do título e acima dos botões, explicação no corpo)
- [ ] #4 Diálogos do contas no modelo (título overline, linha abaixo do título e acima dos botões, explicação no corpo)
- [ ] #5 Diálogos do estoque no modelo (título overline, linha abaixo do título e acima dos botões, explicação no corpo)
- [ ] #6 Diálogos do agro no modelo (título overline, linha abaixo do título e acima dos botões, explicação no corpo)
- [ ] #7 Diálogos do @components (componentes compartilhados) no modelo (título overline, linha abaixo do título e acima dos botões, explicação no corpo)
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Revisão do backlog com o Fábio (10/10/2026, 2ª varredura): contagem nova (arquivos com <q-dialog> no modelo: overline + q-separator inset): estoque 9/9, contas 28/33, pessoas 35/41, @components 10/21, negocios 1/19, notas 0/20, agro 0/23. No estoque a exceção é o detalhe do produto em conferencia/Listagem.vue (text-h6); os do estoque usam bordered flat, o que depende da dúvida aberta (bordered ou não). Em contas faltam os 4 da descrição e ocorrencia/Index.vue.
<!-- SECTION:NOTES:END -->
