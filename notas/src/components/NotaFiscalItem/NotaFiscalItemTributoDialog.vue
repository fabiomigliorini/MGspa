<script setup>
import { ref, watch, computed } from 'vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
  tributo: {
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
  codtributo: null,
  basereducaopercentual: 0,
  basereducao: 0,
  base: 0,
  aliquota: 0,
  valor: 0,
  cst: '',
  cclasstrib: '',
  geracredito: false,
  valorcredito: 0,
  beneficiocodigo: '',
  fundamentolegal: '',
})

const loading = ref(false)

// Computed
const isEditMode = computed(() => !!props.tributo)

// Watch para preencher o formulário quando editar
watch(
  () => props.tributo,
  (newVal) => {
    if (newVal) {
      form.value = {
        codtributo: newVal.codtributo ?? null,
        basereducaopercentual: newVal.basereducaopercentual ?? 0,
        basereducao: newVal.basereducao ?? 0,
        base: newVal.base ?? 0,
        aliquota: newVal.aliquota ?? 0,
        valor: newVal.valor ?? 0,
        cst: newVal.cst ?? '',
        cclasstrib: newVal.cclasstrib ?? '',
        geracredito: newVal.geracredito ?? false,
        valorcredito: newVal.valorcredito ?? 0,
        beneficiocodigo: newVal.beneficiocodigo ?? '',
        fundamentolegal: newVal.fundamentolegal ?? '',
      }
    } else {
      resetForm()
    }
  },
)

// Methods
const close = () => {
  emit('update:modelValue', false)
}

const handleSave = () => {
  // Validação básica
  if (!form.value.codtributo) {
    return
  }

  emit('save', {
    ...form.value,
    codnotafiscalitemtributo: props.tributo?.codnotafiscalitemtributo,
  })
}

const handleDelete = () => {
  emit('delete', props.tributo)
}

const resetForm = () => {
  form.value = {
    codtributo: null,
    basereducaopercentual: 0,
    basereducao: 0,
    base: 0,
    aliquota: 0,
    valor: 0,
    cst: '',
    cclasstrib: '',
    geracredito: false,
    valorcredito: 0,
    beneficiocodigo: '',
    fundamentolegal: '',
  }
}

// Watch dialog close to reset form
watch(
  () => props.modelValue,
  (newVal) => {
    if (!newVal) {
      resetForm()
    }
  },
)
</script>

