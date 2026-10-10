<script setup>
// Passo do wizard: "Já recebido" — um pagamento que já aconteceu e está sem amarração (PIX que
// caiu sem ninguém esperando, cobrança integrada que confirmou depois, a venda cancelada que
// deixou o PIX solto). Lista os não resolvidos no sentido do wizard, os da pessoa primeiro.
// Com valor travado (baixa de títulos, vale/adiantamento) amarra o valor do wizard, se o
// pagamento tiver esse tanto livre; sem trava (venda) amarra o pagamento inteiro, que precisa
// caber no que falta. Só online: a lista vem do servidor.
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
const pendentes = ref([])

const valor = computed(() => sCobranca.valor)
const codpessoa = computed(() => sCobranca.pessoa?.codpessoa ?? null)

onMounted(async () => {
  carregando.value = true
  try {
    const pdv = sCobranca.contexto?.pdv
    const params = { sentido: sCobranca.sentido }
    const { data } = pdv
      ? await api.get('/v1/pdv/pagamento/pendentes', { params: { ...params, pdv } })
      : await api.get('/v1/pagamento/pendentes', { params })
    // os da pessoa primeiro
    pendentes.value = (data.data ?? []).sort(
      (a, b) => (b.codpessoa == codpessoa.value) - (a.codpessoa == codpessoa.value),
    )
  } catch (error) {
    console.log(error)
  } finally {
    carregando.value = false
  }
})

// valor que esta forma amarra: o do wizard (travado) ou o pagamento inteiro (venda)
const amarra = (p) => (sCobranca.valorFixo ? valor.value : p.livre)

const motivo = (p) => {
  if (sCobranca.valorFixo) {
    return p.livre < valor.value ? `Só tem R$ ${formataNumero(p.livre)} livre` : null
  }
  if (p.livre < p.total) {
    return 'Já está amarrado em parte: na venda entra só o pagamento inteiro'
  }
  return p.livre > valor.value
    ? `É maior que o que falta: amarre como adiantamento e use o crédito na venda`
    : null
}

const opcoes = computed(() =>
  pendentes.value.map((p, i) => ({
    ...visualPagamento(p),
    tecla: i < 9 ? i + 1 : null,
    valor: p.codpagamento,
    label: `${p.meiodescricao} · R$ ${formataNumero(p.total)}`,
    caption: [
      formataData(p.transacao),
      p.pessoa,
      p.portador,
      p.autorizacao ? `aut. ${p.autorizacao}` : null,
      p.livre < p.total ? `livre R$ ${formataNumero(p.livre)}` : null,
    ]
      .filter(Boolean)
      .join(' · '),
    desabilitado: !!motivo(p),
    motivo: motivo(p),
    original: p,
  })),
)

const escolher = (opcao) => {
  const p = opcao.original
  emit('pagamento', {
    codpagamento: p.codpagamento,
    meio: p.meio,
    total: amarra(p),
    principal: amarra(p),
    descricao: `${opcao.label} (#${p.codpagamento})`,
  })
}

// devolve true quando consumiu a tecla
const tecla = (e) => !!listaRef.value?.tecla(e)

defineExpose({ tecla })
</script>
<template>
  <div>
    <div class="text-subtitle1 text-grey-8 q-mb-sm">
      Qual pagamento já {{ sCobranca.entrada ? 'recebido' : 'feito' }}?
    </div>
    <div v-if="!carregando && !opcoes.length" class="text-grey-6 text-italic q-pa-sm">
      Nenhum pagamento sem amarração
    </div>
    <lista-opcoes v-else ref="listaRef" :opcoes="opcoes" @escolher="escolher" />
    <q-inner-loading :showing="carregando" />
  </div>
</template>
