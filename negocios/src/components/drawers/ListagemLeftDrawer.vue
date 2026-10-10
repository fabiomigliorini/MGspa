<script setup>
import { onMounted } from 'vue'
import { listagemStore } from 'stores/listagem'
import FilterDrawerShell from 'components/FilterDrawerShell.vue'
import FilterGroup from 'components/FilterGroup.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgInputData from '@components/MgInputData.vue'
import MgSelect from '@components/MgSelect.vue'
import MgSelectEstoqueLocal from '@components/MgSelectEstoqueLocal.vue'
import MgSelectNaturezaOperacao from '@components/MgSelectNaturezaOperacao.vue'
import MgSelectPdv from '@components/MgSelectPdv.vue'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'
import MgSelectUsuario from '@components/MgSelectUsuario.vue'

// A página observa os filtros e recarrega; aqui é só o formulário.
const sListagem = listagemStore()

onMounted(() => {
  sListagem.inicializaFiltro()
})
</script>

<template>
  <FilterDrawerShell :active-count="sListagem.filtrosAtivos" @clear="sListagem.limparFiltros">
    <FilterGroup title="Negócio" first>
      <div class="row q-col-gutter-md">
        <div class="col-12">
          <MgInputValor
            v-model="sListagem.filtro.codnegocio"
            :decimals="0"
            :min="0"
            :grouping="false"
            align="left"
            label="# Negócio"
            clearable
          />
        </div>
        <div class="col-12">
          <MgSelect
            v-model="sListagem.filtro.codnegociostatus"
            :options="sListagem.opcoes.codnegociostatus"
            label="Status"
            map-options
            emit-value
            clearable
          />
        </div>
        <div class="col-12">
          <MgSelectNaturezaOperacao
            v-model="sListagem.filtro.codnaturezaoperacao"
            label="Natureza"
            clearable
          />
        </div>
      </div>
    </FilterGroup>

    <FilterGroup title="Período e valor">
      <div class="row q-col-gutter-md">
        <div class="col-12">
          <MgInputData
            type="timestamp"
            :seconds="false"
            v-model="sListagem.filtro.lancamento_de"
            label="De"
          />
        </div>
        <div class="col-12">
          <MgInputData
            type="timestamp"
            :seconds="false"
            v-model="sListagem.filtro.lancamento_ate"
            label="Até"
          />
        </div>
        <div class="col-6">
          <MgInputValor
            :min="0.01"
            v-model="sListagem.filtro.valor_de"
            :max="sListagem.filtro.valor_ate"
            label="Valor de"
            prefix="R$"
          />
        </div>
        <div class="col-6">
          <MgInputValor
            :min="sListagem.filtro.valor_de > 0 ? sListagem.filtro.valor_de : 0.01"
            v-model="sListagem.filtro.valor_ate"
            label="até"
            prefix="R$"
          />
        </div>
      </div>
    </FilterGroup>

    <FilterGroup title="Onde">
      <div class="row q-col-gutter-md">
        <div class="col-12">
          <MgSelectEstoqueLocal
            v-model="sListagem.filtro.codestoquelocal"
            label="Local"
            clearable
          />
        </div>
        <div class="col-12">
          <MgSelectPdv v-model="sListagem.filtro.codpdv" clearable />
        </div>
        <div class="col-12">
          <MgSelectUsuario v-model="sListagem.filtro.codusuario" clearable />
        </div>
      </div>
    </FilterGroup>

    <FilterGroup title="Pessoas">
      <div class="row q-col-gutter-md">
        <div class="col-12">
          <MgSelectPessoa
            v-model="sListagem.filtro.codpessoa"
            label="Pessoa"
            clearable
            :bottom-slots="false"
          />
        </div>
        <div class="col-12">
          <MgSelectPessoa
            v-model="sListagem.filtro.codpessoavendedor"
            label="Vendedor"
            clearable
            somente-vendedores
            :bottom-slots="false"
          />
        </div>
        <div class="col-12">
          <MgSelectPessoa
            v-model="sListagem.filtro.codpessoatransportador"
            label="Transportador"
            clearable
            :bottom-slots="false"
          />
        </div>
      </div>
    </FilterGroup>

    <FilterGroup title="Pagamento">
      <div class="row q-col-gutter-md">
        <div class="col-12">
          <MgSelect
            v-model="sListagem.filtro.forma"
            multiple
            :options="sListagem.opcoes.forma"
            label="Forma de Pagamento"
            clearable
            map-options
            emit-value
          />
        </div>
        <div class="col-12">
          <MgSelect
            v-model="sListagem.filtro.integracao"
            multiple
            :options="sListagem.opcoes.integracao"
            label="Integração Pagamento"
            clearable
          />
        </div>
      </div>
    </FilterGroup>
  </FilterDrawerShell>
</template>
