<script setup>
import { computed } from 'vue'
import { Notify } from 'quasar'
import {
  sincronizacaoStore,
  DISPOSITIVO_NAO_CADASTRADO,
  DISPOSITIVO_INATIVO,
} from 'stores/sincronizacao'
import { useAuth } from 'src/composables/useAuth'
import DialogSincronizacao from './DialogSincronizacao.vue'
import moment from 'moment'

moment.locale('pt-br')
const sSinc = sincronizacaoStore()
const { estaAutenticado, expiresAt } = useAuth()

const erro = (message) =>
  Notify.create({
    type: 'negative',
    message,
    timeout: 0,
    actions: [{ icon: 'close', color: 'white' }],
  })

// Sincronizar só sincroniza (TASK-46): sem login o botão fica desabilitado; com a sessão
// vencida, com o navegador sem cadastro ou com o dispositivo inativo, só avisa e não abre
// janela nenhuma. O cadastro e a ativação ficam no Meu Dispositivo.
const abrirSincronizacao = () => {
  if (expiresAt.value && new Date(expiresAt.value) < new Date()) {
    erro('Sua sessão expirou. Entre de novo para sincronizar.')
    return
  }
  if (!sSinc.pdv.codpdv) {
    erro(DISPOSITIVO_NAO_CADASTRADO)
    return
  }
  if (sSinc.pdv.inativo) {
    erro(DISPOSITIVO_INATIVO)
    return
  }
  sSinc.importacao.dialog = true
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
    :disable="!estaAutenticado"
    :percentage="sSinc.importacao.progresso"
    @click="abrirSincronizacao"
    class="q-mr-sm"
    :color="btnSincronizarColor"
  >
    <template v-slot:loading>
      <q-spinner-dots />
    </template>
    <q-tooltip class="bg-accent">
      <template v-if="!estaAutenticado">Entre com seu usuário para sincronizar</template>
      <template v-else-if="!sSinc.ultimaSincronizacao.completa">
        Sem Registro de Sincronização
      </template>
      <template v-else>
        Ultima Sincronização
        {{ moment(sSinc.ultimaSincronizacao.completa).fromNow() }}
      </template>
    </q-tooltip>
  </q-btn>
</template>
