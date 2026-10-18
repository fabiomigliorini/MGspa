---
id: TASK-238
title: Datas aparecem em inglês ('22 minutes ago') no Negócios e no Pessoas
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-18 13:47'
updated_date: '2026-10-18 13:53'
labels:
  - negocios
  - pessoas
dependencies: []
priority: low
type: bug
ordinal: 249000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Tempo relativo sai em inglês: o tooltip do botão Sincronizar do PDV mostra "22 minutes ago" em vez de "há 22 minutos". No Pessoas, o mesmo acontece no último acesso do usuário (perfil e detalhe do usuário).

Origem: relatado pelo Fábio em 18/10/2026, olhando a TASK-197.

Causa: esses arquivos importam 'moment' e chamam moment.locale('pt-br'), mas ninguém importa o arquivo do idioma; sem ele o moment não conhece o pt-br e fica no inglês. As outras telas do Negócios com tempo relativo (drawer de negócios e Mercos, títulos, notas, devoluções, orçamentos, Woo, PagarMe) importam 'moment/min/moment-with-locales', que já traz todos os idiomas, e já saíam em português. Nenhum outro app usa moment.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 No Negócios, o tooltip do Sincronizar aparece em português (ex.: 'há 22 minutos')
- [x] #2 No Pessoas, o último acesso do usuário (perfil e detalhe) aparece em português
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Fix: boot novo src/boot/moment.js no negocios e no pessoas (registrado em quasar.config.js > boot), com import 'moment/dist/locale/pt-br' e moment.locale('pt-br') uma vez para o app inteiro.

Pegadinha: o primeiro fix, import 'moment/locale/pt-br', não resolvia. No Vite o 'moment' resolve pelo jsnext:main para moment/dist/moment.js (ESM), e o moment/locale/pt-br (UMD) faz require('../moment'), que é moment/moment.js (CommonJS): o otimizador empacotava uma segunda cópia do moment dentro do arquivo do idioma e o pt-br ficava registrado nela. O moment/dist/locale/pt-br importa '../moment' = moment/dist/moment.js, a mesma instância da app.

Os moment.locale('pt-br') espalhados nos componentes ficaram; com o boot são redundantes e inofensivos.

Teste (18/10/2026): Chrome headless no dev do negocios (9900) com localStorage 'sincronizacao' simulando a última completa há 22 min: tooltip 'Ultima Sincronização há 22 minutos' (antes do fix: '22 minutes ago'). Dev do pessoas (8081, subido só para o teste e derrubado): o app carrega boot/moment e a instância do moment das telas está em pt-br ('há 22 minutos'). Build mínimo com o Vite 8 de cada app (mesma resolução do build de produção): 'pt-br | há 22 minutos | domingo, 18 de outubro' nos dois.
<!-- SECTION:NOTES:END -->
