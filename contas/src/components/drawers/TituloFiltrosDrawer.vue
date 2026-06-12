<script setup>
import { watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { useTituloStore } from 'src/stores/tituloStore'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'
import SelectFilial from 'src/components/select/SelectFilial.vue'
import SelectPortador from 'src/components/select/SelectPortador.vue'
import SelectTipoTitulo from 'src/components/select/SelectTipoTitulo.vue'
import SelectContaContabil from 'src/components/select/SelectContaContabil.vue'
import SelectPessoa from '@components/MgSelectPessoa.vue'
import SelectGrupoEconomico from 'src/components/select/SelectGrupoEconomico.vue'
import SelectGrupoCliente from 'src/components/select/SelectGrupoCliente.vue'
import MgInputData from '@components/MgInputData.vue'
import MgInputValor from '@components/MgInputValor.vue'

const store = useTituloStore()

const debouncedFetch = useDebounceFn(() => store.fetchItems(true), 800)
watch(() => store.filters, debouncedFetch, { deep: true })

const clear = () => {
  store.clearFilters()
  store.fetchItems(true)
}

const statusOptions = [
  { label: 'Abertos', value: 'A' },
  { label: 'Liquidados', value: 'L' },
  { label: 'Abertos e Liquidados', value: 'AL' },
  { label: 'Estornados', value: 'E' },
  { label: 'Liquidados e Estornados', value: 'LE' },
  { label: 'Todos', value: 'T' },
]

const ordemOptions = [
  { label: 'Alfabética / Vencimento', value: 'AV' },
  { label: 'Alfabética / Emissão', value: 'AE' },
  { label: '# Pessoa / Vencimento', value: 'CV' },
  { label: '# Pessoa / Emissão', value: 'CE' },
  { label: 'Vencimento / Saldo', value: 'VS' },
]

const operacaoOptions = [
  { label: 'Todos', value: null },
  { label: 'Crédito', value: 1 },
  { label: 'Débito', value: 2 },
]

const gerencialOptions = [
  { label: 'Todos', value: null },
  { label: 'Gerencial', value: 1 },
  { label: 'Fiscal', value: 2 },
]

const boletoOptions = [
  { label: 'Todos', value: null },
  { label: 'Com Boleto Emitido', value: 'B' },
  { label: 'Aberto no Banco', value: 'BA' },
  { label: 'Liquidado no Banco', value: 'BL' },
  { label: 'Sem Boleto', value: 'SB' },
]

const pagarReceberOptions = [
  { label: 'Todos', value: null },
  { label: 'Contas a Receber', value: 'R' },
  { label: 'Contas a Pagar', value: 'P' },
]
</script>

<template>
  <FilterDrawerShell :active-count="store.activeFiltersCount" @clear="clear">
    <FilterGroup title="Identificação" first>
      <q-select
        v-model="store.filters.status"
        :options="statusOptions"
        emit-value
        map-options
        outlined
        :bottom-slots="false"
        label="Situação"
        class="q-mb-md"
      />
      <q-input
        v-model.number="store.filters.codtitulo"
        outlined
        :bottom-slots="false"
        type="number"
        label="Código"
        class="q-mb-md"
      >
        <template #prepend><q-icon name="numbers" /></template>
      </q-input>

      <q-input
        v-model="store.filters.numero"
        outlined
        :bottom-slots="false"
        label="Número"
        class="q-mb-md"
      />

      <q-input
        v-model="store.filters.nossonumero"
        outlined
        :bottom-slots="false"
        label="Nosso Número"
      />
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

      <SelectGrupoEconomico
        v-model="store.filters.codgrupoeconomico"
        outlined
        clearable
        :bottom-slots="false"
        label="Grupo Econômico"
        class="q-mb-md"
      />

      <SelectGrupoCliente
        v-model="store.filters.codgrupocliente"
        outlined
        :bottom-slots="false"
        label="Grupo de Cliente"
      />
    </FilterGroup>

    <FilterGroup title="Filial / Tipo / Conta">
      <SelectFilial
        v-model="store.filters.codfilial"
        outlined
        clearable
        :bottom-slots="false"
        label="Filial"
        class="q-mb-md"
      >
        <template #prepend><q-icon name="store" /></template>
      </SelectFilial>

      <SelectTipoTitulo
        v-model="store.filters.codtipotitulo"
        outlined
        clearable
        :bottom-slots="false"
        label="Tipo de Título"
        class="q-mb-md"
      />

      <SelectContaContabil
        v-model="store.filters.codcontacontabil"
        outlined
        clearable
        :bottom-slots="false"
        label="Conta Contábil"
        class="q-mb-md"
      />

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
            v-model="store.filters.vencimento_de"
            :bottom-slots="false"
            type="date"
            label="Vencimento"
            stack-label
          />
        </div>
        <div class="col-6">
          <MgInputData
            v-model="store.filters.vencimento_ate"
            :bottom-slots="false"
            type="date"
            label="Até"
            stack-label
          />
        </div>
      </div>
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
      <div class="row q-col-gutter-md q-mb-md">
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
      <div class="row q-col-gutter-md">
        <div class="col-6">
          <MgInputData
            v-model="store.filters.liquidacao_de"
            :bottom-slots="false"
            type="date"
            label="Liquidação"
            stack-label
          />
        </div>
        <div class="col-6">
          <MgInputData
            v-model="store.filters.liquidacao_ate"
            :bottom-slots="false"
            type="date"
            label="Até"
            stack-label
          />
        </div>
      </div>
    </FilterGroup>

    <FilterGroup title="Valores">
      <div class="row q-col-gutter-md q-mb-md">
        <div class="col-6">
          <MgInputValor
            v-model="store.filters.debito_de"
            :bottom-slots="false"
            label="Débito"
            stack-label
          />
        </div>
        <div class="col-6">
          <MgInputValor
            v-model="store.filters.debito_ate"
            :bottom-slots="false"
            label="Até"
            stack-label
          />
        </div>
      </div>
      <div class="row q-col-gutter-md q-mb-md">
        <div class="col-6">
          <MgInputValor
            v-model="store.filters.credito_de"
            :bottom-slots="false"
            label="Crédito"
            stack-label
          />
        </div>
        <div class="col-6">
          <MgInputValor
            v-model="store.filters.credito_ate"
            :bottom-slots="false"
            label="Até"
            stack-label
          />
        </div>
      </div>
      <div class="row q-col-gutter-md">
        <div class="col-6">
          <MgInputValor
            v-model="store.filters.saldo_de"
            :bottom-slots="false"
            label="Saldo"
            stack-label
          />
        </div>
        <div class="col-6">
          <MgInputValor
            v-model="store.filters.saldo_ate"
            :bottom-slots="false"
            label="Até"
            stack-label
          />
        </div>
      </div>
    </FilterGroup>

    <FilterGroup title="Operação e Ordem">
      <q-select
        v-model="store.filters.pagarreceber"
        :options="pagarReceberOptions"
        emit-value
        map-options
        outlined
        :bottom-slots="false"
        label="Pagar / Receber"
        class="q-mb-md"
      />

      <q-select
        v-model="store.filters.credito"
        :options="operacaoOptions"
        emit-value
        map-options
        outlined
        :bottom-slots="false"
        label="Operação"
        class="q-mb-md"
      />

      <q-select
        v-model="store.filters.gerencial"
        :options="gerencialOptions"
        emit-value
        map-options
        outlined
        :bottom-slots="false"
        label="Gerencial / Fiscal"
        class="q-mb-md"
      />

      <q-select
        v-model="store.filters.boleto"
        :options="boletoOptions"
        emit-value
        map-options
        outlined
        :bottom-slots="false"
        label="Boleto"
        class="q-mb-md"
      />

      <q-select
        v-model="store.filters.ordem"
        :options="ordemOptions"
        emit-value
        map-options
        outlined
        :bottom-slots="false"
        label="Ordem"
      />
    </FilterGroup>
  </FilterDrawerShell>
</template>
