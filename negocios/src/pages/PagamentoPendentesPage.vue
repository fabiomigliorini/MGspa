<script setup>
// Pagamentos não resolvidos no PDV: o componente compartilhado (MgPagamentosPendentes) com o que
// é do PDV — o Receber Título / Pagar Vale e o Vale / Adiantamento com as formas do caixa. Só os
// portadores em que o usuário tem papel (o servidor filtra).
import MgPagamentosPendentes from '@components/MgPagamentosPendentes.vue'
import MgCobrancaDialog from '@components/MgCobrancaDialog.vue'
import PixCobDialog from '@components/cobranca/PixCobDialog.vue'
import PagarMePedidoDialog from '@components/cobranca/PagarMePedidoDialog.vue'
import SaurusPedidoDialog from '@components/cobranca/SaurusPedidoDialog.vue'
import { negocioStore } from 'stores/negocio'
import { sincronizacaoStore } from 'stores/sincronizacao'
import { FORMAS_ADIANTAMENTO } from 'stores/pagamento'

const sNegocio = negocioStore()
const pdv = sincronizacaoStore().pdv.uuid

const contexto = () => sNegocio.contextoCobranca(sNegocio.padrao.codestoquelocal)
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <MgPagamentosPendentes
        endpoint="v1/pdv/pagamento"
        :fixos="{ pdv }"
        :rota-titulos="
          (p) => ({ path: '/pagamento/receber', query: { codpagamento: p.codpagamento } })
        "
        :adiantamento="{
          formas: FORMAS_ADIANTAMENTO,
          contexto,
          finalizar: { url: 'v1/pdv/titulo', extras: { pdv } },
          comData: true,
          padrao: sNegocio.padrao,
        }"
      />
    </div>
    <MgCobrancaDialog />
    <PixCobDialog :impressora="sNegocio.padrao.impressora" />
    <PagarMePedidoDialog />
    <SaurusPedidoDialog />
  </q-page>
</template>
