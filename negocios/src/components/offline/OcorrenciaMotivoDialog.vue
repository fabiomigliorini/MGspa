<script setup>
// Motivo de remover item, diminuir quantidade, baixar preço ou excluir pagamento no PDV
// monitorado (TASK-205). Devolve { motivo, justificativa }; Cancelar não faz nada.
import { ref } from 'vue'
import { useDialogPluginComponent } from 'quasar'
import { MOTIVO_OUTRO } from '@components/ocorrencia.js'
import MgSelectOcorrenciaMotivo from '@components/MgSelectOcorrenciaMotivo.vue'
import MgInput from '@components/MgInput.vue'

defineProps({
  titulo: { type: String, required: true },
  mensagem: { type: String, default: null },
  contexto: { type: String, default: 'item' }, // item | pagamento
  okLabel: { type: String, default: 'Confirmar' },
})

defineEmits([...useDialogPluginComponent.emits])

const { dialogRef, onDialogHide, onDialogOK, onDialogCancel } = useDialogPluginComponent()

const motivo = ref(null)
const justificativa = ref(null)

const motivoRule = [(v) => !!v || 'Escolha o motivo!']
const justificativaRule = [
  (v) => motivo.value != MOTIVO_OUTRO || (v && v.trim().length >= 5) || 'Explique o motivo!',
]

const confirmar = () => {
  onDialogOK({ motivo: motivo.value, justificativa: justificativa.value?.trim() || null })
}
</script>

<template>
  <q-dialog ref="dialogRef" @hide="onDialogHide">
    <q-card flat style="width: 400px; max-width: 90vw">
      <q-form @submit.prevent="confirmar">
        <q-card-section class="text-h6">{{ titulo }}</q-card-section>
        <q-card-section class="q-pt-none">
          <div v-if="mensagem" class="q-mb-md">{{ mensagem }}</div>
          <div class="row q-col-gutter-md">
            <div class="col-12">
              <mg-select-ocorrencia-motivo
                v-model="motivo"
                :contexto="contexto"
                :rules="motivoRule"
                autofocus
              />
            </div>
            <div class="col-12">
              <mg-input
                v-model="justificativa"
                :label="motivo == MOTIVO_OUTRO ? 'Justificativa' : 'Justificativa (opcional)'"
                maxlength="300"
                :rules="justificativaRule"
              />
            </div>
          </div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" tabindex="-1" @click="onDialogCancel" />
          <q-btn flat :label="okLabel" color="negative" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
