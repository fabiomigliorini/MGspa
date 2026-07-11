<script setup>
import { ref, computed, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useContratoDetalheStore } from 'src/stores/contratoDetalhe'
import { notifySuccess, notifyError } from 'src/utils/notify'
import { formataNumero, formataDataIso, arredonda } from '@components/formatters'
import MgInputData from '@components/MgInputData.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgSelectPortador from '@components/MgSelectPortador.vue'

// Gerador de várias parcelas do contrato (mesma lógica do agrupamento de Contas):
// define nº de parcelas + dias da 1ª/demais, distribui o total igualmente (a
// última fecha o resto) e salva todas de uma vez. A lista é editável e a soma é
// indicada. Portador pré-preenchido com a conta que recebe do contrato.
// Moeda, preço e teto (sacas) vêm da fixação escolhida (via store), não por prop.
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  cod: { type: [Number, String], required: true }, // codcontrato
  contrato: { type: Object, default: null },
})
const emit = defineEmits(['update:modelValue', 'saved'])

const store = useContratoDetalheStore()
const { fixacoes } = storeToRefs(store)

const show = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

// Modo de pagamento da parcela (unidade do total): valor em R$ ou sacas.
const modos = [
  { label: 'Valor', value: 'VALOR' },
  { label: 'Sacas', value: 'SACAS' },
]
const MAX_PARCELAS = 60

const salvando = ref(false)
const form = ref(novoForm())

function n(v) {
  return Number(v) || 0
}

// ---- Fixação de origem: dirige moeda, preço e teto (sacas DAQUELA fixação) ----
const fixacaoSel = computed(
  () => fixacoes.value.find((f) => f.codcontratofixacao === form.value.codcontratofixacao) || null,
)
const usd = computed(() => store.ehUsd(fixacaoSel.value))
// Opções do select: só fixações com saldo de sacas a parcelar.
const fixacaoOptions = computed(() =>
  fixacoes.value.map((f) => {
    const saldo = store.saldoSacasFixacao(f)
    const moeda = (f.moeda || 'BRL') !== 'BRL' ? 'US$' : 'R$'
    return {
      value: f.codcontratofixacao,
      label: `${moeda} ${formataNumero(f.preco, 2)}/sc · saldo ${formataNumero(saldo, 0)} sc`,
      disable: saldo <= 0,
    }
  }),
)
// Preço por saca na unidade certa: US$ = preço nativo; BRL = líquido (após deduções).
const precoSc = computed(() =>
  usd.value ? n(fixacaoSel.value?.preco) : n(fixacaoSel.value?.precoliquido ?? fixacaoSel.value?.precoreal),
)
// US$ é sempre em SACAS; BRL respeita o modo escolhido.
const dec = computed(() => (usd.value || form.value.modo === 'SACAS' ? 0 : 2))
const emSacas = computed(() => usd.value || form.value.modo === 'SACAS')
const saldoSacasFix = computed(() =>
  fixacaoSel.value ? store.saldoSacasFixacao(fixacaoSel.value) : 0,
)
// Teto do total a parcelar, na unidade corrente: sacas (US$/SACAS) ou R$ (BRL VALOR).
const tetoTotal = computed(() =>
  emSacas.value ? arredonda(saldoSacasFix.value, 0) : arredonda(saldoSacasFix.value * precoSc.value, 2),
)

function novoForm() {
  return {
    codcontratofixacao: null,
    cotacao: null,
    modo: 'SACAS',
    qtd: 1,
    primeira: 30,
    demais: 30,
    total: 0,
    codportador: null,
    observacao: null,
    itens: [], // [{ data: 'YYYY-MM-DD', v: number }]  (v = sacas ou R$, conforme o modo)
  }
}

// ISO local (YYYY-MM-DD) a partir de hoje + dias — sem shift de fuso (formataDataIso).
function isoMaisDias(dias) {
  const d = new Date()
  d.setDate(d.getDate() + (Number(dias) || 0))
  return formataDataIso(d)
}

// Gera a lista do zero a partir da config (distribuição uniforme + datas).
function gerar() {
  const qtd = Math.min(MAX_PARCELAS, Math.max(1, Math.floor(n(form.value.qtd)) || 1))
  const total = n(form.value.total)
  const d = dec.value
  const base = Math.floor((total / qtd) * 10 ** d) / 10 ** d
  const itens = []
  let acc = 0
  let dias = Number(form.value.primeira) || 0
  for (let i = 0; i < qtd; i++) {
    if (i > 0) dias += Number(form.value.demais) || 0
    const v = i === qtd - 1 ? arredonda(total - acc, d) : arredonda(base, d)
    if (i < qtd - 1) acc += v
    itens.push({ data: isoMaisDias(dias), v })
  }
  form.value.itens = itens
}

