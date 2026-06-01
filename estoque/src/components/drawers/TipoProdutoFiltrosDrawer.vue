<script setup>
import { watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { useTipoProdutoStore } from 'src/stores/tipoProdutoStore'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'

const store = useTipoProdutoStore()

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
        v-model.number="store.filters.codtipoproduto"
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
        v-model="store.filters.tipoproduto"
        outlined
        clearable
        :bottom-slots="false"
        label="Tipo de Produto"
      >
        <template #prepend><q-icon name="category" /></template>
      </q-input>
    </FilterGroup>
  </FilterDrawerShell>
</template>
