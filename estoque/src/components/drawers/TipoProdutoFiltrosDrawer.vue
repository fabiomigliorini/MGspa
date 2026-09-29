<script setup>
import { watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { useTipoProdutoStore } from 'src/stores/tipoProdutoStore'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'

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
      <MgInputValor
        v-model="store.filters.codtipoproduto"
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
        v-model="store.filters.tipoproduto"
        outlined
        clearable
        :bottom-slots="false"
        label="Tipo de Produto"
      >
        <template #prepend><q-icon name="category" /></template>
      </MgInput>
    </FilterGroup>
  </FilterDrawerShell>
</template>
