<script setup>
import { ref, computed, watch } from 'vue'
import { useContratoDetalheStore } from 'src/stores/contratoDetalhe'
import { notifyError } from 'src/utils/notify'
import { formataReal } from '@components/formatters'
import MgInputValor from '@components/MgInputValor.vue'
import MgInputData from '@components/MgInputData.vue'
import MgSelectPortador from '@components/MgSelectPortador.vue'

// Registrar recebimento de uma fixação. O valor vem pré-preenchido com o SALDO
// (recebimento total); digitar menos = parcial. Pode receber um pouco a mais/menos
// (diferencinha de imposto) e "Encerrar" quita a fixação mesmo sem bater no centavo.
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  fixacao: { type: Object, default: () => ({}) },
  recebimento: { type: Object, default: null },
})
const emit = defineEmits(['update:modelValue', 'saved'])

const store = useContratoDetalheStore()
const aberto = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})
const editando = computed(() => !!props.recebimento?.codcontratopagamento)

function n(v) {
  return Number(v) || 0
}
const rs = formataReal
const hojeIso = computed(() => new Date().toISOString().slice(0, 10))
const saldo = computed(() => Math.max(0, n(props.fixacao?.saldoreceber)))

const form = ref({ data: '', valor: null, codportador: null, observacao: '', encerrar: false })

function abrir() {
  if (editando.value) {
    const r = props.recebimento
    form.value = {
      data: (r.data || '').slice(0, 10),
      valor: n(r.valor),
      codportador: r.codportador ?? null,
      observacao: r.observacao || '',
      encerrar: false,
    }
    return
  }
  form.value = {
    data: hojeIso.value,
    valor: Math.round(saldo.value * 100) / 100, // pré-preenche com o saldo (total)
    codportador: null,
    observacao: '',
    encerrar: false,
  }
}
watch(
  () => props.modelValue,
  (v) => {
    if (v) abrir()
  },
)

const salvando = ref(false)
async function salvar() {
  if (salvando.value) return
  salvando.value = true
  try {
    await store.salvarRecebimento(props.fixacao.codcontratofixacao, {
      codcontratopagamento: props.recebimento?.codcontratopagamento,
      data: form.value.data,
      valor: form.value.valor,
      codportador: form.value.codportador,
      observacao: form.value.observacao || null,
    })
    if (form.value.encerrar && !props.fixacao.quitado) {
      await store.quitarFixacao(props.fixacao.codcontratofixacao, true)
    }
    emit('saved')
    aberto.value = false
  } catch (e) {
    notifyError(e, 'Erro ao registrar o recebimento.')
  } finally {
    salvando.value = false
  }
}
</script>

<template>
  <q-dialog v-model="aberto">
    <q-card flat style="width: 460px; max-width: 96vw">
      <q-form @submit.prevent="salvar">
        <q-card-section class="bg-primary text-white q-py-sm">
          <div class="text-h6">{{ editando ? 'Editar recebimento' : 'Registrar recebimento' }}</div>
        </q-card-section>

        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-6">
              <MgInputData
                v-model="form.data"
                label="Data"
                type="date"
                :max="hojeIso"
                lazy-rules
                :rules="[(v) => !!v || 'Informe a data']"
              />
            </div>
            <div class="col-6">
              <MgInputValor
                v-model="form.valor"
                :decimals="2"
                prefix="R$"
                label="Valor recebido"
                autofocus
                lazy-rules
                :rules="[(v) => v > 0 || 'Informe o valor']"
              />
            </div>
            <div class="col-12">
              <MgSelectPortador v-model="form.codportador" />
            </div>
            <div class="col-12 text-caption text-grey-7">
              Saldo a receber: <b>{{ rs(saldo) }}</b>
            </div>
            <div v-if="!editando && !fixacao.quitado" class="col-12">
              <q-toggle
                v-model="form.encerrar"
                label="Encerrar (quitar o saldo desta fixação)"
                color="green-6"
                left-label
              />
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn type="submit" flat label="Confirmar" color="primary" :loading="salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
