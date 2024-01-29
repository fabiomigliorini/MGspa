<script setup>

import { ref, onMounted } from 'vue'
import { pessoaStore } from 'stores/pessoa'

const sPessoa = pessoaStore();

onMounted(async () => {
    const ret = await sPessoa.selectCargo()
    opcoes.value = ret.data
})

const opcoes = ref([]);

</script>

<template>
   <q-select outlined label="Cargo" :options="opcoes"
        map-options emit-value option-label="cargo" option-value="codcargo" clearable />
</template>
  