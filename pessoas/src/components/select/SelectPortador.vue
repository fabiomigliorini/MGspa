<script setup>

import { ref, onMounted } from 'vue'
import { api } from 'src/boot/axios';

const opcoes = ref([]);
const todos = ref([]);

const loadOpcoes = async () => {
  const ret = await api.get("v1/select/portador");
  todos.value = ret.data;
  opcoes.value = ret.data;
}

onMounted(() => {
  loadOpcoes();
})

const filterFn = async (val, update) => {
  update(() => {
    console.log(val);
    if (!val) {
      opcoes.value = todos.value;
      return;
    }
    const needle = val.toLowerCase();
    opcoes.value = todos.value.filter(v => v.portador.toLowerCase().indexOf(needle) > -1)
  })
}
</script>

<template>
  <q-select outlined label="Portador" use-input input-debounce="5" :options="opcoes" map-options emit-value
    option-label="portador" option-value="codportador" clearable @filter="filterFn" />
</template>
