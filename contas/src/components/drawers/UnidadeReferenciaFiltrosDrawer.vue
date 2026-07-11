<script setup>
import { watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { useUnidadeReferenciaStore } from 'src/stores/unidadeReferenciaStore'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'

const store = useUnidadeReferenciaStore()

const debouncedFetch = useDebounceFn(() => store.fetchItems(true), 800)
watch(() => store.filters, debouncedFetch, { deep: true })

const clear = () => {
  store.clearFilters()
  store.fetchItems(true)
}

const enteOptions = [
  { label: 'Todos', value: null },
  { label: 'Federal', value: 'FEDERAL' },
  { label: 'Estadual', value: 'ESTADUAL' },
  { label: 'Municipal', value: 'MUNICIPAL' },
]

const statusOptions = [
  { label: 'Ativos', value: false },
  { label: 'Inativos', value: true },
  { label: 'Todos', value: null },
]
</script>

<template>
  <FilterDrawerShell :active-count="store.activeFiltersCount" @clear="clear">
    <FilterGroup title="Identificação" first>
      <q-input
        v-model="store.filters.codigo"
        outlined
        clearable
        :bottom-slots="false"
        label="Código"
        class="q-mb-sm"
      >
        <template #prepend><q-icon name="tag" /></template>
      </q-input>

      <q-select
        v-model="store.filters.ente"
        :options="enteOptions"
        emit-value
        map-options
        outlined
        :bottom-slots="false"
        label="Ente"
      >
        <template #prepend><q-icon name="account_balance" /></template>
      </q-select>
    </FilterGroup>

    <FilterGroup title="Status">
      <q-select
        v-model="store.filters.inativo"
        :options="statusOptions"
        emit-value
        map-options
        outlined
        :bottom-slots="false"
        label="Situação"
      >
        <template #prepend><q-icon name="toggle_on" /></template>
      </q-select>
    </FilterGroup>
  </FilterDrawerShell>
</template>
