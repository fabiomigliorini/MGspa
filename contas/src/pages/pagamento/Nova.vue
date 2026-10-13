<script setup>
// Receber ou Pagar Títulos: a tela de baixa compartilhada com o PDV (MgBaixaTitulos), com as
// formas do financeiro (cartão com bandeira, autorização, parcelas e maquineta; banco; dinheiro
// do cofre; cheque; cartão da empresa; compensação). Gaveta de PDV não aparece. Maquinetas de
// todas as filiais (o financeiro conserta lançamento de qualquer loja).
// Com ?codpix= (vindo da tela PIX, TASK-219), usa o PIX inteiro, na data e na conta dele.
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Notify } from 'quasar'
import { api } from 'src/services/api'
import { formataNumero, formataTimestamp } from '@components/formatters'
import MgBaixaTitulos from '@components/MgBaixaTitulos.vue'
import { contextoCobranca, FORMAS_TITULOS } from 'src/utils/cobranca'

const route = useRoute()
const router = useRouter()

// o PIX que veio para ser usado
const pix = ref(null)
const pronto = ref(!route.query.codpix)

const avisar = (message) =>
  Notify.create({ type: 'negative', message, color: 'red-5', icon: 'error' })

const carregarPix = async () => {
  try {
    const { data } = await api.get(`v1/pix/${route.query.codpix}`)
    const p = data.data
    if (p.utilizacao) {
      avisar('Este PIX já foi usado.')
      return
    }
    pix.value = {
      codpix: p.codpix,
      valor: p.valor,
      descricao: [
        `PIX de R$ ${formataNumero(p.valor)}`,
        p.nome,
        p.portador,
        formataTimestamp(p.horario),
      ]
        .filter(Boolean)
        .join(' · '),
      codpessoa: p.codpessoa,
    }
  } finally {
    pronto.value = true
  }
}

onMounted(async () => {
  if (route.query.codpix) await carregarPix()
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
        :pix="pix"
        com-data
        @finalizado="finalizado"
      />
    </div>
  </q-page>
</template>
