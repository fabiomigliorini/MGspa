<script setup>
// Pagamentos deste PDV (venda, títulos, avulsos), com o detalhe num dialog: estorno (Caixa: os
// próprios nas primeiras 2 horas; Gerente: a filial) e recibo na térmica. Daqui saem os
// pagamentos avulsos do caixa: Receber Título / Pagar Vale (página própria) e Vale / Adiantamento
// (M8, dialog). A tela do PDV fica só com a venda.
import { Notify } from 'quasar'
import { pagamentoListaStore } from '@components/stores/pagamentoListaStore'
import { pagamentoStore, FORMAS_ADIANTAMENTO } from 'stores/pagamento'
import { negocioStore } from 'stores/negocio'
import MgPagamentoLista from '@components/MgPagamentoLista.vue'
import MgPagamentoDetalhe from '@components/MgPagamentoDetalhe.vue'
import MgCobrancaDialog from '@components/MgCobrancaDialog.vue'
import PixCobDialog from '@components/cobranca/PixCobDialog.vue'
import PagarMePedidoDialog from '@components/cobranca/PagarMePedidoDialog.vue'
import SaurusPedidoDialog from '@components/cobranca/SaurusPedidoDialog.vue'
import MgAdiantamentoDialog from '@components/MgAdiantamentoDialog.vue'
import { sincronizacaoStore } from 'stores/sincronizacao'

const store = pagamentoListaStore()
const sPagamento = pagamentoStore()
const sNegocio = negocioStore()
const pdv = sincronizacaoStore().pdv.uuid

const abrir = async (l) => {
  try {
    await store.carregar(l.codpagamento)
    store.dialogDetalhe = true
  } catch (error) {
    Notify.create({
      type: 'negative',
      message: error?.response?.data?.message ?? error?.message,
      timeout: 3000,
    })
  }
}

// filial e maquinetas do estoque local configurado no PDV (a filial do lançamento é a do PDV)
const contexto = () => sNegocio.contextoCobranca(sNegocio.padrao.codestoquelocal)

// lançou vale/adiantamento: recibo na térmica e a listagem mostra o pagamento novo
const adiantamentoLancado = async (pags) => {
  await sPagamento.imprimirRecibo(pags.map((p) => p.codpagamento))
  store.buscar(true)
}

const recibo = async (pag) => {
  await sPagamento.imprimirRecibo([pag.codpagamento])
  Notify.create({
    type: 'positive',
    message: 'Recibo enviado à impressora',
    color: 'green-5',
    icon: 'done',
  })
}
</script>

<template>
  <q-page>
    <div class="q-pa-md" style="max-width: 1086px; margin: auto">
      <MgPagamentoLista @abrir="abrir" />
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
      :finalizar="{ url: 'v1/pdv/titulo', extras: { pdv } }"
      :padrao="sNegocio.padrao"
      @finalizado="adiantamentoLancado"
    />
    <MgCobrancaDialog />
    <PixCobDialog :impressora="sNegocio.padrao.impressora" />
    <PagarMePedidoDialog />
    <SaurusPedidoDialog />

    <q-dialog v-model="store.dialogDetalhe">
      <q-card flat style="width: 1000px; max-width: 95vw">
        <q-card-section>
          <MgPagamentoDetalhe pode-estornar>
            <template #acoes="{ pagamento }">
              <q-btn
                v-if="
                  pagamento.estado !== 'C' &&
                  pagamento.movimentos?.length &&
                  sNegocio.padrao.impressora
                "
                flat
                round
                size="sm"
                icon="print"
                color="grey-7"
                @click="recibo(pagamento)"
              >
                <q-tooltip>Recibo na térmica</q-tooltip>
              </q-btn>
            </template>
          </MgPagamentoDetalhe>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Fechar" color="primary" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>
