---
id: TASK-242
title: >-
  Negócios carrega a biblioteca de datas duas vezes, uma delas com todos os
  idiomas
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-18 14:02'
updated_date: '2026-10-18 14:20'
labels:
  - negocios
  - pessoas
dependencies: []
priority: low
type: chore
ordinal: 251000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
O Negócios carrega a biblioteca de datas (moment) duas vezes: a normal e uma cópia inteira com todos os idiomas, ~316 KB a mais no app (moment.min.js 59 KB x moment-with-locales.min.js 375 KB). E o idioma é ligado em 24 lugares diferentes, quando um só basta.

Origem: limpeza que sobrou da TASK-238 (18/10/2026). Desde ela, o boot src/boot/moment.js do negocios e do pessoas já liga o pt-br para o app inteiro (import 'moment/dist/locale/pt-br' + moment.locale('pt-br')).

Onde está:
- 14 arquivos do negocios importam 'moment/min/moment-with-locales' em vez de 'moment': components/drawers/OfflineLeftDrawerTabMercos.vue, OfflineLeftDrawerTabNegocios.vue; components/offline/DetalheNegocio.vue, InputBarras.vue, ListagemAnexos.vue, ListagemNotas.vue, ListagemProdutos.vue, ListagemTitulos.vue; pages/ConfissaoFaltandoPage.vue, DevolucaoPage.vue, OrcamentoPage.vue, PagarMePage.vue, WooPage.vue, WooPainelPage.vue.
- 24 chamadas moment.locale(...) espalhadas nos componentes do negocios (16) e do pessoas (8), redundantes com o boot; a do WooInfoModal.vue usa 'pt_br'.

Pegadinha: não importar 'moment/locale/pt-br' (puxa a cópia CommonJS do moment e o idioma não pega); o idioma fica só no boot, via moment/dist/locale/pt-br (ver notas da TASK-238).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Os 14 arquivos do Negócios importam 'moment' em vez de 'moment/min/moment-with-locales', e as datas dessas telas continuam em português
- [x] #2 O idioma é ligado só no boot (src/boot/moment.js): nenhum moment.locale(...) espalhado nos componentes do Negócios e do Pessoas
- [x] #3 O Pessoas também deixa de carregar a segunda cópia do moment com todos os idiomas: os 8 import 'moment/min/locales' saem (registravam o pt-br na cópia CommonJS, que ninguém usa)
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Feito (18/10/2026): os 14 arquivos do Negócios trocaram 'moment/min/moment-with-locales' por 'moment'; em 4 deles (DevolucaoPage, OrcamentoPage, DetalheNegocio, ListagemAnexos) o moment nem era usado — só importado para o moment.locale — e o import saiu junto, assim como no WooInfoModal (o do 'pt_br'). Os 24 moment.locale(...) dos componentes saíram (16 no negocios, 8 no pessoas); o idioma fica só no src/boot/moment.js, registrado em quasar.config.js > boot nos dois apps. eslint e prettier limpos nos 24 arquivos.

Teste: no Negócios, conferir que o tempo relativo segue em português (ex.: 'há 3 dias', não '3 days ago') no drawer de negócios e no do Mercos, títulos, notas, produtos/devoluções do negócio, orçamentos da busca de barras, Woo (lista e painel) e PagarMe; e o tooltip do Sincronizar. No Pessoas, o último acesso no perfil e no detalhe do usuário.

Pessoas (AC #3): os 8 import 'moment/min/locales' (MGLayout, perfil, Aniversarios/Index, CardDetalhesUsuario, CardColaborador, CardColaboradorCargo, CardFerias, FormUsuario) puxavam a cópia CommonJS do moment com todos os idiomas, a mesma pegadinha da TASK-238; o pt-br ficava registrado nela, que ninguém usa (o chart.js só tem moment em devDependencies). Saíram.

Teste (18/10/2026): quasar build -m pwa (o do deploy.sh) do negocios e do pessoas num worktree temporário, antes (HEAD b101f670e) e depois, contando no dist/pwa/assets:
- negocios: 2 cópias do moment (uma no chunk moment-with-locales, 285 KB) e 17 .locale(pt-br) -> 1 cópia, sem pacote de todos os idiomas, 1 .locale (o do boot). JS total 1899 KB -> 1613 KB (-286 KB).
- pessoas: 2 cópias do moment (a com todos os idiomas dentro do chunk do MGLayout) -> 1 cópia. JS total 1752 KB -> 1467 KB (-285 KB).
Runtime no build de produção (Chrome headless, dist servido localmente, instância do moment tirada do próprio chunk depois de o app subir): 'pt-br | há 22 minutos | há 3 dias | domingo, 18 de outubro de 2026' no negocios e no pessoas. Com uma cópia só do moment no bundle, toda tela importa a mesma instância em que o boot liga o pt-br.
<!-- SECTION:NOTES:END -->
