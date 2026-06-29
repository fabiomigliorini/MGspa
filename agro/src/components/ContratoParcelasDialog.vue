<script setup>
import { ref, computed, watch } from 'vue'
import { api } from 'src/services/api'
import { notifySuccess, notifyError } from 'src/utils/notify'
import MgInputData from '@components/MgInputData.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgSelectPortador from '@components/MgSelectPortador.vue'

// Gerador de várias parcelas do contrato (mesma lógica do agrupamento de Contas):
// define nº de parcelas + dias da 1ª/demais, distribui o total igualmente (a
// última fecha o resto) e salva todas de uma vez. A lista é editável e a soma é
// indicada. Portador pré-preenchido com a conta que recebe do contrato.
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  cod: { type: [Number, String], required: true }, // codcontrato
  contrato: { type: Object, default: null },
  liquidoSc: { type: Number, default: 0 }, // R$ líquido/sc (converte SACAS -> valor)
  // Teto a parcelar = saldo do que foi FIXADO (não do contratado): espelha a trava
  // do backend (pagamento <= valor bruto fixado). saldoValor em R$, saldoSacas em sc.
  saldoValor: { type: Number, default: 0 },
  saldoSacas: { type: Number, default: 0 },
})
const emit = defineEmits(['update:modelValue', 'saved'])

const show = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

const modos = [
  { label: 'Valor', value: 'VALOR' },
  { label: 'Sacas', value: 'SACAS' },
]

const salvando = ref(false)
const form = ref(novoForm())

function n(v) {
  return Number(v) || 0
}
function round(v, casas) {
  const f = 10 ** casas
  return Math.round(n(v) * f) / f
}
const dec = computed(() => (form.value.modo === 'SACAS' ? 0 : 2))

function novoForm() {
  return {
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

// Total a parcelar = saldo do que foi FIXADO (não o contratado), na unidade do
// modo. Antes usava contrato.quantidade × líquido (todo o contrato), o que
// permitia parcelar além do fixado; agora parte do teto real (backend confirma).
function totalContrato(modo) {
  return modo === 'SACAS' ? round(n(props.saldoSacas), 0) : round(n(props.saldoValor), 2)
}

function isoMaisDias(dias) {
  const d = new Date()
  d.setDate(d.getDate() + (Number(dias) || 0))
  return d.toISOString().slice(0, 10)
}

// Gera a lista do zero a partir da config (distribuição uniforme + datas).
function gerar() {
  const qtd = Math.max(1, Math.floor(n(form.value.qtd)) || 1)
  const total = n(form.value.total)
  const d = dec.value
  const base = Math.floor((total / qtd) * 10 ** d) / 10 ** d
  const itens = []
  let acc = 0
  let dias = Number(form.value.primeira) || 0
  for (let i = 0; i < qtd; i++) {
    if (i > 0) dias += Number(form.value.demais) || 0
    const v = i === qtd - 1 ? round(total - acc, d) : round(base, d)
    if (i < qtd - 1) acc += v
    itens.push({ data: isoMaisDias(dias), v })
  }
  form.value.itens = itens
}

const soma = computed(() => form.value.itens.reduce((a, p) => a + n(p.v), 0))
const bate = computed(
  () => Math.abs(soma.value - n(form.value.total)) < (dec.value === 0 ? 0.5 : 0.01),
)

// Recalcula só os valores (mantém as datas), dividindo o total igualmente.
function redistribuir() {
  const len = form.value.itens.length
  if (!len) return
  const total = n(form.value.total)
  const d = dec.value
  const base = Math.floor((total / len) * 10 ** d) / 10 ** d
  let acc = 0
  form.value.itens.forEach((p, i) => {
    if (i === len - 1) p.v = round(total - acc, d)
    else {
      p.v = round(base, d)
      acc += p.v
    }
  })
}

function adicionar() {
  const itens = form.value.itens
  const ult = itens[itens.length - 1]
  const base = ult ? new Date(ult.data) : new Date(isoMaisDias(form.value.primeira))
  base.setDate(base.getDate() + (Number(form.value.demais) || 0))
  itens.push({ data: base.toISOString().slice(0, 10), v: 0 })
  form.value.qtd = itens.length
  redistribuir()
}

function remover(i) {
  form.value.itens.splice(i, 1)
  form.value.qtd = form.value.itens.length
  redistribuir()
}

function fmt(v) {
  return n(v).toLocaleString('pt-BR', {
    minimumFractionDigits: dec.value,
    maximumFractionDigits: dec.value,
  })
}

// Ao abrir: reseta com o total cheio do contrato e gera a 1ª distribuição.
watch(show, (v) => {
  if (!v) return
  form.value = novoForm()
  form.value.total = totalContrato(form.value.modo)
  form.value.codportador = props.contrato?.codportador || null
  gerar()
})

// Trocar modo recalcula o total na nova unidade e regenera.
watch(
  () => form.value.modo,
  (m) => {
    if (!show.value) return
    form.value.total = totalContrato(m)
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

async function salvar() {
  if (salvando.value || !form.value.itens.length) return
  salvando.value = true
  try {
    const modo = form.value.modo
    for (const p of form.value.itens) {
      const sacas = modo === 'SACAS' ? n(p.v) : null
      const valor = modo === 'SACAS' ? round(n(p.v) * props.liquidoSc, 2) : n(p.v)
      await api.post(`v1/contrato/${props.cod}/pagamento`, {
        data: p.data,
        modo,
        sacas,
        valor,
        codportador: form.value.codportador || null,
        observacao: form.value.observacao || null,
      })
    }
    notifySuccess(
      form.value.itens.length > 1
        ? `${form.value.itens.length} parcelas lançadas!`
        : 'Parcela lançada!',
    )
    show.value = false
    emit('saved')
  } catch (e) {
    notifyError(e)
  } finally {
    salvando.value = false
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
            <div class="col-12">
              <q-btn-toggle
                v-model="form.modo"
                :options="modos"
                spread
                no-caps
                unelevated
                toggle-color="primary"
                color="grey-3"
                text-color="grey-9"
              />
            </div>
            <div class="col-4">
              <MgInputValor v-model="form.qtd" :decimals="0" :min="1" label="Parcelas" />
            </div>
            <div class="col-4">
              <MgInputValor v-model="form.primeira" :decimals="0" suffix="d" label="1ª em" />
            </div>
            <div class="col-4">
              <MgInputValor v-model="form.demais" :decimals="0" suffix="d" label="Demais +" />
            </div>
            <div class="col-12 col-sm-5">
              <MgInputValor
                v-model="form.total"
                :decimals="dec"
                :prefix="form.modo === 'VALOR' ? 'R$' : null"
                :suffix="form.modo === 'SACAS' ? 'sc' : null"
                label="Total a parcelar"
              />
            </div>
            <div class="col-12 col-sm-7">
              <MgSelectPortador v-model="form.codportador" label="Portador (conta que recebe)" />
            </div>
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
            <div class="text-caption" :class="bate ? 'text-grey-7' : 'text-orange-8'">
              Soma: {{ fmt(soma) }} / {{ fmt(form.total) }}
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
            :disable="!form.itens.length"
          />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
