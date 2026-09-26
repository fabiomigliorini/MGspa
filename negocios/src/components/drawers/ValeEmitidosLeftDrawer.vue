<script setup>
import { valeEmitidosStore } from 'stores/valeEmitidos'
import FilterDrawerShell from 'components/FilterDrawerShell.vue'
import FilterGroup from 'components/FilterGroup.vue'
import MgInput from '@components/MgInput.vue'
import MgInputData from '@components/MgInputData.vue'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'
import MgSelectValeModelo from '@components/MgSelectValeModelo.vue'
import MgInputValor from '@components/MgInputValor.vue'

const sVales = valeEmitidosStore()
const limpar = () => sVales.limparFiltros()
const situacaoOptions = [
  { label: 'Ativos', value: 'ativo' },
  { label: 'Cancelados', value: 'cancelado' },
  { label: 'Todos', value: 'todos' },
]
</script>

<template>
  <FilterDrawerShell :active-count="sVales.filtrosAtivos" @clear="limpar">
    <FilterGroup title="Modelo" first>
      <MgSelectValeModelo
        v-model="sVales.filtros.codvalemodelo"
        label="Modelo"
        clearable
        :bottom-slots="false"
      />
    </FilterGroup>

    <FilterGroup title="Busca">
      <MgInput
        v-model="sVales.filtros.busca"
        label="Vale, modelo ou favorecido"
        clearable
        :bottom-slots="false"
      >
        <template #prepend><q-icon name="search" /></template>
      </MgInput>
    </FilterGroup>

    <FilterGroup title="Favorecido">
      <MgSelectPessoa
        v-model="sVales.filtros.codpessoafavorecido"
        label="Favorecido"
        clearable
        :bottom-slots="false"
      />
    </FilterGroup>

    <FilterGroup title="Período">
      <div class="column q-gutter-sm">
        <MgInputData v-model="sVales.filtros.de" label="De" />
        <MgInputData v-model="sVales.filtros.ate" label="Até" />
      </div>
    </FilterGroup>

    <FilterGroup title="Valor">
      <div class="row q-col-gutter-sm">
        <div class="col-6">
          <MgInputValor
            v-model="sVales.filtros.valorde"
            clearable
            :bottom-slots="false"
            label="De R$"
          />
        </div>
        <div class="col-6">
          <MgInputValor
            v-model="sVales.filtros.valorate"
            clearable
            :bottom-slots="false"
            label="Até R$"
          />
        </div>
      </div>
    </FilterGroup>

    <FilterGroup title="Negócio">
      <MgInput
        v-model="sVales.filtros.codnegocio"
        type="number"
        min="1"
        step="1"
        label="# Negócio"
        clearable
        :bottom-slots="false"
      />
    </FilterGroup>

    <FilterGroup title="Situação">
      <q-btn-toggle
        v-model="sVales.filtros.situacao"
        spread
        no-caps
        flat
        toggle-color="primary"
        :options="situacaoOptions"
      />
    </FilterGroup>
  </FilterDrawerShell>
</template>
