<script setup>
import { ref, onMounted } from "vue";
import { pessoaStore } from "stores/pessoa";

const sPessoa = pessoaStore();

onMounted(async () => {
  const ret = await sPessoa.selectFilial();
  opcoes.value = ret.data;
});

const filterFn = async (val, update) => {
  if (val === "") {
    update(async () => {
      const ret = await sPessoa.selectFilial();
      opcoes.value = ret.data;
    });
    return;
  }

  update(() => {
    const needle = val.toLowerCase();
    opcoes.value = opcoes.value.filter(
      (v) => v.label.toLowerCase().indexOf(needle) > -1
    );
  });
};

const opcoes = ref([]);
</script>

<template>
  <q-select
    outlined
    label="Filial"
    use-input
    input-debounce="5"
    :options="opcoes"
    map-options
    emit-value
    option-label="label"
    option-value="value"
    clearable
    @filter="filterFn"
  />
</template>
