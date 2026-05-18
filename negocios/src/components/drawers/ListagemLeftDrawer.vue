<script setup>
import { onMounted, onUnmounted } from 'vue'
import { listagemStore } from 'stores/listagem'
import SelectEstoqueLocal from 'components/selects/SelectEstoqueLocal.vue'
import SelectNaturezaOperacao from 'components/selects/SelectNaturezaOperacao.vue'
import SelectPessoa from 'components/selects/SelectPessoa.vue'
import SelectPdv from 'components/selects/SelectPdv.vue'
import SelectUsuario from 'components/selects/SelectUsuario.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgInputData from '@components/MgInputData.vue'
const sListagem = listagemStore()

onMounted(() => {
  sListagem.inicializaFiltro()
})
</script>
<template>
  <q-list>
    <q-item-label header>Filtro </q-item-label>

    <!-- LOCAL -->
    <q-item>
      <q-item-section>
        <select-estoque-local
          outlined
          v-model="sListagem.filtro.codestoquelocal"
          label="Local"
          clearable
        />
      </q-item-section>
    </q-item>

    <!-- PDV -->
    <q-item>
      <q-item-section>
        <select-pdv outlined v-model="sListagem.filtro.codpdv" label="PDV" clearable />
      </q-item-section>
    </q-item>

    <!-- USUARIO -->
    <q-item>
      <q-item-section>
        <select-usuario outlined v-model="sListagem.filtro.codusuario" label="Usuario" clearable />
      </q-item-section>
    </q-item>

    <!-- STATUS -->
    <q-item>
      <q-item-section>
        <q-select
          outlined
          v-model="sListagem.filtro.codnegociostatus"
          :options="sListagem.opcoes.codnegociostatus"
          label="Status"
          map-options
          emit-value
          clearable
        />
      </q-item-section>
    </q-item>

    <!-- LANCAMENTO_DE -->
    <q-item>
      <q-item-section>
        <MgInputData
          type="timestamp"
          :seconds="false"
          v-model="sListagem.filtro.lancamento_de"
          label="De"
        />
      </q-item-section>
    </q-item>

    <!-- LANCAMENTO_ATE -->
    <q-item>
      <q-item-section>
        <MgInputData
          type="timestamp"
          :seconds="false"
          v-model="sListagem.filtro.lancamento_ate"
          label="Até"
        />
      </q-item-section>
    </q-item>

    <!-- CODNEGOCIO -->
    <q-item>
      <q-item-section>
        <q-input
          outlined
          type="number"
          step="1"
          min="0"
          input-class="text-right"
          v-model="sListagem.filtro.codnegocio"
          label="# Negócio"
        />
      </q-item-section>
    </q-item>

    <!-- VALOR -->
    <q-item>
      <q-item-section>
        <div class="row q-col-gutter-sm">
          <MgInputValor
            :min="0.01"
            v-model="sListagem.filtro.valor_de"
            :max="sListagem.filtro.valor_ate"
            label="Valor de"
            class="col-6"
            prefix="R$"
          />
          <MgInputValor
            :min="sListagem.filtro.valor_de > 0 ? sListagem.filtro.valor_de : 0.01"
            v-model="sListagem.filtro.valor_ate"
            label="até"
            class="col-6"
            prefix="R$"
          />
        </div>
      </q-item-section>
    </q-item>

    <!-- NATUREZA -->
    <q-item>
      <q-item-section>
        <select-natureza-operacao
          outlined
          v-model="sListagem.filtro.codnaturezaoperacao"
          label="Natureza"
          clearable
        />
      </q-item-section>
    </q-item>

    <!-- PESSOA -->
    <q-item>
      <q-item-section>
        <select-pessoa outlined v-model="sListagem.filtro.codpessoa" label="Pessoa" clearable />
      </q-item-section>
    </q-item>

    <!-- VENDEDOR -->
    <q-item>
      <q-item-section>
        <select-pessoa
          outlined
          v-model="sListagem.filtro.codpessoavendedor"
          label="Vendedor"
          clearable
          somente-vendedores
        />
      </q-item-section>
    </q-item>

    <!-- TRANSPORTADOR -->
    <q-item>
      <q-item-section>
        <select-pessoa
          outlined
          v-model="sListagem.filtro.codpessoatransportador"
          label="Transportador"
          clearable
        />
      </q-item-section>
    </q-item>

    <!-- FORMA DE PAGAMENTO -->
    <q-item>
      <q-item-section>
        <q-select
          outlined
          v-model="sListagem.filtro.codformapagamento"
          multiple
          :options="sListagem.opcoes.codformapagamento"
          label="Forma de Pagamento"
          clearable
          map-options
          emit-value
        />
      </q-item-section>
    </q-item>

    <!-- INTEGRACAO -->
    <q-item>
      <q-item-section>
        <q-select
          outlined
          v-model="sListagem.filtro.integracao"
          multiple
          :options="sListagem.opcoes.integracao"
          label="Integração Pagamento"
          clearable
        />
      </q-item-section>
    </q-item>

    <!-- FIM -->
  </q-list>
</template>
