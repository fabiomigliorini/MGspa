<script setup>
// Lançamento avulso na gaveta (M13 doc-3; TASK-188 M9.5): entrada ou saída de dinheiro sem
// documento, com o motivo e o histórico. Só quem lançou exclui, com o caixa aberto.
import { ref, watch } from 'vue'
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'
import { caixaSessaoStore, MOTIVOS } from '@components/stores/caixaSessaoStore'

const store = caixaSessaoStore()
const vazio = () => ({ sentido: 'S', motivo: 'A', valor: null, observacoes: '' })
const form = ref(vazio())

watch(
  () => store.dialogAvulso,
  (aberto) => {
    if (aberto) form.value = vazio()
  },
)

async function salvar() {
  const ok = await store.lancarAvulso({ ...form.value })
  if (ok) store.dialogAvulso = false
}
</script>

<template>
  <q-dialog v-model="store.dialogAvulso">
    <q-card flat style="width: 400px; max-width: 90vw">
      <q-form @submit.prevent="salvar">
        <q-card-section class="text-h6">Lançamento avulso</q-card-section>
        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12">
              <q-btn-toggle
                v-model="form.sentido"
                spread
                no-caps
                unelevated
                toggle-color="primary"
                color="grey-3"
                text-color="grey-9"
                :options="[
                  { label: 'Saída', value: 'S' },
                  { label: 'Entrada', value: 'E' },
                ]"
              />
            </div>
            <div class="col-12">
              <q-btn-toggle
                v-model="form.motivo"
                spread
                no-caps
                unelevated
                toggle-color="primary"
                color="grey-3"
                text-color="grey-9"
                :options="MOTIVOS"
              />
            </div>
            <div class="col-12">
              <MgInputValor
                v-model="form.valor"
                label="Valor"
                autofocus
                :rules="[(v) => v > 0]"
                lazy-rules
              />
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
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup />
          <q-btn flat label="Lançar" color="primary" type="submit" :loading="store.salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
