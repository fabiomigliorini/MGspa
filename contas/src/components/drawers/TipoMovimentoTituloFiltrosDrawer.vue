<script setup>
import { watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { useTipoMovimentoTituloStore } from 'src/stores/tipoMovimentoTituloStore'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'

const store = useTipoMovimentoTituloStore()

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

const flags = [
  { key: 'implantacao', label: 'Implantação' },
  { key: 'ajuste', label: 'Ajuste' },
  { key: 'armotizacao', label: 'Amortização' },
  { key: 'juros', label: 'Juros' },
  { key: 'desconto', label: 'Desconto' },
  { key: 'pagamento', label: 'Pagamento' },
  { key: 'estorno', label: 'Estorno' },
]
</script>

<template>
  <FilterDrawerShell :active-count="store.activeFiltersCount" @clear="clear">
    <FilterGroup title="Identificação" first>
      <q-input
        v-model.number="store.filters.codtipomovimentotitulo"
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
        v-model="store.filters.tipomovimentotitulo"
        outlined
        clearable
        :bottom-slots="false"
        label="Descrição"
      >
        <template #prepend><q-icon name="description" /></template>
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

    <FilterGroup title="Flags">
      <q-select
        v-for="flag in flags"
        :key="flag.key"
        v-model="store.filters[flag.key]"
        :options="boolOptions"
        emit-value
        map-options
        outlined
        :bottom-slots="false"
        :label="flag.label"
        class="q-mb-sm"
      />
    </FilterGroup>
  </FilterDrawerShell>
</template>
