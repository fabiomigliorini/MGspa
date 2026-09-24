---
id: TASK-174
title: 'Campo de texto: o X de limpar rouba o Tab e cada tela repete a mesma gambiarra'
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-09-24 23:13'
updated_date: '2026-09-24 23:21'
labels:
  - components
dependencies: []
priority: low
type: chore
ordinal: 188000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Quem preenche formulario no teclado (PDV, wizard do vale, cadastros) passa pelo X de limpar antes de chegar no proximo campo: o icone do 'clearable' do Quasar tem tabindex=0 fixo no fonte (node_modules/quasar/src/composables/private.use-field/use-field.js, linha ~463) e nao tem prop pra desligar. Campo readonly tem o mesmo problema: para no Tab sem deixar digitar.

Ja existia contorno espalhado: MgInputValor desenha o X no slot #append com tabindex=-1, MgInputData forca tabindex=-1 no DOM depois de renderizar, e no ValeDialog o mesmo #append foi repetido na mao.

Solucao: @components/MgInput.vue (criado em 24/09/2026), q-input da casa que ja nasce com o X fora do Tab e com readonly pulado (:tabindex). Repassa $attrs, os slots prepend/append/before/after/hint e expoe focus/blur/select/validate/resetValidation/nativeEl, entao e troca 1:1 de <q-input> por <MgInput>.

Falta trocar os q-input crus: 485 ocorrencias em 188 arquivos (negocios 48, pessoas 135, contas 88, estoque 64, agro 35, notas 115). O app quasar/ (v1) esta abandonado e fica fora.

Relacionada: TASK-26 faz o mesmo movimento do lado dos selects, no app notas.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Nenhum <q-input> cru sobrou em negocios/src (trocado por MgInput)
- [ ] #2 Nenhum <q-input> cru sobrou em pessoas/src
- [ ] #3 Nenhum <q-input> cru sobrou em contas/src
- [ ] #4 Nenhum <q-input> cru sobrou em estoque/src
- [ ] #5 Nenhum <q-input> cru sobrou em agro/src
- [ ] #6 Nenhum <q-input> cru sobrou em notas/src
- [ ] #7 Tab anda campo a campo sem parar no X de limpar nem em campo readonly nas telas mexidas
- [x] #8 Regra do MgInput escrita no CLAUDE.md (campo novo e form que receber manutencao usam MgInput)
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
24/09/2026: criado o @components/MgInput.vue e a regra no CLAUDE.md. ValeDialog (negocios) ja migrado — foi o que levantou o caso. O MgInputFormatado tambem passou a montar em cima do MgInput (era q-input cru), entao os 21 lugares que usam ele ja herdam o X fora do Tab. Falta a varredura dos q-input crus, app por app.
<!-- SECTION:NOTES:END -->
