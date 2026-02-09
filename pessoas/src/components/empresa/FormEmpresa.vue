<script setup>
import { ref, computed, onMounted } from "vue";
import { useQuasar } from "quasar";
import moment from "moment";

const $q = useQuasar();

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

const contingenciaDataFormatada = computed({
  get() {
    if (!model.value.contingenciadata) return "";
    return moment(model.value.contingenciadata).format("DD/MM/YYYY HH:mm");
  },
  set(val) {
    if (val) {
      model.value.contingenciadata = moment(val, "DD/MM/YYYY HH:mm").format(
        "YYYY-MM-DD HH:mm:ss"
      );
    } else {
      model.value.contingenciadata = null;
    }
  },
});

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

    <q-input
      outlined
      v-model="contingenciaDataFormatada"
      label="Data de Contingência"
      mask="##/##/#### ##:##"
    >
      <template v-slot:prepend>
        <q-icon name="event" class="cursor-pointer" />
        <q-popup-proxy cover transition-show="scale" transition-hide="scale">
          <q-date v-model="model.contingenciadata" mask="YYYY-MM-DD HH:mm:ss">
            <q-btn v-close-popup label="Fechar" color="primary" flat />
          </q-date>
        </q-popup-proxy>
      </template>
    </q-input>

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