const soma = computed(() => form.value.itens.reduce((a, p) => a + n(p.v), 0))
// Parcelamento PARCIAL é permitido: a soma só não pode passar do saldo da fixação
// (teto). Não precisa fechar o total — dá pra parcelar só uma parte agora.
const somaOk = computed(() => {
  const tol = dec.value === 0 ? 0.5 : 0.01
  return soma.value > 0 && soma.value <= tetoTotal.value + tol
})

// Recalcula só os valores (mantém as datas), dividindo o total igualmente.
function redistribuir() {
  const len = form.value.itens.length
  if (!len) return
  const total = n(form.value.total)
  const d = dec.value
  const base = Math.floor((total / len) * 10 ** d) / 10 ** d
  let acc = 0
  form.value.itens.forEach((p, i) => {
    if (i === len - 1) p.v = arredonda(total - acc, d)
    else {
      p.v = arredonda(base, d)
      acc += p.v
    }
  })
}

function adicionar() {
  const itens = form.value.itens
  const ult = itens[itens.length - 1]
  // Parse ao meio-dia local pra não escorregar de dia no fuso.
  const base = ult
    ? new Date(`${String(ult.data).slice(0, 10)}T12:00:00`)
    : new Date(`${isoMaisDias(form.value.primeira)}T12:00:00`)
  base.setDate(base.getDate() + (Number(form.value.demais) || 0))
  itens.push({ data: formataDataIso(base), v: 0 })
  form.value.qtd = itens.length
  redistribuir()
}

function remover(i) {
  form.value.itens.splice(i, 1)
  form.value.qtd = form.value.itens.length
  redistribuir()
}

function fmt(v) {
  return formataNumero(v, dec.value)
}

// Ao abrir: reseta e pré-seleciona a fixação se houver só uma com saldo. A
// distribuição só é gerada depois que a fixação estiver escolhida (define moeda/teto).
watch(show, (v) => {
  if (!v) return
  form.value = novoForm()
  form.value.codportador = props.contrato?.codportador || null
  const comSaldo = fixacoes.value.filter((f) => store.saldoSacasFixacao(f) > 0)
  if (comSaldo.length === 1) form.value.codcontratofixacao = comSaldo[0].codcontratofixacao
})

// Escolher a fixação define a moeda/modo e o teto -> reinicia a distribuição.
watch(
  () => form.value.codcontratofixacao,
  () => {
    if (!show.value || !fixacaoSel.value) return
    if (usd.value) form.value.modo = 'SACAS' // US$ é sempre em sacas
    form.value.total = tetoTotal.value
    gerar()
  },
)

// Trocar modo recalcula o total na nova unidade e regenera.
watch(
  () => form.value.modo,
  () => {
    if (!show.value || !fixacaoSel.value) return
    form.value.total = tetoTotal.value
    gerar()
  },
)

// Config (qtd/dias/total) muda -> regenera mantendo distribuição uniforme.
watch(
  () => [form.value.qtd, form.value.primeira, form.value.demais, form.value.total],
  () => {
    if (show.value) gerar()
  },
)

// Payload de uma parcela. A parcela pertence à fixação escolhida; o valor R$
// PREVISTO nasce aqui: US$ = sacas × preço × cotação; BRL(SACAS) = sacas × líquido;
// BRL(VALOR) = o próprio valor. Barter é do contrato, então forma=CONTA.
function montarPayload(p) {
  const modo = emSacas.value ? 'SACAS' : 'VALOR'
  const sacas = emSacas.value ? n(p.v) : null
  let valor
  if (usd.value) {
    valor = arredonda(n(p.v) * n(fixacaoSel.value?.preco) * n(form.value.cotacao), 2)
  } else if (modo === 'SACAS') {
    valor = arredonda(n(p.v) * precoSc.value, 2)
  } else {
    valor = n(p.v)
  }
  return {
    data: p.data,
    codcontratofixacao: form.value.codcontratofixacao,
    cotacao: usd.value ? n(form.value.cotacao) : null,
    modo,
    forma: 'CONTA',
    sacas,
    valor,
    codportador: form.value.codportador || null,
    observacao: form.value.observacao || null,
  }
}

// Salva o lote parcela a parcela pelo store. Cada item só sai de form.itens APÓS
// gravar — então, se uma falhar no meio (ex.: 422 de teto), um novo Salvar reenvia
// só o que faltou, sem duplicar as já lançadas.
async function salvar() {
  if (salvando.value || !form.value.itens.length) return
  salvando.value = true
  const totalItens = form.value.itens.length
  let salvos = 0
  try {
    while (form.value.itens.length) {
      await store.criarPagamento(montarPayload(form.value.itens[0]))
      form.value.itens.shift()
      salvos++
    }
    notifySuccess(totalItens > 1 ? `${totalItens} parcelas lançadas!` : 'Parcela lançada!')
    show.value = false
  } catch (e) {
    notifyError(e)
  } finally {
    salvando.value = false
    if (salvos > 0) emit('saved') // reflete no pai as parcelas já gravadas
  }
}
</script>

