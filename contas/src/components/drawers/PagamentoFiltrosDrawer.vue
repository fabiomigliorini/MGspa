<script setup>
import MgPagamentoFiltros from '@components/MgPagamentoFiltros.vue'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import { configurarPagamentoLista } from 'src/utils/pagamentoLista'

const store = configurarPagamentoLista()
</script>

<template>
  <FilterDrawerShell :active-count="store.filtrosAtivos" @clear="store.limpar()">
    <MgPagamentoFiltros />
  </FilterDrawerShell>
</template>
