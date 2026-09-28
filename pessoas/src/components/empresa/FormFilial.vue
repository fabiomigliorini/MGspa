<script setup>
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'
import { ref, computed } from 'vue'

const props = defineProps({
  modelValue: {
    type: Object,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'submit'])

const formRef = ref(null)

const model = computed({
  get() {
    return props.modelValue
  },
  set(value) {
    emit('update:modelValue', value)
  },
})

const opcoesCrt = [
  { label: '1 - Simples Nacional', value: 1 },
  { label: '2 - Simples Nacional - Excesso', value: 2 },
  { label: '3 - Regime Normal', value: 3 },
]

const opcoesAmbiente = [
  { label: 'Produção', value: 1 },
  { label: 'Homologação', value: 2 },
]

const validaObrigatorio = (val) => !!val || 'Campo obrigatório'

const submit = async () => {
  const valid = await formRef.value.validate()
  if (valid) {
    emit('submit')
  }
}

defineExpose({
  submit,
  validate: () => formRef.value.validate(),
})
</script>

<template>
  <q-form ref="formRef" @submit.prevent="submit" class="q-gutter-sm">
    <MgInput
      outlined
      v-model="model.filial"
      label="Nome da Filial *"
      :rules="[validaObrigatorio]"
      lazy-rules
    />

    <MgInputValor v-model="model.codpessoa" :decimals="0" :grouping="false" label="Código Pessoa" />

    <q-select
      outlined
      v-model="model.crt"
      :options="opcoesCrt"
      label="CRT - Código do Regime Tributário"
      emit-value
      map-options
      clearable
    />

    <q-select
      outlined
      v-model="model.nfeambiente"
      :options="opcoesAmbiente"
      label="Ambiente NFe"
      emit-value
      map-options
    />

    <MgInputValor v-model="model.nfeserie" :decimals="0" :grouping="false" label="Série NFe" />

    <div class="row q-gutter-sm">
      <q-toggle v-model="model.emitenfe" label="Emite NFe" />
      <q-toggle v-model="model.dfe" label="DF-e" />
    </div>

    <MgInput outlined v-model="model.tokennfce" label="Token NFCe" />

    <MgInput outlined v-model="model.idtokennfce" label="ID Token NFCe" />

    <MgInput outlined v-model="model.tokenibpt" label="Token IBPT" />

    <MgInputValor
      v-model="model.empresadominio"
      :decimals="0"
      :grouping="false"
      label="Empresa Domínio"
    />

    <MgInput outlined v-model="model.senhacertificado" label="Senha Certificado" type="password" />

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn color="primary" :loading="loading" icon="save" round class="q-pa-md" @click="submit" />
    </q-page-sticky>
  </q-form>
</template>
