<script setup>
import { valeEmitidosStore } from 'stores/valeEmitidos'
import FilterDrawerShell from 'components/FilterDrawerShell.vue'
import FilterGroup from 'components/FilterGroup.vue'
import MgInput from '@components/MgInput.vue'
import MgInputData from '@components/MgInputData.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'
import MgSelectValeModelo from '@components/MgSelectValeModelo.vue'

// A página observa os filtros e recarrega; aqui é só o formulário.
const sVales = valeEmitidosStore()

const situacaoOptions = [
  { label: 'Ativos', value: 'ativo' },
  { label: 'Cancelados', value: 'cancelado' },
  { label: 'Todos', value: 'todos' },
]

const saldoOptions = [
  { label: 'Com saldo', value: 1 },
  { label: 'Todos', value: null },
]
</script>

<template>
  <FilterDrawerShell :active-count="sVales.filtrosAtivos" @clear="sVales.limparFiltros">
    <FilterGroup title="Favorecido" first>
      <div class="row q-col-gutter-md">
        <div class="col-12">
          <MgSelectPessoa
            v-model="sVales.filtros.codpessoafavorecido"
            label="Pessoa"
            clearable
            :bottom-slots="false"
          />
        </div>
        <div class="col-12">
          <MgSelectValeModelo
            v-model="sVales.filtros.codvalemodelo"
            :codpessoa="sVales.filtros.codpessoafavorecido"
            clearable
          />
        </div>
        <div class="col-12">
          <MgInput
            v-model="sVales.filtros.busca"
            label="Aluno ou turma"
            clearable
            :bottom-slots="false"
          >
            <template #prepend><q-icon name="search" /></template>
          </MgInput>
        </div>
      </div>
    </FilterGroup>

    <FilterGroup title="Venda">
      <div class="row q-col-gutter-md">
        <div class="col-6">
          <MgInputData v-model="sVales.filtros.de" label="Data" year-digits="2" />
        </div>
        <div class="col-6">
          <MgInputData v-model="sVales.filtros.ate" label="Até" year-digits="2" />
        </div>
        <div class="col-6">
          <MgInputValor v-model="sVales.filtros.valorde" label="Valor" prefix="R$" clearable />
        </div>
        <div class="col-6">
          <MgInputValor v-model="sVales.filtros.valorate" label="Até" prefix="R$" clearable />
        </div>
        <div class="col-12">
          <MgInput
            v-model.number="sVales.filtros.codnegocio"
            type="number"
            label="Nº do negócio"
            clearable
            :bottom-slots="false"
          />
        </div>
      </div>
    </FilterGroup>

    <FilterGroup title="Situação">
      <div class="row q-col-gutter-md">
        <div class="col-12">
          <q-btn-toggle
            v-model="sVales.filtros.situacao"
            spread
            no-caps
            flat
            toggle-color="primary"
            :options="situacaoOptions"
          />
        </div>
        <div class="col-12">
          <q-btn-toggle
            v-model="sVales.filtros.comsaldo"
            spread
            no-caps
            flat
            toggle-color="primary"
            :options="saldoOptions"
          />
        </div>
      </div>
    </FilterGroup>
  </FilterDrawerShell>
</template>
