<script setup>
import { computed } from 'vue'
import { Notify } from 'quasar'
import {
  sincronizacaoStore,
  DISPOSITIVO_NAO_CADASTRADO,
  DISPOSITIVO_INATIVO,
} from 'stores/sincronizacao'
import DialogSincronizacao from './DialogSincronizacao.vue'
import moment from 'moment'

moment.locale('pt-br')
const sSinc = sincronizacaoStore()

const erro = (message) =>
  Notify.create({
    type: 'negative',
    message,
    timeout: 0,
    actions: [{ icon: 'close', color: 'white' }],
  })

// Sincronizar só sincroniza (TASK-46) e não pede usuário logado (TASK-197): o backend autoriza
// pelo dispositivo. Com o navegador sem cadastro ou com o dispositivo inativo, só avisa e não
// abre janela nenhuma; com outra janela sincronizando, o abrirJanela recusa. O cadastro e a
// ativação ficam no Meu Dispositivo.
const abrirSincronizacao = () => {
  if (!sSinc.pdv.codpdv) {
    erro(DISPOSITIVO_NAO_CADASTRADO)
    return
  }
  if (sSinc.pdv.inativo) {
    erro(DISPOSITIVO_INATIVO)
    return
  }
  sSinc.abrirJanela()
}

const btnSincronizarColor = computed({
  get() {
    if (!sSinc.ultimaSincronizacao.completa) {
      return 'red-4'
    }
    if (moment(sSinc.ultimaSincronizacao.completa).isAfter(moment().subtract(4, 'hours'))) {
      return null
    }
    return 'red-4'
  },
})
</script>
<template>
  <dialog-sincronizacao />
  <q-btn
    round
    dense
    flat
    icon="refresh"
    :loading="sSinc.importacao.rodando"
    :percentage="sSinc.importacao.progresso"
    @click="abrirSincronizacao"
    class="q-mr-sm"
    :color="btnSincronizarColor"
  >
    <template v-slot:loading>
      <q-spinner-dots />
    </template>
    <q-tooltip class="bg-accent">
      <template v-if="!sSinc.ultimaSincronizacao.completa">
        Sem Registro de Sincronização
      </template>
      <template v-else>
        Ultima Sincronização
        {{ moment(sSinc.ultimaSincronizacao.completa).fromNow() }}
      </template>
    </q-tooltip>
  </q-btn>
</template>
