<script setup>

import { ref, onMounted } from 'vue'
import { pessoaStore } from 'stores/pessoa'

const sPessoa = pessoaStore();

onMounted(async () => {
    const ret = await sPessoa.selectCertidaoTipo()
    opcoes.value = ret.data
})

const opcoes = ref([]);

</script>

<template>
   <q-select outlined label="Tipo" :options="opcoes"
        map-options emit-value option-label="certidaotipo" option-value="codcertidaotipo"/>
</template>
  