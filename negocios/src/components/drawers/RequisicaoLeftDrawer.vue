<script setup>
import { requisicaoStore, STATUS, DESTINOS, PRIORIDADES } from 'stores/requisicao'
import FilterDrawerShell from 'components/FilterDrawerShell.vue'
import FilterGroup from 'components/FilterGroup.vue'
import MgInput from '@components/MgInput.vue'
import MgInputData from '@components/MgInputData.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgSelect from '@components/MgSelect.vue'
import MgSelectEstoqueLocal from '@components/MgSelectEstoqueLocal.vue'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'

// A página observa os filtros e recarrega; aqui é só o formulário.
const sRequisicao = requisicaoStore()

const opcoesStatus = Object.entries(STATUS).map(([value, s]) => ({ value, label: s.label }))
const opcoesDestino = DESTINOS.map((d) => ({ value: d.value, label: d.label }))
const opcoesPrioridade = PRIORIDADES.map((p) => ({ value: p.value, label: p.label }))
</script>

<template>
  <FilterDrawerShell :active-count="sRequisicao.filtrosAtivos" @clear="sRequisicao.limparFiltros">
    <FilterGroup title="Requisição" first>
      <div class="row q-col-gutter-md">
        <div class="col-12">
          <MgInputValor
            v-model="sRequisicao.filtros.codrequisicao"
            :decimals="0"
            :grouping="false"
            align="left"
            label="Nº da requisição"
            clearable
            :bottom-slots="false"
          />
        </div>
        <div class="col-12">
          <MgSelect
            v-model="sRequisicao.filtros.status"
            :options="opcoesStatus"
            label="Status"
            map-options
            emit-value
            clearable
          />
        </div>
        <div class="col-12">
          <MgSelect
            v-model="sRequisicao.filtros.destino"
            :options="opcoesDestino"
            label="Destino"
            map-options
            emit-value
            clearable
          />
        </div>
        <div class="col-12">
          <MgSelect
            v-model="sRequisicao.filtros.prioridade"
            :options="opcoesPrioridade"
            label="Prioridade"
            map-options
            emit-value
            clearable
          />
        </div>
        <div class="col-12">
          <MgInputValor
            v-model="sRequisicao.filtros.codnegocio"
            :decimals="0"
            :grouping="false"
            align="left"
            label="Nº do negócio"
            clearable
            :bottom-slots="false"
          />
        </div>
      </div>
    </FilterGroup>

    <FilterGroup title="Criação">
      <div class="row q-col-gutter-md">
        <div class="col-6">
          <MgInputData v-model="sRequisicao.filtros.de" label="De" year-digits="2" />
        </div>
        <div class="col-6">
          <MgInputData v-model="sRequisicao.filtros.ate" label="Até" year-digits="2" />
        </div>
      </div>
    </FilterGroup>

    <FilterGroup title="Lojas">
      <div class="row q-col-gutter-md">
        <div class="col-12">
          <MgSelectEstoqueLocal
            v-model="sRequisicao.filtros.codestoquelocalrequisitante"
            label="Loja que pede"
            clearable
          />
        </div>
        <div class="col-12">
          <MgSelectEstoqueLocal
            v-model="sRequisicao.filtros.codestoquelocalrequisitada"
            label="Loja que atende"
            clearable
          />
        </div>
      </div>
    </FilterGroup>

    <FilterGroup title="Cliente">
      <div class="row q-col-gutter-md">
        <div class="col-12">
          <MgSelectPessoa
            v-model="sRequisicao.filtros.codpessoa"
            label="Pessoa"
            clearable
            :bottom-slots="false"
          />
        </div>
        <div class="col-12">
          <MgInput
            v-model="sRequisicao.filtros.busca"
            label="Quem retira"
            clearable
            :bottom-slots="false"
          >
            <template #prepend><q-icon name="search" /></template>
          </MgInput>
        </div>
        <div class="col-12">
          <MgInput
            v-model="sRequisicao.filtros.telefone"
            label="Telefone"
            type="tel"
            clearable
            :bottom-slots="false"
          >
            <template #prepend><q-icon name="phone" /></template>
          </MgInput>
        </div>
        <div class="col-12">
          <MgInput
            v-model="sRequisicao.filtros.codigoretirada"
            label="Código de retirada"
            mask="######"
            inputmode="numeric"
            clearable
            :bottom-slots="false"
          >
            <template #prepend><q-icon name="pin" /></template>
          </MgInput>
        </div>
      </div>
    </FilterGroup>
  </FilterDrawerShell>
</template>
