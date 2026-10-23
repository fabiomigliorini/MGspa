<script setup>
// Receber Título / Pagar Vale no PDV (M6.1 doc-3): a mesma tela do contas (MgBaixaTitulos), com
// as formas do caixa, o dinheiro na gaveta do PDV e o recibo na térmica. Busca só com pessoa ou
// grupo econômico, de todas as filiais.
import { useRouter } from 'vue-router'
import MgBaixaTitulos from '@components/MgBaixaTitulos.vue'
import { negocioStore } from 'stores/negocio'
import { pagamentoStore, FORMAS_RECEBER } from 'stores/pagamento'

const router = useRouter()
const sNegocio = negocioStore()
const sPagamento = pagamentoStore()

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
          exigePessoa: true,
        }"
        :formas="FORMAS_RECEBER"
        :contexto="contexto"
        :finalizar="{ url: 'v1/pdv/pagamento' }"
        com-data
        :impressora="sNegocio.padrao.codimpressora"
        :padrao="sNegocio.padrao"
        @finalizado="finalizado"
      />
    </div>
  </q-page>
</template>
