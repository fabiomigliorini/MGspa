<script setup>
import { onMounted } from 'vue'
import { liquidacaoStore } from 'stores/liquidacao'
import SelectPessoa from 'components/selects/SelectPessoa.vue'
import SelectPdv from 'components/selects/SelectPdv.vue'
import SelectUsuario from 'components/selects/SelectUsuario.vue'
import SelectPortador from 'components/selects/SelectPortador.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgInputData from '@components/MgInputData.vue'
const sLiquidacao = liquidacaoStore()

onMounted(() => {
  sLiquidacao.inicializaFiltro()
})
</script>
<template>
  <q-list>
    <q-item-label header>Filtro </q-item-label>

    <!-- PDV -->
    <q-item>
      <q-item-section>
        <select-pdv outlined v-model="sLiquidacao.filtro.codpdv" label="PDV" clearable />
      </q-item-section>
    </q-item>

    <!-- USUARIO -->
    <q-item>
      <q-item-section>
        <select-usuario
          outlined
          v-model="sLiquidacao.filtro.codusuariocriacao"
          :somente-ativos="false"
          label="Usuário"
          clearable
        />
      </q-item-section>
    </q-item>

    <!-- PORTADOR -->
    <q-item>
      <q-item-section>
        <select-portador
          outlined
          v-model="sLiquidacao.filtro.codportador"
          :somente-ativos="false"
          label="Portador"
          clearable
        />
      </q-item-section>
    </q-item>

    <!-- CODPAGAMENTO -->
    <q-item>
      <q-item-section>
        <MgInputValor
          :decimals="0"
          :min="1"
          :grouping="false"
          v-model="sLiquidacao.filtro.codpagamento"
          label="# Pagamento (ou liquidação antiga)"
        />
      </q-item-section>
    </q-item>

    <!-- DATA_DE -->
    <q-item>
      <q-item-section>
        <MgInputData
          type="timestamp"
          :seconds="false"
          v-model="sLiquidacao.filtro.lancamento_de"
          label="De"
        />
      </q-item-section>
    </q-item>

    <!-- DATA_ATE -->
    <q-item>
      <q-item-section>
        <MgInputData
          type="timestamp"
          :seconds="false"
          v-model="sLiquidacao.filtro.lancamento_ate"
          label="Até"
        />
      </q-item-section>
    </q-item>

    <!-- PESSOA -->
    <q-item>
      <q-item-section>
        <select-pessoa outlined v-model="sLiquidacao.filtro.codpessoa" label="Pessoa" clearable />
      </q-item-section>
    </q-item>

    <!-- SENTIDO -->
    <q-item>
      <q-item-section>
        <q-select
          outlined
          v-model="sLiquidacao.filtro.sentido"
          label="Sentido"
          clearable
          :options="sLiquidacao.opcoes.sentido"
          map-options
          emit-value
        />
      </q-item-section>
    </q-item>

    <!-- MEIO -->
    <q-item>
      <q-item-section>
        <q-select
          outlined
          v-model="sLiquidacao.filtro.meio"
          multiple
          label="Meio"
          clearable
          :options="sLiquidacao.opcoes.meio"
          map-options
          emit-value
        />
      </q-item-section>
    </q-item>

    <!-- VALOR -->
    <q-item>
      <q-item-section>
        <div class="row q-col-gutter-sm">
          <MgInputValor
            :min="0.01"
            v-model="sLiquidacao.filtro.valor_de"
            :max="sLiquidacao.filtro.valor_ate"
            label="Valor de"
            class="col-6"
            prefix="R$"
          />
          <MgInputValor
            :min="sLiquidacao.filtro.valor_de > 0 ? sLiquidacao.filtro.valor_de : 0.01"
            v-model="sLiquidacao.filtro.valor_ate"
            label="até"
            class="col-6"
            prefix="R$"
          />
        </div>
      </q-item-section>
    </q-item>

    <!-- FIM -->
  </q-list>
</template>
