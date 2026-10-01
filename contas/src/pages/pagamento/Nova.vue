<script setup>
// Receber ou Pagar Títulos (M6.1 doc-3): seleciona os títulos (capital, multa, juros, desconto)
// e paga pelo mesmo wizard de cobrança do PDV, uma forma por pagamento (cartão com bandeira,
// autorização, parcelas e maquineta; banco; dinheiro do cofre; cheque; cartão da empresa;
// compensação). Gaveta de PDV não aparece. Quando as formas fecham o líquido, grava.
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { notifySuccess, notifyError } from 'src/utils/notify'
import { api } from 'src/services/api'
import { useAuthStore } from 'src/stores/auth'
import { useSelectCacheStore } from '@components/stores/selectCacheStore'
import { baixaTitulosStore } from '@components/stores/baixaTitulosStore'
import { formataNumero, formataDataIso } from '@components/formatters'
import MgInput from '@components/MgInput.vue'
import MgInputData from '@components/MgInputData.vue'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'
import MgCobrancaDialog from '@components/MgCobrancaDialog.vue'
import PixCobDialog from '@components/cobranca/PixCobDialog.vue'
import PagarMePedidoDialog from '@components/cobranca/PagarMePedidoDialog.vue'
import SaurusPedidoDialog from '@components/cobranca/SaurusPedidoDialog.vue'
import SeletorTitulosAbertos from 'src/components/SeletorTitulosAbertos.vue'

// o financeiro não usa gaveta (o PDV recebe na dele)
const FORMAS = {
  entrada: ['cartao', 'pix', 'dinheiro', 'cheque', 'banco', 'compensacao'],
  saida: ['banco', 'dinheiro', 'cartaoEmpresa', 'cheque', 'compensacao'],
}

const router = useRouter()
const auth = useAuthStore()
const selectCache = useSelectCacheStore()
const sBaixa = baixaTitulosStore()

const titulos = ref([])
const codpessoaFiltro = ref(null)
const codpessoa = ref(null)
const transacao = ref(formataDataIso(new Date()))
const observacao = ref('')

const lancou = computed(() => sBaixa.pagamentos.length > 0)
const descricao = computed(() => {
  if (sBaixa.compensacao) return 'Encontro de contas'
  return sBaixa.entrada ? 'Receber' : 'Pagar'
})

// títulos escolhidos: a pessoa sugerida é a deles, se for uma só; mudar a seleção recomeça
watch(
  titulos,
  (lista) => {
    if (lancou.value) {
      notifyError({ message: 'Seleção mudou: as formas lançadas foram descartadas.' }, 'Atenção')
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

// portadores que o usuário pode usar (filiais dele), menos gaveta
const portadores = async () => {
  const todos = await selectCache.loadList('portador', 'v1/select/portador')
  const filiais = auth.filiaisRestritas()
  return todos.filter(
    (p) => !p.gaveta && (filiais == null || filiais.map(Number).includes(Number(p.codfilial))),
  )
}

const abrirWizard = async () => {
  if (!codpessoa.value) {
    notifyError({ message: 'Selecione a pessoa!' }, 'Selecione a pessoa')
    return
  }
  if (sBaixa.compensacao) {
    await finalizar()
    return
  }
  const codfilial = titulos.value[0]?.codfilial ?? null
  sBaixa.abrirWizard({
    formas: FORMAS,
    contexto: {
      pdv: null,
      codfilial,
      portadores: await portadores(),
      carregarMaquinetas: async () => {
        const { data } = await api.get(`v1/cobranca/maquineta/${codfilial}`)
        return data.data
      },
    },
  })
}

const finalizar = async () => {
  const pags = await sBaixa.finalizar('v1/pagamento', {
    transacao: transacao.value,
    observacao: observacao.value || null,
  })
  if (!pags) return
  notifySuccess(
    { Receber: 'Recebimento registrado', Pagar: 'Pagamento registrado' }[descricao.value] ??
      'Encontro de contas registrado',
  )
  sBaixa.iniciar({ pessoa: null, titulos: [] })
  router.replace({ name: 'pagamento-detalhe', params: { id: pags[0].codpagamento } })
}

// lançou tudo: grava sozinho
watch(
  () => [sBaixa.saldo, sBaixa.pagamentos.length],
  ([saldo, lancados]) => {
    if (lancados > 0 && Math.abs(saldo) < 0.005) {
      finalizar()
    }
  },
)

onMounted(() => sBaixa.iniciar({ pessoa: null, titulos: [] }))
onUnmounted(() => sBaixa.iniciar({ pessoa: null, titulos: [] }))
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <q-item class="q-pb-md q-px-none">
        <q-item-section avatar>
          <q-btn flat round icon="arrow_back" :to="{ name: 'pagamento' }" aria-label="Voltar" />
        </q-item-section>
        <q-item-section>
          <div class="text-h5 text-grey-9">Receber ou Pagar Títulos</div>
        </q-item-section>
      </q-item>

      <SeletorTitulosAbertos
        v-model="titulos"
        :codpessoa-inicial="codpessoaFiltro"
        class="q-mb-md"
        @update:codpessoa="(v) => (codpessoaFiltro = v)"
      />

      <q-card v-if="titulos.length" bordered flat class="q-mb-md">
        <q-card-section class="text-grey-9 text-overline row items-center">
          {{ descricao.toUpperCase() }}
          <q-space />
          <span class="text-h6">R$ {{ formataNumero(sBaixa.totalLiquido) }}</span>
        </q-card-section>
        <q-separator inset />
        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-xs-12 col-sm-3">
              <MgInputData v-model="transacao" label="Data" :bottom-slots="false" />
            </div>
            <div class="col-xs-12 col-sm-9">
              <MgSelectPessoa v-model="codpessoa" label="Pessoa" :bottom-slots="false" />
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
      </q-card>
    </div>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn
        fab
        :icon="sBaixa.compensacao ? 'sync_alt' : 'payments'"
        color="primary"
        :disable="!titulos.length || sBaixa.finalizando"
        :loading="sBaixa.finalizando"
        @click="abrirWizard"
      >
        <q-tooltip>{{ descricao }}</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <MgCobrancaDialog />
    <PixCobDialog />
    <PagarMePedidoDialog />
    <SaurusPedidoDialog />
  </q-page>
</template>
