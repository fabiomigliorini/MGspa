<script setup>
// Passo do wizard no PDV, pagando vale/crédito do cliente: devolver no cartão ou no PIX que ele
// usou. Escolhe o pagamento original (cartão ou PIX dos últimos 12 meses, com o que ainda dá
// para devolver); o PDV só registra o cancelamento/devolução feito na maquineta ou no banco.
import { ref, computed, onMounted } from 'vue'
import { api } from 'src/services/api'
import { cobrancaStore } from '@components/stores/cobrancaStore'
import { formataData, formataNumero } from '@components/formatters'
import ListaOpcoes from './ListaOpcoes.vue'
import { visualPagamento } from './pagamento.js'

const emit = defineEmits(['pagamento'])

const sCobranca = cobrancaStore()
const listaRef = ref(null)
const carregando = ref(false)
const originais = ref([])

const valor = computed(() => sCobranca.valor)

onMounted(async () => {
  carregando.value = true
  try {
    const { data } = await api.get('/v1/pdv/pagamento/originais', {
      params: { pdv: sCobranca.contexto?.pdv, codpessoa: sCobranca.pessoa?.codpessoa },
    })
    originais.value = data.data ?? []
  } catch (error) {
    console.log(error)
  } finally {
    carregando.value = false
  }
})

const opcoes = computed(() =>
  originais.value.map((o, i) => ({
    ...visualPagamento(o),
    tecla: i < 9 ? i + 1 : null,
    valor: o.codpagamento,
    label: `${o.meiodescricao} · R$ ${formataNumero(o.total)}`,
    caption: [
      formataData(o.transacao),
      o.codnegocio ? `venda ${o.codnegocio}` : null,
      o.maquineta,
      o.autorizacao ? `aut. ${o.autorizacao}` : null,
      o.disponivel < o.total ? `resta R$ ${formataNumero(o.disponivel)}` : null,
    ]
      .filter(Boolean)
      .join(' · '),
    desabilitado: o.disponivel < valor.value,
    motivo: `Só dá para devolver R$ ${formataNumero(o.disponivel)}`,
    original: o,
  })),
)

const escolher = (opcao) => {
  emit('pagamento', {
    meio: opcao.original.meio,
    principal: valor.value,
    codpagamentoorigem: opcao.original.codpagamento,
    descricaoorigem: opcao.label,
  })
}

// devolve true quando consumiu a tecla
const tecla = (e) => !!listaRef.value?.tecla(e)

defineExpose({ tecla })
</script>
<template>
  <div>
    <div class="text-subtitle1 text-grey-8 q-mb-sm">
      Devolver R$ {{ formataNumero(valor) }} em qual pagamento?
    </div>
    <div v-if="!carregando && !opcoes.length" class="text-grey-6 text-italic q-pa-sm">
      Nenhum cartão ou PIX deste cliente nos últimos 12 meses
    </div>
    <lista-opcoes v-else ref="listaRef" :opcoes="opcoes" @escolher="escolher" />
    <q-inner-loading :showing="carregando" />
  </div>
</template>
