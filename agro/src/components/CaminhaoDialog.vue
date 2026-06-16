<script setup>
import { ref, watch } from 'vue'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'

// Cadastro rápido de caminhão no pátio — cria um tblveiculo com o mínimo que o
// backend exige (apelido, tipo, proprietário, placa, UF). Online apenas; offline
// a placa fica como texto livre na carga. Emite o veículo criado pra ser
// selecionado na hora e cacheado pro autocomplete.
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  placa: { type: String, default: '' },
})
const emit = defineEmits(['update:modelValue', 'criado'])

const $q = useQuasar()
const salvando = ref(false)
const tipoOptions = ref([])
const estadoOptions = ref([])

const TIPO_PROPRIETARIO_OPTIONS = [
  { value: 0, label: 'TAC Agregado' },
  { value: 1, label: 'TAC Independente' },
  { value: 2, label: 'Outros' },
]

const form = ref({})
function resetForm() {
  const placa = (props.placa || '').toUpperCase()
  form.value = {
    placa,
    veiculo: placa, // apelido — default = placa (editável); backend exige >= 5
    codveiculotipo: null,
    tipoproprietario: 2, // Outros
    codestado: null,
    renavam: null,
    tara: null,
  }
}

async function carregarSelects() {
  try {
    const [tipos, estados] = await Promise.all([
      api.get('v1/select/veiculo-tipo', { skipLoading: true }),
      api.get('v1/select/estado', { skipLoading: true }),
    ])
    tipoOptions.value = tipos.data
    estadoOptions.value = estados.data
  } catch {
    $q.notify({ type: 'warning', message: 'Sem conexão para cadastrar caminhão agora.' })
  }
}

watch(
  () => props.modelValue,
  (aberto) => {
    if (!aberto) return
    resetForm()
    carregarSelects()
  },
)

function fechar() {
  emit('update:modelValue', false)
}

async function salvar() {
  salvando.value = true
  try {
    const { data } = await api.post('v1/veiculo', form.value)
    $q.notify({ type: 'positive', message: `Caminhão ${data.placa} cadastrado.` })
    emit('criado', data)
    fechar()
  } catch (e) {
    const msg = e?.response?.data?.message || 'Falha ao cadastrar o caminhão.'
    $q.notify({ type: 'negative', message: msg })
  } finally {
    salvando.value = false
  }
}
</script>

<template>
  <q-dialog :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)">
    <q-card flat style="width: 600px; max-width: 95vw">
      <q-form @submit.prevent="salvar">
        <q-card-section class="bg-primary text-white">
          <div class="text-h6">Cadastrar caminhão</div>
        </q-card-section>
        <q-card-section class="q-pt-md">
          <div class="row q-col-gutter-md">
            <div class="col-12 col-sm-6">
              <q-input
                v-model="form.placa"
                label="Placa"
                outlined
                autofocus
                mask="AAA#X##"
                hint="Ex: ABC1D23"
                :rules="[
                  (v) => !!v || 'Informe a placa',
                  (v) => (v && v.length === 7) || '7 caracteres',
                ]"
                @update:model-value="(v) => (form.placa = (v || '').toUpperCase())"
              />
            </div>
            <div class="col-12 col-sm-6">
              <q-select
                v-model="form.codestado"
                label="UF"
                outlined
                :options="estadoOptions"
                option-value="value"
                option-label="sigla"
                emit-value
                map-options
                :rules="[(v) => !!v || 'Informe a UF']"
              />
            </div>
            <div class="col-12">
              <q-input
                v-model="form.veiculo"
                label="Apelido"
                outlined
                maxlength="50"
                :rules="[(v) => (v && v.length >= 5) || 'Mínimo 5 caracteres']"
              />
            </div>
            <div class="col-12 col-sm-6">
              <q-select
                v-model="form.codveiculotipo"
                label="Tipo de veículo"
                outlined
                :options="tipoOptions"
                option-value="value"
                option-label="label"
                emit-value
                map-options
                :rules="[(v) => !!v || 'Informe o tipo']"
              />
            </div>
            <div class="col-12 col-sm-6">
              <q-select
                v-model="form.tipoproprietario"
                label="Tipo de proprietário"
                outlined
                :options="TIPO_PROPRIETARIO_OPTIONS"
                option-value="value"
                option-label="label"
                emit-value
                map-options
              />
            </div>
            <div class="col-12 col-sm-6">
              <q-input v-model="form.renavam" label="Renavam" outlined mask="###########" />
            </div>
            <div class="col-12 col-sm-6">
              <q-input
                v-model.number="form.tara"
                label="Tara (kg)"
                outlined
                type="number"
                min="0"
              />
            </div>
          </div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" @click="fechar" tabindex="-1" />
          <q-btn type="submit" flat label="Cadastrar" color="primary" :loading="salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
