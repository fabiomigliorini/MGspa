<script setup>
// Pagamento no PDV: o detalhe compartilhado (MgPagamentoDetalhe) numa página com endereço próprio,
// como no contas (TASK-216): desamarrar e cancelar (o servidor confere o papel), os recibos (o A5 e
// o cupom, com o Imprimir na térmica do PDV), o vale de crédito dos títulos e o "Referente a".
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { Notify } from 'quasar'
import { configurarPagamentoLista } from 'src/utils/pagamentoLista'
import { pagamentoStore } from 'stores/pagamento'
import { negocioStore } from 'stores/negocio'
import MgPagamentoDetalhe from '@components/MgPagamentoDetalhe.vue'

const route = useRoute()
const store = configurarPagamentoLista()
const sPagamento = pagamentoStore()
const sNegocio = negocioStore()

const loading = ref(false)
const id = computed(() => Number(route.params.id))

async function carregar() {
  if (!id.value) return
  loading.value = true
  // sem o pagamento aberto antes aparecendo enquanto carrega
  store.pagamento = null
  try {
    await store.carregar(id.value)
  } catch (error) {
    Notify.create({
      type: 'negative',
      message: error?.response?.data?.message ?? error?.message,
      timeout: 3000,
    })
  } finally {
    loading.value = false
  }
}

// Imprimir do cupom do recibo: manda para a térmica do PDV
const recibo = async (pag) => {
  if (!sNegocio.padrao.impressora) {
    Notify.create({
      type: 'negative',
      message: 'Nenhuma impressora térmica selecionada!',
      timeout: 3000,
      actions: [{ icon: 'close', color: 'white' }],
    })
    return
  }
  await sPagamento.imprimirRecibo([pag.codpagamento])
  Notify.create({
    type: 'positive',
    message: 'Recibo enviado à impressora',
    color: 'green-5',
    icon: 'done',
  })
}

onMounted(carregar)
watch(() => route.params.id, carregar)
</script>

<template>
  <q-page>
    <div class="q-pa-md" style="max-width: 1086px; margin: auto">
      <MgPagamentoDetalhe
        v-if="store.pagamento"
        pode-estornar
        :imprimir-termica="recibo"
        :imprimir-vale="sPagamento.imprimirVale"
      />
    </div>
    <q-inner-loading :showing="loading" color="primary" />
  </q-page>
</template>
