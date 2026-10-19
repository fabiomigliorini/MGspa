<script setup>
// Receber ou pagar títulos, a mesma tela no contas e no PDV: seleciona os títulos (capital,
// multa, juros, desconto) e paga com UM pagamento (uma baixa = um pagamento, conceito do Fábio de
// 09/10/2026). O FAB abre o diálogo com data, pessoa e observação; "Receber"/"Pagar" abre o wizard
// com o valor do líquido travado e a forma confirmada nele grava na hora (a integrada, quando o
// banco/maquineta confirma). Títulos que se anulam não pedem forma: é o encontro de contas. Cada
// app informa onde busca os títulos, as formas, o contexto do wizard (PDV ou contas) e para onde
// manda a baixa. Com `pix` (vindo da tela PIX, TASK-219), a baixa usa o PIX que caiu na conta:
// inteiro, na data e na conta dele. Com `inicial` (o Duplicar do contas, TASK-236), abre com os
// títulos, valores, pessoa, data, "referente a" e observação de outro pagamento; a forma se escolhe
// de novo.
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { Notify } from 'quasar'
import { baixaTitulosStore } from '@components/stores/baixaTitulosStore'
import { formataNumero } from '@components/formatters'
import { agora } from '@components/utils/datas'
import MgPagamentoForm from '@components/MgPagamentoForm.vue'
import MgSeletorTitulosAbertos from '@components/MgSeletorTitulosAbertos.vue'
import MgCobrancaDialog from '@components/MgCobrancaDialog.vue'
import PixCobDialog from '@components/cobranca/PixCobDialog.vue'
import PagarMePedidoDialog from '@components/cobranca/PagarMePedidoDialog.vue'
import SaurusPedidoDialog from '@components/cobranca/SaurusPedidoDialog.vue'

