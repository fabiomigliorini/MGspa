---
id: TASK-146
title: 'Agro/Patio: blocos editaveis por dialog - visao geral'
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-23 19:56'
updated_date: '2026-09-23 20:15'
labels:
  - agro
dependencies: []
priority: medium
type: feature
ordinal: 155000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Tela de carga do Patio (agro/src/components/carga/CargaForm.vue) hoje e um unico formulario sempre editavel, sem confirmacao — edicao acidental de campo ja lancado passa despercebida. Dividir em 4 blocos (Caminhao, Origem/Destino do grao, Pesagem, Classificacao), cada um com botao lapis que abre um q-dialog Cancelar/Salvar. Decisoes de arquitetura ja validadas: (1) replicar o padrao local ja maduro no repo (familia Card*.vue em pessoas/src/components/pessoa/, ex. CardEndereco.vue), sem criar componente compartilhado novo tipo MgEditableCard; (2) cada dialog de bloco salva IMEDIATAMENTE ao clicar Salvar, reaproveitando o fluxo ja existente CargaPage.vue::persistir() (store.salvar + troca de safra + tratamento de erro) via uma funcao persistirBloco() provida pelo CargaForm.vue — nenhum bloco importa a store diretamente nem duplica esse tratamento; (3) enquanto a carga e nova (novo===true) nenhum bloco persiste, so edita estado local — nada e criado no Dexie/servidor antes do clique em Registrar. Plano completo em /home/usuario/.claude/plans/quero-montar-um-novo-synthetic-karp.md. Ordem de execucao: Caminhao, Pesagem, Classificacao, Origem/Destino (mais complexo, por ultimo), depois limpeza do FAB 'salvar sem avancar'. Sub-tasks referenciadas abaixo (parent).
<!-- SECTION:DESCRIPTION:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
4 blocos implementados (TASK-146.1 a 146.4): CargaForm.vue reescrito para renderizar CargaBlocoCaminhao/Pontos/Pesagem/Classificacao no lugar dos campos inline, com provide('persistirBloco', ...) + provide de calc/itensCarga/sacasLiquido/avisoClassificacao/finalizando para os blocos injetarem. CargaPage.vue passa :persistir='persistir' pro CargaForm. FAB cinza 'salvar sem avancar' e observacao ainda NAO tocados (fica pro TASK-146.5, que so deve rodar apos uso real validado). Lint limpo em todos os arquivos tocados. Verificacao visual em navegador bloqueada nesta sessao por problema de infra nos dev servers (TASK-147, criada durante este trabalho) — trabalho pronto na arvore, sem commit, aguardando validacao do usuario na tela real.
<!-- SECTION:FINAL_SUMMARY:END -->
