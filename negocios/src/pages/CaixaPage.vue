<script setup>
// Caixa do PDV (M9; tela única desde o M13 doc-3): a gaveta deste PDV na mesma tela do contas —
// abrir e fechar contando cédulas, moedas e itens, itens dos parceiros, avulsos, transferências
// e o borderô na impressora do PDV.
import { onMounted } from 'vue'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgCaixaSessao from '@components/MgCaixaSessao.vue'
import { caixaStore } from 'stores/caixa'
import { negocioStore } from 'stores/negocio'
import { sincronizacaoStore } from 'stores/sincronizacao'

const sCaixa = caixaStore()
const sNegocio = negocioStore()
const sSinc = sincronizacaoStore()

onMounted(() => sCaixa.status())
</script>

<template>
  <q-page class="bg-grey-2">
    <div class="q-pa-md" style="max-width: 800px; margin: auto">
      <MgEmptyState v-if="!sCaixa.carregando && !sCaixa.gaveta" icon="point_of_sale">
        Este PDV não tem gaveta. Peça ao administrador para vincular a gaveta em Configurações →
        PDV.
      </MgEmptyState>
      <MgCaixaSessao
        v-else-if="sCaixa.gaveta"
        :codportador="sCaixa.gaveta.codportador"
        :codpdv="sSinc.pdv.codpdv"
        :impressora="sNegocio.padrao.impressora"
      />
    </div>
  </q-page>
</template>
