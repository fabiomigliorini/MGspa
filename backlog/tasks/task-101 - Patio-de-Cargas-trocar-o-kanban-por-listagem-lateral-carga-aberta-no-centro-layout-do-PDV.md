---
id: TASK-101
title: >-
  Patio de Cargas: trocar o kanban por listagem lateral + carga aberta no centro
  (layout do PDV)
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-16 19:37'
updated_date: '2026-09-22 13:54'
labels:
  - agro
dependencies: []
priority: medium
type: feature
ordinal: 100000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: pedido do Fabio em 16/09/2026. A tela /carga do agro obriga a trocar o select 'Tipo de Romaneio' (Recebimento/Expedicao/Transferencia) pra enxergar cada fluxo, e o sentido e fixado na criacao (o CargaDialog nao expoe). Pedido: ficar visualmente parecido com o PDV do negocios (OfflineLayout): listagem de cargas no drawer esquerdo (como 'Meus em Aberto'/'Ultimos'), a carga aberta no centro (formulario inline no lugar do CargaDialog modal, FABs de acao), resumo de pesos no drawer direito (como TotalNegocio/DetalheNegocio), e o sentido decidido na hora de digitar (toggle no formulario, editavel ate a primeira pesagem). Backend nao muda: CargaService so armazena/filtra 'sentido' — o sinal do movimento vem de contatipo+papel e a ordem das etapas vive so no front (ETAPAS_POR_SENTIDO). Rota vira carga/:uuid? com meta.leftDrawer (MainLayout do agro ja suporta). Store: remover sentidoAtivo/cargasPorEtapa/definirSentido; criar cargasNoPatio (ignora filtro de data) + cargasFinalizadas (respeita data) + cargaAtiva por uuid. Reaproveita o script do CargaDialog (~450 linhas) quase intacto como CargaForm.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Listagem no drawer esquerdo mostra 'No patio' (todos os sentidos, qualquer dia) e 'Finalizadas' (dia filtrado ou ultimas 30)
- [x] #2 Carga abre no centro pela rota /carga/:uuid; /carga/nova registra sem criar lixo no Dexie ate o Registrar
- [x] #3 Tipo de romaneio e um toggle no formulario, editavel ate a primeira pesagem; trocar re-semeia origem/destino vazios e volta pra 1a etapa
- [x] #4 Drawer direito mostra pesos, progresso das etapas, classificacao, origens/destinos e sync/reenvio
- [x] #5 F2 nova carga, F3 acao principal, F4 imprimir romaneio
- [x] #6 Caminhao que chegou ontem e nao finalizou continua aparecendo com o dia de hoje filtrado (pull das etapas abertas)
- [x] #7 Talhao da origem e escolhido NO MAPA (PlantioMapaDialog): cultura/safra -> fazenda -> clique no poligono; lista de apoio para talhao sem poligono; a carga segue a safra do talhao escolhido
- [x] #8 Nenhum campo manual de 'colhido' na carga: colhido por talhao/variedade continua derivado de tblmovimentograo (CargaService::gerarMovimento)
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Implementado em 16/09/2026. Arquivos: agro/src/utils/carga.js (constantes+helpers do dominio, sem Pinia), stores/carga.js (cargaAtiva, cargasNoPatio, cargasFinalizadas, chipsClassificacao, reenviar), stores/sincronizacao.js (pull das etapas abertas quando ha dia filtrado), components/carga/{CargaLeftDrawer,CargaListItem,CargaEtapaProgresso,SelectSentido,CargaForm,CargaResumo}.vue, pages/CargaPage.vue, router/routes.js (carga/:uuid? + meta.leftDrawer/rightDrawer), layouts/MainLayout.vue (drawer direito). CargaDialog.vue removido. Backend intocado. Como testar: sistema-dev:<porta agro>/#/carga — hard reload (Ctrl+Shift+R) antes, porque @components fica fora do HMR.

Conferido em 22/09/2026: tudo entregue, com DOIS criterios ja superados por trabalho posterior — marcados como atendidos porque o que valia na epoca foi entregue:
- AC #1: o filtro de dia foi REMOVIDO da tela (veio do legado); hoje 'Finalizadas' e sempre as ultimas 30. O recorte por dia virou assunto da TASK-132.
- AC #3: o tipo de romaneio nao trava mais na 1a pesagem — agora muda ate FINALIZAR (TASK-141).
<!-- SECTION:NOTES:END -->
