<script setup>
import { watch, onMounted } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { usePortadorStore } from 'src/stores/portadorStore'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'

const store = usePortadorStore()

const debouncedFetch = useDebounceFn(() => store.fetchItems(true), 800)
watch(() => store.filters, debouncedFetch, { deep: true })

onMounted(() => {
  store.fetchBancos()
  store.fetchFiliais()
})

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
      <q-select
        v-model="store.filters.codbanco"
        :options="store.bancos"
        option-value="codbanco"
        option-label="banco"
        emit-value
        map-options
        outlined
        clearable
        :bottom-slots="false"
        label="Banco"
        class="q-mb-sm"
      >
        <template #prepend><q-icon name="account_balance" /></template>
      </q-select>

      <q-select
        v-model="store.filters.codfilial"
        :options="store.filiais"
        option-value="value"
        option-label="label"
        emit-value
        map-options
        outlined
        clearable
        :bottom-slots="false"
        label="Filial"
      >
        <template #prepend><q-icon name="store" /></template>
      </q-select>
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
