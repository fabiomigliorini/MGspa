<script setup>
import { ref, computed } from "vue";
import MgInputData from "@components/MgInputData.vue";

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

const opcoesModoemissao = [
  { label: "Normal", value: 1 },
  { label: "Offline", value: 9 },
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
      v-model="model.empresa"
      label="Nome da Empresa *"
      :rules="[validaObrigatorio]"
      lazy-rules
      class="q-pa-none"
    />

    <q-select
      outlined
      v-model="model.modoemissaonfce"
      :options="opcoesModoemissao"
      label="Modo Emissão NFCe *"
      emit-value
      map-options
      :rules="[validaObrigatorio]"
      lazy-rules
      class="q-pa-none"
    />

    <MgInputData
      v-model="model.contingenciadata"
      type="timestamp"
      label="Data de Contingência"
    />

    <q-input
      outlined
      v-model="model.contingenciajustificativa"
      label="Justificativa de Contingência"
      type="textarea"
      rows="3"
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
