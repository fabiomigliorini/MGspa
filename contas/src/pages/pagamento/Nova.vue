<script setup>
// Receber ou Pagar Títulos (M6.1 doc-3): a tela de baixa compartilhada com o PDV
// (MgBaixaTitulos), com as formas do financeiro (cartão com bandeira, autorização, parcelas e
// maquineta; banco; dinheiro do cofre; cheque; cartão da empresa; compensação). Gaveta de PDV não
// aparece. Maquinetas de todas as filiais (o financeiro conserta lançamento de qualquer loja).
import { useRouter } from 'vue-router'
import MgBaixaTitulos from '@components/MgBaixaTitulos.vue'
import { contextoCobranca, FORMAS_TITULOS } from 'src/utils/cobranca'

const router = useRouter()

const finalizado = (pags) =>
  router.replace({ name: 'pagamento-detalhe', params: { id: pags[0].codpagamento } })
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <q-item class="q-pb-md q-px-none">
        <q-item-section avatar>
          <q-btn flat round icon="arrow_back" :to="{ name: 'pagamento' }" aria-label="Voltar" />
        </q-item-section>
        <q-item-section>
          <div class="text-h5 text-grey-9">Receber ou Pagar Títulos</div>
        </q-item-section>
      </q-item>

      <MgBaixaTitulos
        :formas="FORMAS_TITULOS"
        :contexto="contextoCobranca"
        :finalizar="{ url: 'v1/pagamento', extras: {} }"
        com-data
        @finalizado="finalizado"
      />
    </div>
  </q-page>
</template>
