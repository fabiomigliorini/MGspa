<script setup>
// Passo do wizard: pagamento a prazo. Etapa 1 = forma (fechamento/boleto/crediário),
// etapa 2 = plano de parcelas. Tudo por ListaOpcoes; o foco continua no card do wizard.
import { ref, computed } from 'vue'
import { negocioStore } from 'stores/negocio'
import { formataNumero } from '@components/formatters'
import { calcularParcelas } from '../../../utils/parcelamento.js'
import ListaOpcoes from './ListaOpcoes.vue'
import moment from 'moment/min/moment-with-locales'
moment.locale('pt-br')

const emit = defineEmits(['concluido', 'voltar'])

const sNegocio = negocioStore()

const listaRef = ref(null)
const etapa = ref('forma')
const forma = ref(null)

const valor = computed(() => sNegocio.receber.valor)
const consumidor = computed(() => sNegocio.negocio.codpessoa == 1)

const FORMAS = [
  {
    tecla: 1,
    valor: parseInt(process.env.CODFORMAPAGAMENTO_FECHAMENTO),
    label: 'Fechamento Mensal',
    caption: 'Financeiro cobra no fim do mês; vence no último dia do mês seguinte',
    icone: 'calendar_month',
    tipo: 5, // Crédito Loja
    valorMinimo: 0,
    valorMinimoParcela: 40,
    maximoParcelas: 4,
    fechamento: true,
    diasAvulsos: [],
  },
  {
    tecla: 2,
    valor: parseInt(process.env.CODFORMAPAGAMENTO_BOLETO),
    label: 'Boleto',
    caption: 'Mínimo R$ 70,00 · parcela mínima R$ 100,00 · até 4x',
    icone: 'account_balance',
    tipo: 15, // Boleto Bancário
    valorMinimo: 70,
    valorMinimoParcela: 100,
    maximoParcelas: 4,
    diasAvulsos: [7, 10, 15],
  },
  {
    tecla: 3,
    valor: parseInt(process.env.CODFORMAPAGAMENTO_CARTEIRA),
    label: 'Crediário',
    caption: 'Mínimo R$ 30,00 · parcela mínima R$ 50,00 · até 4x',
    icone: 'wallet',
    tipo: 5, // Crédito Loja
    valorMinimo: 30,
    valorMinimoParcela: 50,
    maximoParcelas: 4,
    diasAvulsos: [],
  },
]

const opcoesForma = computed(() =>
  FORMAS.map((f) => {
    if (consumidor.value) {
      return { ...f, desabilitado: true, motivo: 'Informe o cliente (F10)' }
    }
    if (valor.value < f.valorMinimo) {
      return { ...f, desabilitado: true, motivo: `Mínimo R$ ${formataNumero(f.valorMinimo)}` }
    }
    return f
  }),
)

const planos = computed(() => {
  const f = forma.value
  if (!f) {
    return []
  }
  const v = valor.value
  const lista = []
  f.diasAvulsos.forEach((dias) => {
    lista.push({ parcelas: 1, valorjuros: 0, valorparcela: v, dias, label: `${dias} dias` })
  })
  calcularParcelas({
    valor: v,
    maximoParcelas: f.maximoParcelas,
    valorMinimoParcela: f.valorMinimoParcela,
  }).forEach((p) => {
    let label
    if (f.fechamento) {
      const meses = []
      for (let i = 1; i <= p.parcelas; i++) {
        meses.push(moment().add(5, 'days').add(i, 'month').endOf('month').format('MMM/YY'))
      }
      label = meses.join(' / ')
    } else {
      label = p.parcelas === 1 ? '30 dias' : `${p.parcelas}x a cada 30 dias`
    }
    lista.push({ ...p, dias: 30, label })
  })
  return lista.map((p, i) => ({
    ...p,
    tecla: i < 9 ? i + 1 : null,
    valor: i,
    caption: `${p.parcelas} parcela(s) de R$ ${formataNumero(p.valorparcela)}`,
  }))
})

const escolherForma = (f) => {
  forma.value = f
  etapa.value = 'plano'
}

const salvar = async (plano) => {
  await sNegocio.adicionarPagamento({
    codformapagamento: forma.value.valor,
    tipo: forma.value.tipo,
    valorpagamento: valor.value,
    valorjuros: plano.valorjuros || null,
    parcelas: plano.parcelas,
    valorparcela: plano.valorparcela,
    dias: plano.dias,
  })
  emit('concluido')
}

// devolve true quando consumiu a tecla
const tecla = (e) => {
  if (e.key === 'Escape') {
    if (etapa.value === 'plano') {
      etapa.value = 'forma'
      return true
    }
    return false
  }
  return !!listaRef.value?.tecla(e)
}

defineExpose({ tecla })
</script>
<template>
  <div>
    <template v-if="etapa === 'forma'">
      <lista-opcoes ref="listaRef" :opcoes="opcoesForma" @escolher="escolherForma" />
    </template>
    <template v-else>
      <lista-opcoes ref="listaRef" :opcoes="planos" @escolher="salvar" />
    </template>
  </div>
</template>
