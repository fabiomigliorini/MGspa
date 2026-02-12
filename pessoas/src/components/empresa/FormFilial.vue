<script setup>
import { ref, computed } from "vue";

const props = defineProps({
  modelValue: {
    type: Object,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["update:modelValue", "submit"]);

const formRef = ref(null);

const model = computed({
  get() {
    return props.modelValue;
  },
  set(value) {
    emit("update:modelValue", value);
  },
});

const opcoesCrt = [
  { label: "1 - Simples Nacional", value: 1 },
  { label: "2 - Simples Nacional - Excesso", value: 2 },
  { label: "3 - Regime Normal", value: 3 },
];

const opcoesAmbiente = [
  { label: "Produção", value: 1 },
  { label: "Homologação", value: 2 },
];

const validaObrigatorio = (val) => !!val || "Campo obrigatório";

const submit = async () => {
  const valid = await formRef.value.validate();
  if (valid) {
    emit("submit");
  }
};

defineExpose({
  submit,
  validate: () => formRef.value.validate(),
});
</script>

<template>
  <q-form ref="formRef" @submit.prevent="submit" class="q-gutter-sm">
    <q-input
      outlined
      v-model="model.filial"
      label="Nome da Filial *"
      :rules="[validaObrigatorio]"
      lazy-rules
    />

    <q-input
      outlined
      v-model.number="model.codpessoa"
      label="Código Pessoa"
      type="number"
    />

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

    <q-input
      outlined
      v-model.number="model.nfeserie"
      label="Série NFe"
      type="number"
    />

    <div class="row q-gutter-sm">
      <q-toggle v-model="model.emitenfe" label="Emite NFe" />
      <q-toggle v-model="model.dfe" label="DF-e" />
    </div>

    <q-input outlined v-model="model.tokennfce" label="Token NFCe" />

    <q-input outlined v-model="model.idtokennfce" label="ID Token NFCe" />

    <q-input outlined v-model="model.tokenibpt" label="Token IBPT" />

    <q-input
      outlined
      v-model.number="model.empresadominio"
      label="Empresa Domínio"
      type="number"
    />

    <q-input outlined v-model="model.stonecode" label="Stone Code" />

    <q-input
      outlined
      v-model="model.senhacertificado"
      label="Senha Certificado"
      type="password"
    />

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn
        color="primary"
        :loading="loading"
        icon="save"
        round
        class="q-pa-md"
        @click="submit"
      />
    </q-page-sticky>
  </q-form>
</template>
