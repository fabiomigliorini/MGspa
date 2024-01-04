<script setup>

import { ref, onMounted } from 'vue'
import { pessoaStore } from 'stores/pessoa'

const sPessoa = pessoaStore();

onMounted(async () => {
    const ret = await sPessoa.selectCertidaoEmissor()
    opcoes.value = ret.data
})

const opcoes = ref([]);

</script>

<template>
   <q-select outlined label="Emissor" :options="opcoes"
        map-options emit-value option-label="certidaoemissor" option-value="codcertidaoemissor" />
</template>
  