const props = defineProps({
  // busca dos títulos: { endpoint, params, exigePessoa, filialPadrao } (MgSeletorTitulosAbertos)
  seletor: {
    type: Object,
    default: () => ({}),
  },
  // formas do wizard: { entrada: [...], saida: [...] }
  formas: {
    type: Object,
    required: true,
  },
  // async (codfilial dos títulos) => contexto do wizard (pdv, codfilial, maquinetas, portadores)
  contexto: {
    type: Function,
    required: true,
  },
  // configuração do PDV: maquineta e conta PIX padrão
  padrao: {
    type: Object,
    default: () => ({}),
  },
  // para onde vai a baixa: { url, extras }
  finalizar: {
    type: Object,
    required: true,
  },
  // data e hora da baixa
  comData: {
    type: Boolean,
    default: false,
  },
  // impressora térmica do QR do PIX (PDV)
  impressora: {
    type: String,
    default: null,
  },
  // o PIX sem uso (vindo da tela PIX): { codpix, valor, descricao, codpessoa }
  pix: {
    type: Object,
    default: null,
  },
  // a baixa já preenchida (Duplicar): { titulos (no formato do seletor), codpessoa, transacao,
  // referente, observacao }
  inicial: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['finalizado'])

const sBaixa = baixaTitulosStore()

const titulos = ref(props.inicial?.titulos ?? [])
const codpessoaFiltro = ref(props.pix?.codpessoa ?? props.inicial?.codpessoa ?? null)
const codpessoa = ref(props.inicial?.codpessoa ?? null)
const transacao = ref(props.inicial?.transacao ?? agora(false))
// a data só fica parada se a pessoa mexeu nela (ou se veio do pagamento duplicado)
const transacaoMexida = ref(!!props.inicial?.transacao)
const referente = ref(props.inicial?.referente ?? '')
const observacao = ref(props.inicial?.observacao ?? '')
// data, pessoa, "referente a" e observação ficam no diálogo do FAB, à vista com a lista rolada
const dialog = ref(false)

// usando o PIX: títulos a receber somando o valor dele (o PIX entra inteiro; maior que os
// títulos, lança como adiantamento)
const problemaPix = computed(() => {
  if (!props.pix || !titulos.value.length || sBaixa.compensacao) return null
  if (!sBaixa.entrada) {
    return 'O PIX é uma entrada de dinheiro: selecione títulos a receber.'
  }
  if (Math.abs(sBaixa.totalLiquido - props.pix.valor) > 0.005) {
    return `Os títulos somam R$ ${formataNumero(sBaixa.totalLiquido)} e o PIX é de R$ ${formataNumero(props.pix.valor)}: precisam somar o valor dele. Se ele for maior, lance como adiantamento.`
  }
  return null
})

// usando o PIX: quanto falta (positivo) ou passou (negativo) para os títulos somarem o valor
// dele; à vista ao lado do FAB, que só habilita quando bate
const faltaPix = computed(() => {
  if (!props.pix) return null
  return Math.round((props.pix.valor - sBaixa.liquido) * 100) / 100
})
const situacaoPix = computed(() => {
  if (faltaPix.value === null) return null
  if (faltaPix.value > 0.005) {
    return { cor: 'orange-8', texto: `Falta R$ ${formataNumero(faltaPix.value)} do PIX` }
  }
  if (faltaPix.value < -0.005) {
    return { cor: 'red-5', texto: `Passou R$ ${formataNumero(-faltaPix.value)} do PIX` }
  }
  return { cor: 'green-5', texto: 'Bate com o PIX' }
})
const pixNaoBate = computed(() => faltaPix.value !== null && Math.abs(faltaPix.value) > 0.005)

const descricao = computed(() => {
  if (sBaixa.compensacao) return 'Encontro de contas'
  return sBaixa.entrada ? 'Receber' : 'Pagar'
})

// foco ao abrir o diálogo: sem pessoa, na pessoa; com ela, no Receber/Pagar, ou no Gravar quando
// não há wizard (encontro de contas, PIX) ou a forma já foi escolhida
const temWizard = computed(() => !sBaixa.compensacao && !props.pix)
const foco = computed(() => {
  if (!codpessoa.value) return 'pessoa'
  return temWizard.value && !sBaixa.forma ? 'wizard' : 'gravar'
})

const notificar = (type, message) =>
  Notify.create({
    type,
    message,
    color: type === 'positive' ? 'green-5' : 'red-5',
    icon: type === 'positive' ? 'done' : 'error',
    timeout: 3000,
  })

// o PIX vira a forma, com o valor do líquido
const aplicarPix = () => {
  if (!props.pix || sBaixa.compensacao || !titulos.value.length) {
    return
  }
  sBaixa.adicionar({
    codpix: props.pix.codpix,
    total: sBaixa.totalLiquido,
    descricao: props.pix.descricao,
  })
}

// títulos escolhidos: a pessoa é a deles quando é uma só (várias ou nenhuma: vazia), refeita só
// quando mudam as pessoas dos títulos (editar juros ou total não apaga a escolhida à mão);
// mudar a seleção ou os valores recomeça a forma
const pessoasDe = (lista) => [...new Set(lista.map((t) => t.codpessoa).filter(Boolean))].sort()
// o duplicado já vem com a pessoa dele: só muda se mudarem as pessoas dos títulos
let pessoasDosTitulos = pessoasDe(titulos.value).join(',')
watch(
  titulos,
  (lista) => {
    if (sBaixa.forma && !props.pix) {
      Notify.create({
        message: 'Seleção mudou: escolha a forma de novo.',
        color: 'grey-8',
        icon: 'info',
        timeout: 3000,
      })
    }
    const pessoas = pessoasDe(lista)
    if (pessoas.join(',') !== pessoasDosTitulos) {
      pessoasDosTitulos = pessoas.join(',')
      codpessoa.value = pessoas.length === 1 ? pessoas[0] : null
    }
    sBaixa.iniciar({
      pessoa: { codpessoa: codpessoa.value },
      titulos: lista.map((t) => ({ ...t })),
    })
    aplicarPix()
  },
  { deep: true },
)

watch(codpessoa, (v) => {
  if (sBaixa.pessoa) sBaixa.pessoa.codpessoa = v
})

// abre o diálogo com a hora de agora, se ninguém mexeu na data
const abrirDialog = () => {
  if (!transacaoMexida.value) {
    transacao.value = agora(false)
  }
  dialog.value = true
}

const mudouData = (v) => {
  transacao.value = v
  transacaoMexida.value = true
}

const abrirWizard = async () => {
  if (!codpessoa.value) {
    notificar('negative', 'Selecione a pessoa!')
    return
  }
  sBaixa.abrirWizard({
    formas: props.formas,
    contexto: await props.contexto(titulos.value[0]?.codfilial ?? null),
    padrao: props.padrao,
  })
}

const gravar = async () => {
  if (!codpessoa.value) {
    notificar('negative', 'Selecione a pessoa!')
    return
  }
  const pags = await sBaixa.finalizar(props.finalizar.url, {
    ...props.finalizar.extras,
    // o PIX tem a data dele (o fato)
    ...(props.comData && !props.pix ? { transacao: transacao.value } : {}),
    referente: referente.value || null,
    observacao: observacao.value || null,
  })
  if (!pags) {
    // não gravou: volta a escolher a forma; o dinheiro que já entrou (o PIX, o cartão confirmado
    // na maquininha) fica para o Gravar
    if (!sBaixa.formaConfirmada) sBaixa.remover()
    return
  }
  notificar(
    'positive',
    { Receber: 'Recebimento registrado', Pagar: 'Pagamento registrado' }[descricao.value] ??
      'Encontro de contas registrado',
  )
  dialog.value = false
  sBaixa.iniciar({ pessoa: null, titulos: [] })
  emit('finalizado', pags)
}

// a forma chegou do wizard (ou a integrada confirmada pelo banco/maquineta): grava na hora;
// encontro de contas e o PIX seguem no Gravar
watch(
  () => sBaixa.forma,
  (forma) => {
    if (dialog.value && forma && temWizard.value) gravar()
  },
)

onMounted(() =>
  sBaixa.iniciar({
    pessoa: codpessoa.value ? { codpessoa: codpessoa.value } : null,
    titulos: titulos.value.map((t) => ({ ...t })),
  }),
)
onUnmounted(() => sBaixa.iniciar({ pessoa: null, titulos: [] }))
</script>

<template>
  <div>
    <MgSeletorTitulosAbertos
      v-model="titulos"
      :codpessoa-inicial="codpessoaFiltro"
      :endpoint="seletor.endpoint"
      :params="seletor.params"
      :exige-pessoa="!!seletor.exigePessoa"
      :filial-padrao="seletor.filialPadrao ?? null"
      class="q-mb-md"
      @update:codpessoa="(v) => (codpessoaFiltro = v)"
    />

    <q-dialog v-model="dialog">
      <MgPagamentoForm
        :titulo="descricao"
        :valor="sBaixa.totalLiquido"
        :com-data="comData && !pix"
        :foco-pessoa="foco === 'pessoa'"
        :transacao="transacao"
        @update:transacao="mudouData"
        v-model:codpessoa="codpessoa"
        v-model:referente="referente"
        v-model:observacao="observacao"
        @submit="gravar"
      >
        <template #rodape>
          <q-list v-if="sBaixa.forma" separator>
            <q-item>
              <q-item-section>{{ sBaixa.forma.descricao }}</q-item-section>
              <q-item-section side>
                R$ {{ formataNumero(sBaixa.forma.total) }}
                <span v-if="sBaixa.forma.valortroco" class="text-caption">
                  troco R$ {{ formataNumero(sBaixa.forma.valortroco) }}
                </span>
              </q-item-section>
            </q-item>
          </q-list>
          <q-card-section v-if="problemaPix" class="text-caption text-negative">
            {{ problemaPix }}
          </q-card-section>
        </template>
        <template #acoes>
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn
            v-if="temWizard"
            flat
            :label="descricao"
            color="primary"
            :disable="sBaixa.finalizando"
            :autofocus="foco === 'wizard'"
            @click="abrirWizard"
          />
          <q-btn
            flat
            label="Gravar"
            type="submit"
            color="primary"
            :autofocus="foco === 'gravar'"
            :disable="!sBaixa.pronto || !!problemaPix"
            :loading="sBaixa.finalizando"
          />
        </template>
      </MgPagamentoForm>
    </q-dialog>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <div class="row items-center no-wrap">
        <q-chip v-if="situacaoPix" :color="situacaoPix.cor" text-color="white" class="q-mr-sm">
          {{ situacaoPix.texto }}
        </q-chip>
        <q-btn
          fab
          :icon="sBaixa.compensacao ? 'sync_alt' : 'payments'"
          color="primary"
          :disable="!titulos.length || sBaixa.finalizando || pixNaoBate"
          :loading="sBaixa.finalizando"
          @click="abrirDialog"
        >
          <q-tooltip>{{ descricao }}</q-tooltip>
        </q-btn>
      </div>
    </q-page-sticky>

    <MgCobrancaDialog />
    <PixCobDialog :impressora="impressora" />
    <PagarMePedidoDialog />
    <SaurusPedidoDialog />
  </div>
</template>
