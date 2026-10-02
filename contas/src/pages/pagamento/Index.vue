<script setup>
// Pagamentos (M6.1 doc-3): a listagem única, com venda, títulos, transferência e avulso. Daqui
// saem o Receber ou Pagar Títulos (página própria) e o Vale / Adiantamento (M8, o mesmo dialog
// do PDV, com as formas do financeiro: depósito, transferência, cofre, cartão da empresa...).
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import MgPagamentoLista from '@components/MgPagamentoLista.vue'
import MgAdiantamentoDialog from '@components/MgAdiantamentoDialog.vue'
import MgCobrancaDialog from '@components/MgCobrancaDialog.vue'
import PixCobDialog from '@components/cobranca/PixCobDialog.vue'
import PagarMePedidoDialog from '@components/cobranca/PagarMePedidoDialog.vue'
import SaurusPedidoDialog from '@components/cobranca/SaurusPedidoDialog.vue'
import { abrirPdf } from 'src/utils/abrirPdf'
import { configurarPagamentoLista } from 'src/utils/pagamentoLista'
import { contextoCobranca, FORMAS_ADIANTAMENTO } from 'src/utils/cobranca'
import { useAuthStore } from 'src/stores/auth'

const store = configurarPagamentoLista()
const router = useRouter()
const auth = useAuthStore()

const dialogAdiantamento = ref(false)

function abrirRelatorio() {
  abrirPdf('v1/pagamento/relatorio', store.parametros(), { title: 'Pagamentos' })
}

const adiantamentoLancado = (pags) =>
  router.push({ name: 'pagamento-detalhe', params: { id: pags[0].codpagamento } })
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
        <q-btn fab-mini color="deep-purple-4" icon="payments" @click="dialogAdiantamento = true">
          <q-tooltip anchor="top middle" self="bottom middle">Vale / Adiantamento</q-tooltip>
        </q-btn>
        <q-btn fab icon="add" color="primary" :to="{ name: 'pagamento-novo' }">
          <q-tooltip anchor="top middle" self="bottom middle">Receber ou Pagar Títulos</q-tooltip>
        </q-btn>
      </div>
    </q-page-sticky>

    <MgAdiantamentoDialog
      v-model="dialogAdiantamento"
      :formas="FORMAS_ADIANTAMENTO"
      :contexto="contextoCobranca"
      :finalizar="{ url: 'v1/titulo/adiantamento', extras: {} }"
      com-data
      com-filial
      :filial-padrao="auth.usuario?.codfilial ?? null"
      @finalizado="adiantamentoLancado"
    />
    <MgCobrancaDialog />
    <PixCobDialog />
    <PagarMePedidoDialog />
    <SaurusPedidoDialog />
  </q-page>
</template>
