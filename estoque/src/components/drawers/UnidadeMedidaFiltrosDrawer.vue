<script setup>
import { watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { useUnidadeMedidaStore } from 'src/stores/unidadeMedidaStore'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'

const store = useUnidadeMedidaStore()

const debouncedFetch = useDebounceFn(() => store.fetchItems(true), 800)
watch(() => store.filters, debouncedFetch, { deep: true })

const clear = () => {
  store.clearFilters()
  store.fetchItems(true)
}

const statusOptions = [
  { label: 'Ativos', value: 1 },
  { label: 'Inativos', value: 2 },
  { label: 'Todos', value: null },
]
</script>

<template>
  <FilterDrawerShell :active-count="store.activeFiltersCount" @clear="clear">
    <FilterGroup title="Identificação" first>
      <MgInputValor
        v-model="store.filters.codunidademedida"
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
        v-model="store.filters.unidademedida"
        outlined
        clearable
        :bottom-slots="false"
        label="Descrição"
        class="q-mb-sm"
      >
        <template #prepend><q-icon name="straighten" /></template>
      </MgInput>

      <MgInput v-model="store.filters.sigla" outlined clearable :bottom-slots="false" label="Sigla">
        <template #prepend><q-icon name="tag" /></template>
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
