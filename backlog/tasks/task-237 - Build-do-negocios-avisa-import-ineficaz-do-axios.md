---
id: TASK-237
title: Build do negocios avisa import ineficaz do axios
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-18 13:40'
updated_date: '2026-10-18 13:47'
labels:
  - negocios
  - pessoas
  - notas
  - contas
  - estoque
  - agro
dependencies: []
priority: low
type: chore
ordinal: 248000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
O `quasar build` do negocios avisa INEFFECTIVE_DYNAMIC_IMPORT. O Quasar carrega cada boot file com import() para ele virar um chunk próprio, mas 28 arquivos do negocios importam a instância `api` direto de `boot/axios`, alguns deles no arquivo principal (router -> stores). Com import estático no meio, o Rolldown do Vite 8 desiste do chunk e avisa.

O pessoas tem a mesma estrutura (26 arquivos importando o boot) mas não avisa: lá todos os importadores são telas carregadas sob demanda, e o boot ainda ganha chunk próprio. Entra junto pela padronização.

Hoje não muda nada no app, mas boot file não é biblioteca. A TASK-207 registra o negocios caindo por importar o boot antes do Pinia existir.

Padrão (o mesmo que notas, contas, estoque e agro já usam):
- `src/services/api.js`: axios.create, timeout e dedup de double-submit. Sem Vue, Quasar ou store. Exporta `api`.
- `src/boot/axios.js`: importa o `api` de services, instala os interceptors (token e erros) e registra $axios/$api. Não exporta nada.
- Todo import da instância vem de `src/services/api`, como os componentes de @components já fazem.
- O comportamento dos interceptors de cada app não muda.

Junto: trocar os wrappers descontinuados de `quasar/wrappers` (boot, route, store) por defineBoot, defineRouter e defineStore de `#q-app/wrappers`. O próprio Quasar marca os antigos como descontinuados desde o app-vite 2.

Origem: build do negocios em 17/10/2026. A doc do Quasar (app-vite 3.10.3) ainda ensina exportar o axios do boot; é ela que dispara o aviso no Vite 8.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 negocios: axios nasce em services/api.js, o boot só instala os interceptors e os imports da instância apontam para src/services/api
- [x] #2 pessoas: axios nasce em services/api.js, o boot só instala os interceptors e os imports da instância apontam para src/services/api
- [x] #3 contas, estoque e agro: o boot para de exportar axios e api (notas já está no padrão)
- [x] #4 Wrappers de quasar/wrappers trocados por defineBoot/defineRouter/defineStore de #q-app/wrappers nos seis apps
- [x] #5 Os seis apps buildam sem o aviso INEFFECTIVE_DYNAMIC_IMPORT e abrem sem erro de boot, com o token indo no cabeçalho das requisições
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
O que mudou:
- negocios e pessoas: `src/services/api.js` passou a criar a instância (baseURL, timeout 15s, dedup de double-submit; no pessoas também o Content-type JSON). O `boot/axios.js` importa dali, instala os interceptors dentro do defineBoot e registra $axios/$api, sem exportar nada. Os 28 imports do negocios e os 26 do pessoas trocados de `boot/axios` para `src/services/api`. Comportamento dos interceptors igual ao de antes.
- contas, estoque e agro: removido o `export { axios, api }` do fim do boot. Ninguém importava dali.
- Wrappers: 16 arquivos (boots, routers e o store index do pessoas) de `quasar/wrappers` para defineBoot/defineRouter/defineStore de `#q-app/wrappers`.

Achado: o pessoas não dava o aviso nem antes. Lá todos os importadores do boot são telas carregadas sob demanda, então o boot ainda ganhava chunk próprio. Só no negocios o boot ia para o arquivo principal (router -> stores -> boot).

Como foi testado (cópias dos apps no scratchpad, Node 22, `quasar build -m pwa`):
- Negocios no último commit: 1 aviso INEFFECTIVE_DYNAMIC_IMPORT. Depois da mudança: nenhum, e o boot ganhou chunk próprio. Os seis apps buildam sem o aviso.
- Cada build aberto num Chrome headless com token falso e a API simulada: app monta, 1 interceptor de request e 1 de response no $api, requisição de teste sai com `Bearer <token>`, dois POST idênticos simultâneos viram 1, e o 401 faz o mesmo de antes (negocios abre o diálogo de login e limpa o cookie; os outros mostram "Sessão expirada").
- Requisições feitas pelas próprias telas (store/página importando de services/api) saem com o token: negocios /vale-modelo, pessoas aniversários, notas dashboard, contas /pix, agro cadastros.
- Erro `forEach is not a function` no pessoas aparece igual no build do último commit; vem da resposta simulada do teste, não da mudança.
- Prettier e eslint limpos nos arquivos alterados, fora ComandaPage.vue (negocios) e colaborador.js (pessoas), que já estavam fora do prettier antes.
<!-- SECTION:NOTES:END -->
