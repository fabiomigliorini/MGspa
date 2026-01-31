<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
})

const emit = defineEmits(['update:modelValue', 'save'])

const form = ref({
  texto: '',
})

const loading = ref(false)

// Methods
const close = () => {
  emit('update:modelValue', false)
}

const handleSave = () => {
  if (!form.value.texto || form.value.texto.length < 15) {
    return
  }

  loading.value = true
  emit('save', form.value.texto)
}

const resetForm = () => {
  form.value = {
    texto: '',
  }
  loading.value = false
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

// Expose para o pai poder controlar o loading
defineExpose({
  setLoading: (val) => {
    loading.value = val
  },
})
</script>

<template>
  <q-dialog :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)">
    <q-card class="dialog-card">
      <q-card-section class="bg-primary text-white">
        <div class="text-h6">Nova Carta de Correção</div>
      </q-card-section>

      <q-separator />

      <q-form @submit.prevent="handleSave">
        <q-card-section class="q-pt-md q-pb-md">
          <div class="row q-col-gutter-md">
            <!-- Texto da Correção -->
            <div class="col-12">
              <q-input
                v-model="form.texto"
                label="Texto da Correção *"
                outlined
                type="textarea"
                rows="6"
                counter
                maxlength="1000"
                :rules="[
                  (val) => !!val || 'Campo obrigatório',
                  (val) => val?.length >= 15 || 'Deve ter pelo menos 15 caracteres',
                ]"
                lazy-rules
                autofocus
                :disable="loading"
                hint="Descreva a correção a ser feita na NFe (mín. 15 caracteres)"
              />
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md">
          <q-btn flat label="Cancelar" @click="close" :disable="loading" />
          <q-btn
            unelevated
            label="Enviar Carta de Correção"
            color="primary"
            icon="send"
            type="submit"
            :loading="loading"
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
