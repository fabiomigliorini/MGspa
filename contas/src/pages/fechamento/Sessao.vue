<script setup>
// Caixa (sessão da gaveta) no Fechamentos: a mesma tela do PDV (M13 doc-3). O gerente fecha o
// caixa que subiu sem fechar, contando o que fica na gaveta, ou reabre o que já foi fechado.
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import MgCaixaSessao from '@components/MgCaixaSessao.vue'

const route = useRoute()
const id = computed(() => Number(route.params.id))
</script>

<template>
  <q-page>
    <div class="q-pa-md" style="max-width: 1086px; margin: auto">
      <q-btn
        flat
        round
        icon="arrow_back"
        :to="{ name: 'fechamento' }"
        aria-label="Voltar"
        class="q-mb-sm"
      />
      <MgCaixaSessao :codportadorperiodo="id" />
    </div>
  </q-page>
</template>
