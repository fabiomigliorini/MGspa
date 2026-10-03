<script setup>
// Lançamento avulso (M13 doc-3; TASK-188 M9.5; genérico desde o doc-4): entrada ou saída sem
// documento, com o motivo e o histórico. Na gaveta cai na sessão aberta (cancela só quem lançou,
// com o caixa aberto); nos demais portadores, no período da data (M12: a implantação é um Ajuste
// na data do go-live).
import { ref, watch } from 'vue'
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgInputData from '@components/MgInputData.vue'
import { formataTimestampIso } from '@components/formatters'
import { periodoStore } from '@components/stores/periodoStore'
import { MOTIVOS } from '@components/stores/caixaSessaoStore'

const SENTIDOS = [
  { label: 'Saída', value: 'S' },
  { label: 'Entrada', value: 'E' },
]

const store = periodoStore()
// a data já vem com agora; na gaveta é sempre agora (a sessão aberta)
const vazio = () => ({
  sentido: 'S',
  motivo: 'A',
  valor: null,
  observacoes: '',
  transacao: formataTimestampIso(new Date()),
})
const form = ref(vazio())

watch(
  () => store.dialogAvulso,
  (aberto) => {
    if (aberto) form.value = vazio()
  },
)

// foco no Tipo ao abrir (o grupo de radios não tem autofocus): o radio marcado
const refTipo = ref(null)
const focarTipo = () => refTipo.value?.querySelector('[role="radio"][aria-checked="true"]')?.focus()

async function salvar() {
  const ok = await store.lancarAvulso({ ...form.value })
  if (ok) store.dialogAvulso = false
}
</script>

<template>
  <q-dialog v-model="store.dialogAvulso" @show="focarTipo">
    <q-card flat style="width: 400px; max-width: 90vw">
      <q-form @submit.prevent="salvar">
        <q-card-section class="text-h6">Lançamento avulso</q-card-section>
        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12">
              <MgInputData
                v-model="form.transacao"
                type="timestamp"
                default-time="now"
                label="Data"
                :readonly="!!store.portador?.ehGaveta"
                :rules="[(v) => !!v]"
              />
            </div>
            <div ref="refTipo" class="col-12">
              <div class="text-caption text-grey-7">Tipo</div>
              <q-option-group v-model="form.motivo" type="radio" inline :options="MOTIVOS" />
            </div>
            <div class="col-5">
              <q-select
                v-model="form.sentido"
                :options="SENTIDOS"
                emit-value
                map-options
                outlined
                label="Entrada ou saída"
              />
            </div>
            <div class="col-7">
              <MgInputValor v-model="form.valor" label="Valor" :rules="[(v) => v > 0]" lazy-rules />
            </div>
            <div class="col-12">
              <MgInput
                v-model="form.observacoes"
                label="Histórico"
                type="textarea"
                autogrow
                maxlength="300"
                :rules="[(v) => (v || '').trim().length >= 3]"
                lazy-rules
              />
            </div>
          </div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn flat label="Lançar" color="primary" type="submit" :loading="store.salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
