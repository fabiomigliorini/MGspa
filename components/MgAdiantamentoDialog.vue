<script setup>
// Vale colaborador e adiantamentos (M8 do plano doc-3), o mesmo dialog no contas e no PDV: título
// que já nasce com o dinheiro (tipos com "movimenta portador"). Tipo, pessoa, valor, vencimento,
// conta e observação → wizard de cobrança no sentido do tipo (vale e adiantamento a fornecedor:
// sai dinheiro; adiantamento de cliente: entra). A forma confirmada no wizard grava na hora (a
// integrada, quando o banco/maquineta confirma). Cada app informa as formas, o contexto do wizard
// (PDV ou contas) e para onde manda. O wizard e os dialogs PIX/Stone/SafraPay ficam na página que
// usa. A data tem hora (TASK-204: a data manda no período do portador; no PDV, a sessão da gaveta
// daquela hora); sem mexer nela, vai vazia e o servidor usa agora. Com `pix` (vindo da tela PIX,
// TASK-219), o adiantamento de cliente usa o PIX inteiro, na data dele.
import { ref, computed, watch } from 'vue'
import { Notify } from 'quasar'
import { api } from 'src/services/api'
import { useSelectCacheStore } from '@components/stores/selectCacheStore'
import { baixaTitulosStore } from '@components/stores/baixaTitulosStore'
import { formataNumero } from '@components/formatters'
import { agora, hoje, somaDias } from '@components/utils/datas'
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgInputData from '@components/MgInputData.vue'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'
import MgSelectFilial from '@components/MgSelectFilial.vue'
import MgSelectContaContabil from '@components/MgSelectContaContabil.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  // formas do wizard: { entrada: [...], saida: [...] }
  formas: { type: Object, required: true },
  // async (codfilial) => contexto do wizard (pdv, codfilial, maquinetas, portadores)
  contexto: { type: Function, required: true },
  // para onde vai: { url, extras }
  finalizar: { type: Object, required: true },
  // data e hora (contas e PDV) e filial escolhida (contas; no PDV, a dele)
  comData: { type: Boolean, default: false },
  comFilial: { type: Boolean, default: false },
  filialPadrao: { type: Number, default: null },
  // configuração do PDV para o wizard (maquineta, conta PIX, impressora)
  padrao: { type: Object, default: () => ({}) },
  // o PIX sem uso (vindo da tela PIX): { codpix, valor, descricao, codpessoa }
  pix: { type: Object, default: null },
})

const emit = defineEmits(['update:modelValue', 'finalizado'])

// conta contábil sugerida por tipo (editável)
const CONTA_PADRAO = { 120: 42, 121: 1, 211: 2 }

const sBaixa = baixaTitulosStore()
const selectCache = useSelectCacheStore()

// data de outro mês avisa (DIMP e relatórios já apurados), sem bloquear
const trocaMes = computed(
  () => !!form.value.transacao && form.value.transacao.slice(0, 7) !== agora(false).slice(0, 7),
)

// a data de quando abriu; o que está no campo é o que vai
const transacaoAbertura = ref(agora(false))

const vazio = () => ({
  codtipotitulo: null,
  codpessoa: props.pix?.codpessoa ?? null,
  valor: props.pix?.valor ?? null,
  vencimento: somaDias(hoje(), 30),
  codcontacontabil: null,
  codfilial: props.filialPadrao,
  transacao: transacaoAbertura.value,
  referente: null,
  observacao: null,
})

const form = ref(vazio())
const tipos = ref([])

// uma forma: um vale/adiantamento = um pagamento
const lancou = computed(() => !!sBaixa.forma)
const tipo = computed(() => tipos.value.find((t) => t.value === form.value.codtipotitulo))
// título a pagar (adiantamento/crédito de cliente) = entra dinheiro
const entrada = computed(() => tipo.value?.natureza === 'P')
const verbo = computed(() => (entrada.value ? 'Receber' : 'Pagar'))

