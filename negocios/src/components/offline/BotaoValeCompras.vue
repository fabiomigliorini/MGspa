<script setup>
import { negocioStore } from 'stores/negocio'
import ValeDialog from './ValeDialog.vue'

const sNegocio = negocioStore()
</script>

<template>
  <!-- So' em natureza de venda, e que gera financeiro: o vale vira titulo de
       credito no fechamento, e sem financeiro ele nunca viraria credito nenhum.
       O dialog fica fora do v-if para a edicao de vale ja' lancado (pela
       listagem) continuar funcionando mesmo se a natureza mudar. -->
  <q-btn
    v-if="sNegocio.negocio.venda"
    round
    dense
    flat
    icon="card_giftcard"
    @click="sNegocio.abrirVale()"
  >
    <q-tooltip class="bg-accent">Vender Vale Compras</q-tooltip>
  </q-btn>
  <vale-dialog />
</template>
