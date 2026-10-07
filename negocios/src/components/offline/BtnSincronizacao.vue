<script setup>
import { computed, ref } from 'vue'
import { sincronizacaoStore } from 'stores/sincronizacao'
import DialogEditarPdv from 'components/pdv/DialogEditarPdv.vue'
import DialogSincronizacao from './DialogSincronizacao.vue'
import moment from 'moment'

moment.locale('pt-br')
const sSinc = sincronizacaoStore()
const dialogCadastroPdv = ref(false)

const abrirSincronizacao = () => {
  if (!sSinc.pdv.codpdv) {
    dialogCadastroPdv.value = true
  } else {
    sSinc.importacao.dialog = true
  }
}

const cadastrarPdv = (model) => {
  sSinc.pdv.apelido = model.apelido
  sSinc.pdv.codfilial = model.codfilial
  sSinc.pdv.codsetor = model.codsetor
  sSinc.pdv.observacoes = model.observacoes
  dialogCadastroPdv.value = false
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
  <dialog-editar-pdv
    v-model="dialogCadastroPdv"
    :pdv="sSinc.pdv"
    titulo="Confirme seus dados:"
    @salvar="cadastrarPdv"
  />
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
