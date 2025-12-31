<script setup>
import { ref, watch } from 'vue'
import { validarChaveNFe } from 'src/utils/validators'

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
  referenciada: {
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
  nfechave: '',
})

const loading = ref(false)

// Computed
const isEditMode = ref(false)

// Watch para preencher o formulário quando editar
watch(() => props.referenciada, (newVal) => {
  if (newVal) {
    isEditMode.value = true
    form.value = {
      nfechave: newVal.nfechave ?? '',
    }
  } else {
    isEditMode.value = false
    form.value = {
      nfechave: '',
    }
  }
})

// Methods
const close = () => {
  emit('update:modelValue', false)
}

// Limpa caracteres não numéricos ao colar
const handlePaste = (evt) => {
  const pastedText = evt.clipboardData?.getData('text')
  if (pastedText) {
    evt.preventDefault()
    const cleanedText = pastedText.replace(/\D/g, '').substring(0, 44)
    form.value.nfechave = cleanedText
  }
}

const handleSave = () => {
  // Remove espaços e caracteres não numéricos
  const chaveNumeros = form.value.nfechave.replace(/\D/g, '')

  // Validação
  if (!chaveNumeros || chaveNumeros.length !== 44) {
    return
  }

  // Valida o dígito verificador
  if (!validarChaveNFe(chaveNumeros)) {
    return
  }

  emit('save', {
    nfechave: chaveNumeros,
    codnotafiscalreferenciada: props.referenciada?.codnotafiscalreferenciada,
  })
}

const handleDelete = () => {
  emit('delete', props.referenciada)
}

const resetForm = () => {
  form.value = {
    nfechave: '',
  }
}

// Watch dialog close to reset form
watch(() => props.modelValue, (newVal) => {
  if (!newVal) {
    resetForm()
  }
})
</script>

<template>
  <q-dialog :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)" persistent>
    <q-card style="min-width: 600px">
      <q-card-section class="bg-primary text-white">
        <div class="text-h6">
          {{ isEditMode ? 'Editar' : 'Nova' }} Nota Fiscal Referenciada
        </div>
      </q-card-section>

      <q-separator />

      <q-form @submit.prevent="handleSave">
        <q-card-section class="q-pt-md q-pb-md">
          <div class="row q-col-gutter-md">
            <!-- Chave de Acesso -->
            <div class="col-12">
              <q-input
                v-model="form.nfechave"
                label="Chave de Acesso da NFe *"
                outlined
                mask="#### #### #### #### #### #### #### #### #### #### ####"
                unmasked-value
                placeholder="Digite os 44 dígitos da chave de acesso"
                :rules="[
                  (val) => !!val || 'Campo obrigatório',
                  (val) => validarChaveNFe(val) || 'Chave de acesso inválida (dígito verificador incorreto)',
                ]"
                lazy-rules
                :disable="notaBloqueada"
                @paste="handlePaste"
                autofocus
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
