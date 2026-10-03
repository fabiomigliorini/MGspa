<script setup>
// Lançamento avulso (M13 doc-3; TASK-188 M9.5; genérico desde o doc-4): entrada ou saída sem
// documento, com o motivo e o histórico. No caixa (espécie) cai na sessão da tela, com a data
// dentro dela (do início ao fim; aberta, até agora) e cancela só quem lançou; nos demais
// portadores, no período da data (M12).
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
// caixa: a data fica entre o início e o fim da sessão (aberta, agora)
const sessao = computed(() => (store.portador?.ehCaixa ? store.periodo : null))
const limite = () => (sessao.value?.fim ? new Date(sessao.value.fim) : new Date())
// lê o valor do form (ISO), não o texto que o MgInputData passa às rules; vazio fica com o !!v
const naSessao = () => {
  if (!sessao.value || !form.value.transacao) return true
  const d = new Date(form.value.transacao)
  return (
    (d >= new Date(sessao.value.inicio) && d <= limite()) ||
    `Fora da sessão (de ${formataTimestamp(sessao.value.inicio, 0)} a ${formataTimestamp(limite(), 0)})`
  )
}
// a data já vem com agora (na sessão reaberta, com o fim dela)
const vazio = () => ({
  sentido: 'E',
  motivo: 'A',
  valor: null,
  observacoes: '',
  transacao: formataTimestampIso(sessao.value ? limite() : new Date()),
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
    <q-card flat style="width: 300px; max-width: 90vw">
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
                :rules="[(v) => !!v, naSessao]"
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
