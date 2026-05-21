<script setup>
import { watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { useTituloAgrupamentoStore } from 'src/stores/tituloAgrupamentoStore'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'
import SelectPessoa from 'src/components/select/SelectPessoa.vue'
import SelectGrupoEconomico from 'src/components/select/SelectGrupoEconomico.vue'
import SelectGrupoCliente from 'src/components/select/SelectGrupoCliente.vue'
import MgInputData from '@components/MgInputData.vue'

const store = useTituloAgrupamentoStore()

const debouncedFetch = useDebounceFn(() => store.fetchItems(true), 800)
watch(() => store.filters, debouncedFetch, { deep: true })

function clear() {
  store.clearFilters()
  store.fetchItems(true)
}

const estornadoOptions = [
  { label: 'Não Estornados', value: '0' },
  { label: 'Estornados', value: '1' },
  { label: 'Todos', value: '9' },
]
</script>

<template>
  <FilterDrawerShell :active-count="store.activeFiltersCount" @clear="clear">
    <FilterGroup title="Identificação" first>
      <q-select
        v-model="store.filters.estornado"
        :options="estornadoOptions"
        emit-value
        map-options
        outlined
        :bottom-slots="false"
        label="Situação"
        class="q-mb-md"
      />
      <q-input
        v-model.number="store.filters.codtituloagrupamento"
        outlined
        :bottom-slots="false"
        type="number"
        label="Código"
      >
        <template #prepend><q-icon name="numbers" /></template>
      </q-input>
    </FilterGroup>

    <FilterGroup title="Pessoa">
      <SelectGrupoEconomico
        v-model="store.filters.codgrupoeconomico"
        outlined
        clearable
        :bottom-slots="false"
        label="Grupo Econômico"
        class="q-mb-md"
      />
      <SelectPessoa
        v-model="store.filters.codpessoa"
        outlined
        clearable
        :bottom-slots="false"
        label="Pessoa"
        class="q-mb-md"
      />
      <SelectGrupoCliente
        v-model="store.filters.codgrupocliente"
        outlined
        clearable
        :bottom-slots="false"
        label="Grupo de Cliente"
      />
    </FilterGroup>

    <FilterGroup title="Datas">
      <div class="row q-col-gutter-md q-mb-md">
        <div class="col-6">
          <MgInputData
            v-model="store.filters.emissao_de"
            :bottom-slots="false"
            type="date"
            label="Emissão"
            stack-label
          />
        </div>
        <div class="col-6">
          <MgInputData
            v-model="store.filters.emissao_ate"
            :bottom-slots="false"
            type="date"
            label="Até"
            stack-label
          />
        </div>
      </div>
      <div class="row q-col-gutter-md">
        <div class="col-6">
          <MgInputData
            v-model="store.filters.criacao_de"
            :bottom-slots="false"
            type="date"
            label="Criação"
            stack-label
          />
        </div>
        <div class="col-6">
          <MgInputData
            v-model="store.filters.criacao_ate"
            :bottom-slots="false"
            type="date"
            label="Até"
            stack-label
          />
        </div>
      </div>
    </FilterGroup>
  </FilterDrawerShell>
</template>
