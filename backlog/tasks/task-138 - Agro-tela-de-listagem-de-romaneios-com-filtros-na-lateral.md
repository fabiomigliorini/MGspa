---
id: TASK-138
title: 'Agro: tela de listagem de romaneios com filtros na lateral'
status: Done
assignee:
  - '@eduardo'
created_date: '2026-09-22 12:23'
updated_date: '2026-10-06 18:34'
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
- [x] #1 Listagem no molde de Vales Emitidos: card com cabeçalho de colunas, filtros no padrão do Vale e botão Imprimir lista no topo
- [x] #2 Totais separados por recebido, expedido e transferido, sem as canceladas
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

06/10/2026 (4º ajuste, pedido do usuário): rodapé com total de Sacas sempre. CargaService::totais passou a devolver `sacas` (topo e por sentido), calculada carga a carga no SQL: liquido / pesosaca da cultura da safra daquela carga (subquery correlacionada; nullif 0 → 60, igual ao front e ao CargaRelatorioService::sacas), depois somada. Saiu a regra antiga de só mostrar sacas com uma cultura no recorte (culturaUnica removida da store). Conferido em dev: total do servidor = soma linha a linha em 3 recortes (todos, só expedição, desde 01/09). Etapa Finalizado também colorida (verde, cor do ETAPA_META).

06/10/2026 (5º ajuste, pedido do usuário): "limpar filtros" limpa tudo, inclusive o período (o 1º dia do mês ficou só como ponto de partida da primeira abertura). Saíram do drawer o grupo "Relatório" (Agrupar por) e o seletor Ambos/Origem/Destino (`papel`); `agrupar` e `papel` saíram da store e do persist (filtro salvo antigo é limpo pelo normalizarFiltros). Imprimir lista manda só os filtros; o backend usa agrupamento "nenhum".

06/10/2026 (6º ajuste, pedido do usuário, na ficha /cargas/:codcarga — CargaDetailPage): a info de criação (MgInfoCriacao) foi para dentro do card principal, abaixo da barra de etapas; o voltar saiu do FAB e foi para o canto superior direito do card principal (absolute-top-right, escolha do usuário entre esquerda e direita), ficando no topo também no celular. FAB só com Imprimir romaneio. Conferido com screenshot do template real + romaneio #53043 em 1200px e 360px.

06/10/2026 (correção do 6º ajuste, reprovado pelo usuário): o voltar em absolute-top-right dentro do card não funcionava — a q-card-section (position: relative, depois no DOM) ficava por cima e roubava o clique. Agora fica FORA do card, numa linha de botões no topo à direita (row justify-end q-mb-sm, q-btn flat primary arrow_back "Voltar"), mesmo padrão do "Imprimir lista" da listagem e das telas do negocios, acima até do aviso de cancelado. Conferido no mock: elementFromPoint no centro do botão cai dentro do botão; desktop 1200px e celular 360px.

06/10/2026: na ficha, a info de criação (ⓘ) foi para a extrema direita da linha abaixo da barra de etapas (text-right). O voltar do topo ficou como o usuário ajustou no fonte: só a seta, cinza e redonda.

06/10/2026: Origem e Destino mais estreitos (classe celula-ponto, max-width 80px; o motorista segue em 105px). Ajustes do usuário no fonte mantidos: padding lateral 15px, reticências na Safra, sem tooltip no Destino, botão "Gerar relatório". Medido no mock: com 15px a tabela tem 1311px para 1166px (rolagem lateral de ~145px); com 8px cabe exato.

06/10/2026: rolagem lateral voltou com o padding de 15px (1311px para 1166px). Padding lateral 9px: o maior que cabe (medido 12px=1233, 11=1207, 10=1181, 9=1166). Sem rolagem em 1200px; com o drawer aberto em 1366px sobram ~120px de rolagem.
<!-- SECTION:NOTES:END -->
