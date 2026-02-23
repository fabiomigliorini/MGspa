<script setup>
import { ref, onMounted } from "vue";
import { api } from "src/boot/axios.js";

const props = defineProps({
  modelValue: {
    type: Number,
  },
});

const emit = defineEmits(["update:modelValue"]);

const opcoes = ref([]);
const todos = ref([]);

const alterar = (value) => {
  emit("update:modelValue", value);
};

onMounted(async () => {
  try {
    const ret = await api.get("v1/setor");
    todos.value = ret.data.data
      .filter((s) => !s.inativo)
      .map((s) => ({
        codsetor: s.codsetor,
        label: s.setor + (s.UnidadeNegocio ? " — " + s.UnidadeNegocio.descricao : ""),
        setor: s.setor,
      }));
    opcoes.value = todos.value;
  } catch (error) {
    console.log(error);
  }
});

const filterFn = (val, update) => {
  update(() => {
    const needle = val.toLowerCase();
    opcoes.value = todos.value.filter(
      (v) => v.label.toLowerCase().indexOf(needle) > -1
    );
  });
};
</script>
<template>
  <q-select
    :options="opcoes"
    :model-value="modelValue"
    @filter="filterFn"
    use-input
    emit-value
    map-options
    option-value="codsetor"
    option-label="label"
    v-bind="$attrs"
    @update:model-value="(value) => alterar(value)"
  />
</template>
