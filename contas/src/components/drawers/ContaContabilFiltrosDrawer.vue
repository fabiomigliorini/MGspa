<script setup>
import { watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { useContaContabilStore } from 'src/stores/contaContabilStore'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'

const store = useContaContabilStore()

const debouncedFetch = useDebounceFn(() => store.fetchItems(true), 800)
watch(() => store.filters, debouncedFetch, { deep: true })

const clear = () => {
  store.clearFilters()
  store.fetchItems(true)
}

const statusOptions = [
  { label: 'Ativas', value: false },
  { label: 'Inativas', value: true },
  { label: 'Todas', value: null },
]
</script>

<template>
  <FilterDrawerShell :active-count="store.activeFiltersCount" @clear="clear">
    <FilterGroup title="Identificação" first>
      <q-input
        v-model.number="store.filters.codcontacontabil"
        outlined
        clearable
        :bottom-slots="false"
        type="number"
        label="Código"
        class="q-mb-sm"
      >
        <template #prepend><q-icon name="numbers" /></template>
      </q-input>

      <q-input
        v-model="store.filters.contacontabil"
        outlined
        clearable
        :bottom-slots="false"
        label="Descrição"
        class="q-mb-sm"
      >
        <template #prepend><q-icon name="description" /></template>
      </q-input>

      <q-input
        v-model="store.filters.numero"
        outlined
        clearable
        :bottom-slots="false"
        label="Número"
      >
        <template #prepend><q-icon name="pin" /></template>
      </q-input>
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
