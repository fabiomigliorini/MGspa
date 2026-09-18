---
id: TASK-109
title: 'Patio: caminhao some da lista quando a safra selecionada e outra'
status: To Do
assignee: []
created_date: '2026-09-17 19:06'
labels:
  - agro
dependencies: []
priority: medium
type: bug
ordinal: 108000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
O patio e FISICO: no mesmo dia entram caminhoes de milho e de soja. Mas a lista "No patio" so mostra cargas da safra selecionada no topo do drawer esquerdo, porque `carregarCargas()` le do Dexie filtrando por `codsafra` da safra ativa e `cargasNoPatio` filtra so por etapa.

Efeito medido em 17/09/2026: a carga 1 (Milho 2026, parada em CLASSIFICACAO desde 11:46, so com PBT) fica INVISIVEL enquanto a safra selecionada for Soja 2026/2027 — a tela mostra "No pátio 0 / Nenhum caminhão no pátio". Sem abrir a carga nao da pra pesar a tara e finalizar; o caminhao fica preso no patio.

**Proposta:** "No patio" passa a listar cargas NAO finalizadas de todas as safras (a cultura/safra aparece no item da lista pra nao confundir), e "Finalizadas" continua filtrando pela safra ativa. `CargaPage.selecionar()` ja troca a safra ativa ao abrir carga de outra safra, entao o clique na lista ja funciona. Rever tambem `puxarCargas()`, que puxa as etapas abertas so da safra ativa.

**Decisao pendente:** confirmar com quem opera se o patio misturado e o comportamento desejado.
<!-- SECTION:DESCRIPTION:END -->