<template>
  <q-dialog :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)" persistent>
    <q-card style="min-width: 700px; max-width: 800px">
      <q-card-section class="bg-primary text-white">
        <div class="text-h6">
          {{ isEditMode ? 'Editar' : 'Adicionar' }} Tributo
        </div>
      </q-card-section>

      <q-separator />

      <q-form @submit.prevent="handleSave">
        <q-card-section class="q-pt-md q-pb-md" style="max-height: 70vh; overflow-y: auto">
          <div class="row q-col-gutter-md">
            <!-- Tributo -->
            <div class="col-12">
              <q-input
                v-model.number="form.codtributo"
                label="Código Tributo *"
                outlined
                type="number"
                hint="Código do tributo (codtributo)"
                :disable="notaBloqueada"
                input-class="text-right"
              />
            </div>

            <!-- CST e Classificação -->
            <div class="col-12 col-sm-6">
              <q-input
                v-model="form.cst"
                label="CST"
                outlined
                maxlength="2"
                hint="Código de Situação Tributária"
                :disable="notaBloqueada"
              />
            </div>

            <div class="col-12 col-sm-6">
              <q-input
                v-model="form.cclasstrib"
                label="Classificação Tributária"
                outlined
                maxlength="20"
                :disable="notaBloqueada"
              />
            </div>

            <!-- Redução de Base -->
            <div class="col-12">
              <q-separator class="q-my-md" />
              <div class="text-subtitle2 text-weight-medium q-mb-md">Redução de Base de Cálculo</div>
            </div>

            <div class="col-12 col-sm-6">
              <q-input
                v-model.number="form.basereducaopercentual"
                label="% Redução da Base"
                outlined
                type="number"
                step="0.01"
                min="0"
                max="100"
                suffix="%"
                :disable="notaBloqueada"
                input-class="text-right"
              />
            </div>

            <div class="col-12 col-sm-6">
              <q-input
                v-model.number="form.basereducao"
                label="Valor da Redução"
                outlined
                type="number"
                step="0.01"
                min="0"
                prefix="R$"
                :disable="notaBloqueada"
                input-class="text-right"
              />
            </div>

            <!-- Base, Alíquota e Valor -->
            <div class="col-12">
              <q-separator class="q-my-md" />
              <div class="text-subtitle2 text-weight-medium q-mb-md">Cálculo do Tributo</div>
            </div>

            <div class="col-12 col-sm-4">
              <q-input
                v-model.number="form.base"
                label="Base de Cálculo *"
                outlined
                type="number"
                step="0.01"
                min="0"
                prefix="R$"
                :rules="[(val) => val !== null && val !== undefined || 'Campo obrigatório']"
                lazy-rules
                :disable="notaBloqueada"
                input-class="text-right"
              />
            </div>

            <div class="col-12 col-sm-4">
              <q-input
                v-model.number="form.aliquota"
                label="Alíquota *"
                outlined
                type="number"
                step="0.01"
                min="0"
                max="100"
                suffix="%"
                :rules="[(val) => val !== null && val !== undefined || 'Campo obrigatório']"
                lazy-rules
                :disable="notaBloqueada"
                input-class="text-right"
              />
            </div>

            <div class="col-12 col-sm-4">
              <q-input
                v-model.number="form.valor"
                label="Valor do Tributo *"
                outlined
                type="number"
                step="0.01"
                min="0"
                prefix="R$"
                :rules="[(val) => val !== null && val !== undefined || 'Campo obrigatório']"
                lazy-rules
                :disable="notaBloqueada"
                input-class="text-right"
              />
            </div>

            <!-- Crédito -->
            <div class="col-12">
              <q-separator class="q-my-md" />
              <div class="text-subtitle2 text-weight-medium q-mb-md">Geração de Crédito</div>
            </div>

            <div class="col-12 col-sm-6">
              <div style="height: 56px; display: flex; align-items: center">
                <q-toggle
                  v-model="form.geracredito"
                  label="Gera Crédito Tributário"
                  color="primary"
                  :disable="notaBloqueada"
                />
              </div>
            </div>

            <div v-if="form.geracredito" class="col-12 col-sm-6">
              <q-input
                v-model.number="form.valorcredito"
                label="Valor do Crédito"
                outlined
                type="number"
                step="0.01"
                min="0"
                prefix="R$"
                :disable="notaBloqueada"
                input-class="text-right"
              />
            </div>

            <!-- Benefício e Fundamento Legal -->
            <div class="col-12">
              <q-separator class="q-my-md" />
              <div class="text-subtitle2 text-weight-medium q-mb-md">Benefícios Fiscais</div>
            </div>

            <div class="col-12 col-sm-6">
              <q-input
                v-model="form.beneficiocodigo"
                label="Código do Benefício"
                outlined
                maxlength="10"
                hint="Código do benefício fiscal aplicado"
                :disable="notaBloqueada"
              />
            </div>

            <div class="col-12">
              <q-input
                v-model="form.fundamentolegal"
                label="Fundamento Legal"
                outlined
                type="textarea"
                rows="2"
                maxlength="500"
                counter
                hint="Base legal para aplicação do tributo ou benefício (máx. 500 caracteres)"
                :disable="notaBloqueada"
              />
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md">
          <q-btn v-if="isEditMode && !notaBloqueada" flat label="Excluir" color="negative" @click="handleDelete" />
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