const notificar = (type, message) =>
  Notify.create({
    type,
    message,
    color: type === 'positive' ? 'green-5' : 'red-5',
    icon: type === 'positive' ? 'done' : 'error',
    timeout: 3000,
  })

const trocarTipo = (codtipotitulo) => {
  form.value.codtipotitulo = codtipotitulo
  // tipo sem padrão mantém a conta que já estava
  form.value.codcontacontabil = CONTA_PADRAO[codtipotitulo] ?? form.value.codcontacontabil
}

// tipos com "movimenta portador", do cadastro: os que saem dinheiro (a receber) primeiro
const carregarTipos = async () => {
  const todos = await selectCache.loadList('tipoTitulo', 'v1/select/tipo-titulo')
  tipos.value = todos
    .filter((t) => t.movimentaportador && !t.inativo)
    .map((t) => ({ value: t.value, label: t.label, natureza: t.natureza }))
    .sort((a, b) => b.natureza.localeCompare(a.natureza) || a.label.localeCompare(b.label))
}

// o mais usado é o vale colaborador
const TIPO_PADRAO = 120

watch(
  () => props.modelValue,
  async (aberto) => {
    if (!aberto) return
    transacaoAbertura.value = agora(false)
    form.value = vazio()
    sBaixa.iniciar({ pessoa: null, titulos: [] })
    await carregarTipos()
    // o PIX é entrada de dinheiro: adiantamento/crédito de cliente
    if (props.pix) {
      tipos.value = tipos.value.filter((t) => t.natureza === 'P')
    }
    const padrao = tipos.value.find((t) => t.value === TIPO_PADRAO) ?? tipos.value[0]
    if (padrao) trocarTipo(padrao.value)
  },
  { immediate: true },
)

// o título ainda não existe: entra na baixa como uma linha só, no sentido do tipo, para o
// wizard e as cobranças integradas funcionarem como na baixa de títulos (valor travado)
const prepararBaixa = () => {
  const valor = Math.round((parseFloat(form.value.valor) || 0) * 100) / 100
  sBaixa.iniciar({
    pessoa: { codpessoa: form.value.codpessoa },
    titulos: [
      {
        codtitulo: null,
        operacao: entrada.value ? 'DB' : 'CR',
        saldo: valor,
        juros: 0,
        multa: 0,
        desconto: 0,
        total: valor,
      },
    ],
  })
}

const cobrar = async () => {
  if (sBaixa.finalizando) return
  prepararBaixa()
  // o PIX é a forma, com o valor dele: não há o que cobrar
  if (props.pix) {
    sBaixa.adicionar({
      codpix: props.pix.codpix,
      total: sBaixa.totalLiquido,
      descricao: props.pix.descricao,
    })
    return
  }
  sBaixa.abrirWizard({
    formas: props.formas,
    padrao: props.padrao,
    contexto: await props.contexto(form.value.codfilial),
  })
}

const gravar = async () => {
  if (sBaixa.finalizando || !lancou.value) return
  sBaixa.finalizando = true
  try {
    const forma = { ...sBaixa.forma }
    delete forma.descricao
    const { data } = await api.post(props.finalizar.url, {
      ...props.finalizar.extras,
      codtipotitulo: form.value.codtipotitulo,
      codpessoa: form.value.codpessoa,
      codcontacontabil: form.value.codcontacontabil,
      vencimento: form.value.vencimento,
      referente: form.value.referente || null,
      observacao: form.value.observacao,
      ...(props.comData ? { transacao: form.value.transacao } : {}),
      ...(props.comFilial ? { codfilial: form.value.codfilial } : {}),
      pagamentos: [forma],
    })
    notificar('positive', `${tipo.value?.label ?? 'Título'} lançado!`)
    sBaixa.iniciar({ pessoa: null, titulos: [] })
    emit('update:modelValue', false)
    emit('finalizado', data.data)
  } catch (error) {
    notificar('negative', error?.response?.data?.message ?? error?.message ?? 'Erro ao lançar')
    // não gravou: volta a editar; a integrada já confirmada (o dinheiro entrou) fica para o Gravar
    if (props.pix || !sBaixa.formaConfirmada) {
      sBaixa.remover()
    }
  } finally {
    sBaixa.finalizando = false
  }
}

