<script setup>
// Pagamentos deste PDV (venda, títulos, avulsos), com o detalhe num dialog: estorno (Caixa: os
// próprios nas primeiras 2 horas; Gerente: a filial) e recibo na térmica.
import { Notify } from 'quasar'
import { pagamentoListaStore } from '@components/stores/pagamentoListaStore'
import { pagamentoStore } from 'stores/pagamento'
import { negocioStore } from 'stores/negocio'
import MgPagamentoLista from '@components/MgPagamentoLista.vue'
import MgPagamentoDetalhe from '@components/MgPagamentoDetalhe.vue'

const store = pagamentoListaStore()
const sPagamento = pagamentoStore()
const sNegocio = negocioStore()

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
