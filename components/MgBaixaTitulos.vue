<script setup>
// Receber ou pagar títulos, a mesma tela no contas e no PDV (M6.1 do plano doc-3): seleciona os
// títulos (capital, multa, juros, desconto) e paga pelo wizard de cobrança, uma forma por
// pagamento. Quando as formas fecham o líquido, grava. Cada app informa onde busca os títulos,
// as formas, o contexto do wizard (PDV ou contas) e para onde manda a baixa. A data tem hora
// (TASK-204: a data manda no período do portador); sem mexer nela, vai vazia e o servidor usa agora.
// O FAB abre o diálogo com data, pessoa e observação; o botão dele abre o wizard por cima, e o
// diálogo mostra o que já foi lançado e quanto falta.
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
  // para onde vai a baixa: { url, extras }
  finalizar: {
    type: Object,
    required: true,
  },
  // data e hora da baixa (sem ela, agora)
  comData: {
    type: Boolean,
    default: false,
  },
  // impressora térmica do QR do PIX (PDV)
  impressora: {
    type: String,
    default: null,
  },
})

const emit = defineEmits(['finalizado'])

const sBaixa = baixaTitulosStore()

const titulos = ref([])
const codpessoaFiltro = ref(null)
const codpessoa = ref(null)
// a data de quando abriu a tela; sem mexer, vai vazia (agora, no servidor)
const transacaoAbertura = formataTimestampIso(new Date()).slice(0, 16)
const transacao = ref(transacaoAbertura)
const observacao = ref('')
// data, pessoa e observação ficam no diálogo do FAB, à vista com a lista rolada
const dialog = ref(false)

const lancou = computed(() => sBaixa.pagamentos.length > 0)
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

// títulos escolhidos: a pessoa sugerida é a deles, se for uma só; mudar a seleção recomeça
watch(
  titulos,
  (lista) => {
    if (lancou.value) {
      notificar('negative', 'Seleção mudou: as formas lançadas foram descartadas.')
    }
    const pessoas = new Set(lista.map((t) => t.codpessoa).filter(Boolean))
    if (pessoas.size === 1) {
      codpessoa.value = [...pessoas][0]
    }
    sBaixa.iniciar({
      pessoa: { codpessoa: codpessoa.value },
      titulos: lista.map((t) => ({ ...t })),
    })
  },
  { deep: true },
)

watch(codpessoa, (v) => {
  if (sBaixa.pessoa) sBaixa.pessoa.codpessoa = v
})

const abrirWizard = async () => {
  if (!codpessoa.value) {
    notificar('negative', 'Selecione a pessoa!')
    return
  }
  if (sBaixa.compensacao) {
    await gravar()
    return
  }
  sBaixa.abrirWizard({
    formas: props.formas,
    contexto: await props.contexto(titulos.value[0]?.codfilial ?? null),
  })
}

const gravar = async () => {
  const pags = await sBaixa.finalizar(props.finalizar.url, {
    ...props.finalizar.extras,
    ...(props.comData && transacao.value !== transacaoAbertura
      ? { transacao: transacao.value }
      : {}),
    observacao: observacao.value || null,
  })
  if (!pags) return
  notificar(
    'positive',
    { Receber: 'Recebimento registrado', Pagar: 'Pagamento registrado' }[descricao.value] ??
      'Encontro de contas registrado',
  )
  sBaixa.iniciar({ pessoa: null, titulos: [] })
  emit('finalizado', pags)
}

// lançou tudo: grava sozinho
watch(
  () => [sBaixa.saldo, sBaixa.pagamentos.length],
  ([saldo, lancados]) => {
    if (lancados > 0 && Math.abs(saldo) < 0.005) {
      gravar()
    }
  },
)

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
        <q-form @submit.prevent="abrirWizard">
          <q-card-section class="text-grey-9 text-overline row items-center">
            {{ descricao.toUpperCase() }}
            <q-space />
            <span class="text-h6">R$ {{ formataNumero(sBaixa.totalLiquido) }}</span>
          </q-card-section>
          <q-separator inset />
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-xs-12 col-sm-4" v-if="comData">
                <MgInputData
                  v-model="transacao"
                  type="timestamp"
                  default-time="keep"
                  :seconds="false"
                  label="Data"
                  :bottom-slots="false"
                />
              </div>
              <div class="col-xs-12" :class="comData ? 'col-sm-8' : ''">
                <MgSelectPessoa
                  v-model="codpessoa"
                  label="Pessoa"
                  autofocus
                  :bottom-slots="false"
                />
              </div>
              <div class="col-12">
                <MgInput
                  v-model="observacao"
                  type="textarea"
                  label="Observação"
                  maxlength="300"
                  autogrow
                  :bottom-slots="false"
                />
              </div>
            </div>
          </q-card-section>
          <q-list v-if="lancou" separator>
            <q-item v-for="(p, i) in sBaixa.pagamentos" :key="i">
              <q-item-section>{{ p.descricao }}</q-item-section>
              <q-item-section side>R$ {{ formataNumero(p.total) }}</q-item-section>
              <q-item-section side>
                <q-btn
                  v-if="!p.codpagamento"
                  flat
                  round
                  size="sm"
                  icon="close"
                  color="grey-7"
                  @click="sBaixa.remover(i)"
                >
                  <q-tooltip>Tirar esta forma</q-tooltip>
                </q-btn>
              </q-item-section>
            </q-item>
            <q-item>
              <q-item-section class="text-orange-10">Falta</q-item-section>
              <q-item-section side class="text-orange-10 text-weight-bold">
                R$ {{ formataNumero(sBaixa.saldo) }}
              </q-item-section>
              <q-item-section side style="width: 40px" />
            </q-item>
          </q-list>
          <q-separator inset />
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
            <q-btn
              flat
              :label="descricao"
              type="submit"
              color="primary"
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
        @click="dialog = true"
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
