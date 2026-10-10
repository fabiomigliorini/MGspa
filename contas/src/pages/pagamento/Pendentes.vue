<script setup>
// Pagamentos não resolvidos no contas: o componente compartilhado (MgPagamentosPendentes) com o
// que é do contas — o Receber ou Pagar Títulos e o Vale / Adiantamento com as formas do
// financeiro.
import { useAuthStore } from 'src/stores/auth'
import { contextoCobranca, FORMAS_ADIANTAMENTO } from 'src/utils/cobranca'
import MgPagamentosPendentes from '@components/MgPagamentosPendentes.vue'
import MgCobrancaDialog from '@components/MgCobrancaDialog.vue'
import PixCobDialog from '@components/cobranca/PixCobDialog.vue'
import PagarMePedidoDialog from '@components/cobranca/PagarMePedidoDialog.vue'
import SaurusPedidoDialog from '@components/cobranca/SaurusPedidoDialog.vue'

const auth = useAuthStore()
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <q-item class="q-pb-md q-px-none">
        <q-item-section avatar>
          <q-btn flat round icon="arrow_back" :to="{ name: 'pagamento' }" aria-label="Voltar" />
        </q-item-section>
        <q-item-section>
          <div class="text-h5 text-grey-9">Pagamentos não resolvidos</div>
        </q-item-section>
      </q-item>

      <MgPagamentosPendentes
        endpoint="v1/pagamento"
        :rota-titulos="(p) => ({ name: 'pagamento-novo', query: { codpagamento: p.codpagamento } })"
        :rota-detalhe="(cod) => ({ name: 'pagamento-detalhe', params: { id: cod } })"
        :adiantamento="{
          formas: FORMAS_ADIANTAMENTO,
          contexto: contextoCobranca,
          finalizar: { url: 'v1/titulo/adiantamento', extras: {} },
          comData: true,
          comFilial: true,
          filialPadrao: auth.usuario?.codfilial ?? null,
        }"
      />
    </div>
    <MgCobrancaDialog />
    <PixCobDialog />
    <PagarMePedidoDialog />
    <SaurusPedidoDialog />
  </q-page>
</template>
