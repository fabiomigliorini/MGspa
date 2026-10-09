<script setup>
import { ref, watch } from 'vue'
import SelectFilial from 'components/selects/SelectFilial.vue'
import SelectSetor from 'src/components/selects/SelectSetor.vue'
import MgInput from '@components/MgInput.vue'
import MgSelectPortador from '@components/MgSelectPortador.vue'
import MgInputData from '@components/MgInputData.vue'
import MgInputValor from '@components/MgInputValor.vue'

const props = defineProps({
  modelValue: Boolean,
  pdv: Object,
  titulo: {
    type: String,
    required: true,
  },
})

const form = ref(null)

const onSubmit = () => {
  if (form.value && form.value.validate()) {
    salvar()
  }
}

const emit = defineEmits(['update:modelValue', 'salvar'])

const model = ref({})

watch(
  () => props.modelValue,
  (val) => {
    if (val) {
      model.value = { ...props.pdv }
    }
  },
)

const salvar = () => {
  emit('salvar', model.value)
  emit('update:modelValue', false)
}
</script>
<template>
  <q-dialog :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)">
    <q-card style="width: 400px; max-width: 95vw">
      <q-form ref="form" @submit.prevent="onSubmit">
        <q-card-section class="text-h6">
          {{ titulo }}
        </q-card-section>
        <q-card-section class="q-gutter-md">
          <MgInput outlined v-model="model.apelido" autofocus label="Apelido" />
          <select-filial
            outlined
            v-model="model.codfilial"
            :rules="[(val) => !!val || 'Filial é obrigatória']"
            hide-bottom-space
          />
          <select-setor
            outlined
            v-model="model.codsetor"
            label="Setor"
            :rules="[(val) => !!val || 'Setor é obrigatório']"
            hide-bottom-space
          />
          <MgSelectPortador
            v-model="model.codportador"
            label="Portador (gaveta)"
            :tipos="['E']"
            :filiais="[model.codfilial]"
            clearable
          />
          <!-- livro de ocorrencias (TASK-205): sem data, o PDV nao e' monitorado -->
          <MgInputData
            v-model="model.monitoramento"
            label="Monitorar a partir de"
            hint="Vazio = não monitora"
          />
          <MgInputValor
            v-model="model.minutosesquecido"
            label="Minutos até considerar o negócio esquecido"
            :decimals="0"
            :min="10"
            :rules="[(val) => !val || val >= 10 || 'Mínimo de 10 minutos']"
          />
          <MgInput
            outlined
            autogrow
            v-model="model.observacoes"
            label="Observações"
            type="textarea"
            class="q-mb-md"
          />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn flat label="Salvar" type="submit" color="primary" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
