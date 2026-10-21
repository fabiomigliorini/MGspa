<script setup>
// Alterar a data de um lançamento (TASK-204): a data, com hora, manda no período, então a linha
// vai para o período daquela data (fechado ou conferido recusa). Justificativa obrigatória; o
// antes/depois fica gravado. Mudar de mês avisa, sem bloquear: pode mexer na DIMP e em relatório
// já tirado. O mesmo dialog no extrato do portador (caixa, banco; contas e PDV) e no período da
// maquineta; quem usa faz a chamada no `salvar`. Com `com-observacoes` (o movimento do extrato) é o
// Editar da linha (TASK-248): a observação também, e a justificativa só vale se a data mudar. O
// `salvar` leva só o que mudou.
import { ref, computed, watch } from 'vue'
import MgInput from '@components/MgInput.vue'
import MgInputData from '@components/MgInputData.vue'
import { formataTimestamp, formataTimestampIso } from '@components/formatters'

const model = defineModel({ type: Boolean, default: false })
const props = defineProps({
  // a data atual do lançamento
  data: { type: String, default: null },
  // o que muda: "a data", "a data do cancelamento"
  rotulo: { type: String, default: 'a data' },
  // editar também a observação (a atual em `observacoes`)
  comObservacoes: { type: Boolean, default: false },
  observacoes: { type: String, default: null },
  salvando: { type: Boolean, default: false },
})
const emit = defineEmits(['salvar'])

const form = ref({ transacao: null, justificativa: '', observacoes: '' })
const refJustificativa = ref(null)

watch(model, (aberto) => {
  if (aberto) {
    form.value = {
      transacao: formataTimestampIso(props.data),
      justificativa: '',
      observacoes: props.observacoes ?? '',
    }
  }
})

const mes = (d) => (d ? formataTimestampIso(d).slice(0, 7) : null)
const trocaMes = computed(
  () => !!form.value.transacao && !!props.data && mes(form.value.transacao) !== mes(props.data),
)
const mudouData = computed(
  () =>
    !!form.value.transacao &&
    formataTimestampIso(form.value.transacao).slice(0, 16) !==
      formataTimestampIso(props.data).slice(0, 16),
)
const mudouObservacoes = computed(
  () => props.comObservacoes && form.value.observacoes.trim() !== (props.observacoes ?? '').trim(),
)

// a data voltou a ser a atual: a justificativa desabilita sem o erro de antes
watch(mudouData, (mudou) => {
  if (!mudou) refJustificativa.value?.resetValidation()
})

function salvar() {
  const dados = {}
  if (mudouData.value) {
    dados.transacao = formataTimestampIso(form.value.transacao)
    dados.justificativa = form.value.justificativa.trim()
  }
  if (mudouObservacoes.value) dados.observacoes = form.value.observacoes.trim()
  emit('salvar', dados)
}
</script>

<template>
  <q-dialog v-model="model">
    <q-card flat style="width: 400px; max-width: 90vw">
      <q-form @submit.prevent="salvar">
        <q-card-section class="text-grey-9 text-overline text-uppercase">
          {{ comObservacoes ? 'Editar lançamento' : `Alterar ${rotulo}` }}
        </q-card-section>
        <q-separator inset />
        <q-card-section class="text-caption text-grey-7 q-pb-none">
          Data atual: {{ formataTimestamp(data) }}. O lançamento vai para o período da data nova.
        </q-card-section>
        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12">
              <MgInputData
                v-model="form.transacao"
                type="timestamp"
                default-time="keep"
                :seconds="false"
                label="Data"
                autofocus
                :rules="[(v) => !!v || 'Informe a data']"
              />
            </div>
            <div v-if="trocaMes" class="col-12 text-caption text-orange-9 row no-wrap items-center">
              <q-icon name="warning" size="xs" class="q-mr-xs" />
              Muda de mês: pode afetar a DIMP e relatórios já apurados.
            </div>
            <div v-if="comObservacoes" class="col-12">
              <MgInput v-model="form.observacoes" label="Observação" maxlength="300" />
            </div>
            <div class="col-12">
              <MgInput
                ref="refJustificativa"
                v-model="form.justificativa"
                label="Justificativa"
                type="textarea"
                autogrow
                maxlength="300"
                :disable="!mudouData"
                :rules="[
                  (v) =>
                    !mudouData || (v || '').trim().length >= 5 || 'Diga o motivo (mínimo 5 letras)',
                ]"
                lazy-rules
              />
            </div>
          </div>
        </q-card-section>
        <q-separator inset />
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn
            flat
            label="Alterar"
            color="primary"
            type="submit"
            :disable="!mudouData && !mudouObservacoes"
            :loading="salvando"
          />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
