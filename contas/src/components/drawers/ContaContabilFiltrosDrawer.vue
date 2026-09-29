<script setup>
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'
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
      <MgInputValor
        v-model="store.filters.codcontacontabil"
        :decimals="0"
        :grouping="false"
        clearable
        :bottom-slots="false"
        label="Código"
        class="q-mb-sm"
      >
        <template #prepend><q-icon name="numbers" /></template>
      </MgInputValor>

      <MgInput
        v-model="store.filters.contacontabil"
        outlined
        clearable
        :bottom-slots="false"
        label="Descrição"
        class="q-mb-sm"
      >
        <template #prepend><q-icon name="description" /></template>
      </MgInput>

      <MgInput
        v-model="store.filters.numero"
        outlined
        clearable
        :bottom-slots="false"
        label="Número"
      >
        <template #prepend><q-icon name="pin" /></template>
      </MgInput>
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
