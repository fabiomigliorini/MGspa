<script setup>
import { watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { useDivergenciaStore, CASOS } from 'src/stores/divergenciaStore'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'
import MgSelect from '@components/MgSelect.vue'
import MgSelectFilial from '@components/MgSelectFilial.vue'
import MgInputData from '@components/MgInputData.vue'

const store = useDivergenciaStore()

const debouncedFetch = useDebounceFn(() => store.fetchItems(), 800)
watch(() => store.filters, debouncedFetch, { deep: true })

function clear() {
  store.clearFilters()
}
</script>

<template>
  <FilterDrawerShell :active-count="store.activeFiltersCount" @clear="clear">
    <FilterGroup title="Caso" first>
      <MgSelect
        v-model="store.filters.caso"
        :options="CASOS"
        emit-value
        map-options
        clearable
        :bottom-slots="false"
        label="Todos os casos"
      />
    </FilterGroup>

    <FilterGroup title="Data">
      <div class="row q-col-gutter-md">
        <div class="col-6">
          <MgInputData
            v-model="store.filters.data_de"
            :bottom-slots="false"
            type="date"
            label="De"
            stack-label
          />
        </div>
        <div class="col-6">
          <MgInputData
            v-model="store.filters.data_ate"
            :bottom-slots="false"
            type="date"
            label="Até"
            stack-label
          />
        </div>
      </div>
    </FilterGroup>

    <FilterGroup title="Filial">
      <MgSelectFilial v-model="store.filters.codfilial" clearable :bottom-slots="false" />
    </FilterGroup>
  </FilterDrawerShell>
</template>
