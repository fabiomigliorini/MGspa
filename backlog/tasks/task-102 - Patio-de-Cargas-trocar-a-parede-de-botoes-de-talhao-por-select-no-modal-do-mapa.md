---
id: TASK-102
title: >-
  Patio de Cargas: trocar a parede de botoes de talhao por select no modal do
  mapa
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-09-16 20:45'
updated_date: '2026-09-16 20:47'
labels:
  - agro
dependencies: []
priority: medium
type: enhancement
ordinal: 101000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
No PlantioMapaDialog (Escolher talhao no mapa) o passo 3 renderiza um q-btn por talhao numa row que quebra linha. Numa fazenda com dezenas de talhoes (Renascer tem 40) isso vira uma parede de botoes coloridos ilegivel, ocupando a parte de baixo do modal inteira e empurrando o mapa. Trocar por um select com busca, mantendo a cor do talhao como marcador para nao perder o vinculo visual com o poligono do mapa.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Implementado em 16/09/2026 (agro/src/components/PlantioMapaDialog.vue).

A parede de q-btn do passo 3 virou um q-select com busca:
- cor do talhao mantida como bolinha (prepend do campo e avatar de cada opcao), mesma linguagem visual do SelectTalhao que abre o modal;
- busca por rotulo (use-input + filter), util com dezenas de talhoes;
- display e cor saem do talhao atual (computed talhaoAtual) e nao das opcoes: com a busca ativa a opcao escolhida pode estar fora da lista filtrada e o campo ficaria em branco;
- filtro limpa no popup-hide;
- quando nenhuma fazenda esta selecionada, a opcao mostra o nome da fazenda como caption (a lista abrange a safra inteira);
- slot no-option com 'Nenhum talhao encontrado.';
- rotulo do passo 3 virou 'clique no poligono ou escolha abaixo' e o vazio do mapa aponta pro campo, nao pra lista.

Removido o CSS .plantio-mapa-lista (max-height 22vh + overflow), que existia so pra segurar a parede de botoes — o mapa ganha essa altura de volta.

Observacao: o arquivo ainda NAO esta versionado (untracked), faz parte do WIP da TASK-101.
<!-- SECTION:NOTES:END -->
