---
id: TASK-146.4
title: 'Agro/Patio: bloco Origem/Destino do grao em dialog editavel'
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
ordinal: 159000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Extrair a lista dinamica de pontos (origem/destino, add/remove linha, rateio de %, troca de safra pelo talhao escolhido, campos condicionais de NF na saida) de CargaForm.vue para novo agro/src/components/carga/CargaBlocoPontos.vue. UM UNICO bloco/dialog com as duas colunas (Origem | Destino) lado a lado, como a tela atual — nao separar em dois blocos, ja que a % de cada lado e editada em conjunto. Dialog mais largo (2 colunas). Copia editavel e um clone do array 'pontos' (nao da carga inteira); Salvar substitui props.carga.pontos pelo array editado e chama persistirBloco() quando !novo. Validacao de soma=100% continua condicionada a 'finalizando' (so obrigatoria perto de FINALIZAR) e permanece no FAB principal de CargaForm.vue (validarFinalizacao) — o dialog do bloco so mostra a dica visual, nao bloqueia o Salvar fora dessa etapa. Peças puras compartilhadas com CargaForm.vue (soma de %, filtro por papel) migram para agro/src/utils/carga.js. Ultimo bloco a ser feito, de proposito — e o mais complexo (unica lista editavel pelo usuario) e o mais usado no patio real; fazer por ultimo minimiza o tempo de instabilidade na etapa mais critica. Ver TASK-146 para contexto completo e o plano em /home/usuario/.claude/plans/quero-montar-um-novo-synthetic-karp.md.
<!-- SECTION:DESCRIPTION:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Implementado: novo agro/src/components/carga/CargaBlocoPontos.vue (exibicao read-only de origem/destino com % e kg estimado + lapis unico + q-dialog largo com as 2 colunas editaveis: add/remove linha, SelectContaTipo/Talhao/Unidade/Contrato, troca de safra pelo talhao, campos de NF condicionais). Copia editavel e um clone do array pontos + codsafra; Salvar substitui carga.pontos e chama persistirBloco(). Validacao de soma=100% continua condicionada a 'finalizando' (injetado), gate duro fica no FAB principal de CargaForm.vue. Helpers puros (distribuirPercentual/pontosPorPapel/somaPercentual/somaPercBate) extraidos para agro/src/utils/carga.js e reutilizados por CargaForm.vue e este bloco. Lint limpo. Verificacao visual em navegador NAO foi possivel nesta sessao (dev servers sem resposta HTTP, TASK-147) — pendente validacao do usuario na tela real, com atencao especial a este bloco por ser o mais complexo (lista editavel).
<!-- SECTION:FINAL_SUMMARY:END -->
