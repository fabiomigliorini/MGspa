<script setup>
import { ref, watch, computed } from 'vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
  cartaCorrecao: {
    type: Object,
    default: null,
  },
  proximaSequencia: {
    type: Number,
    default: 1,
  },
  notaBloqueada: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'save', 'delete'])

const form = ref({
  texto: '',
  sequencia: 1,
})

const loading = ref(false)

// Computed
const isEditMode = computed(() => !!props.cartaCorrecao)
const isAutorizada = computed(() => props.cartaCorrecao?.protocolo)

// Watch para preencher o formulário quando editar
watch(() => props.cartaCorrecao, (newVal) => {
  if (newVal) {
    form.value = {
      texto: newVal.texto ?? '',
      sequencia: newVal.sequencia ?? 1,
    }
  } else {
    form.value = {
      texto: '',
      sequencia: props.proximaSequencia,
    }
  }
})

// Watch proximaSequencia para atualizar no modo criação
watch(() => props.proximaSequencia, (newVal) => {
  if (!isEditMode.value) {
    form.value.sequencia = newVal
  }
})

// Methods
const close = () => {
  emit('update:modelValue', false)
}

const handleSave = () => {
  // Validação
  if (!form.value.texto || !form.value.sequencia) {
    return
  }

  emit('save', {
    texto: form.value.texto,
    sequencia: form.value.sequencia,
    codnotafiscalcartacorrecao: props.cartaCorrecao?.codnotafiscalcartacorrecao,
  })
}

const handleDelete = () => {
  emit('delete', props.cartaCorrecao)
}

const resetForm = () => {
  form.value = {
    texto: '',
    sequencia: props.proximaSequencia,
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
    <q-card style="min-width: 700px">
      <q-card-section class="bg-primary text-white">
        <div class="text-h6">
          {{ isEditMode ? 'Editar' : 'Nova' }} Carta de Correção
        </div>
        <div v-if="isEditMode" class="text-caption">
          Sequência {{ cartaCorrecao?.sequencia }} - Lote {{ cartaCorrecao?.lote }}
        </div>
      </q-card-section>

      <q-separator />

      <q-banner v-if="isAutorizada" class="bg-warning text-white">
        <template v-slot:avatar>
          <q-icon name="lock" />
        </template>
        Esta carta de correção já foi autorizada e não pode ser editada.
      </q-banner>

      <q-form @submit.prevent="handleSave">
        <q-card-section class="q-pt-md q-pb-md">
        <div class="row q-col-gutter-md">
          <!-- Sequência -->
          <div class="col-12 col-sm-4">
            <q-input
              v-model.number="form.sequencia"
              label="Sequência *"
              outlined
              type="number"
              min="1"
              :rules="[
                (val) => !!val || 'Campo obrigatório',
                (val) => val > 0 || 'Deve ser maior que zero',
              ]"
              lazy-rules
              :disable="isEditMode || notaBloqueada"
              hint="Sequência automática"
              autofocus
            />
          </div>

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
              :disable="isAutorizada || notaBloqueada"
              hint="Descreva a correção a ser feita na NFe (mín. 15 caracteres)"
            />
          </div>

          <!-- Informações da Autorização (somente leitura quando editando) -->
          <template v-if="isEditMode && cartaCorrecao">
            <div class="col-12">
              <q-separator class="q-my-md" />
              <div class="text-caption text-grey-7 q-mb-sm">Informações da Transmissão</div>
            </div>

            <div class="col-12 col-sm-6">
              <q-input
                :model-value="cartaCorrecao.protocolo || 'Aguardando transmissão'"
                label="Protocolo"
                outlined
                readonly
                dense
              />
            </div>

            <div class="col-12 col-sm-6">
              <q-input
                :model-value="cartaCorrecao.protocolodata || '-'"
                label="Data do Protocolo"
                outlined
                readonly
                dense
              />
            </div>
          </template>
        </div>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md">
          <q-btn
            v-if="isEditMode && !isAutorizada && !notaBloqueada"
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
            :disable="isAutorizada || notaBloqueada"
          />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
