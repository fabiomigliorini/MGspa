---
id: TASK-133
title: 'Agro: emissao triangular de NF tem backend pronto e nenhuma tela'
status: To Do
assignee: []
created_date: '2026-09-21 21:33'
labels:
  - agro
dependencies: []
priority: low
type: feature
ordinal: 144000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
NotaFiscalContratoService::planoEmissao esta implementado e exposto em GET v1/contrato/{codcontrato}/carga/{codcarga}/emissao (sequencia de notas, partes, kg/sacas rateados da carga, bruto/liquido por saca com tributacao, encadeamento pai->filho via refNFe). O front do agro nunca chama esse endpoint - o grep de endpoints usados nao o encontra. O que existe na tela e so o CRUD do plano (ContratoNotas.vue, v1/contrato/{}/nota) e os campos manuais numeronf/valornf na etapa FISCAL da carga. O proprio docblock do service registra o pre-requisito que falta: vinculo cultura -> produto fiscal (codprodutobarra/NCM), que nao existe no cadastro de cultura. Definir se o passo seguinte e a tela de emissao (preview do plano + disparo pela pipeline de NotaFiscal) ou se o endpoint fica marcado como incompleto.
<!-- SECTION:DESCRIPTION:END -->
