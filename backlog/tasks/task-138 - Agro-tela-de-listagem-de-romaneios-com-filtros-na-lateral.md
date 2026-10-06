---
id: TASK-138
title: 'Agro: tela de listagem de romaneios com filtros na lateral'
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-09-22 12:23'
updated_date: '2026-10-06 13:44'
labels:
  - agro
dependencies:
  - TASK-137
priority: medium
type: feature
ordinal: 2000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Tela /cargas do agro: consulta do histórico de romaneios, online, com filtros no drawer da esquerda. O Pátio (/carga/:uuid) fica INTOCADO: ele é offline-first e só vê a safra ativa; a listagem é online e vê tudo.

Molde: a tela Modelos de Vale do negocios (/vale-modelo: ValeModeloPage + ValeModeloLeftDrawer). Layout, UI e UX iguais, com os dados do romaneio:
- página cinza centralizada (max-width 1200px, decisão do usuário; a vale usa 1086), botão "Imprimir lista" no topo à direita, q-table num card, MgEmptyState, scroll infinito;
- linha não é link: ações em ícone na última coluna (info de criação e abrir a ficha);
- sem botão + (romaneio novo continua no pátio);
- drawer com FilterDrawerShell + FilterGroup (cópia local, como em negocios/contas/estoque) e Situação em q-btn-toggle Ativos/Cancelados/Todos.

Colunas: todos os dados do romaneio, para o usuário cortar as desnecessárias na validação.

NÃO reusar SelectUnidade/SelectContrato/SelectTalhao: leem o Dexie, populado só ao abrir o Pátio. Num navegador que nunca abriu o Pátio viriam vazios, em silêncio. Os selects daqui são alimentados pela store cargaListagem (API).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Listagem no molde de Vales Emitidos: card com cabeçalho de colunas, filtros no padrão do Vale e botão Imprimir lista no topo
- [ ] #2 Totais separados por recebido, expedido e transferido, sem as canceladas
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Refeita em 05/10/2026 no molde da /vale-modelo do negocios (a versão no molde da NotasPage foi reprovada visualmente). Na árvore, sem commit.

Arquivos:
- agro/src/pages/CargasPage.vue: reescrita igual à ValeModeloPage. q-table com 19 colunas (Romaneio, Data, Tipo, Etapa, Safra, Cultura, Placa, Carreta, Motorista, Origem, Destino, PBT, Tara, Bruto, Desconto, Líquido, Sacas, Situação, ações). Totais no #bottom-row: uma linha por tipo (Recebido/Expedido/Transferido) com qtd e as somas embaixo de Bruto, Desconto, Líquido e Sacas (sacas só com uma cultura no recorte). Sai o FAB de impressão; entra "Imprimir lista" no topo (mesmo imprimirRelatorio, mesmos filtros).
- agro/src/components/cargas/CargasFiltrosDrawer.vue: reescrito no molde do ValeModeloLeftDrawer (FilterDrawerShell/FilterGroup, :bottom-slots=false, uma espera de 500 ms para o filtro inteiro). Lado em q-btn-toggle Ambos/Origem/Destino; Situação em q-btn-toggle Ativos/Cancelados/Todos.
- agro/src/components/FilterDrawerShell.vue e FilterGroup.vue: cópia literal das do negocios.
- agro/src/stores/cargaListagem.js: canceladas (bool) virou inativo 1/2/9; normalizarFiltros() no afterHydrate do persist (filtro salvo da versão anterior não tem inativo); carregarMais()/reiniciar() no esquema do valeModelo (espera a busca em voo); limparFiltros não busca mais (o watch do drawer busca); saiu sacasTotais.
- api/app/Mg/Grao/CargaService.php::totais: novo `sentidos` (ENTRADA/SAIDA/TRANSFERENCIA) sempre sem canceladas, mesmo com Cancelados/Todos no filtro. O qtd do topo continua sendo o recorte inteiro (guarda de 5000 linhas do relatório).

Conferido: php -l; totais em dev com inativo=9: soma dos sentidos = 315 romaneios e 8.413.825,824 kg, igual ao recorte só de ativas (39 canceladas fora); com inativo=2 os sentidos não mudam. eslint e prettier limpos; os 5 módulos compilam pelo Vite do dev. Screenshot headless (template real + Quasar do node_modules + 30 romaneios reais do endpoint) em 1600px, 2700px e 430px: cabeçalho do drawer, card, botão no topo, badges e ícones como na vale; totais alinhados embaixo das colunas. Com 19 colunas a tabela rola de lado dentro do card em 1200px.

FALTA VALIDAÇÃO do usuário em https://sistema-dev.mgpapelaria.com.br:8088/#/cargas, e a escolha das colunas que ficam.

06/10/2026 — colunas escolhidas pelo usuário (Tipo, Data, Etapa, Safra, Placa, Motorista, Origem, Destino, Bruto, Tara, Desconto, Líquido, Sacas, ações). Saíram Romaneio, Cultura, Carreta, PBT e Situação. As células do #body estavam na ordem antiga e desalinhavam do cabeçalho: agora seguem a ordem do array `colunas`. Linha de totais gerada a partir das colunas depois do Bruto (continua alinhada se a ordem mudar). Para caber sem rolagem lateral: padding lateral 8px, wrap-cells, Tipo só com o ícone (nome no tooltip), Data em 2 linhas (data/hora), largura mínima de 130px em Motorista/Origem/Destino. Sem a coluna Situação, o romaneio cancelado aparece com o selo Cancelado no lugar da etapa. Medido no mock (template real + Quasar + dados reais): sem rolagem em 1200px e em 1366px com o drawer aberto; em 1280px com drawer sobram 64px de rolagem.

06/10/2026 (2º ajuste, pedido do usuário): Motorista e Destino numa linha com reticências (max-width 110px, texto inteiro no tooltip); Data só DD/MM/AAAA; Tipo só com o nome, sem ícone. A largura mínima de 130px ficou só na Origem, que continua quebrando linha. Medido no mock: sem rolagem lateral em 1200px; com o drawer aberto em 1366px sobram 115px de rolagem (o nome do tipo e o ano com 4 dígitos ocupam o que o ícone e a data em 2 linhas liberavam).

06/10/2026 (3º ajuste, pedido do usuário): a linha inteira abre o romaneio por link de verdade (router-link em cada célula, com ::after cobrindo a célula; o td do q-table já é position: relative), então Ctrl+clique, botão do meio e "Abrir em nova guia" funcionam. Só o link da 1ª célula entra no Tab. Saiu o botão do olho; a coluna de ações ficou só com a info de criação (fora do link). Origem também com reticências (max-width 105px nas três, texto inteiro no tooltip, que fica acima da camada do link). Linha de totais sem ícone. Medido no mock: sem rolagem lateral em 1200px; elementFromPoint no canto da célula Tipo = <a href="#/cargas/…">, no meio do motorista = div dentro do <a>, nas ações = ícone fora do <a>.
<!-- SECTION:NOTES:END -->
