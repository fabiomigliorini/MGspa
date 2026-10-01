<script setup>
import MgInputValor from '@components/MgInputValor.vue'
import { watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { usePagamentoStore, MEIOS_FILTRO } from 'src/stores/pagamentoStore'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'
import MgSelectPortador from '@components/MgSelectPortador.vue'
import SelectPessoa from '@components/MgSelectPessoa.vue'
import MgSelectGrupoEconomico from '@components/MgSelectGrupoEconomico.vue'
import MgSelectGrupoCliente from '@components/MgSelectGrupoCliente.vue'
import MgSelectUsuario from '@components/MgSelectUsuario.vue'
import MgInputData from '@components/MgInputData.vue'

const store = usePagamentoStore()

const debouncedFetch = useDebounceFn(() => store.fetchItems(true), 800)
watch(() => store.filters, debouncedFetch, { deep: true })

function clear() {
  store.clearFilters()
  store.fetchItems(true)
}

const canceladoOptions = [
  { label: 'Não Estornados', value: '0' },
  { label: 'Estornados', value: '1' },
  { label: 'Todos', value: '9' },
]

const sentidoOptions = [
  { label: 'Recebimentos', value: 'R' },
  { label: 'Pagamentos', value: 'P' },
  { label: 'Encontro de contas', value: 'C' },
]
</script>

<template>
  <FilterDrawerShell :active-count="store.activeFiltersCount" @clear="clear">
    <FilterGroup title="Identificação" first>
      <q-select
        v-model="store.filters.cancelado"
        :options="canceladoOptions"
        emit-value
        map-options
        outlined
        :bottom-slots="false"
        label="Situação"
        class="q-mb-md"
      />
      <q-select
        v-model="store.filters.sentido"
        :options="sentidoOptions"
        emit-value
        map-options
        outlined
        clearable
        :bottom-slots="false"
        label="Sentido"
        class="q-mb-md"
      />
      <MgInputValor
        v-model="store.filters.codpagamento"
        :decimals="0"
        :grouping="false"
        :bottom-slots="false"
        label="Código (ou da liquidação antiga)"
      >
        <template #prepend><q-icon name="numbers" /></template>
      </MgInputValor>
    </FilterGroup>

    <FilterGroup title="Pessoa">
      <SelectPessoa
        v-model="store.filters.codpessoa"
        outlined
        clearable
        :bottom-slots="false"
        label="Pessoa"
        class="q-mb-md"
      />
      <MgSelectGrupoEconomico
        v-model="store.filters.codgrupoeconomico"
        outlined
        clearable
        :bottom-slots="false"
        label="Grupo Econômico"
        class="q-mb-md"
      />
      <MgSelectGrupoCliente
        v-model="store.filters.codgrupocliente"
        multiple
        outlined
        :bottom-slots="false"
        label="Grupo de Cliente"
      />
    </FilterGroup>

    <FilterGroup title="Portador e meio">
      <MgSelectPortador
        v-model="store.filters.codportador"
        outlined
        clearable
        inativos
        :bottom-slots="false"
        label="Portador"
        class="q-mb-md"
      />
      <q-select
        v-model="store.filters.meio"
        :options="MEIOS_FILTRO"
        multiple
        emit-value
        map-options
        outlined
        clearable
        :bottom-slots="false"
        label="Meio"
      />
    </FilterGroup>

    <FilterGroup title="Usuário">
      <MgSelectUsuario
        v-model="store.filters.codusuariocriacao"
        outlined
        clearable
        :bottom-slots="false"
        label="Criado por"
      />
    </FilterGroup>

    <FilterGroup title="Datas">
      <div class="row q-col-gutter-md q-mb-md">
        <div class="col-6">
          <MgInputData
            v-model="store.filters.lancamento_de"
            :bottom-slots="false"
            type="date"
            label="Data"
            stack-label
          />
        </div>
        <div class="col-6">
          <MgInputData
            v-model="store.filters.lancamento_ate"
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
