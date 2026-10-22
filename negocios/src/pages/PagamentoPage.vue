<script setup>
// Pagamentos deste PDV (venda, títulos, avulsos): a lista só navega; o detalhe é a página
// /pagamento/:id (PagamentoDetalhePage), como no contas. Daqui saem os pagamentos avulsos do caixa:
// Receber Título / Pagar Vale (página própria) e Vale / Adiantamento (M8, dialog). A tela do PDV
// fica só com a venda.
import { configurarPagamentoLista } from 'src/utils/pagamentoLista'
import { pagamentoStore, FORMAS_ADIANTAMENTO } from 'stores/pagamento'
import { negocioStore } from 'stores/negocio'
import MgPagamentoLista from '@components/MgPagamentoLista.vue'
import MgCobrancaDialog from '@components/MgCobrancaDialog.vue'
import PixCobDialog from '@components/cobranca/PixCobDialog.vue'
import PagarMePedidoDialog from '@components/cobranca/PagarMePedidoDialog.vue'
import SaurusPedidoDialog from '@components/cobranca/SaurusPedidoDialog.vue'
import MgAdiantamentoDialog from '@components/MgAdiantamentoDialog.vue'

const store = configurarPagamentoLista()
const sPagamento = pagamentoStore()
const sNegocio = negocioStore()

// filial e maquinetas do estoque local configurado no PDV (a filial do lançamento é a do PDV)
const contexto = () => sNegocio.contextoCobranca(sNegocio.padrao.codestoquelocal)

// lançou vale/adiantamento: recibo na térmica e a listagem mostra o pagamento novo
const adiantamentoLancado = async (pags) => {
  await sPagamento.imprimirRecibo(pags.map((p) => p.codpagamento))
  store.buscar(true)
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
        <q-btn
          fab-mini
          color="deep-purple-4"
          icon="payments"
          @click="sPagamento.dialogAdiantamento = true"
        >
          <q-tooltip anchor="top middle" self="bottom middle">Vale / Adiantamento</q-tooltip>
        </q-btn>
        <q-btn fab icon="add" color="primary" to="/pagamento/receber">
          <q-tooltip anchor="top middle" self="bottom middle"
            >Receber Título / Pagar Vale</q-tooltip
          >
        </q-btn>
      </div>
    </q-page-sticky>

    <MgAdiantamentoDialog
      v-model="sPagamento.dialogAdiantamento"
      :formas="FORMAS_ADIANTAMENTO"
      :contexto="contexto"
      :finalizar="{ url: 'v1/pdv/titulo' }"
      com-data
      :padrao="sNegocio.padrao"
      @finalizado="adiantamentoLancado"
    />
    <MgCobrancaDialog />
    <PixCobDialog :impressora="sNegocio.padrao.codimpressora" />
    <PagarMePedidoDialog />
    <SaurusPedidoDialog />
  </q-page>
</template>
