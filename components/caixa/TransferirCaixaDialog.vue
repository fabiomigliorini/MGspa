<script setup>
// Transferência de qualquer portador (M11 doc-3, decisão 22; genérico desde o doc-4): enviar
// (sangria, envio ao financeiro, depósito) ou receber (reforço). No caixa (espécie) chama-se
// Reforço / Sangria e a data fica dentro do período da tela (do início ao fim; aberto, até agora). Nasce efetivada
// quando quem registra opera o destino; senão fica a confirmar. Caixas fechados aparecem
// desabilitados.
import { ref, computed, watch } from 'vue'
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgInputData from '@components/MgInputData.vue'
import { formataTimestamp, formataTimestampIso } from '@components/formatters'
import MgSelectPortador from '@components/MgSelectPortador.vue'
import { periodoStore } from '@components/stores/periodoStore'

const store = periodoStore()
const caixa = computed(() => !!store.portador?.ehCaixa)
const nome = computed(() => store.portador?.portador)
const SENTIDOS = computed(() =>
  caixa.value
    ? [
        { label: `Sangria (sai de ${nome.value})`, value: 'E' },
        { label: `Reforço (entra em ${nome.value})`, value: 'R' },
      ]
    : [
        { label: `Enviar de ${nome.value}`, value: 'E' },
        { label: `Receber em ${nome.value}`, value: 'R' },
      ],
)
// caixa: a data fica dentro do período da tela (do início ao fim; aberto, até agora)
const sessao = computed(() => (caixa.value ? store.periodo : null))
const limite = () => (sessao.value?.fim ? new Date(sessao.value.fim) : new Date())
// lê o valor do form (ISO), não o texto que o MgInputData passa às rules; vazio fica com o !!v
const naSessao = () => {
  if (!form.value.transacao) return true
  const d = new Date(form.value.transacao)
  if (d > new Date()) return 'Não pode ser no futuro'
  if (sessao.value && (d < new Date(sessao.value.inicio) || d > limite())) {
    return `Fora da sessão (de ${formataTimestamp(sessao.value.inicio, 0)} a ${formataTimestamp(limite(), 0)})`
  }
  return true
}
const vazio = () => ({
  sentido: 'E',
  codportador: null,
  valor: null,
  observacoes: '',
  transacao: formataTimestampIso(sessao.value ? limite() : new Date()),
})
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
        <q-card-section class="text-h6">
          {{ caixa ? 'Reforço / Sangria' : 'Transferir' }}
        </q-card-section>
        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12">
              <q-option-group v-model="form.sentido" type="radio" inline :options="SENTIDOS" />
            </div>
            <div class="col-12">
              <MgSelectPortador
                v-model="form.codportador"
                :label="form.sentido === 'E' ? 'Para' : 'De'"
                :tipos="['E', 'B']"
                agrupar
                :codfilial="store.portador?.codfilial"
                :excluir="[store.portador?.codportador]"
                :bloqueios="store.bloqueios"
                autofocus
                :rules="[(v) => !!v]"
                lazy-rules
              />
            </div>
            <div class="col-12 col-sm-6">
              <MgInputData
                v-model="form.transacao"
                type="timestamp"
                label="Data"
                :rules="[(v) => !!v, naSessao]"
              />
            </div>
            <div class="col-12 col-sm-6">
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
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn
            flat
            :label="caixa ? 'Lançar' : 'Transferir'"
            color="primary"
            type="submit"
            :loading="store.salvando"
          />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
