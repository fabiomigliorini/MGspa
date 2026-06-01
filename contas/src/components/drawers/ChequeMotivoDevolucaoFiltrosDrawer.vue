<script setup>
import { watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { useChequeMotivoDevolucaoStore } from 'src/stores/chequeMotivoDevolucaoStore'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'

const store = useChequeMotivoDevolucaoStore()

const debouncedFetch = useDebounceFn(() => store.fetchItems(true), 800)
watch(() => store.filters, debouncedFetch, { deep: true })

const clear = () => {
  store.clearFilters()
  store.fetchItems(true)
}
</script>

<template>
  <FilterDrawerShell :active-count="store.activeFiltersCount" @clear="clear">
    <FilterGroup title="Identificação" first>
      <q-input
        v-model.number="store.filters.codchequemotivodevolucao"
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
        v-model.number="store.filters.numero"
        outlined
        clearable
        :bottom-slots="false"
        type="number"
        label="Número"
        class="q-mb-sm"
      >
        <template #prepend><q-icon name="pin" /></template>
      </q-input>

      <q-input
        v-model="store.filters.chequemotivodevolucao"
        outlined
        clearable
        :bottom-slots="false"
        label="Descrição"
      >
        <template #prepend><q-icon name="description" /></template>
      </q-input>
    </FilterGroup>
  </FilterDrawerShell>
</template>
