---
id: TASK-232
title: 'Filtros da tela de Pagamentos não mudam a listagem (PDV, Portador, Maquineta)'
status: Done
assignee: []
created_date: '2026-10-17 14:21'
updated_date: '2026-10-17 15:14'
labels:
  - contas
dependencies: []
priority: high
type: bug
ordinal: 243000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Na tela /pagamento do contas, a maioria dos filtros do drawer não muda a listagem. O backend (PagamentoListaService::filtrar) aplica todos certo — medido no dev em jul/2026: PDV 18 → 238, maquineta 87 → 105, portador 4 → 398. As causas estão no front compartilhado (@components):

1. MgSelectPortador e MgSelectMaquineta com a prop inativos carregam a lista no balde portador__inativos / maquineta__inativos do selectCacheStore, mas leem o balde sem sufixo → abrem com 'Nenhum portador' / 'Nenhuma maquineta'. O mesmo erro está em mais 23 MgSelectXxx (só morde com inativos; hoje também a Filial na edição do cartão-benefício no pessoas). Padrão do conserto: chaveCache, como no MgSelectSetor/MgSelectRubrica.
2. pagamentoListaStore.buscar() retornava cedo se já havia busca em voo: o buscar(true) do filtro novo era descartado em silêncio (a anterior voltando, ou a página 2 do infinite-scroll), e a lista ficava com o filtro antigo.
3. Depois de um filtro que coube numa página, o q-infinite-scroll recebia done(true) e parava; o próximo filtro maior só mostrava a página 1.

Pedido junto: filtro de Valor De / Até (tblpagamento.total, nunca negativo).

Teste: hard-reload; /pagamento no período jul/2026 (o dev não tem pagamento em outubro); Portador/Maquineta abrem com opções; trocar Filial e logo PDV; filtrar PDV pequeno, limpar e rolar até o fim (página 2); Valor De/Até; PDF com a mesma faixa; mesmo no negocios /pagamento; pessoas → editar cartão-benefício → Filial com nome.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Portador e Maquineta do filtro abrem com as opções (inclusive inativos)
- [x] #2 Os demais selects com inativos leem a lista certa (Filial do cartão-benefício no pessoas)
- [x] #3 Filtro trocado durante uma busca em andamento vale (resposta velha descartada)
- [x] #4 Depois de um filtro que coube numa página, a listagem volta a paginar
- [x] #5 Dá para filtrar por faixa de valor (de / até)
- [x] #6 Portador e Maquineta do filtro buscam no servidor ao digitar (2+ caracteres, 20 por página), como o campo Usuário, sem despejar a lista toda
- [x] #7 Rótulos do filtro: Meio vira Meio de pagamento e Estado vira Status
- [x] #8 Menu do negocios tem atalho Negócios (ícone de lista) para a listagem de negócios
- [x] #9 Meio de pagamento do filtro só lista depois de digitar 2 caracteres
- [x] #10 Maquineta do filtro só traz as ativas
- [x] #11 Datas do filtro de pagamentos não vêm preenchidas e o Limpar filtros esvazia as datas; o relatório pede o período
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Rodada 2 (aguardando validação): MgSelectPortador/MgSelectMaquineta ganharam a prop remoto (só o filtro de pagamentos usa; os outros usos seguem com a lista local); v1/select/maquineta ganhou busca (apelido, serial, filial, adquirente) e page; v1/select/portador pagina 20 quando vem page; rótulos Meio de pagamento/Status em MgPagamentoFiltros; item Negócios (icon list, /listagem) em negocios/src/layouts/MainLayout.vue.
<!-- SECTION:NOTES:END -->
