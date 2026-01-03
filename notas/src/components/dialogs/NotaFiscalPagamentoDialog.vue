<script setup>
import { ref, watch, computed } from 'vue'
import { TIPO_PAGAMENTO_OPTIONS, BANDEIRA_CARTAO_OPTIONS, tipoPagamentoRequerBandeira } from 'src/constants/notaFiscal'
import SelectPessoa from 'src/components/selects/SelectPessoa.vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
  pagamento: {
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
  tipo: null,
  valorpagamento: null,
  avista: true,
  troco: null,
  bandeira: null,
  autorizacao: '',
  codpessoa: null,
  descricao: '',
})

const loading = ref(false)

// Computed
const isEditMode = computed(() => !!props.pagamento)
const requerBandeira = computed(() => tipoPagamentoRequerBandeira(form.value.tipo))
const requerAutorizacao = computed(() => [3, 4].includes(form.value.tipo)) // Cartão
const podeTerTroco = computed(() => form.value.tipo === 1) // Dinheiro

// Watch para preencher o formulário quando editar
watch(() => props.pagamento, (newVal) => {
  if (newVal) {
    form.value = {
      tipo: newVal.tipo ?? null,
      valorpagamento: newVal.valorpagamento ?? null,
      avista: newVal.avista !== undefined ? newVal.avista : true,
      troco: newVal.troco ?? null,
      bandeira: newVal.bandeira ?? null,
      autorizacao: newVal.autorizacao ?? '',
      codpessoa: newVal.codpessoa ?? null,
      descricao: newVal.descricao ?? '',
    }
  } else {
    resetForm()
  }
})

// Watch tipo para limpar campos condicionais
watch(() => form.value.tipo, (newTipo) => {
  // Limpa bandeira se não for cartão
  if (!tipoPagamentoRequerBandeira(newTipo)) {
    form.value.bandeira = null
  }
  // Limpa autorização se não for cartão
  if (![3, 4].includes(newTipo)) {
    form.value.autorizacao = ''
  }
  // Limpa troco se não for dinheiro
  if (newTipo !== 1) {
    form.value.troco = null
  }
})

// Methods
const close = () => {
  emit('update:modelValue', false)
}

const handleSave = () => {
  // Validação
  if (!form.value.tipo || !form.value.valorpagamento) {
    return
  }

  if (requerBandeira.value && !form.value.bandeira) {
    return
  }

  emit('save', {
    ...form.value,
    codnotafiscalpagamento: props.pagamento?.codnotafiscalpagamento,
  })
}

const handleDelete = () => {
  emit('delete', props.pagamento)
}

const resetForm = () => {
  form.value = {
    tipo: null,
    valorpagamento: null,
    avista: true,
    troco: null,
    bandeira: null,
    autorizacao: '',
    codpessoa: null,
    descricao: '',
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
          {{ isEditMode ? 'Editar' : 'Nova' }} Forma de Pagamento
        </div>
      </q-card-section>

      <q-form @submit.prevent="handleSave">
        <q-card-section class="q-pt-md q-pb-md">
          <div class="row q-col-gutter-md">
            <!-- Tipo de Pagamento -->
            <div class="col-12 col-sm-6">
              <q-select v-model="form.tipo" :options="TIPO_PAGAMENTO_OPTIONS" label="Tipo de Pagamento *" outlined
                emit-value map-options :rules="[(val) => val !== null && val !== undefined || 'Campo obrigatório']"
                lazy-rules :disable="notaBloqueada" autofocus />
            </div>

            <!-- Valor do Pagamento -->
            <div class="col-12 col-sm-6">
              <q-input v-model.number="form.valorpagamento" label="Valor *" outlined type="number" min="0" step="0.01"
                prefix="R$" :rules="[
                  (val) => val !== null && val !== undefined || 'Campo obrigatório',
                  (val) => val > 0 || 'Valor deve ser maior que zero',
                ]" lazy-rules :disable="notaBloqueada" input-class="text-right" />
            </div>

            <!-- À Vista / Prazo -->
            <div class="col-12 col-sm-6">
              <div style="height: 56px; display: flex; align-items: center">
                <q-toggle v-model="form.avista" label="Pagamento à Vista" color="primary" :disable="notaBloqueada" />
              </div>
            </div>

            <!-- Troco (somente para dinheiro) -->
            <div v-if="podeTerTroco" class="col-12 col-sm-6">
              <q-input v-model.number="form.troco" label="Troco" outlined type="number" min="0" step="0.01" prefix="R$"
                :disable="notaBloqueada" input-class="text-right" hint="Somente para pagamento em dinheiro" />
            </div>

            <!-- Bandeira (somente para cartões) -->
            <div v-if="requerBandeira" class="col-12 col-sm-6">
              <q-select v-model="form.bandeira" :options="BANDEIRA_CARTAO_OPTIONS" label="Bandeira do Cartão *" outlined
                emit-value map-options :rules="[(val) => val !== null && val !== undefined || 'Campo obrigatório']"
                lazy-rules :disable="notaBloqueada" />
            </div>

            <!-- Autorização (somente para cartões) -->
            <div v-if="requerAutorizacao" class="col-12 col-sm-6">
              <q-input v-model="form.autorizacao" label="Código de Autorização" outlined maxlength="40"
                :disable="notaBloqueada" hint="NSU, código de autorização da transação" />
            </div>

            <!-- Credenciadora / Administradora -->
            <div class="col-12">
              <SelectPessoa v-model="form.codpessoa" label="Credenciadora / Administradora" :disable="notaBloqueada"
                hint="Empresa processadora do pagamento (opcional)" />
            </div>

            <!-- Descrição Adicional -->
            <div class="col-12">
              <q-input v-model="form.descricao" label="Descrição Adicional" outlined maxlength="100"
                :disable="notaBloqueada" hint="Informações complementares sobre este pagamento" />
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md">
          <q-btn v-if="isEditMode && !notaBloqueada" flat label="Excluir" color="negative" @click="handleDelete" />
          <q-space />
          <q-btn flat label="Cancelar" @click="close" />
          <q-btn unelevated label="Salvar" color="primary" icon="save" type="submit" :loading="loading"
            :disable="notaBloqueada" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
