---
id: TASK-146.3
title: 'Agro/Patio: bloco Classificacao em dialog editavel'
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
ordinal: 158000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Extrair leituras de classificacao (itensCarga/parametrosDaCarga, hints de tolerancia/desconto) de CargaForm.vue para novo agro/src/components/carga/CargaBlocoClassificacao.vue: exibicao read-only + lapis + q-dialog Cancelar/Salvar, mesmo padrao do bloco Caminhao (TASK-146.1). Lista de itens vem dos parametros da cultura (nao e editavel em quantidade de linhas pelo usuario, so as leituras). Preview de desconto por parametro reaproveita props.carga.pbt/tara ja salvos. Salvar aplica o patch e chama persistirBloco() quando !novo. Terceiro bloco a ser feito, apos Pesagem (reaproveita o preview validado la). Ver TASK-146 para contexto completo e o plano em /home/usuario/.claude/plans/quero-montar-um-novo-synthetic-karp.md.
<!-- SECTION:DESCRIPTION:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Implementado: novo agro/src/components/carga/CargaBlocoClassificacao.vue (exibicao read-only das leituras por parametro + lapis + q-dialog Cancelar/Salvar, com preview de desconto ao vivo via calcularCarga sobre uma copia editavel do array classificacao). Injeta calc/itensCarga/avisoClassificacao/persistirBloco. Lint limpo. Verificacao visual em navegador NAO foi possivel nesta sessao (dev servers sem resposta HTTP, TASK-147) — pendente validacao do usuario na tela real.
<!-- SECTION:FINAL_SUMMARY:END -->
