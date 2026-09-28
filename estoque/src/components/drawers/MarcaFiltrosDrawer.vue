<script setup>
import { watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { useMarcaStore } from 'src/stores/marcaStore'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'

const store = useMarcaStore()

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

const sortOptions = [
  { label: 'Alfabética', value: 'marca' },
  { label: 'Curva ABC (vendas)', value: 'abcposicao' },
]
</script>

<template>
  <FilterDrawerShell :active-count="store.activeFiltersCount" @clear="clear">
    <FilterGroup title="Identificação" first>
      <MgInputValor
        v-model="store.filters.codmarca"
        :decimals="0"
        :grouping="false"
        clearable
        :bottom-slots="false"
        label="Código"
        class="q-mb-sm"
      >
        <template #prepend><q-icon name="numbers" /></template>
      </MgInputValor>

      <MgInput v-model="store.filters.marca" outlined clearable :bottom-slots="false" label="Marca">
        <template #prepend><q-icon name="sell" /></template>
      </MgInput>
    </FilterGroup>

    <FilterGroup title="Ordenação e Status">
      <q-select
        v-model="store.filters.sort"
        :options="sortOptions"
        emit-value
        map-options
        outlined
        :bottom-slots="false"
        label="Ordenar por"
        class="q-mb-sm"
      >
        <template #prepend><q-icon name="sort" /></template>
      </q-select>

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

    <FilterGroup title="Estoque">
      <q-toggle
        v-model="store.filters.faltando"
        label="Com itens abaixo do mínimo"
        color="red-6"
        class="q-mb-xs"
      />
      <q-toggle
        v-model="store.filters.sobrando"
        label="Com itens acima do máximo"
        color="orange-7"
      />
    </FilterGroup>

    <FilterGroup title="Curva ABC">
      <div class="q-px-sm">
        <q-range
          v-model="store.filters.abccategoria"
          :min="0"
          :max="3"
          :step="1"
          markers
          label-always
          color="primary"
        />
      </div>
    </FilterGroup>
  </FilterDrawerShell>
</template>
