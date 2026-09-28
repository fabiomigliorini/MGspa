<script setup>
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'
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
      <MgInputValor
        v-model="store.filters.codchequemotivodevolucao"
        :decimals="0"
        :grouping="false"
        clearable
        :bottom-slots="false"
        label="Código"
        class="q-mb-sm"
      >
        <template #prepend><q-icon name="numbers" /></template>
      </MgInputValor>

      <MgInputValor
        v-model="store.filters.numero"
        :decimals="0"
        :grouping="false"
        clearable
        :bottom-slots="false"
        label="Número"
        class="q-mb-sm"
      >
        <template #prepend><q-icon name="pin" /></template>
      </MgInputValor>

      <MgInput
        v-model="store.filters.chequemotivodevolucao"
        outlined
        clearable
        :bottom-slots="false"
        label="Descrição"
      >
        <template #prepend><q-icon name="description" /></template>
      </MgInput>
    </FilterGroup>
  </FilterDrawerShell>
</template>
