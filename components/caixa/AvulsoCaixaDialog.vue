<script setup>
// Ajuste (doc-4, redefinição do dinheiro): acerto do saldo do portador, sem contraparte; é
// movimento do portador, não pagamento. Cai no período da tela, com a data dentro dele (do início
// ao fim; aberto, até agora), e só se cancela, com justificativa. No banco o mesmo dialog lança
// taxa, tarifa e rendimento (pagamento sem pessoa, fora do escopo do dinheiro).
import { ref, computed, watch } from 'vue'
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgInputData from '@components/MgInputData.vue'
import { formataTimestamp, formataTimestampIso } from '@components/formatters'
import { periodoStore } from '@components/stores/periodoStore'
import { MOTIVOS } from '@components/stores/caixaSessaoStore'

const SENTIDOS = [
  { label: 'Entrada', value: 'E' },
  { label: 'Saída', value: 'S' },
]

const store = periodoStore()
// taxa, tarifa e rendimento só no banco (não espécie)
const TIPOS = computed(() =>
  store.portador?.ehCaixa ? [] : [{ value: 'A', label: 'Ajuste' }, ...MOTIVOS],
)
const periodo = computed(() => store.periodo)
const limite = () =>
  periodo.value?.fim && new Date(periodo.value.fim) < new Date()
    ? new Date(periodo.value.fim)
    : new Date()
// lê o valor do form (ISO), não o texto que o MgInputData passa às rules; vazio fica com o !!v
const noPeriodo = () => {
  if (!periodo.value || !form.value.transacao || form.value.tipo !== 'A') return true
  const d = new Date(form.value.transacao)
  return (
    (d >= new Date(periodo.value.inicio) && d <= limite()) ||
    `Fora do período (de ${formataTimestamp(periodo.value.inicio, 0)} a ${formataTimestamp(limite(), 0)})`
  )
}
const vazio = () => ({
  tipo: 'A',
  sentido: null,
  valor: null,
  observacoes: '',
  transacao: formataTimestampIso(limite()),
})
const form = ref(vazio())
const ajuste = computed(() => form.value.tipo === 'A')

watch(
  () => store.dialogAvulso,
  (aberto) => {
    if (aberto) form.value = vazio()
  },
)

async function salvar() {
  const f = form.value
  const valor = f.sentido === 'E' ? f.valor : -f.valor
  const ok = ajuste.value
    ? await store.ajustar({ valor, observacoes: f.observacoes, transacao: f.transacao })
    : await store.lancarTaxa({
        motivo: f.tipo,
        valor,
        observacoes: f.observacoes || null,
        transacao: f.transacao,
      })
  if (ok) store.dialogAvulso = false
}
</script>

<template>
  <q-dialog v-model="store.dialogAvulso">
    <q-card flat style="width: 400px; max-width: 90vw">
      <q-form @submit.prevent="salvar">
        <q-card-section class="text-grey-9 text-overline">
          {{ TIPOS.length ? 'LANÇAMENTO' : 'AJUSTE' }}
        </q-card-section>
        <q-separator inset />
        <q-card-section v-if="ajuste" class="text-caption text-grey-7 q-pb-none">
          Acerto do saldo, sem contraparte. Pagamento, vale e venda não são ajuste.
        </q-card-section>
        <q-card-section>
          <div class="row q-col-gutter-md">
            <div v-if="TIPOS.length" class="col-12">
              <q-option-group v-model="form.tipo" type="radio" inline :options="TIPOS" />
            </div>
            <div class="col-12">
              <MgInputData
                v-model="form.transacao"
                type="timestamp"
                default-time="now"
                label="Data"
                :rules="[(v) => !!v, noPeriodo]"
              />
            </div>
            <div class="col-12">
              <MgInput
                v-model="form.observacoes"
                :label="ajuste ? 'Motivo do ajuste' : 'Observação'"
                type="textarea"
                :autofocus="!TIPOS.length"
                autogrow
                maxlength="300"
                :rules="[(v) => !ajuste || (v || '').trim().length >= 3]"
                lazy-rules
              />
            </div>
            <div class="col-12">
              <!-- o q-option-group não tem rules: o q-field sem borda valida a escolha -->
              <q-field
                v-model="form.sentido"
                borderless
                :rules="[(v) => !!v || 'Informe se é entrada ou saída']"
              >
                <template #control>
                  <q-option-group v-model="form.sentido" type="radio" inline :options="SENTIDOS" />
                </template>
              </q-field>
            </div>
            <div class="col-12">
              <MgInputValor v-model="form.valor" label="Valor" :rules="[(v) => v > 0]" lazy-rules />
            </div>
          </div>
        </q-card-section>
        <q-separator inset />
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn flat label="Lançar" color="primary" type="submit" :loading="store.salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
