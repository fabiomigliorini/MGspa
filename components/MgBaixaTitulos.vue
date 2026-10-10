<script setup>
// Receber ou pagar títulos, a mesma tela no contas e no PDV: seleciona os títulos (capital,
// multa, juros, desconto) e paga com UM pagamento (uma baixa = um pagamento, conceito do Fábio de
// 09/10/2026). O FAB abre o diálogo com data, pessoa e observação; "Receber"/"Pagar" abre o wizard
// com o valor do líquido travado, a forma escolhida aparece no diálogo e "Gravar" grava. Títulos
// que se anulam não pedem forma: é o encontro de contas. Cada app informa onde busca os títulos,
// as formas, o contexto do wizard (PDV ou contas) e para onde manda a baixa. Com `pagamento`, a
// baixa amarra um pagamento que já existe (vindo de "Pagamentos não resolvidos").
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { Notify } from 'quasar'
import { baixaTitulosStore } from '@components/stores/baixaTitulosStore'
import { formataNumero, formataTimestampIso } from '@components/formatters'
import MgInput from '@components/MgInput.vue'
import MgInputData from '@components/MgInputData.vue'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'
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
  // pagamento que já existe, para amarrar: { codpagamento, livre, descricao, codpessoa }
  pagamento: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['finalizado'])

const sBaixa = baixaTitulosStore()

const agora = () => formataTimestampIso(new Date()).slice(0, 16)

const titulos = ref([])
const codpessoaFiltro = ref(props.pagamento?.codpessoa ?? null)
const codpessoa = ref(null)
const transacao = ref(agora())
// a data só fica parada se a pessoa mexeu nela
const transacaoMexida = ref(false)
const observacao = ref('')
// data, pessoa e observação ficam no diálogo do FAB, à vista com a lista rolada
const dialog = ref(false)

const descricao = computed(() => {
  if (sBaixa.compensacao) return 'Encontro de contas'
  return sBaixa.entrada ? 'Receber' : 'Pagar'
})

const notificar = (type, message) =>
  Notify.create({
    type,
    message,
    color: type === 'positive' ? 'green-5' : 'red-5',
    icon: type === 'positive' ? 'done' : 'error',
    timeout: 3000,
  })

// o pagamento que veio para ser amarrado vira a forma, com o valor do líquido
const aplicarPagamento = () => {
  if (!props.pagamento || sBaixa.compensacao || !titulos.value.length) {
    return
  }
  sBaixa.adicionar({
    codpagamento: props.pagamento.codpagamento,
    total: sBaixa.totalLiquido,
    descricao: props.pagamento.descricao,
  })
}

// títulos escolhidos: a pessoa é a deles quando é uma só (várias ou nenhuma: vazia), refeita só
// quando mudam as pessoas dos títulos (editar juros ou total não apaga a escolhida à mão);
// mudar a seleção ou os valores recomeça a forma
let pessoasDosTitulos = ''
watch(
  titulos,
  (lista) => {
    if (sBaixa.forma && !props.pagamento) {
      notificar('negative', 'Seleção mudou: escolha a forma de novo.')
    }
    const pessoas = [...new Set(lista.map((t) => t.codpessoa).filter(Boolean))].sort()
    if (pessoas.join(',') !== pessoasDosTitulos) {
      pessoasDosTitulos = pessoas.join(',')
      codpessoa.value = pessoas.length === 1 ? pessoas[0] : null
    }
    sBaixa.iniciar({
      pessoa: { codpessoa: codpessoa.value },
      titulos: lista.map((t) => ({ ...t })),
    })
    aplicarPagamento()
  },
  { deep: true },
)

watch(codpessoa, (v) => {
  if (sBaixa.pessoa) sBaixa.pessoa.codpessoa = v
})

// abre o diálogo com a hora de agora, se ninguém mexeu na data
const abrirDialog = () => {
  if (!transacaoMexida.value) {
    transacao.value = agora()
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
    // o pagamento que já existe tem a data dele (o fato)
    ...(props.comData && !props.pagamento ? { transacao: transacao.value } : {}),
    observacao: observacao.value || null,
  })
  if (!pags) return
  notificar(
    'positive',
    { Receber: 'Recebimento registrado', Pagar: 'Pagamento registrado' }[descricao.value] ??
      'Encontro de contas registrado',
  )
  dialog.value = false
  sBaixa.iniciar({ pessoa: null, titulos: [] })
  emit('finalizado', pags)
}

onMounted(() => sBaixa.iniciar({ pessoa: null, titulos: [] }))
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
      <q-card flat style="width: 600px; max-width: 90vw">
        <q-form @submit.prevent="gravar">
          <q-card-section class="text-grey-9 text-overline row items-center">
            {{ descricao.toUpperCase() }}
            <q-space />
            <span class="text-h6">R$ {{ formataNumero(sBaixa.totalLiquido) }}</span>
          </q-card-section>
          <q-separator inset />
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-xs-12 col-sm-4" v-if="comData && !pagamento">
                <MgInputData
                  :model-value="transacao"
                  @update:model-value="mudouData"
                  type="timestamp"
                  default-time="keep"
                  :seconds="false"
                  label="Data"
                  autofocus
                  :bottom-slots="false"
                />
              </div>
              <div class="col-xs-12" :class="comData && !pagamento ? 'col-sm-8' : ''">
                <MgSelectPessoa
                  v-model="codpessoa"
                  label="Pessoa"
                  :autofocus="!comData || !!pagamento"
                  :bottom-slots="false"
                />
              </div>
              <div class="col-12">
                <MgInput
                  v-model="observacao"
                  label="Observação"
                  maxlength="300"
                  :bottom-slots="false"
                />
              </div>
            </div>
          </q-card-section>
          <q-list v-if="sBaixa.forma" separator>
            <q-item>
              <q-item-section>{{ sBaixa.forma.descricao }}</q-item-section>
              <q-item-section side>
                R$ {{ formataNumero(sBaixa.forma.total) }}
                <span v-if="sBaixa.forma.valortroco" class="text-caption">
                  troco R$ {{ formataNumero(sBaixa.forma.valortroco) }}
                </span>
              </q-item-section>
              <q-item-section side>
                <q-btn
                  v-if="!sBaixa.forma.codpagamento"
                  flat
                  round
                  size="sm"
                  icon="close"
                  color="grey-7"
                  @click="sBaixa.remover()"
                >
                  <q-tooltip>Trocar a forma</q-tooltip>
                </q-btn>
              </q-item-section>
            </q-item>
          </q-list>
          <q-card-section v-if="pagamento" class="text-caption text-grey-7">
            Amarrando o pagamento #{{ pagamento.codpagamento }} (livre R$
            {{ formataNumero(pagamento.livre) }})
          </q-card-section>
          <q-separator inset />
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
            <q-btn
              v-if="!sBaixa.compensacao && !pagamento"
              flat
              :label="descricao"
              color="primary"
              :disable="sBaixa.finalizando"
              @click="abrirWizard"
            />
            <q-btn
              flat
              label="Gravar"
              type="submit"
              color="primary"
              :disable="!sBaixa.pronto"
              :loading="sBaixa.finalizando"
            />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn
        fab
        :icon="sBaixa.compensacao ? 'sync_alt' : 'payments'"
        color="primary"
        :disable="!titulos.length || sBaixa.finalizando"
        :loading="sBaixa.finalizando"
        @click="abrirDialog"
      >
        <q-tooltip>{{ descricao }}</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <MgCobrancaDialog />
    <PixCobDialog :impressora="impressora" />
    <PagarMePedidoDialog />
    <SaurusPedidoDialog />
  </div>
</template>
