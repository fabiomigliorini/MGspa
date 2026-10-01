<script setup>
// Passo do wizard: pagamento a prazo. Etapa 1 = condição (fechamento/boleto/crediário),
// etapa 2 = plano de parcelas, etapa 3 = parcelas com vencimento e valor editáveis (sugeridos
// pela condição). Emite 'parcelas'; o documento as guarda e o fechamento as transforma em títulos.
import { ref, computed, onMounted } from 'vue'
import { Notify } from 'quasar'
import { cobrancaStore } from 'stores/cobranca'
import { formataNumero } from '@components/formatters'
import MgInputData from '@components/MgInputData.vue'
import MgInputValor from '@components/MgInputValor.vue'
import { calcularParcelas } from '../../../utils/parcelamento.js'
import { CONDICAO, VISUAL, vencimentoSugerido } from '../../../utils/pagamento.js'
import ListaOpcoes from './ListaOpcoes.vue'
import moment from 'moment/min/moment-with-locales'
moment.locale('pt-br')

const emit = defineEmits(['parcelas'])

const sCobranca = cobrancaStore()

const listaRef = ref(null)
const etapa = ref('forma')
const forma = ref(null)
const plano = ref(null)
const parcelas = ref([])
const feriados = ref([])

const valor = computed(() => sCobranca.valor)
const hoje = moment().format('YYYY-MM-DD')

onMounted(async () => {
  feriados.value = sCobranca.feriados
  feriados.value = await sCobranca.carregarFeriados()
})

const FORMAS = [
  {
    tecla: 1,
    valor: CONDICAO.FECHAMENTO,
    label: 'Fechamento Mensal',
    caption: 'Financeiro cobra no fim do mês; vence no último dia útil do mês seguinte',
    ...VISUAL.fechamento,
    valorMinimo: 0,
    valorMinimoParcela: 40,
    maximoParcelas: 4,
    diasAvulsos: [],
  },
  {
    tecla: 2,
    valor: CONDICAO.BOLETO,
    label: 'Boleto',
    caption: 'Mínimo R$ 70,00 · parcela mínima R$ 100,00 · até 4x',
    ...VISUAL.boleto,
    valorMinimo: 70,
    valorMinimoParcela: 100,
    maximoParcelas: 4,
    diasAvulsos: [7, 10, 15],
  },
  {
    tecla: 3,
    valor: CONDICAO.PARCELADO,
    label: 'Crediário',
    caption: 'Mínimo R$ 30,00 · parcela mínima R$ 50,00 · até 4x',
    ...VISUAL.crediario,
    valorMinimo: 30,
    valorMinimoParcela: 50,
    maximoParcelas: 4,
    diasAvulsos: [],
  },
]

const opcoesForma = computed(() =>
  FORMAS.map((f) => {
    if (sCobranca.consumidor) {
      return { ...f, desabilitado: true, motivo: 'Informe o cliente (F10)' }
    }
    if (valor.value < f.valorMinimo) {
      return { ...f, desabilitado: true, motivo: `Mínimo R$ ${formataNumero(f.valorMinimo)}` }
    }
    return f
  }),
)