<template>
  <q-dialog v-model="show">
    <q-card flat style="width: 580px; max-width: 95vw">
      <q-form @submit.prevent="salvar">
        <q-card-section class="bg-primary text-white q-py-sm">
          <div class="text-h6">Novas parcelas</div>
        </q-card-section>

        <q-card-section class="scroll" style="max-height: 72vh">
          <div class="row q-col-gutter-md">
            <!-- Fixação de origem (obrigatória): define moeda, preço e o teto de
                 sacas. Em US$ o preço é dolarizado e a parcela é sempre em sacas. -->
            <div class="col-12">
              <q-select
                v-model="form.codcontratofixacao"
                :options="fixacaoOptions"
                label="Fixação"
                outlined
                emit-value
                map-options
                lazy-rules
                :rules="[(v) => !!v || 'Escolha a fixação']"
              />
            </div>

            <template v-if="fixacaoSel">
              <div class="col-4">
                <MgInputValor
                  v-model="form.qtd"
                  :decimals="0"
                  :min="1"
                  :max="MAX_PARCELAS"
                  label="Parcelas"
                />
              </div>
              <div class="col-4">
                <MgInputValor v-model="form.primeira" :decimals="0" suffix="d" label="1ª em" />
              </div>
              <div class="col-4">
                <MgInputValor v-model="form.demais" :decimals="0" suffix="d" label="Demais +" />
              </div>

              <!-- BRL: escolhe modo (valor/sacas). US$: cotação do dólar (sempre sacas). -->
              <div class="col-12 col-sm-3">
                <q-select
                  v-if="!usd"
                  v-model="form.modo"
                  :options="modos"
                  label="Modo de pagamento"
                  outlined
                  emit-value
                  map-options
                />
                <MgInputValor
                  v-else
                  v-model="form.cotacao"
                  :decimals="4"
                  prefix="R$"
                  label="Cotação do dólar"
                  lazy-rules
                  :rules="[(v) => v > 0 || 'Informe a cotação']"
                />
              </div>
              <div class="col-12 col-sm-4">
                <MgInputValor
                  v-model="form.total"
                  :decimals="dec"
                  :max="tetoTotal"
                  :prefix="!emSacas ? 'R$' : null"
                  :suffix="emSacas ? 'sc' : null"
                  label="Total a parcelar"
                  lazy-rules
                  :rules="[(v) => v > 0 || 'Informe o total']"
                />
              </div>
              <div class="col-12 col-sm-5">
                <MgSelectPortador v-model="form.codportador" label="Portador (conta que recebe)" />
              </div>
            </template>
          </div>

          <q-list separator class="q-mt-sm">
            <q-item v-for="(p, i) in form.itens" :key="i" class="q-px-none">
              <q-item-section avatar style="min-width: 36px">
                <q-avatar size="26px" color="grey-5" text-color="white">{{ i + 1 }}</q-avatar>
              </q-item-section>
              <q-item-section>
                <MgInputData
                  v-model="p.data"
                  label="Vencimento"
                  type="date"
                  :bottom-slots="false"
                  lazy-rules
                  :rules="[(v) => !!v || 'Informe a data']"
                />
              </q-item-section>
              <q-item-section>
                <MgInputValor
                  v-model="p.v"
                  :decimals="dec"
                  :prefix="form.modo === 'VALOR' ? 'R$' : null"
                  :suffix="form.modo === 'SACAS' ? 'sc' : null"
                  label="Valor"
                  :bottom-slots="false"
                  lazy-rules
                  :rules="[(v) => v > 0 || 'Informe o valor']"
                />
              </q-item-section>
              <q-item-section side style="flex: 0 0 36px">
                <q-btn
                  v-if="form.itens.length > 1"
                  flat
                  dense
                  round
                  size="sm"
                  color="grey-7"
                  icon="close"
                  @click="remover(i)"
                />
              </q-item-section>
            </q-item>
          </q-list>

          <div class="row items-center q-mt-xs">
            <q-btn flat color="primary" icon="add" label="Adicionar parcela" @click="adicionar" />
            <q-space />
            <div class="text-caption" :class="somaOk ? 'text-grey-7' : 'text-orange-8'">
              Soma: {{ fmt(soma) }} / saldo {{ fmt(tetoTotal) }} {{ emSacas ? 'sc' : '' }}
            </div>
          </div>

          <q-input v-model="form.observacao" label="Observação" outlined dense class="q-mt-md" />
        </q-card-section>

        <q-separator />
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn
            type="submit"
            flat
            :label="form.itens.length > 1 ? 'Salvar parcelas' : 'Salvar'"
            color="primary"
            :loading="salvando"
            :disable="!somaOk || !form.itens.length || !form.codcontratofixacao || (usd && !form.cotacao)"
          />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
