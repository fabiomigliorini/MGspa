<script setup>

import { ref, onMounted } from 'vue'
import { pessoaStore } from 'stores/pessoa'

const sPessoa = pessoaStore();

onMounted(async () => {
    const ret = await sPessoa.selectPagamento()
    opcoes.value = ret.data
})

const opcoes = ref([]);

</script>

<template>
   <q-select outlined label="Forma de Pagamento" :options="opcoes"
        map-options emit-value option-label="formapagamento" option-value="codformapagamento" clearable />
</template>
  