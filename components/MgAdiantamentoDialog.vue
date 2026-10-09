<script setup>
// Vale colaborador e adiantamentos (M8 do plano doc-3), o mesmo dialog no contas e no PDV: título
// que já nasce com o dinheiro (tipos com "movimenta portador"). Tipo, pessoa, valor, vencimento,
// conta e observação → wizard de cobrança no sentido do tipo (vale e adiantamento a fornecedor:
// sai dinheiro; adiantamento de cliente: entra). Cada forma lançada vira um título; quando as
// formas fecham o valor, grava. Cada app informa as formas, o contexto do wizard (PDV ou contas)
// e para onde manda. O wizard e os dialogs PIX/Stone/SafraPay ficam na página que usa. A data tem
// hora (TASK-204: a data manda no período do portador; no PDV, a sessão da gaveta daquela hora);
// sem mexer nela, vai vazia e o servidor usa agora.
import { ref, computed, watch } from 'vue'
import { Notify } from 'quasar'
import { api } from 'src/services/api'
import { useSelectCacheStore } from '@components/stores/selectCacheStore'
import { baixaTitulosStore } from '@components/stores/baixaTitulosStore'
import { formataNumero, formataDataIso, formataTimestampIso } from '@components/formatters'
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
})

const emit = defineEmits(['update:modelValue', 'finalizado'])

// conta contábil sugerida por tipo (editável)
const CONTA_PADRAO = { 120: 42, 121: 1, 211: 2 }

const sBaixa = baixaTitulosStore()
const selectCache = useSelectCacheStore()

const daquiA30Dias = () => {
  const d = new Date()
  d.setDate(d.getDate() + 30)
  return formataDataIso(d)
}

// data de outro mês avisa (DIMP e relatórios já apurados), sem bloquear
const trocaMes = computed(
  () => !!form.value.transacao && form.value.transacao.slice(0, 7) !== agora().slice(0, 7),
)

// a data de quando abriu; sem mexer, vai vazia (agora, no servidor)
const agora = () => formataTimestampIso(new Date()).slice(0, 16)
const transacaoAbertura = ref(agora())

const vazio = () => ({
  codtipotitulo: null,
  codpessoa: null,
  valor: null,
  vencimento: daquiA30Dias(),
  codcontacontabil: null,
  codfilial: props.filialPadrao,
  transacao: transacaoAbertura.value,
  observacao: null,
})

const form = ref(vazio())
const tipos = ref([])

const lancou = computed(() => sBaixa.pagamentos.length > 0)
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
    transacaoAbertura.value = agora()
    form.value = vazio()
    sBaixa.iniciar({ pessoa: null, titulos: [] })
    await carregarTipos()
    const padrao = tipos.value.find((t) => t.value === TIPO_PADRAO) ?? tipos.value[0]
    if (padrao) trocarTipo(padrao.value)
  },
  { immediate: true },
)

// o título ainda não existe: entra na baixa como uma linha só, no sentido do tipo, para o
// wizard e as cobranças integradas funcionarem como na baixa de títulos
const cobrar = async () => {
  if (!lancou.value) {
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
  sBaixa.abrirWizard({
    formas: props.formas,
    padrao: props.padrao,
    contexto: await props.contexto(form.value.codfilial),
  })
}

// um título por forma lançada; pagou menos (cobrança integrada de parte), lança o que pagou
const gravar = async () => {
  if (sBaixa.finalizando || !lancou.value) return
  sBaixa.finalizando = true
  try {
    const { data } = await api.post(props.finalizar.url, {
      ...props.finalizar.extras,
      codtipotitulo: form.value.codtipotitulo,
      codpessoa: form.value.codpessoa,
      codcontacontabil: form.value.codcontacontabil,
      vencimento: form.value.vencimento,
      observacao: form.value.observacao,
      ...(props.comData && form.value.transacao !== transacaoAbertura.value
        ? { transacao: form.value.transacao }
        : {}),
      ...(props.comFilial ? { codfilial: form.value.codfilial } : {}),
      pagamentos: sBaixa.pagamentos.map((p) => {
        const forma = { ...p }
        delete forma.descricao
        return forma
      }),
    })
    notificar('positive', `${tipo.value?.label ?? 'Título'} lançado!`)
    sBaixa.iniciar({ pessoa: null, titulos: [] })
    emit('update:modelValue', false)
    emit('finalizado', data.data)
  } catch (error) {
    notificar('negative', error?.response?.data?.message ?? error?.message ?? 'Erro ao lançar')
  } finally {
    sBaixa.finalizando = false
  }
}

// lançou tudo: grava sozinho
watch(
  () => [sBaixa.saldo, sBaixa.pagamentos.length],
  ([saldo, lancados]) => {
    if (props.modelValue && lancados > 0 && Math.abs(saldo) < 0.005) {
      gravar()
    }
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
            <div class="col-12 col-sm-5" v-if="comData">
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
              v-if="comData && trocaMes"
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
                :disable="lancou"
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
                v-model="form.observacao"
                label="Observação"
                maxlength="255"
                :disable="lancou"
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

        <q-card-actions align="right">
          <q-btn flat color="grey-8" label="Cancelar" v-close-popup />
          <q-btn
            v-if="lancou && sBaixa.saldo > 0"
            flat
            color="primary"
            label="Lançar o que foi pago"
            :loading="sBaixa.finalizando"
            @click="gravar"
          />
          <q-btn flat color="primary" :label="verbo" type="submit" :loading="sBaixa.finalizando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
