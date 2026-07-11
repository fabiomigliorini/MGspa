<script setup>
import { ref, computed, watch } from 'vue'
import veiculoService from '../../services/veiculoService'
import { TIPO_RODADO_OPTIONS, TIPO_CARROCERIA_OPTIONS } from '../../stores/veiculoStore'
import { notificarSucesso, notificarErro } from '../../utils/notify'

const props = defineProps({
  modelValue: { type: Boolean, required: true },
  tipo: { type: Object, default: null },
})
const emit = defineEmits(['update:modelValue', 'saved'])

const loading = ref(false)
const isNovo = computed(() => !props.tipo)

const form = ref({})

const resetForm = () => {
  form.value = {
    veiculotipo: null,
    tracao: true,
    reboque: false,
    tiporodado: 1,
    tipocarroceria: 0,
  }
}

watch(
  () => props.modelValue,
  (aberto) => {
    if (!aberto) return
    if (props.tipo) {
      form.value = {
        veiculotipo: props.tipo.veiculotipo,
        tracao: !!props.tipo.tracao,
        reboque: !!props.tipo.reboque,
        tiporodado: props.tipo.tiporodado,
        tipocarroceria: props.tipo.tipocarroceria,
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
      ? await veiculoService.createTipo(form.value)
      : await veiculoService.updateTipo(props.tipo.codveiculotipo, form.value)
    notificarSucesso(isNovo.value ? 'Tipo cadastrado!' : 'Tipo atualizado!')
    emit('saved', salvo)
    close()
  } catch (error) {
    notificarErro(error, 'Falha ao salvar tipo')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <q-dialog :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)">
    <q-card style="width: 500px; max-width: 95vw">
      <q-card-section class="bg-primary text-white">
        <div class="text-h6">{{ isNovo ? 'Novo' : 'Editar' }} Tipo de Veículo</div>
      </q-card-section>

      <q-separator />

      <q-form @submit.prevent="submit">
        <q-card-section class="q-gutter-md">
          <q-input
            v-model="form.veiculotipo"
            label="Nome *"
            outlined
            autofocus
            maxlength="50"
            :rules="[(v) => !!v || 'Nome é obrigatório']"
          />

          <div class="row q-col-gutter-md">
            <div class="col-6">
              <q-toggle v-model="form.tracao" label="Tração" color="primary" />
            </div>
            <div class="col-6">
              <q-toggle v-model="form.reboque" label="Reboque" color="primary" />
            </div>
          </div>

          <div>
            <div class="text-caption text-grey-7 q-mb-xs">Rodado</div>
            <q-option-group
              v-model="form.tiporodado"
              :options="TIPO_RODADO_OPTIONS"
              color="primary"
            />
          </div>

          <div>
            <div class="text-caption text-grey-7 q-mb-xs">Carroceria</div>
            <q-option-group
              v-model="form.tipocarroceria"
              :options="TIPO_CARROCERIA_OPTIONS"
              color="primary"
            />
          </div>
        </q-card-section>

        <q-separator />

        <q-card-actions align="right" class="q-pa-md">
          <q-btn flat label="Cancelar" color="grey-8" @click="close" tabindex="-1" />
          <q-btn flat label="Salvar" color="primary" icon="save" type="submit" :loading="loading" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