// a forma chegou (confirmada no wizard, a integrada confirmada pelo banco/maquineta ou o PIX):
// grava na hora
watch(
  () => sBaixa.forma,
  (forma) => {
    if (props.modelValue && forma) gravar()
  },
)
</script>

<template>
  <q-dialog :model-value="modelValue" @update:model-value="(v) => emit('update:modelValue', v)">
    <q-card flat style="width: 600px; max-width: 95vw">
      <q-form @submit.prevent="cobrar">
        <q-card-section>
          <div class="text-h6">Vale / Adiantamento</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <div class="row q-col-gutter-md">
            <div class="col-12">
              <q-option-group
                :model-value="form.codtipotitulo"
                :options="tipos"
                :disable="lancou"
                inline
                @update:model-value="trocarTipo"
              />
            </div>
            <div class="col-12 col-sm-5" v-if="comData && !pix">
              <MgInputData
                v-model="form.transacao"
                type="timestamp"
                default-time="keep"
                :seconds="false"
                label="Data"
                :disable="lancou"
                :rules="[(v) => !!v]"
                lazy-rules
              />
            </div>
            <div class="col-12 col-sm-7" v-if="comFilial">
              <MgSelectFilial
                v-model="form.codfilial"
                label="Filial"
                :disable="lancou"
                :rules="[(v) => !!v]"
                lazy-rules
              />
            </div>
            <div
              v-if="comData && !pix && trocaMes"
              class="col-12 text-caption text-orange-9 row no-wrap items-center"
            >
              <q-icon name="warning" size="xs" class="q-mr-xs" />
              Data de outro mês: pode afetar a DIMP e relatórios já apurados.
            </div>
            <div class="col-12">
              <MgSelectPessoa
                v-model="form.codpessoa"
                label="Pessoa"
                autofocus
                :disable="lancou"
                :rules="[(v) => !!v]"
                lazy-rules
              />
            </div>
            <div class="col-12 col-sm-6">
              <MgInputValor
                v-model="form.valor"
                label="Valor"
                prefix="R$"
                :min="0.01"
                :disable="lancou || !!pix"
                :rules="[(v) => v > 0]"
                lazy-rules
              />
            </div>
            <div class="col-12 col-sm-6">
              <MgInputData
                v-model="form.vencimento"
                label="Vencimento"
                :disable="lancou"
                :rules="[(v) => !!v]"
                lazy-rules
              />
            </div>
            <div class="col-12">
              <MgSelectContaContabil
                v-model="form.codcontacontabil"
                :disable="lancou"
                :rules="[(v) => !!v]"
                lazy-rules
              />
            </div>
            <div class="col-12">
              <MgInput
                v-model="form.referente"
                label="Referente a (sai no recibo)"
                maxlength="255"
                :disable="lancou"
              />
            </div>
            <div class="col-12">
              <MgInput
                v-model="form.observacao"
                label="Observação (interna)"
                maxlength="255"
                :disable="lancou"
              />
            </div>
          </div>
        </q-card-section>

        <q-card-section v-if="pix" class="text-caption text-grey-7">
          Usando o {{ pix.descricao }}
        </q-card-section>

        <q-list v-else-if="lancou" separator>
          <q-item>
            <q-item-section>{{ sBaixa.forma.descricao }}</q-item-section>
            <q-item-section side>R$ {{ formataNumero(sBaixa.forma.total) }}</q-item-section>
          </q-item>
        </q-list>

        <q-card-actions align="right">
          <q-btn flat color="grey-8" label="Cancelar" v-close-popup tabindex="-1" />
          <q-btn
            v-if="!lancou || sBaixa.finalizando"
            flat
            color="primary"
            :label="verbo"
            type="submit"
            :loading="sBaixa.finalizando"
          />
          <!-- a integrada já confirmada que não gravou: grava de novo -->
          <q-btn v-else flat color="primary" label="Gravar" @click="gravar" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
