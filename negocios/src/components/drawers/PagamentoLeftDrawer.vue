<script setup>
// Filtros da listagem de pagamentos do PDV (a mesma do contas, travada neste PDV)
import { configurarPagamentoLista } from 'src/utils/pagamentoLista'
import MgPagamentoFiltros from '@components/MgPagamentoFiltros.vue'
import FilterDrawerShell from 'components/FilterDrawerShell.vue'

const store = configurarPagamentoLista()
</script>

<template>
  <FilterDrawerShell :active-count="store.filtrosAtivos" @clear="store.limpar()">
    <MgPagamentoFiltros />
  </FilterDrawerShell>
</template>
