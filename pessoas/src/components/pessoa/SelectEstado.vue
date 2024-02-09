<script setup>

import { ref, onMounted } from 'vue'
import { pessoaStore } from 'stores/pessoa'

const sPessoa = pessoaStore();

onMounted(async () => {
    const ret = await sPessoa.selectEstado()
    opcoes.value = ret.data
})

const opcoes = ref([]);

</script>

<template>
   <q-select outlined label="Estados" :options="opcoes"
        map-options emit-value option-label="sigla" option-value="value" clearable />
</template>
  