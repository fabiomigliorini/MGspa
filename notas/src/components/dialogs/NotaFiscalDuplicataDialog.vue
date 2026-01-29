<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
  duplicata: {
    type: Object,
    default: null,
  },
  notaBloqueada: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'save', 'delete'])

const form = ref({
  fatura: '',
  vencimento: '',
  valor: null,
})

const loading = ref(false)

// Computed
const isEditMode = ref(false)

// Converte ISO date (YYYY-MM-DD) para DD/MM/YYYY
const isoToFormDate = (isoDate) => {
  if (!isoDate) return null
  const date = new Date(isoDate)
  const day = String(date.getDate()).padStart(2, '0')
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const year = date.getFullYear()
  return `${day}/${month}/${year}`
}

// Converte DD/MM/YYYY para YYYY-MM-DD
const formDateToIso = (formDate) => {
  if (!formDate || formDate.length !== 10) return null
  const [day, month, year] = formDate.split('/')
  return `${year}-${month}-${day}`
}

// Watch para preencher o formulário quando editar
watch(
  () => props.duplicata,
  (newVal) => {
    if (newVal) {
      isEditMode.value = true
      form.value = {
        fatura: newVal.fatura ?? '',
        vencimento: isoToFormDate(newVal.vencimento) ?? '',
        valor: newVal.valor ?? null,
      }
    } else {
      isEditMode.value = false
      form.value = {
        fatura: '',
        vencimento: '',
        valor: null,
      }
    }
  }
)

// Methods
const close = () => {
  emit('update:modelValue', false)
}

const handleSave = () => {
  // Validação
  if (!form.value.fatura || !form.value.vencimento || !form.value.valor) {
    return
  }

  emit('save', {
    fatura: form.value.fatura,
    vencimento: formDateToIso(form.value.vencimento),
    valor: form.value.valor,
    codnotafiscalduplicatas: props.duplicata?.codnotafiscalduplicatas,
  })
}

const handleDelete = () => {
  emit('delete', props.duplicata)
}

const resetForm = () => {
  form.value = {
    fatura: '',
    vencimento: '',
    valor: null,
  }
}

// Watch dialog close to reset form
watch(
  () => props.modelValue,
  (newVal) => {
    if (!newVal) {
      resetForm()
    }
  }
)
</script>

<template>
  <q-dialog :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)">
    <q-card class="dialog-card">
      <q-card-section class="bg-primary text-white">
        <div class="text-h6">{{ isEditMode ? 'Editar' : 'Nova' }} Duplicata</div>
      </q-card-section>

      <q-separator />

      <q-form @submit.prevent="handleSave">
        <q-card-section class="q-pt-md q-pb-md">
          <div class="row q-col-gutter-md">
            <!-- Fatura -->
            <div class="col-12">
              <q-input
                v-model="form.fatura"
                label="Número da Fatura *"
                outlined
                maxlength="50"
                placeholder="Ex: 001/01"
                :rules="[(val) => !!val || 'Campo obrigatório']"
                lazy-rules
                :disable="notaBloqueada"
                autofocus
              />
            </div>

            <!-- Vencimento -->
            <div class="col-12 col-sm-6">
              <q-input
                v-model="form.vencimento"
                label="Vencimento *"
                outlined
                placeholder="DD/MM/AAAA"
                mask="##/##/####"
                :rules="[
                  (val) => !!val || 'Campo obrigatório',
                  (val) => val?.length === 10 || 'Data inválida',
                ]"
                lazy-rules
                :disable="notaBloqueada"
              >
                <template v-slot:append>
                  <q-icon name="event" class="cursor-pointer">
                    <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                      <q-date v-model="form.vencimento" mask="DD/MM/YYYY">
                        <div class="row items-center justify-end">
                          <q-btn v-close-popup label="Fechar" color="primary" flat />
                        </div>
                      </q-date>
                    </q-popup-proxy>
                  </q-icon>
                </template>
              </q-input>
            </div>

            <!-- Valor -->
            <div class="col-12 col-sm-6">
              <q-input
                v-model.number="form.valor"
                label="Valor *"
                outlined
                type="number"
                min="0"
                step="0.01"
                prefix="R$"
                :rules="[
                  (val) => (val !== null && val !== undefined) || 'Campo obrigatório',
                  (val) => val > 0 || 'Valor deve ser maior que zero',
                ]"
                lazy-rules
                :disable="notaBloqueada"
                input-class="text-right"
              />
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md">
          <q-btn
            v-if="isEditMode && !notaBloqueada"
            flat
            label="Excluir"
            color="negative"
            @click="handleDelete"
          />
          <q-space />
          <q-btn flat label="Cancelar" @click="close" />
          <q-btn
            unelevated
            label="Salvar"
            color="primary"
            icon="save"
            type="submit"
            :loading="loading"
            :disable="notaBloqueada"
          />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>

<style scoped>
.dialog-card {
  width: 600px;
  max-width: 95vw;
}
</style>
