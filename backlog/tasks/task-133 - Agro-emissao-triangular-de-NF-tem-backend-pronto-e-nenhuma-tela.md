---
id: TASK-133
title: 'Agro: emissao triangular de NF tem backend pronto e nenhuma tela'
status: To Do
assignee:
  - '@eduardo'
created_date: '2026-09-21 21:33'
updated_date: '2026-10-22 19:26'
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

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Plano de emissão da nota do contrato não dá erro
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
22/10/2026 — causa do 500 do critério #1 (leitura de código): NotaFiscalContratoService::planoEmissao lê $calc['itens'] (linha 66), mas ContratoCalculoService::calcularDoContrato não devolve a chave itens (só bruto, liquido e totais). Só estoura quando o contrato tem nota no plano. A correção depende de decidir de onde vêm os tributos por carga (os gravados em cada fixação desde a TASK-183, por exemplo); o pré-requisito do vínculo cultura → produto fiscal continua faltando.

22/10/2026 (2) — desativado a pedido: o card 'Plano de NF' (ContratoNotas.vue) saiu do detalhe do contrato e a rota GET v1/contrato/{}/carga/{}/emissao ficou comentada em api/routes/api.php. O CRUD v1/contrato/{}/nota continua, para não perder o plano já cadastrado. Para religar: decidir de onde vêm os tributos por carga (os gravados na fixação) e criar o vínculo cultura → produto fiscal. Como testar: abrir um contrato; o card Plano de NF não aparece.
<!-- SECTION:NOTES:END -->
