<script setup>
import { watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { usePortadorStore } from 'src/stores/portadorStore'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'
import SelectBanco from 'src/components/select/SelectBanco.vue'
import SelectFilial from 'src/components/select/SelectFilial.vue'

const store = usePortadorStore()

const debouncedFetch = useDebounceFn(() => store.fetchItems(true), 800)
watch(() => store.filters, debouncedFetch, { deep: true })

const clear = () => {
  store.clearFilters()
  store.fetchItems(true)
}

const boolOptions = [
  { label: 'Sim', value: true },
  { label: 'Não', value: false },
  { label: 'Todos', value: null },
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
        v-model.number="store.filters.codportador"
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
        v-model="store.filters.portador"
        outlined
        clearable
        :bottom-slots="false"
        label="Portador"
      >
        <template #prepend><q-icon name="description" /></template>
      </q-input>
    </FilterGroup>

    <FilterGroup title="Vínculos">
      <SelectBanco
        v-model="store.filters.codbanco"
        outlined
        clearable
        :bottom-slots="false"
        label="Banco"
        class="q-mb-sm"
      >
        <template #prepend><q-icon name="account_balance" /></template>
      </SelectBanco>

      <SelectFilial
        v-model="store.filters.codfilial"
        outlined
        clearable
        :bottom-slots="false"
        label="Filial"
      >
        <template #prepend><q-icon name="store" /></template>
      </SelectFilial>
    </FilterGroup>

    <FilterGroup title="Boleto e Status">
      <q-select
        v-model="store.filters.emiteboleto"
        :options="boolOptions"
        emit-value
        map-options
        outlined
        :bottom-slots="false"
        label="Emite Boleto"
        class="q-mb-sm"
      />
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
