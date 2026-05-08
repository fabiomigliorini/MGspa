<script setup>
import { ref, computed, nextTick } from "vue";

const removerAcentos = (str) => {
  if (!str) return str;
  return str.normalize("NFD").replace(/\p{Mn}/gu, "");
};

const primeiraLetraMaiuscula = (str) => {
  if (!str) return str;
  return removerAcentos(str)
    .trimStart()
    .replace(/\s+/g, " ")
    .replace(/\w\S*/g, (txt) => txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase());
};

const refInput = ref();
const filtrar = ref(true);
const modelAnterior = ref(null);

const props = defineProps({
  modelValue: { type: [String], default: "" },
  filtro: { type: [String], default: "primeiraLetraMaiuscula" },
});

const emit = defineEmits(["update:modelValue"]);

const model = computed({
  get() {
    return props.modelValue;
  },
  set(value) {
    return emit("update:modelValue", value);
  },
});

const aplicarFiltro = () => {
  if (model.value == modelAnterior.value || !filtrar.value) {
    return;
  }
  const posStart = refInput.value.getNativeElement().selectionStart;
  const posEnd = refInput.value.getNativeElement().selectionEnd;
  switch (props.filtro) {
    case "primeiraLetraMaiuscula":
      model.value = primeiraLetraMaiuscula(model.value);
      nextTick(() => {
        refInput.value.getNativeElement().selectionStart = posStart;
        refInput.value.getNativeElement().selectionEnd = posEnd;
      });
      break;
    default:
      break;
  }
  modelAnterior.value = model.value;
};

const toggleFiltrar = () => {
  filtrar.value = !filtrar.value;
  refInput.value.focus();
  modelAnterior.value = model.value;
};
</script>

<template>
  <q-input
    ref="refInput"
    v-model="model"
    @keyup="aplicarFiltro"
    @focus="modelAnterior = model"
  >
    <template v-slot:append>
      <q-icon
        :name="filtrar ? 'published_with_changes' : 'unpublished'"
        class="cursor-pointer"
        @click="toggleFiltrar()"
      />
      <slot name="append" />
    </template>
  </q-input>
</template>
