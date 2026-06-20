<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import veiculoService from '../../services/veiculoService'
import { notificarSucesso, notificarErro } from '../../utils/notify'

const props = defineProps({
  modelValue: { type: Boolean, required: true },
  conjunto: { type: Object, default: null },
})
const emit = defineEmits(['update:modelValue', 'saved'])

const loading = ref(false)
const isNovo = computed(() => !props.conjunto)
const veiculoOptions = ref([])

const form = ref({ veiculoconjunto: null, veiculos: [] })

const carregarVeiculos = async () => {
  if (veiculoOptions.value.length) return
  try {
    veiculoOptions.value = await veiculoService.selectVeiculos()
  } catch (error) {
    notificarErro(error, 'Falha ao carregar veículos')
  }
}

onMounted(carregarVeiculos)

watch(
  () => props.modelValue,
  (aberto) => {
    if (!aberto) return
    if (props.conjunto) {
      form.value = {
        veiculoconjunto: props.conjunto.veiculoconjunto,
        veiculos: (props.conjunto.veiculos || []).map((v) => ({ codveiculo: v.codveiculo })),
      }
    } else {
      form.value = { veiculoconjunto: null, veiculos: [{ codveiculo: null }, { codveiculo: null }] }
    }
  },
  { immediate: true },
)

const addVeiculo = () => form.value.veiculos.push({ codveiculo: null })
const delVeiculo = (index) => form.value.veiculos.splice(index, 1)

const close = () => emit('update:modelValue', false)

const submit = async () => {
  if (form.value.veiculos.filter((v) => v.codveiculo).length < 2) {
    notificarErro(null, 'Informe ao menos 2 veículos')
    return
  }
  loading.value = true
  try {
    const payload = {
      veiculoconjunto: form.value.veiculoconjunto,
      veiculos: form.value.veiculos.filter((v) => v.codveiculo),
    }
    const salvo = isNovo.value
      ? await veiculoService.createConjunto(payload)
      : await veiculoService.updateConjunto(props.conjunto.codveiculoconjunto, payload)
    notificarSucesso(isNovo.value ? 'Conjunto cadastrado!' : 'Conjunto atualizado!')
    emit('saved', salvo)
    close()
  } catch (error) {
    notificarErro(error, 'Falha ao salvar conjunto')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <q-dialog :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)">
    <q-card style="width: 500px; max-width: 95vw">
      <q-card-section class="bg-primary text-white">
        <div class="text-h6">{{ isNovo ? 'Novo' : 'Editar' }} Conjunto</div>
      </q-card-section>

      <q-separator />

      <q-form @submit.prevent="submit">
        <q-card-section class="q-gutter-md">
          <q-input
            v-model="form.veiculoconjunto"
            label="Nome *"
            outlined
            autofocus
            maxlength="50"
            :rules="[
              (v) => !!v || 'Nome é obrigatório',
              (v) => (v && v.length >= 5) || 'Mínimo 5 caracteres',
            ]"
          />

          <div class="text-caption text-grey-7">Veículos (mínimo 2)</div>
          <div
            v-for="(veiculo, index) in form.veiculos"
            :key="index"
            class="row items-center q-col-gutter-sm"
          >
            <div class="col">
              <q-select
                v-model="veiculo.codveiculo"
                label="Veículo"
                outlined
                :options="veiculoOptions"
                option-value="value"
                option-label="label"
                emit-value
                map-options
              />
            </div>
            <div class="col-auto">
              <q-btn flat round size="sm" color="grey-7" icon="delete" @click="delVeiculo(index)" />
            </div>
          </div>

          <q-btn flat color="primary" icon="add" label="Adicionar Veículo" @click="addVeiculo" />
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
