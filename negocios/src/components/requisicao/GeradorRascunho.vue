<script setup>
import { useQuasar } from 'quasar'
import { requisicaoGeradorStore } from 'stores/requisicaoGerador'

// Os dois botões do rascunho no cabeçalho das telas do gerador (TASK-258): Revisar abre a
// revisão, de onde saem as requisições; Limpar só limpa.
const $q = useQuasar()
const sGerador = requisicaoGeradorStore()

function limpar() {
  $q.dialog({
    title: 'Limpar rascunho',
    message: `Apagar as ${sGerador.totalItens} quantidades do rascunho do ${sGerador.nomeLoja(sGerador.codestoquelocal)}?`,
    cancel: { label: 'Voltar', color: 'grey-8', flat: true },
    ok: { label: 'Limpar', color: 'primary', flat: true },
  }).onOk(() => sGerador.limparRascunho())
}
</script>

<template>
  <div class="row items-center no-wrap">
    <q-btn
      flat
      round
      size="sm"
      color="grey-7"
      icon="delete_sweep"
      :disable="!sGerador.totalItens"
      @click="limpar"
    >
      <q-tooltip>Limpar rascunho</q-tooltip>
    </q-btn>
    <q-btn
      flat
      color="primary"
      icon="checklist"
      :label="`Revisar (${sGerador.totalItens})`"
      :disable="!sGerador.totalItens"
      :to="`/requisicao/gerador/${sGerador.codestoquelocal}/revisao`"
    />
  </div>
</template>
