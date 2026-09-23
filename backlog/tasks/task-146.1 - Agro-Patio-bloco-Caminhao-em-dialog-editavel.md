---
id: TASK-146.1
title: 'Agro/Patio: bloco Caminhao em dialog editavel'
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
ordinal: 156000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Extrair Placa/Carreta/Motorista/Chegada de CargaForm.vue para novo agro/src/components/carga/CargaBlocoCaminhao.vue: exibicao read-only + botao lapis que abre q-dialog (Cancelar/Salvar), seguindo o padrao de pessoas/src/components/pessoa/CardEndereco.vue. Recebe a carga inteira por referencia (mesma instancia 'local' que CargaForm.vue ja mantem), le/escreve nela diretamente. Move tambem o autocomplete de placa e o CaminhaoDialog.vue (cadastro rapido de veiculo, ja existente, so muda de 'morada'). Salvar do dialog: aplica o patch e, quando !novo, chama a funcao persistirBloco() (provide/inject vindo de CargaForm.vue) — nao importa a store diretamente, nao duplica tratamento de erro/troca de safra (isso ja existe em CargaPage.vue::persistir). Primeiro bloco a ser feito — estabelece o gabarito (estrutura do dialog + uso de persistirBloco) para os demais. Ver TASK-146 para contexto completo e o plano em /home/usuario/.claude/plans/quero-montar-um-novo-synthetic-karp.md.
<!-- SECTION:DESCRIPTION:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Implementado: novo agro/src/components/carga/CargaBlocoCaminhao.vue (exibicao read-only + lapis + q-dialog Cancelar/Salvar para Placa/Carreta/Motorista/Chegada, com CaminhaoDialog de cadastro rapido embutido). Salvar aplica o patch em local (mesma referencia da carga) e chama persistirBloco() (provide/inject vindo de CargaForm.vue), que so persiste quando !novo. Lint (eslint) limpo. Verificacao visual em navegador NAO foi possivel nesta sessao: os 4 dev servers Quasar (agro incluso) estao retornando resposta HTTP vazia — ver TASK-147. Pendente: usuario validar na tela real (sistema-dev.mgpapelaria.com.br:8088/#/carga) assim que TASK-147 for resolvido.
<!-- SECTION:FINAL_SUMMARY:END -->
