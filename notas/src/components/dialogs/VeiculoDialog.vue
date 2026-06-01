<script setup>
import { ref, computed, watch } from 'vue'
import veiculoService from '../../services/veiculoService'
import { useVeiculoStore, TIPO_PROPRIETARIO_OPTIONS } from '../../stores/veiculoStore'
import { notificarSucesso, notificarErro } from '../../utils/notify'
import SelectPessoa from '../selects/SelectPessoa.vue'
import SelectEstado from '../selects/SelectEstado.vue'

const props = defineProps({
  modelValue: { type: Boolean, required: true },
  veiculo: { type: Object, default: null },
})
const emit = defineEmits(['update:modelValue', 'saved'])

const veiculoStore = useVeiculoStore()
const loading = ref(false)
const isNovo = computed(() => !props.veiculo)

const tipoOptions = computed(() =>
  veiculoStore.tipos.map((t) => ({ value: t.codveiculotipo, label: t.veiculotipo })),
)

const form = ref({})

const resetForm = () => {
  form.value = {
    veiculo: null,
    codpessoaproprietario: null,
    tipoproprietario: null,
    codveiculotipo: null,
    renavam: null,
    placa: null,
    codestado: null,
    tara: null,
    capacidade: null,
    capacidadem3: null,
  }
}

watch(
  () => props.modelValue,
  (aberto) => {
    if (!aberto) return
    if (props.veiculo) {
      form.value = {
        veiculo: props.veiculo.veiculo,
        codpessoaproprietario: props.veiculo.codpessoaproprietario,
        tipoproprietario: props.veiculo.tipoproprietario,
        codveiculotipo: props.veiculo.codveiculotipo,
        renavam: props.veiculo.renavam,
        placa: props.veiculo.placa,
        codestado: props.veiculo.codestado,
        tara: props.veiculo.tara,
        capacidade: props.veiculo.capacidade,
        capacidadem3: props.veiculo.capacidadem3,
      }
    } else {
      resetForm()
    }
  },
  { immediate: true },
)

const close = () => emit('update:modelValue', false)

const submit = async () => {
  loading.value = true
  try {
    const salvo = isNovo.value
      ? await veiculoService.createVeiculo(form.value)
      : await veiculoService.updateVeiculo(props.veiculo.codveiculo, form.value)
    notificarSucesso(isNovo.value ? 'Veículo cadastrado!' : 'Veículo atualizado!')
    emit('saved', salvo)
    close()
  } catch (error) {
    notificarErro(error, 'Falha ao salvar veículo')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <q-dialog
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <q-card style="width: 600px; max-width: 95vw">
      <q-card-section class="bg-primary text-white">
        <div class="text-h6">{{ isNovo ? 'Novo' : 'Editar' }} Veículo</div>
      </q-card-section>

      <q-separator />

      <q-form @submit.prevent="submit">
        <q-card-section class="q-gutter-md">
          <q-input
            v-model="form.veiculo"
            label="Apelido *"
            outlined
            autofocus
            maxlength="50"
            :rules="[(v) => !!v || 'Apelido é obrigatório', (v) => (v && v.length >= 5) || 'Mínimo 5 caracteres']"
          />

          <SelectPessoa v-model="form.codpessoaproprietario" label="Proprietário" />

          <q-select
            v-model="form.tipoproprietario"
            label="Tipo de Proprietário *"
            outlined
            :options="TIPO_PROPRIETARIO_OPTIONS"
            option-value="value"
            option-label="label"
            emit-value
            map-options
            :rules="[(v) => v !== null || 'Obrigatório']"
          />

          <q-select
            v-model="form.codveiculotipo"
            label="Tipo de Veículo *"
            outlined
            :options="tipoOptions"
            option-value="value"
            option-label="label"
            emit-value
            map-options
            :rules="[(v) => !!v || 'Obrigatório']"
          />

          <div class="row q-col-gutter-md">
            <div class="col-12 col-sm-6">
              <q-input
                v-model="form.placa"
                label="Placa *"
                outlined
                mask="AAA#X##"
                hint="Ex: ABC1D23"
                :rules="[(v) => !!v || 'Placa é obrigatória', (v) => (v && v.length === 7) || 'Placa deve ter 7 caracteres']"
                @update:model-value="(v) => (form.placa = (v || '').toUpperCase())"
              />
            </div>
            <div class="col-12 col-sm-6">
              <SelectEstado v-model="form.codestado" label="Estado *" :bottom-slots="false" />
            </div>
          </div>

          <q-input v-model="form.renavam" label="Renavam" outlined mask="###########" />

          <div class="row q-col-gutter-md">
            <div class="col-12 col-sm-4">
              <q-input v-model.number="form.tara" label="Tara (KG)" outlined type="number" min="0" />
            </div>
            <div class="col-12 col-sm-4">
              <q-input
                v-model.number="form.capacidade"
                label="Capacidade (KG)"
                outlined
                type="number"
                min="0"
              />
            </div>
            <div class="col-12 col-sm-4">
              <q-input
                v-model.number="form.capacidadem3"
                label="Capacidade (M³)"
                outlined
                type="number"
                min="0"
              />
            </div>
          </div>
        </q-card-section>

        <q-separator />

        <q-card-actions align="right" class="q-pa-md">
          <q-btn flat label="Cancelar" color="grey-8" @click="close" tabindex="-1" />
          <q-btn unelevated label="Salvar" color="primary" icon="save" type="submit" :loading="loading" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
