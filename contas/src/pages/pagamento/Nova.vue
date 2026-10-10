<script setup>
// Receber ou Pagar Títulos: a tela de baixa compartilhada com o PDV (MgBaixaTitulos), com as
// formas do financeiro (cartão com bandeira, autorização, parcelas e maquineta; banco; dinheiro
// do cofre; cheque; cartão da empresa; compensação; o que já foi recebido). Gaveta de PDV não
// aparece. Maquinetas de todas as filiais (o financeiro conserta lançamento de qualquer loja).
// Com ?codpagamento= (vindo de "Pagamentos não resolvidos"), a baixa amarra aquele pagamento.
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Notify } from 'quasar'
import { api } from 'src/services/api'
import MgBaixaTitulos from '@components/MgBaixaTitulos.vue'
import { contextoCobranca, FORMAS_TITULOS } from 'src/utils/cobranca'

const route = useRoute()
const router = useRouter()

// o pagamento que veio para ser amarrado (com o que sobra livre)
const pagamento = ref(null)
const pronto = ref(!route.query.codpagamento)

onMounted(async () => {
  if (!route.query.codpagamento) return
  try {
    const { data } = await api.get('v1/pagamento/pendentes', {
      params: { codpagamento: route.query.codpagamento },
    })
    const p = data.data?.[0]
    if (p) {
      pagamento.value = {
        codpagamento: p.codpagamento,
        livre: p.livre,
        descricao: `${p.meiodescricao} #${p.codpagamento}`,
        codpessoa: p.codpessoa,
      }
    } else {
      Notify.create({
        type: 'negative',
        message: 'Este pagamento não tem nada livre para amarrar.',
        color: 'red-5',
        icon: 'error',
      })
    }
  } finally {
    pronto.value = true
  }
})

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
        v-if="pronto"
        :formas="FORMAS_TITULOS"
        :contexto="contextoCobranca"
        :finalizar="{ url: 'v1/pagamento', extras: {} }"
        :pagamento="pagamento"
        com-data
        @finalizado="finalizado"
      />
    </div>
  </q-page>
</template>
