<script setup>
import { watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { useOcorrenciaStore } from 'src/stores/ocorrenciaStore'
import { TIPOS } from '@components/ocorrencia.js'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
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
    <div class="row q-col-gutter-md">
      <div class="col-12">
        <div class="text-caption text-grey-7 q-mb-xs">Situação</div>
        <q-option-group v-model="store.filters.situacao" :options="SITUACAO_OPTIONS" type="radio" />
      </div>
      <div class="col-12">
        <div class="text-caption text-grey-7 q-mb-xs">Ordem</div>
        <q-option-group v-model="store.filters.ordem" :options="ORDEM_OPTIONS" type="radio" />
      </div>
      <div class="col-12">
        <MgSelectFilial
          v-model="store.filters.codfilial"
          clearable
          :bottom-slots="false"
          label="Filial"
        />
      </div>
      <div class="col-12">
        <MgSelectPdv
          v-model="store.filters.codpdv"
          :codfilial="store.filters.codfilial"
          clearable
          :bottom-slots="false"
          label="PDV"
        />
      </div>
      <div class="col-12">
        <MgSelectUsuario
          v-model="store.filters.codusuario"
          clearable
          :bottom-slots="false"
          label="Usuário"
        />
      </div>
      <div class="col-12">
        <MgSelect
          v-model="store.filters.tipo"
          :options="TIPO_OPTIONS"
          emit-value
          map-options
          clearable
          :bottom-slots="false"
          label="Tipo"
        />
      </div>
      <div class="col-6">
        <MgInputData v-model="store.filters.data_de" :bottom-slots="false" label="De" />
      </div>
      <div class="col-6">
        <MgInputData v-model="store.filters.data_ate" :bottom-slots="false" label="Até" />
      </div>
    </div>
  </FilterDrawerShell>
</template>