const vencimentos = (condicao, quantidade, dias) => {
  const lista = []
  for (let i = 1; i <= quantidade; i++) {
    lista.push(vencimentoSugerido(condicao, i, dias, feriados.value))
  }
  return lista
}

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
    if (f.valor === CONDICAO.FECHAMENTO) {
      label = vencimentos(f.valor, p.parcelas, 30)
        .map((d) => moment(d).format('DD/MMM'))
        .join(' / ')
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

const arredonda = (v) => Math.round((parseFloat(v) || 0) * 100) / 100

const soma = computed(() => arredonda(parcelas.value.reduce((s, p) => s + arredonda(p.valor), 0)))
const diferenca = computed(() => arredonda(valor.value - soma.value))

const escolherForma = (f) => {
  forma.value = f
  etapa.value = 'plano'
}

// plano escolhido: parcelas sugeridas, a última fecha a diferença do arredondamento
const escolherPlano = (p) => {
  plano.value = p
  const datas = vencimentos(forma.value.valor, p.parcelas, p.dias)
  const lista = datas.map((vencimento, i) => ({
    numero: i + 1,
    vencimento,
    valor: arredonda(p.valorparcela),
  }))
  lista[lista.length - 1].valor = arredonda(
    valor.value - lista.slice(0, -1).reduce((s, x) => s + x.valor, 0),
  )
  parcelas.value = lista
  etapa.value = 'parcelas'
}

// mexeu no valor de uma parcela que não é a última: a última absorve a diferença
const valorAlterado = (indice) => {
  const ultima = parcelas.value.length - 1
  if (indice === ultima) {
    return
  }
  const outras = parcelas.value.slice(0, -1).reduce((s, x) => s + arredonda(x.valor), 0)
  parcelas.value[ultima].valor = arredonda(valor.value - outras)
}

const avisar = (message) => {
  Notify.create({
    type: 'negative',
    message,
    timeout: 3000, // 3 segundos
    actions: [{ icon: 'close', color: 'white' }],
  })
}

const salvar = () => {
  for (const p of parcelas.value) {
    if (!p.vencimento || p.vencimento < hoje) {
      avisar(`Vencimento da parcela ${p.numero} inválido!`)
      return
    }
    if (!(arredonda(p.valor) > 0)) {
      avisar(`Valor da parcela ${p.numero} precisa ser maior que zero!`)
      return
    }
  }
  if (diferenca.value != 0) {
    avisar(
      `A soma das parcelas não bate com o valor (diferença R$ ${formataNumero(diferenca.value)})!`,
    )
    return
  }
  emit(
    'parcelas',
    parcelas.value.map((p) => ({
      condicao: forma.value.valor,
      numero: p.numero,
      vencimento: p.vencimento,
      valor: arredonda(p.valor),
      juros: 0,
    })),
  )
}

// devolve true quando consumiu a tecla
const tecla = (e) => {
  if (e.key === 'Escape') {
    if (etapa.value === 'parcelas') {
      etapa.value = 'plano'
      return true
    }
    if (etapa.value === 'plano') {
      etapa.value = 'forma'
      return true
    }
    return false
  }
  if (etapa.value === 'parcelas') {
    if (e.key === 'Enter') {
      salvar()
      return true
    }
    // o resto é digitação nos campos (Tab anda entre eles)
    return false
  }
  return !!listaRef.value?.tecla(e)
}

const acao = computed(() =>
  etapa.value === 'parcelas' ? { label: 'Lançar (Enter)', executar: salvar } : null,
)

defineExpose({ tecla, acao })
</script>
<template>
  <div>
    <template v-if="etapa === 'forma'">
      <lista-opcoes ref="listaRef" :opcoes="opcoesForma" @escolher="escolherForma" />
    </template>
    <template v-else-if="etapa === 'plano'">
      <lista-opcoes ref="listaRef" :opcoes="planos" @escolher="escolherPlano" />
    </template>
    <template v-else>
      <div class="text-subtitle1 text-grey-8 q-mb-md">
        {{ forma.label }} · R$ {{ formataNumero(valor) }}
      </div>
      <div class="row q-col-gutter-md">
        <template v-for="(p, i) in parcelas" :key="p.numero">
          <div class="col-6">
            <MgInputData
              v-model="p.vencimento"
              :label="`Vencimento ${p.numero}/${parcelas.length}`"
              :min="hoje"
            />
          </div>
          <div class="col-6">
            <MgInputValor
              v-model="p.valor"
              :label="`Valor ${p.numero}/${parcelas.length}`"
              prefix="R$"
              :min="0.01"
              :autofocus="i === 0"
              @update:model-value="valorAlterado(i)"
            />
          </div>
        </template>
      </div>
      <div class="text-caption q-mt-sm" :class="diferenca != 0 ? 'text-red-9' : 'text-grey-7'">
        <template v-if="diferenca != 0">
          Diferença de R$ {{ formataNumero(diferenca) }} para o valor ·
        </template>
        Tab anda entre os campos (Shift+Tab volta ao vencimento) · Enter lança
      </div>
    </template>
  </div>
</template>
