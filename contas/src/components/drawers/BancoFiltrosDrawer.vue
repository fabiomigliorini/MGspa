<script setup>
import { watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { useBancoStore } from 'src/stores/bancoStore'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'

const store = useBancoStore()

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
        v-model.number="store.filters.codbanco"
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
        v-model="store.filters.banco"
        outlined
        clearable
        :bottom-slots="false"
        label="Banco"
        class="q-mb-sm"
      >
        <template #prepend><q-icon name="tag" /></template>
      </q-input>

      <q-input
        v-model="store.filters.sigla"
        outlined
        clearable
        :bottom-slots="false"
        label="Sigla"
        class="q-mb-sm"
      >
        <template #prepend><q-icon name="vpn_key" /></template>
      </q-input>

      <q-input
        v-model.number="store.filters.numerobanco"
        outlined
        clearable
        :bottom-slots="false"
        type="number"
        label="Número Banco"
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
