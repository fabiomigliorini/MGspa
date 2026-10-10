<script setup>
// Receber Título / Pagar Vale no PDV (M6.1 doc-3): a mesma tela do contas (MgBaixaTitulos), com
// as formas do caixa, o dinheiro na gaveta do PDV e o recibo na térmica. Busca só com pessoa ou
// grupo econômico; a filial do PDV vem no filtro. Com ?codpagamento= (vindo de "Pagamentos não
// resolvidos"), a baixa amarra aquele pagamento.
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Notify } from 'quasar'
import { api } from 'boot/axios'
import MgBaixaTitulos from '@components/MgBaixaTitulos.vue'
import { negocioStore } from 'stores/negocio'
import { sincronizacaoStore } from 'stores/sincronizacao'
import { pagamentoStore, FORMAS_RECEBER } from 'stores/pagamento'

const route = useRoute()
const router = useRouter()
const sNegocio = negocioStore()
const sSinc = sincronizacaoStore()
const sPagamento = pagamentoStore()

const pdv = sSinc.pdv.uuid

// filial e maquinetas do estoque local configurado no PDV
const contexto = () => sNegocio.contextoCobranca(sNegocio.padrao.codestoquelocal)

// o pagamento que veio para ser amarrado (com o que sobra livre)
const pagamento = ref(null)
const pronto = ref(!route.query.codpagamento)

onMounted(async () => {
  if (!route.query.codpagamento) return
  try {
    const { data } = await api.get('v1/pdv/pagamento/pendentes', {
      params: { pdv, codpagamento: route.query.codpagamento },
    })
    const p = data.data?.[0]
    if (p) {
      pagamento.value = {
        codpagamento: p.codpagamento,
        livre: p.livre,
        descricao: `${p.meiodescricao} #${p.codpagamento}`,
        codpessoa: p.codpessoa,
        entrada: p.entrada,
      }
    } else {
      Notify.create({
        type: 'negative',
        message: 'Este pagamento não tem nada livre para amarrar.',
        timeout: 3000,
      })
    }
  } catch (error) {
    console.log(error)
  } finally {
    pronto.value = true
  }
})

const finalizado = async (pags) => {
  await sPagamento.imprimirRecibo(pags.map((p) => p.codpagamento))
  router.push('/pagamento')
}
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <MgBaixaTitulos
        v-if="pronto"
        :pagamento="pagamento"
        :seletor="{
          endpoint: 'v1/pdv/pagamento/titulos',
          params: { pdv },
          exigePessoa: true,
          filialPadrao: sSinc.pdv.codfilial,
        }"
        :formas="FORMAS_RECEBER"
        :contexto="contexto"
        :finalizar="{ url: 'v1/pdv/pagamento', extras: { pdv } }"
        com-data
        :impressora="sNegocio.padrao.impressora"
        :padrao="sNegocio.padrao"
        @finalizado="finalizado"
      />
    </div>
  </q-page>
</template>
