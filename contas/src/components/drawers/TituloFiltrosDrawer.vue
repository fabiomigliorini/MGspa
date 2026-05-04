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
import SelectPessoa from 'src/components/select/SelectPessoa.vue'
import SelectGrupoEconomico from 'src/components/select/SelectGrupoEconomico.vue'

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
      <q-input
        v-model.number="store.filters.codtitulo"
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
        v-model="store.filters.numero"
        outlined
        clearable
        :bottom-slots="false"
        label="Número"
        class="q-mb-sm"
      />

      <q-input
        v-model="store.filters.nossonumero"
        outlined
        clearable
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
        class="q-mb-sm"
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

    <FilterGroup title="Filial / Tipo / Conta">
      <SelectFilial
        v-model="store.filters.codfilial"
        outlined
        clearable
        :bottom-slots="false"
        label="Filial"
        class="q-mb-sm"
      >
        <template #prepend><q-icon name="store" /></template>
      </SelectFilial>

      <SelectTipoTitulo
        v-model="store.filters.codtipotitulo"
        outlined
        clearable
        :bottom-slots="false"
        label="Tipo de Título"
        class="q-mb-sm"
      />

      <SelectContaContabil
        v-model="store.filters.codcontacontabil"
        outlined
        clearable
        :bottom-slots="false"
        label="Conta Contábil"
        class="q-mb-sm"
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
      <div class="row q-col-gutter-xs q-mb-sm">
        <div class="col-6">
          <q-input
            v-model="store.filters.vencimento_de"
            outlined
            dense
            clearable
            :bottom-slots="false"
            type="date"
            label="Vencimento"
            stack-label
          />
        </div>
        <div class="col-6">
          <q-input
            v-model="store.filters.vencimento_ate"
            outlined
            dense
            clearable
            :bottom-slots="false"
            type="date"
            label="até"
            stack-label
          />
        </div>
      </div>
      <div class="row q-col-gutter-xs q-mb-sm">
        <div class="col-6">
          <q-input
            v-model="store.filters.emissao_de"
            outlined
            dense
            clearable
            :bottom-slots="false"
            type="date"
            label="Emissão"
            stack-label
          />
        </div>
        <div class="col-6">
          <q-input
            v-model="store.filters.emissao_ate"
            outlined
            dense
            clearable
            :bottom-slots="false"
            type="date"
            label="até"
            stack-label
          />
        </div>
      </div>
      <div class="row q-col-gutter-xs q-mb-sm">
        <div class="col-6">
          <q-input
            v-model="store.filters.criacao_de"
            outlined
            dense
            clearable
            :bottom-slots="false"
            type="date"
            label="Criação"
            stack-label
          />
        </div>
        <div class="col-6">
          <q-input
            v-model="store.filters.criacao_ate"
            outlined
            dense
            clearable
            :bottom-slots="false"
            type="date"
            label="Criação até"
            stack-label
          />
        </div>
      </div>
      <div class="row q-col-gutter-xs">
        <div class="col-6">
          <q-input
            v-model="store.filters.liquidacao_de"
            outlined
            dense
            clearable
            :bottom-slots="false"
            type="date"
            label="Liq. de"
            stack-label
          />
        </div>
        <div class="col-6">
          <q-input
            v-model="store.filters.liquidacao_ate"
            outlined
            dense
            clearable
            :bottom-slots="false"
            type="date"
            label="Liq. até"
            stack-label
          />
        </div>
      </div>
    </FilterGroup>

    <FilterGroup title="Valores">
      <div class="row q-col-gutter-xs q-mb-sm">
        <div class="col-6">
          <q-input
            v-model.number="store.filters.debito_de"
            outlined
            dense
            clearable
            :bottom-slots="false"
            type="number"
            label="Déb. de"
            stack-label
          />
        </div>
        <div class="col-6">
          <q-input
            v-model.number="store.filters.debito_ate"
            outlined
            dense
            clearable
            :bottom-slots="false"
            type="number"
            label="Déb. até"
            stack-label
          />
        </div>
      </div>
      <div class="row q-col-gutter-xs q-mb-sm">
        <div class="col-6">
          <q-input
            v-model.number="store.filters.credito_de"
            outlined
            dense
            clearable
            :bottom-slots="false"
            type="number"
            label="Créd. de"
            stack-label
          />
        </div>
        <div class="col-6">
          <q-input
            v-model.number="store.filters.credito_ate"
            outlined
            dense
            clearable
            :bottom-slots="false"
            type="number"
            label="Créd. até"
            stack-label
          />
        </div>
      </div>
      <div class="row q-col-gutter-xs">
        <div class="col-6">
          <q-input
            v-model.number="store.filters.saldo_de"
            outlined
            dense
            clearable
            :bottom-slots="false"
            type="number"
            label="Saldo de"
            stack-label
          />
        </div>
        <div class="col-6">
          <q-input
            v-model.number="store.filters.saldo_ate"
            outlined
            dense
            clearable
            :bottom-slots="false"
            type="number"
            label="Saldo até"
            stack-label
          />
        </div>
      </div>
    </FilterGroup>

    <FilterGroup title="Situação e Ordem">
      <q-select
        v-model="store.filters.status"
        :options="statusOptions"
        emit-value
        map-options
        outlined
        :bottom-slots="false"
        label="Situação"
        class="q-mb-sm"
      />

      <q-select
        v-model="store.filters.pagarreceber"
        :options="pagarReceberOptions"
        emit-value
        map-options
        outlined
        :bottom-slots="false"
        label="Direção"
        class="q-mb-sm"
      />

      <q-select
        v-model="store.filters.credito"
        :options="operacaoOptions"
        emit-value
        map-options
        outlined
        :bottom-slots="false"
        label="Operação"
        class="q-mb-sm"
      />

      <q-select
        v-model="store.filters.gerencial"
        :options="gerencialOptions"
        emit-value
        map-options
        outlined
        :bottom-slots="false"
        label="Gerencial / Fiscal"
        class="q-mb-sm"
      />

      <q-select
        v-model="store.filters.boleto"
        :options="boletoOptions"
        emit-value
        map-options
        outlined
        :bottom-slots="false"
        label="Boleto"
        class="q-mb-sm"
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
