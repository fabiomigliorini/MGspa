<script setup>
// Receber Título / Pagar Vale no PDV (M6.1 doc-3): a mesma tela do contas (MgBaixaTitulos), com
// as formas do caixa, o dinheiro na gaveta do PDV e o recibo na térmica. Busca só com pessoa ou
// grupo econômico; a filial do PDV vem no filtro.
import { useRouter } from 'vue-router'
import MgBaixaTitulos from '@components/MgBaixaTitulos.vue'
import { negocioStore } from 'stores/negocio'
import { sincronizacaoStore } from 'stores/sincronizacao'
import { pagamentoStore, FORMAS_RECEBER } from 'stores/pagamento'

const router = useRouter()
const sNegocio = negocioStore()
const sSinc = sincronizacaoStore()
const sPagamento = pagamentoStore()

const pdv = sSinc.pdv.uuid

// filial e maquinetas do estoque local configurado no PDV
const contexto = () => sNegocio.contextoCobranca(sNegocio.padrao.codestoquelocal)

const finalizado = async (pags) => {
  await sPagamento.imprimirRecibo(pags.map((p) => p.codpagamento))
  router.push('/pagamento')
}
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <MgBaixaTitulos
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
