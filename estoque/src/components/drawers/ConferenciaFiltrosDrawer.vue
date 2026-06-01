<script setup>
import { watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { useConferenciaStore } from 'src/stores/conferenciaStore'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'

const store = useConferenciaStore()

const debouncedFetch = useDebounceFn(() => store.fetchListagem(true), 800)
watch(
  () => [store.filters.inativo, store.filters.dataCorte],
  debouncedFetch,
)

const clear = () => {
  store.clearFilters()
  store.fetchListagem(true)
}

const statusOptions = [
  { label: 'Ativos', value: 0 },
  { label: 'Inativos', value: 1 },
  { label: 'Todos', value: 9 },
]
</script>

<template>
  <FilterDrawerShell :active-count="store.activeFiltersCount" @clear="clear">
    <FilterGroup title="Situação do produto" first>
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

    <FilterGroup title="Data de corte">
      <q-input
        v-model="store.filters.dataCorte"
        outlined
        type="date"
        stack-label
        :bottom-slots="false"
        label="Conferir desde"
        hint="Lista itens não conferidos desde esta data"
      >
        <template #prepend><q-icon name="event" /></template>
      </q-input>
    </FilterGroup>
  </FilterDrawerShell>
</template>
