<script setup>
import { ref, computed, watch } from 'vue'
import { useContratoDetalheStore } from 'src/stores/contratoDetalhe'
import { notifyError } from 'src/utils/notify'
import { formataReal } from '@components/formatters'
import MgInputValor from '@components/MgInputValor.vue'
import MgInputData from '@components/MgInputData.vue'
import ResumoImposto from 'components/ResumoImposto.vue'

// Modal "Travar câmbio" de uma fixação dolarizada: fixa a cotação (R$/moeda) de
// uma fatia do valor em moeda estrangeira. O valor já vem pré-preenchido com o
// saldo a travar. À direita, o RESUMO da fatia (bruto → impostos → líquido): é
// aqui que o R$ nasce, então é onde o produtor vê quanto recebe líquido.
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  fixacao: { type: Object, default: () => ({}) },
  cambio: { type: Object, default: null },
})
const emit = defineEmits(['update:modelValue', 'saved'])

const store = useContratoDetalheStore()

const aberto = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

const editando = computed(() => !!props.cambio?.codcontratofixacaocambio)
const simbolo = computed(() => store.simboloMoeda(props.fixacao?.moeda))

function n(v) {
  return Number(v) || 0
}
// Saldo disponível p/ esta trava: o que falta travar + (na edição) a própria trava.
const disponivel = computed(
  () => n(props.fixacao?.saldomoeda) + (editando.value ? n(props.cambio?.valor) : 0),
)

const form = ref({ data: '', valor: null, cotacao: null, observacao: '' })

const valorBrl = computed(() => n(form.value.valor) * n(form.value.cotacao))
// Sacas desta fatia (valor na moeda ÷ preço/saca) — base dos impostos por unidade.
const sacasTranche = computed(() =>
  n(props.fixacao?.preco) > 0 ? n(form.value.valor) / n(props.fixacao.preco) : 0,
)
const hojeIso = computed(() => new Date().toISOString().slice(0, 10))

function abrir() {
  if (editando.value) {
    const c = props.cambio
    form.value = {
      data: (c.data || '').slice(0, 10),
      valor: n(c.valor),
      cotacao: n(c.cotacao),
      observacao: c.observacao || '',
    }
    return
  }
  form.value = {
    data: hojeIso.value,
    valor: Math.round(disponivel.value * 100) / 100, // pré-preenche com o saldo
    cotacao: null,
    observacao: '',
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
    await store.salvarCambio(props.fixacao.codcontratofixacao, {
      codcontratofixacaocambio: props.cambio?.codcontratofixacaocambio,
      data: form.value.data,
      valor: form.value.valor,
      cotacao: form.value.cotacao,
      observacao: form.value.observacao || null,
    })
    emit('saved')
    aberto.value = false
  } catch (e) {
    notifyError(e, 'Erro ao travar o câmbio.')
  } finally {
    salvando.value = false
  }
}
</script>

<template>
  <q-dialog v-model="aberto">
    <q-card flat style="width: 700px; max-width: 96vw">
      <q-form @submit.prevent="salvar">
        <q-card-section class="bg-primary text-white q-py-sm">
          <div class="text-h6">{{ editando ? 'Editar trava de câmbio' : 'Travar câmbio' }}</div>
        </q-card-section>

        <q-card-section>
          <div class="row q-col-gutter-lg">
            <!-- Esquerda: inputs da trava -->
            <div class="col-12 col-sm-6">
              <div class="row q-col-gutter-md">
                <div class="col-12">
                  <MgInputData
                    v-model="form.data"
                    label="Data da trava"
                    type="date"
                    :max="hojeIso"
                    lazy-rules
                    :rules="[(v) => !!v || 'Informe a data']"
                  />
                </div>
                <div class="col-12">
                  <MgInputValor
                    v-model="form.valor"
                    :decimals="2"
                    :prefix="simbolo"
                    :max="disponivel"
                    label="Valor travado"
                    autofocus
                    lazy-rules
                    :rules="[(v) => v > 0 || 'Informe o valor']"
                  />
                </div>
                <div class="col-12">
                  <MgInputValor
                    v-model="form.cotacao"
                    :decimals="4"
                    prefix="R$"
                    label="Cotação"
                    lazy-rules
                    :rules="[(v) => v > 0 || 'Informe a cotação']"
                  />
                </div>
                <div class="col-12 text-caption text-grey-7">
                  Saldo a travar:
                  <b>{{ simbolo }} {{ formataReal(disponivel).replace('R$', '').trim() }}</b>
                </div>
              </div>
            </div>

            <!-- Direita: o cálculo desta fatia (bruto → impostos → líquido) -->
            <div class="col-12 col-sm-6">
              <div class="text-caption text-uppercase text-grey-6 q-mb-sm">
                Recebimento da fatia
              </div>
              <ResumoImposto
                :bruto="valorBrl"
                :sacas="sacasTranche"
                :tributos="fixacao.tributos || []"
                :pesosaca="store.pesosaca"
              />
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn type="submit" flat label="Travar" color="primary" :loading="salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
