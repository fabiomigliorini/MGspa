<script setup>
// Filtros do painel /portador (doc-4): a filial (padrão a do usuário; Financeiro e Admin, todas)
// e os inativos.
import { computed, watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { usePortadorStore } from 'src/stores/portadorStore'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'
import MgSelectFilial from '@components/MgSelectFilial.vue'

const store = usePortadorStore()

const debounced = useDebounceFn(() => store.buscarPainel(), 500)
watch(() => store.filtros, debounced, { deep: true })

const ativos = computed(() => (store.filtros.codfilial ? 1 : 0) + (store.filtros.inativos ? 1 : 0))
</script>

<template>
  <FilterDrawerShell :active-count="ativos" @clear="store.limpar()">
    <FilterGroup title="Filial" first>
      <MgSelectFilial
        v-model="store.filtros.codfilial"
        outlined
        clearable
        :bottom-slots="false"
        label="Filial (vazio = todas)"
      />
    </FilterGroup>
    <FilterGroup title="Situação">
      <q-toggle v-model="store.filtros.inativos" label="Mostrar inativos" />
    </FilterGroup>
  </FilterDrawerShell>
</template>
