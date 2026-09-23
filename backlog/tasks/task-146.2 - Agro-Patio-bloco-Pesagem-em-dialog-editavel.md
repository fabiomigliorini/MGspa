---
id: TASK-146.2
title: 'Agro/Patio: bloco Pesagem em dialog editavel'
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-23 19:56'
updated_date: '2026-09-23 20:15'
labels:
  - agro
dependencies: []
parent_task_id: TASK-146
priority: medium
type: feature
ordinal: 157000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Extrair PBT/Tara + preview ao vivo (bruto/desconto/liquido/sacas via calcularCarga, de agro/src/utils/desconto.js) de CargaForm.vue para novo agro/src/components/carga/CargaBlocoPesagem.vue: exibicao read-only + lapis + q-dialog Cancelar/Salvar, mesmo padrao do bloco Caminhao (TASK-146.1). Preview dentro do dialog usa os valores em edicao (pbt/tara) + a classificacao ja salva em props.carga. Salvar aplica o patch e chama persistirBloco() (provide/inject de CargaForm.vue) quando !novo. Segundo bloco a ser feito, apos Caminhao. Ver TASK-146 para contexto completo e o plano em /home/usuario/.claude/plans/quero-montar-um-novo-synthetic-karp.md.
<!-- SECTION:DESCRIPTION:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Implementado: novo agro/src/components/carga/CargaBlocoPesagem.vue (exibicao read-only com preview bruto/desconto/liquido/sacas + lapis + q-dialog Cancelar/Salvar para PBT/Tara, com preview ao vivo dentro do dialog via calcularCarga). Injeta calc/itensCarga/sacasLiquido/persistirBloco providos por CargaForm.vue. Lint limpo. Verificacao visual em navegador NAO foi possivel nesta sessao (dev servers sem resposta HTTP, TASK-147) — pendente validacao do usuario na tela real.
<!-- SECTION:FINAL_SUMMARY:END -->
