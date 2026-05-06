<script setup>
import { watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { useLiquidacaoTituloStore } from 'src/stores/liquidacaoTituloStore'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'
import SelectPortador from 'src/components/select/SelectPortador.vue'
import SelectPessoa from 'src/components/select/SelectPessoa.vue'
import SelectGrupoEconomico from 'src/components/select/SelectGrupoEconomico.vue'
import MgInputData from '@components/MgInputData.vue'

const store = useLiquidacaoTituloStore()

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
        v-model.number="store.filters.codliquidacaotitulo"
        outlined
        :bottom-slots="false"
        type="number"
        label="Código"
      >
        <template #prepend><q-icon name="numbers" /></template>
      </q-input>
    </FilterGroup>

    <FilterGroup title="Pessoa">
      <SelectPessoa
        v-model="store.filters.codpessoa"
        outlined
        clearable
        :bottom-slots="false"
        label="Pessoa"
        class="q-mb-md"
      >
        <template #prepend><q-icon name="person" /></template>
      </SelectPessoa>
      <SelectGrupoEconomico
        v-model="store.filters.codgrupoeconomico"
        outlined
        clearable
        :bottom-slots="false"
        label="Grupo Econômico"
      >
        <template #prepend><q-icon name="groups" /></template>
      </SelectGrupoEconomico>
    </FilterGroup>

    <FilterGroup title="Portador">
      <SelectPortador
        v-model="store.filters.codportador"
        outlined
        clearable
        :bottom-slots="false"
        label="Portador"
      >
        <template #prepend><q-icon name="credit_card" /></template>
      </SelectPortador>
    </FilterGroup>

    <FilterGroup title="Datas">
      <div class="row q-col-gutter-md q-mb-md">
        <div class="col-6">
          <MgInputData
            v-model="store.filters.transacao_de"
            :bottom-slots="false"
            type="date"
            label="Transação"
            stack-label
          />
        </div>
        <div class="col-6">
          <MgInputData
            v-model="store.filters.transacao_ate"
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
