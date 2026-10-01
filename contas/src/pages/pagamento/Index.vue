<script setup>
// Pagamentos (M6.1 doc-3): a listagem única, com venda, títulos, transferência e avulso
import MgPagamentoLista from '@components/MgPagamentoLista.vue'
import { abrirPdf } from 'src/utils/abrirPdf'
import { configurarPagamentoLista } from 'src/utils/pagamentoLista'

const store = configurarPagamentoLista()

function abrirRelatorio() {
  abrirPdf('v1/pagamento/relatorio', store.parametros(), { title: 'Pagamentos' })
}
</script>

<template>
  <q-page>
    <div class="q-pa-md" style="max-width: 1086px; margin: auto">
      <MgPagamentoLista
        :to="(l) => ({ name: 'pagamento-detalhe', params: { id: l.codpagamento } })"
      />
    </div>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <div class="row q-gutter-sm items-end">
        <q-btn fab-mini color="grey-8" icon="print" @click="abrirRelatorio">
          <q-tooltip anchor="top middle" self="bottom middle">Relatório</q-tooltip>
        </q-btn>
        <q-btn fab icon="add" color="primary" :to="{ name: 'pagamento-novo' }">
          <q-tooltip anchor="top middle" self="bottom middle">Receber ou Pagar Títulos</q-tooltip>
        </q-btn>
      </div>
    </q-page-sticky>
  </q-page>
</template>
