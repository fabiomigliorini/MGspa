<script setup>
// Transferência da gaveta (M11 doc-3, decisão 22): enviar (sangria, envio ao financeiro,
// depósito) ou receber (suprimento do cofre). Nasce efetivada quando quem registra opera o
// destino; senão fica a confirmar. Gavetas fechadas aparecem desabilitadas com o motivo.
import { ref, watch } from 'vue'
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgSelectPortador from '@components/MgSelectPortador.vue'
import { caixaSessaoStore } from '@components/stores/caixaSessaoStore'

const store = caixaSessaoStore()
const vazio = () => ({ sentido: 'E', codportador: null, valor: null, observacoes: '' })
const form = ref(vazio())

watch(
  () => store.dialogTransferir,
  (aberto) => {
    if (!aberto) return
    form.value = vazio()
    store.buscarBloqueios()
  },
)

async function salvar() {
  const ok = await store.transferir({
    ...form.value,
    observacoes: form.value.observacoes || null,
  })
  if (ok) store.dialogTransferir = false
}
</script>

<template>
  <q-dialog v-model="store.dialogTransferir">
    <q-card flat style="width: 500px; max-width: 90vw">
      <q-form @submit.prevent="salvar">
        <q-card-section class="text-h6">Transferir</q-card-section>
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
                  { label: `Enviar de ${store.gaveta?.portador}`, value: 'E' },
                  { label: `Receber em ${store.gaveta?.portador}`, value: 'R' },
                ]"
              />
            </div>
            <div class="col-12">
              <MgSelectPortador
                v-model="form.codportador"
                :label="form.sentido === 'E' ? 'Para' : 'De'"
                :tipos="['E', 'B']"
                agrupar
                :codfilial="store.gaveta?.codfilial"
                :excluir="[store.gaveta?.codportador]"
                :bloqueios="store.bloqueios"
                autofocus
                :rules="[(v) => !!v]"
                lazy-rules
              />
            </div>
            <div class="col-12">
              <MgInputValor v-model="form.valor" label="Valor" :rules="[(v) => v > 0]" lazy-rules />
            </div>
            <div class="col-12">
              <MgInput
                v-model="form.observacoes"
                label="Observação"
                type="textarea"
                autogrow
                maxlength="300"
              />
            </div>
          </div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup />
          <q-btn flat label="Transferir" color="primary" type="submit" :loading="store.salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
