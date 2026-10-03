<script setup>
// Filtros da listagem única de pagamentos (M6.1 doc-3), no drawer de cada app.
// No PDV a listagem é travada nele: sem filial nem PDV.
import { pagamentoListaStore } from '@components/stores/pagamentoListaStore'
import { MEIOS } from '@components/cobranca/pagamento.js'
import MgInput from '@components/MgInput.vue'
import MgInputData from '@components/MgInputData.vue'
import MgSelectFilial from '@components/MgSelectFilial.vue'
import MgSelectPdv from '@components/MgSelectPdv.vue'
import MgSelectPortador from '@components/MgSelectPortador.vue'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'
import MgSelectMaquineta from '@components/MgSelectMaquineta.vue'
import MgSelectUsuario from '@components/MgSelectUsuario.vue'

const store = pagamentoListaStore()

const ESTADOS = [
  { value: 'P', label: 'Pendente' },
  { value: 'E', label: 'Efetivado' },
  { value: 'C', label: 'Cancelado' },
]

const ORIGENS = [
  { value: 'V', label: 'Venda' },
  { value: 'T', label: 'Títulos' },
  { value: 'X', label: 'Transferência' },
  { value: 'I', label: 'Item do caixa' },
  { value: 'A', label: 'Avulso' },
]

const OPCOES_MEIO = Object.entries(MEIOS).map(([value, label]) => ({ value: Number(value), label }))
</script>

<template>
  <div class="row q-col-gutter-md">
    <div class="col-6">
      <MgInputData v-model="store.filtros.transacao_de" label="De" :bottom-slots="false" />
    </div>
    <div class="col-6">
      <MgInputData v-model="store.filtros.transacao_ate" label="Até" :bottom-slots="false" />
    </div>
    <div class="col-12">
      <q-select
        v-model="store.filtros.origem"
        :options="ORIGENS"
        label="Origem"
        multiple
        emit-value
        map-options
        outlined
        clearable
        :bottom-slots="false"
      />
    </div>
    <div class="col-12">
      <q-select
        v-model="store.filtros.estado"
        :options="ESTADOS"
        label="Estado"
        multiple
        emit-value
        map-options
        outlined
        clearable
        :bottom-slots="false"
      />
    </div>
    <div class="col-12">
      <MgInput
        v-model="store.filtros.documento"
        label="Documento (venda ou título)"
        clearable
        :bottom-slots="false"
      />
    </div>
    <div class="col-12">
      <MgSelectPessoa
        v-model="store.filtros.codpessoa"
        label="Pessoa"
        clearable
        :bottom-slots="false"
      />
    </div>
    <div class="col-12" v-if="!store.travadoPdv">
      <MgSelectFilial
        v-model="store.filtros.codfilial"
        label="Filial"
        clearable
        :bottom-slots="false"
      />
    </div>
    <div class="col-12" v-if="!store.travadoPdv">
      <MgSelectPdv
        v-model="store.filtros.codpdv"
        :codfilial="store.filtros.codfilial"
        label="PDV"
        clearable
        :bottom-slots="false"
      />
    </div>
    <div class="col-12">
      <q-select
        v-model="store.filtros.meio"
        :options="OPCOES_MEIO"
        label="Meio"
        multiple
        emit-value
        map-options
        outlined
        clearable
        :bottom-slots="false"
      />
    </div>
    <div class="col-12">
      <MgSelectPortador
        v-model="store.filtros.codportador"
        label="Portador"
        clearable
        inativos
        :bottom-slots="false"
      />
    </div>
    <div class="col-12">
      <MgSelectMaquineta
        v-model="store.filtros.codmaquineta"
        label="Maquineta"
        clearable
        inativos
        :bottom-slots="false"
      />
    </div>
    <div class="col-12">
      <MgSelectUsuario
        v-model="store.filtros.codusuariocriacao"
        label="Usuário"
        clearable
        :bottom-slots="false"
      />
    </div>
    <div class="col-12">
      <MgInput
        v-model="store.filtros.codpagamento"
        label="Código do pagamento"
        inputmode="numeric"
        clearable
        :bottom-slots="false"
      />
    </div>
  </div>
</template>
