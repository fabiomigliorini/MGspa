<script setup>
import { watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { useOcorrenciaStore } from 'src/stores/ocorrenciaStore'
import { TIPOS } from '@components/ocorrencia.js'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'
import MgSelectFilial from '@components/MgSelectFilial.vue'
import MgSelectPdv from '@components/MgSelectPdv.vue'
import MgSelectUsuario from '@components/MgSelectUsuario.vue'
import MgInputData from '@components/MgInputData.vue'
import MgSelect from '@components/MgSelect.vue'

const store = useOcorrenciaStore()

const TIPO_OPTIONS = Object.entries(TIPOS).map(([value, label]) => ({
  value: Number(value),
  label,
}))

const SITUACAO_OPTIONS = [
  { value: 'pendente', label: 'A conferir' },
  { value: 'conferida', label: 'Conferidas' },
  { value: 'todas', label: 'Todas' },
]

const ORDEM_OPTIONS = [
  { value: 'recentes', label: 'Mais recentes' },
  { value: 'valor', label: 'Maior valor' },
]

const debouncedFetch = useDebounceFn(() => store.fetchItems(true), 800)
watch(() => store.filters, debouncedFetch, { deep: true })

const clear = () => {
  store.clearFilters()
  store.fetchItems(true)
}
</script>

<template>
  <FilterDrawerShell :active-count="store.activeFiltersCount" @clear="clear">
    <FilterGroup title="Situação" first>
      <q-option-group v-model="store.filters.situacao" :options="SITUACAO_OPTIONS" type="radio" />
    </FilterGroup>

    <FilterGroup title="Ordem">
      <q-option-group v-model="store.filters.ordem" :options="ORDEM_OPTIONS" type="radio" />
    </FilterGroup>

    <FilterGroup title="Onde e quem">
      <MgSelectFilial
        v-model="store.filters.codfilial"
        clearable
        :bottom-slots="false"
        label="Filial"
        class="q-mb-sm"
      />
      <MgSelectPdv
        v-model="store.filters.codpdv"
        :codfilial="store.filters.codfilial"
        clearable
        :bottom-slots="false"
        label="PDV"
        class="q-mb-sm"
      />
      <MgSelectUsuario
        v-model="store.filters.codusuario"
        clearable
        :bottom-slots="false"
        label="Usuário"
      />
    </FilterGroup>

    <FilterGroup title="Tipo e período">
      <MgSelect
        v-model="store.filters.tipo"
        :options="TIPO_OPTIONS"
        emit-value
        map-options
        outlined
        clearable
        :bottom-slots="false"
        label="Tipo"
        class="q-mb-sm"
      />
      <MgInputData
        v-model="store.filters.data_de"
        :bottom-slots="false"
        label="De"
        class="q-mb-sm"
      />
      <MgInputData v-model="store.filters.data_ate" :bottom-slots="false" label="Até" />
    </FilterGroup>
  </FilterDrawerShell>
</template>
