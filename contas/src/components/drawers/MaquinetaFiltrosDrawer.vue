<script setup>
import MgInput from '@components/MgInput.vue'
import { onMounted, watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { useMaquinetaStore } from 'src/stores/maquinetaStore'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'
import MgSelectFilial from '@components/MgSelectFilial.vue'
import { MAQUINETA_INTEGRACAO_OPTIONS } from 'src/constants/maquinetaIntegracao'

const store = useMaquinetaStore()

const debouncedFetch = useDebounceFn(() => store.fetchItems(), 800)
watch(() => store.filters, debouncedFetch, { deep: true })

const clear = () => {
  store.clearFilters()
  store.fetchItems()
}

const statusOptions = [
  { label: 'Ativas', value: false },
  { label: 'Inativas', value: true },
  { label: 'Todas', value: null },
]

onMounted(() => store.carregarAdquirentes())
</script>

<template>
  <FilterDrawerShell :active-count="store.activeFiltersCount" @clear="clear">
    <FilterGroup title="Identificação" first>
      <MgInput
        v-model="store.filters.texto"
        clearable
        :bottom-slots="false"
        label="Apelido ou serial"
      >
        <template #prepend><q-icon name="point_of_sale" /></template>
      </MgInput>
    </FilterGroup>

    <FilterGroup title="Filial e Operadora">
      <MgSelectFilial
        v-model="store.filters.codfilial"
        outlined
        clearable
        :bottom-slots="false"
        label="Filial"
        class="q-mb-sm"
      />
      <q-select
        v-model="store.filters.codpessoa"
        :options="store.adquirentes"
        emit-value
        map-options
        outlined
        clearable
        :bottom-slots="false"
        label="Adquirente"
        class="q-mb-sm"
      />
      <q-select
        v-model="store.filters.integracao"
        :options="MAQUINETA_INTEGRACAO_OPTIONS"
        emit-value
        map-options
        outlined
        clearable
        :bottom-slots="false"
        label="Integração"
      />
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
