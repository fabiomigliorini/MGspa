<script setup>
import { watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { useChequeRepasseStore } from 'src/stores/chequeRepasseStore'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'
import MgSelectPortador from '@components/MgSelectPortador.vue'
import MgInputData from '@components/MgInputData.vue'

const store = useChequeRepasseStore()

const debouncedFetch = useDebounceFn(() => store.fetchItems(true), 800)
watch(() => store.filters, debouncedFetch, { deep: true })

const clear = () => {
  store.clearFilters()
  store.fetchItems(true)
}

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
        v-model.number="store.filters.codchequerepasse"
        outlined
        clearable
        :bottom-slots="false"
        type="number"
        label="Código"
        class="q-mb-sm"
      >
        <template #prepend><q-icon name="numbers" /></template>
      </q-input>

      <MgSelectPortador
        v-model="store.filters.codportador"
        outlined
        clearable
        :bottom-slots="false"
        label="Portador"
      />
    </FilterGroup>

    <FilterGroup title="Data do Repasse">
      <MgInputData v-model="store.filters.data_de" label="Data de" class="q-mb-sm" />
      <MgInputData v-model="store.filters.data_ate" label="Data até" />
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
